<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Review System Debug</h2>";

try {
    $pdo = getDBConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "<p>❌ User not logged in</p>";
        echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";
        exit;
    }
    
    echo "<p>✅ User logged in: " . $_SESSION['user_id'] . "</p>";
    
    // Test product existence
    $test_product_id = 1;
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$test_product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo "<p>❌ Product not found with ID: $test_product_id</p>";
        
        // List available products
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE is_active = 1 LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Available products:</p>";
        foreach ($products as $prod) {
            echo "<p>- ID: {$prod['id']}, Name: {$prod['name']}</p>";
        }
        exit;
    }
    
    echo "<p>✅ Product found: " . $product['name'] . "</p>";
    
    // Test review submission
    $test_data = [
        'product_id' => $test_product_id,
        'rating' => 5,
        'review_text' => 'Test review from debug script'
    ];
    
    echo "<p>Testing review submission with data: " . print_r($test_data, true) . "</p>";
    
    // Check if user has already reviewed this product
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $test_product_id]);
    $existing_review = $stmt->fetch();
    
    if ($existing_review) {
        echo "<p>⚠️ User has already reviewed this product, will update existing review</p>";
        
        // Update existing review
        $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $result = $stmt->execute([$test_data['rating'], $test_data['review_text'], $existing_review['id']]);
        
        if ($result) {
            echo "<p>✅ Review updated successfully</p>";
        } else {
            echo "<p>❌ Failed to update review</p>";
            echo "<p>Error: " . print_r($stmt->errorInfo(), true) . "</p>";
        }
    } else {
        echo "<p>📝 Creating new review</p>";
        
        // Check if user has purchased this product
        $stmt = $pdo->prepare("SELECT id FROM orders o 
                              JOIN order_items oi ON o.id = oi.order_id 
                              WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
        $stmt->execute([$_SESSION['user_id'], $test_product_id]);
        $is_verified_purchase = $stmt->fetch() ? true : false;
        
        echo "<p>Verified purchase: " . ($is_verified_purchase ? 'Yes' : 'No') . "</p>";
        
        // Insert new review
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text, is_verified_purchase) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$test_product_id, $_SESSION['user_id'], $test_data['rating'], $test_data['review_text'], $is_verified_purchase]);
        
        if ($result) {
            echo "<p>✅ Review inserted successfully</p>";
        } else {
            echo "<p>❌ Failed to insert review</p>";
            echo "<p>Error: " . print_r($stmt->errorInfo(), true) . "</p>";
        }
    }
    
    // Update product average rating and review count
    echo "<p>Updating product rating statistics...</p>";
    
    $stmt = $pdo->prepare("
        UPDATE products 
        SET average_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM reviews 
            WHERE product_id = ?
        ),
        review_count = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE product_id = ?
        )
        WHERE products.id = ?
    ");
    $result = $stmt->execute([$test_product_id, $test_product_id, $test_product_id]);
    
    if ($result) {
        echo "<p>✅ Product rating statistics updated successfully</p>";
        
        // Show updated product data
        $stmt = $pdo->prepare("SELECT name, average_rating, review_count FROM products WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $updated_product = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Updated product data: " . print_r($updated_product, true) . "</p>";
    } else {
        echo "<p>❌ Failed to update product rating statistics</p>";
        echo "<p>Error: " . print_r($stmt->errorInfo(), true) . "</p>";
    }
    
    // Show all reviews for this product
    echo "<h3>All Reviews for Product:</h3>";
    $stmt = $pdo->prepare("
        SELECT r.*, u.username 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$test_product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reviews as $review) {
        echo "<p>- User: {$review['username']}, Rating: {$review['rating']}, Text: {$review['review_text']}</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Error code: " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ General error: " . $e->getMessage() . "</p>";
}
?> 