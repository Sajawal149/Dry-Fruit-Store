<?php
session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !isset($input['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$product_id = (int)$input['product_id'];
$rating = (int)$input['rating'];
$review_text = isset($input['review_text']) ? trim($input['review_text']) : '';
$user_id = $_SESSION['user_id'];

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check if user has already reviewed this product
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_review = $stmt->fetch();
    
    // Check if user has purchased this product (for verified purchase badge)
    $stmt = $pdo->prepare("SELECT id FROM orders o 
                          JOIN order_items oi ON o.id = oi.order_id 
                          WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
    $stmt->execute([$user_id, $product_id]);
    $is_verified_purchase = $stmt->fetch() ? true : false;
    
    if ($existing_review) {
        // Update existing review
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $result = $stmt->execute([$rating, $review_text, $existing_review['id']]);
        
        if (!$result) {
            $error = $stmt->errorInfo();
            echo json_encode(['success' => false, 'message' => 'Failed to update review: ' . $error[2]]);
            exit;
        }
    } else {
        // Insert new review
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text, is_verified_purchase) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$product_id, $user_id, $rating, $review_text, $is_verified_purchase]);
        
        if (!$result) {
            $error = $stmt->errorInfo();
            echo json_encode(['success' => false, 'message' => 'Failed to insert review: ' . $error[2]]);
            exit;
        }
    }
    
    // Update product average rating and review count
    $stmt = $pdo->prepare("
        UPDATE products 
        SET average_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM reviews 
            WHERE product_id = products.id
        ),
        review_count = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE product_id = products.id
        )
        WHERE products.id = ?
    ");
    $result = $stmt->execute([$product_id]);
    
    if (!$result) {
        $error = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Failed to update product statistics: ' . $error[2]]);
        exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    
} catch (PDOException $e) {
    error_log("Database error in submit_review.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in submit_review.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'General error: ' . $e->getMessage()]);
}
?> 