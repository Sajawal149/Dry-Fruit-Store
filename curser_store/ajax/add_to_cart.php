<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$product_id = (int)$input['product_id'];
$quantity = (int)$input['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if product exists and is active
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit;
    }
    
    // Check if item already exists in cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_item = $stmt->fetch();
    
    if ($existing_item) {
        // Update existing cart item
        $new_quantity = $existing_item['quantity'] + $quantity;
        
        if ($new_quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $result = $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // Add new cart item
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $result = $stmt->execute([$user_id, $product_id, $quantity]);
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 