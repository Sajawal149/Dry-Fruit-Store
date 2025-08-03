<?php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header('Location: products_new.php');
    exit;
}

$product_id = (int)$_GET['id'];
$pdo = getDBConnection();

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products_new.php');
    exit;
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];
}

// Get reviews for this product
$stmt = $pdo->prepare("
    SELECT 
        r.id AS review_id,
        r.user_id AS review_user_id,
        r.product_id,
        r.rating,
        r.review_text,
        r.helpful_votes,
        r.created_at,
        u.id AS user_account_id,
        u.username,
        u.full_name,
        (
            SELECT COUNT(*) 
            FROM review_likes 
            WHERE review_id = r.id
        ) AS like_count,
        (
            SELECT COUNT(*) 
            FROM review_likes 
            WHERE review_id = r.id AND user_id = ?
        ) AS user_liked
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.helpful_votes DESC, r.created_at DESC
    LIMIT 10
");

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$stmt->execute([$user_id, $product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's existing review
$user_review = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM reviews 
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $user_review = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate rating distribution
$stmt = $pdo->prepare("
    SELECT rating, COUNT(*) AS count
    FROM reviews
    WHERE product_id = ?
    GROUP BY rating
    ORDER BY rating DESC
");
$stmt->execute([$product_id]);
$rating_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill in empty rating counts (1 to 5)
$rating_counts = array_fill(1, 5, 0);
foreach ($rating_distribution as $rating) {
    $rating_counts[$rating['rating']] = $rating['count'];
}

// Calculate average rating
$stmt = $pdo->prepare("
    SELECT COALESCE(AVG(rating), 0) AS average_rating 
    FROM reviews 
    WHERE product_id = ?
");
$stmt->execute([$product_id]);
$rating_result = $stmt->fetch(PDO::FETCH_ASSOC);
$rating = $rating_result['average_rating'];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Northern Dry Fruits Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Theme Colors */
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --background-color: #f5f3f0;
            --surface-color: rgba(255, 255, 255, 0.8);
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --border-color: rgba(255, 255, 255, 0.2);
            --shadow-color: rgba(0, 0, 0, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        [data-theme="dark"] {
            /* Dark Theme Colors */
            --primary-color: #8b5cf6;
            --secondary-color: #a855f7;
            --background-color: #0f172a;
            --surface-color: rgba(30, 41, 59, 0.8);
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
            --glass-bg: rgba(30, 41, 59, 0.25);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background: var(--background-color);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 400;
            line-height: 1.6;
            letter-spacing: -0.01em;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5em;
            color: var(--text-primary) !important;
            letter-spacing: -0.02em;
        }

        .nav-link {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: var(--text-secondary) !important;
            transition: all 0.3s ease;
            letter-spacing: -0.01em;
        }

        .product-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .product-price {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            color: var(--primary-color);
            letter-spacing: -0.01em;
        }

        .product-description {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
        }

        .btn-cart {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Liquid Glass Effects */
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .glass-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px 0 var(--shadow-color);
        }

        /* Navbar Styling */
        .navbar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5em;
            color: var(--text-primary) !important;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        /* Theme Toggle Button */
        .theme-toggle {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            padding: 8px 16px;
            color: var(--text-primary);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        /* Product Details */
        .product-image {
            border-radius: 20px;
            box-shadow: 0 15px 35px 0 var(--shadow-color);
            transition: all 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.02);
        }

        .price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5em;
        }

        .original-price {
            text-decoration: line-through;
            color: var(--text-secondary);
        }

        .btn-cart {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Rating Stars */
        .rating-stars {
            color: #ffc107;
            font-size: 1.2em;
        }

        .rating-stars .far {
            color: #e4e5e9;
        }

        .rating-input {
            display: none;
        }

        .rating-label {
            cursor: pointer;
            font-size: 1.5em;
            color: #e4e5e9;
            transition: color 0.2s ease;
        }

        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #ffc107;
        }

        .rating-input:checked ~ .rating-label {
            color: #ffc107;
        }

        /* Review Section */
        .review-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        /* Ensure textarea is fully interactive */
        #review_text {
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            background: var(--surface-color) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            padding: 12px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            resize: vertical !important;
            min-height: 100px !important;
        }

        #review_text:focus {
            outline: none !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }

        #review_text:disabled {
            opacity: 0.6 !important;
            pointer-events: none !important;
        }

        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px 0 var(--shadow-color);
        }

        .verified-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: bold;
        }

        .helpful-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 5px 15px;
            color: var(--text-primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .helpful-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .helpful-btn.liked {
            background: var(--primary-color);
            color: white;
        }

        /* Form Styling */
        .form-control, .form-select {
            background: var(--surface-color);
            border: 2px solid #e0e0e0;
            color: var(--text-primary);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border-radius: 12px;
            padding: 12px 16px;
        }

        .form-control:focus, .form-select:focus {
            background: var(--surface-color);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Specific styling for textarea in review form */
        textarea.form-control {
            border: 2px solid #e0e0e0;
            min-height: 100px;
            resize: vertical;
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
        }

        textarea.form-control:focus {
            border-color: var(--primary-color);
            outline: none !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
        }

        textarea.form-control:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Ensure form controls are interactive */
        .form-control:not(:disabled) {
            cursor: text;
            pointer-events: auto;
        }

        /* Cart Badge */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3);
        }

        .nav-item {
            position: relative;
        }

        /* Dropdown Menu Styling */
        .dropdown-menu {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            margin-top: 10px;
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown-item {
            color: var(--text-primary);
            padding: 10px 20px;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 8px;
        }

        .dropdown-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .dropdown-toggle::after {
            margin-left: 8px;
            color: var(--text-secondary);
        }

        /* Ensure dropdown is visible */
        .dropdown-menu.show {
            display: block !important;
            opacity: 1;
            transform: translateY(0);
        }

        /* Fix z-index for navbar and dropdown */
        .navbar {
            z-index: 1030 !important;
        }

        .dropdown-menu {
            z-index: 1040 !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .glass-card {
                margin-bottom: 1rem;
            }
        }

        /* Debug and ensure proper grid layout */
        .reviews-section {
            display: flex;
            flex-wrap: wrap;
        }

        .reviews-section .col-lg-8,
        .reviews-section .col-lg-4 {
            display: block;
            width: 100%;
        }

        @media (min-width: 992px) {
            .reviews-section .col-lg-8 {
                width: 66.666667%;
            }
            .reviews-section .col-lg-4 {
                width: 33.333333%;
            }
        }

        /* Ensure proper spacing */
        .reviews-section .row {
            margin-left: -15px;
            margin-right: -15px;
        }

        .reviews-section .col-lg-8,
        .reviews-section .col-lg-4 {
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light glass-effect">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain"></i> Northern Dry Fruits
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products_new.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-3">
                        <button class="btn theme-toggle" onclick="toggleTheme()">
                            <i class="fas fa-moon" id="theme-icon"></i>
                            <span id="theme-text">Dark</span>
                        </button>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu glass-effect">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Product Details -->
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products_new.php">Products</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-6">
                <img src="<?php echo $product['image_url'] ? 'images/products/' . htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/500x400?text=Dry+Fruits'; ?>" 
                     class="img-fluid product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <h1 class="mb-3 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Rating Display -->
                    <div class="mb-3">
                        <div class="rating-stars mb-2">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            <span class="ms-2 text-muted">(<?php echo $product['review_count'] ?? 0; ?> reviews)</span>
                        </div>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    </div>

                    <p class="lead mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    
                    <div class="mb-4">
                        <span class="price">Rs. <?php echo number_format($product['price']); ?></span>
                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                            <span class="original-price ms-2">Rs. <?php echo number_format($product['original_price']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-cart text-white fw-bold" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="fas fa-cart-plus me-2"></i> Add to Cart
                        </button>
                        <a href="products_new.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Products
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="row mt-5 reviews-section">
            <!-- Customer Reviews -->
            <div class="col-lg-8">
                <div class="glass-card p-4 h-100">
                    <h3 class="mb-4 fw-bold">Customer Reviews</h3>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Review Form -->
                        <div class="review-form mb-4">
                            <h5 class="mb-3">Write a Review</h5>
                            <form id="reviewForm">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input-group">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                                   id="star<?php echo $i; ?>" class="rating-input" 
                                                   <?php echo ($user_review && $user_review['rating'] == $i) ? 'checked' : ''; ?>>
                                            <label for="star<?php echo $i; ?>" class="rating-label">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Review (Optional)</label>
                                    <textarea class="form-control" id="review_text" name="review_text" rows="4" 
                                              placeholder="Share your experience with this product..."><?php echo $user_review ? htmlspecialchars($user_review['review_text']) : ''; ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary fw-bold">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Review
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info glass-effect">
                            <i class="fas fa-info-circle me-2"></i>
                            <a href="login.php" class="alert-link">Login</a> to write a review
                        </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <p class="text-muted text-center py-4">No reviews yet. Be the first to review this product!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($review['full_name'] ?: $review['username']); ?></h6>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($review['is_verified_purchase']): ?>
                                                <span class="verified-badge">Verified Purchase</span>
                                            <?php endif; ?>
                                            <small class="text-muted d-block"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($review['review_text']): ?>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button class="helpful-btn <?php echo $review['user_liked'] ? 'liked' : ''; ?>" 
                                                onclick="likeReview(<?php echo $review['review_id']; ?>, this)">
                                            <i class="fas fa-thumbs-up me-1"></i>
                                            Helpful (<?php echo $review['like_count']; ?>)
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rating Summary -->
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100">
                    <h5 class="mb-3 fw-bold">Rating Summary</h5>
                    <div class="text-center mb-3">
                        <div class="rating-stars mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $rating ? '' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <h4 class="mb-1"><?php echo number_format($rating, 1); ?> out of 5</h4>
                        <p class="text-muted"><?php echo $product['review_count'] ?? 0; ?> total reviews</p>
                    </div>
                    
                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2"><?php echo $i; ?>★</span>
                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                    <?php 
                                    $percentage = $product['review_count'] > 0 ? ($rating_counts[$i] / $product['review_count']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-warning" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $rating_counts[$i]; ?></small>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggle Functionality
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (html.getAttribute('data-theme') === 'dark') {
                html.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = 'Dark';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme and initialize components
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light';
            }

            // Ensure textarea is properly enabled
            const reviewTextarea = document.getElementById('review_text');
            console.log('Found textarea:', reviewTextarea);
            
            if (reviewTextarea) {
                // Remove any potential restrictions
                reviewTextarea.disabled = false;
                reviewTextarea.readOnly = false;
                reviewTextarea.style.pointerEvents = 'auto';
                reviewTextarea.style.userSelect = 'text';
                reviewTextarea.style.webkitUserSelect = 'text';
                reviewTextarea.style.mozUserSelect = 'text';
                reviewTextarea.style.msUserSelect = 'text';
                reviewTextarea.style.opacity = '1';
                reviewTextarea.style.visibility = 'visible';
                reviewTextarea.style.display = 'block';
                
                console.log('Textarea properties after setup:', {
                    disabled: reviewTextarea.disabled,
                    readOnly: reviewTextarea.readOnly,
                    pointerEvents: reviewTextarea.style.pointerEvents,
                    userSelect: reviewTextarea.style.userSelect
                });
                
                // Remove any event listeners that might interfere
                reviewTextarea.addEventListener('focus', function() {
                    console.log('Textarea focused');
                    this.style.pointerEvents = 'auto';
                });
                
                reviewTextarea.addEventListener('input', function() {
                    console.log('Textarea input:', this.value);
                });
                
                reviewTextarea.addEventListener('click', function() {
                    console.log('Textarea clicked');
                    this.focus();
                });
                
                // Force enable the textarea
                setTimeout(() => {
                    reviewTextarea.disabled = false;
                    reviewTextarea.readOnly = false;
                    reviewTextarea.style.pointerEvents = 'auto';
                    console.log('Textarea enabled after timeout');
                }, 100);
            } else {
                console.log('Textarea not found!');
            }

            // Initialize review form submission
            const reviewForm = document.getElementById('reviewForm');
            console.log('Review form element:', reviewForm);
            
            if (reviewForm) {
                console.log('Adding submit event listener to review form');
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Review form submitted');
                    
                    const formData = new FormData(this);
                    const rating = formData.get('rating');
                    const reviewText = formData.get('review_text');
                    
                    console.log('Rating:', rating);
                    console.log('Review text:', reviewText);
                    
                    if (!rating) {
                        alert('Please select a rating');
                        return;
                    }
                    
                    const submitData = {
                        product_id: <?php echo $product_id; ?>,
                        rating: parseInt(rating),
                        review_text: reviewText
                    };
                    
                    console.log('Submitting data:', submitData);
                    console.log('Product ID:', <?php echo $product_id; ?>);
                    
                    fetch('ajax/submit_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(submitData)
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        return response.text().then(text => {
                            console.log('Raw response:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Failed to parse JSON:', e);
                                throw new Error('Invalid JSON response');
                            }
                        });
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            alert('Review submitted successfully!');
                            location.reload();
                        } else {
                            alert(data.message || 'Error submitting review');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error submitting review: ' + error.message);
                    });
                });
            } else {
                console.log('Review form not found');
            }

            // Initialize dropdown functionality
            const dropdownToggle = document.querySelector('.dropdown-toggle');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownToggle && dropdownMenu) {
                // Add click event for dropdown
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });

                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        dropdownMenu.classList.remove('show');
                    }
                });

                // Prevent dropdown from closing when clicking inside it
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        function addToCart(productId) {
            fetch('ajax/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Error adding product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        }

        // Review submission
        document.addEventListener('DOMContentLoaded', function() {
            const reviewForm = document.getElementById('reviewForm');
            console.log('Review form element:', reviewForm);
            
            if (reviewForm) {
                console.log('Adding submit event listener to review form');
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Review form submitted');
                    
                    const formData = new FormData(this);
                    const rating = formData.get('rating');
                    const reviewText = formData.get('review_text');
                    
                    console.log('Rating:', rating);
                    console.log('Review text:', reviewText);
                    
                    if (!rating) {
                        alert('Please select a rating');
                        return;
                    }
                    
                    const submitData = {
                        product_id: <?php echo $product_id; ?>,
                        rating: parseInt(rating),
                        review_text: reviewText
                    };
                    
                    console.log('Submitting data:', submitData);
                    console.log('Product ID:', <?php echo $product_id; ?>);
                    
                    fetch('ajax/submit_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(submitData)
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        return response.text().then(text => {
                            console.log('Raw response:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Failed to parse JSON:', e);
                                throw new Error('Invalid JSON response');
                            }
                        });
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            alert('Review submitted successfully!');
                            location.reload();
                        } else {
                            alert(data.message || 'Error submitting review');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error submitting review: ' + error.message);
                    });
                });
            } else {
                console.log('Review form not found');
            }
        });

        // Like review functionality
        function likeReview(reviewId, button) {
            console.log('Liking review:', reviewId);
            
            fetch('ajax/like_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    review_id: reviewId
                })
            })
            .then(response => {
                console.log('Like response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Like response data:', data);
                if (data.success) {
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                        const count = parseInt(button.textContent.match(/\d+/)[0]) + 1;
                        button.innerHTML = `<i class="fas fa-thumbs-up me-1"></i> Helpful (${count})`;
                    } else {
                        button.classList.remove('liked');
                        const count = parseInt(button.textContent.match(/\d+/)[0]) - 1;
                        button.innerHTML = `<i class="fas fa-thumbs-up me-1"></i> Helpful (${count})`;
                    }
                } else {
                    alert(data.message || 'Error liking review');
                }
            })
            .catch(error => {
                console.error('Error liking review:', error);
                alert('Error liking review');
            });
        }
    </script>
</body>
</html> 