<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to like reviews']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['review_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing review ID']);
    exit;
}

$review_id = (int)$input['review_id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Check if review exists
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Review not found']);
        exit;
    }
    
    // Check if user has already liked this review
    $stmt = $pdo->prepare("SELECT id FROM review_likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$user_id, $review_id]);
    $existing_like = $stmt->fetch();
    
    if ($existing_like) {
        // Remove like
        $stmt = $pdo->prepare("DELETE FROM review_likes WHERE user_id = ? AND review_id = ?");
        $stmt->execute([$user_id, $review_id]);
        
        // Update helpful votes count
        $stmt = $pdo->prepare("UPDATE reviews SET helpful_votes = helpful_votes - 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        echo json_encode(['success' => true, 'message' => 'Like removed', 'action' => 'unliked']);
    } else {
        // Add like
        $stmt = $pdo->prepare("INSERT INTO review_likes (user_id, review_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $review_id]);
        
        // Update helpful votes count
        $stmt = $pdo->prepare("UPDATE reviews SET helpful_votes = helpful_votes + 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        echo json_encode(['success' => true, 'message' => 'Review liked', 'action' => 'liked']);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 