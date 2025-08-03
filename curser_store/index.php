<?php
require_once 'config/database.php';

// Get featured products
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.is_featured = 1 AND p.is_active = 1 
                       ORDER BY p.created_at DESC LIMIT 6");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured reviews (top-rated reviews)
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.username, p.name AS product_name, p.id
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.rating >= 4
    ORDER BY r.helpful_votes DESC, r.created_at DESC
    LIMIT 3
");
$stmt->execute();
$featured_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northern Dry Fruits Store</title>
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
            padding-top: 100px; /* Account for promo banner + navbar */
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

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
            text-align: left;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 1.3rem;
            font-weight: 400;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
            opacity: 0.95;
            text-align: left;
            letter-spacing: -0.01em;
            line-height: 1.5;
        }

        .hero-btn {
            font-family: 'Inter', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
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
            position: fixed;
            top: 55px; /* Reduced from 50px to 40px due to smaller promo banner */
            left: 0;
            right: 0;
            z-index: 1040;
            min-height: 90px; /* Increased navbar height by 10px */
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
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        /* Hero Section with Background Image */
        .hero-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1601493700631-2ab19c9e6c22?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        /* Product Cards */
        .product-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 660px; /* Increased from 585px to 660px (+75px) for better button alignment */
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px 0 var(--shadow-color);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            transition: all 0.3s ease;
            flex-shrink: 0; /* Prevent image from shrinking */
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .card-body {
            display: flex;
            flex-direction: column;
            flex: 1; /* Take remaining space */
            padding: 1.25rem;
        }

        .product-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
            line-height: 1.3;
            height: 2.6em; /* Fixed height for title (2 lines) */
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-category {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .product-description {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.5;
            letter-spacing: -0.01em;
            height: 4.5em; /* Fixed height for description (3 lines) */
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            margin-bottom: 1rem;
            flex: 1; /* Take available space */
        }

        .product-price {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
            letter-spacing: -0.01em;
            margin-bottom: 1rem;
            flex-shrink: 0; /* Prevent price from shrinking */
        }

        .btn-cart {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            flex-shrink: 0; /* Prevent buttons from shrinking */
        }

        .btn-outline-primary {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            flex-shrink: 0; /* Prevent buttons from shrinking */
        }

        /* Section Headers */
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--text-primary);
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: -0.02em;
        }

        .section-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 1.1rem;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 4rem;
            letter-spacing: -0.01em;
            line-height: 1.6;
        }

        /* Promotional Banner */
        .promo-banner {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 8px 0; /* Reduced from 12px to 8px */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .promo-banner .timer {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1rem;
        }

        /* Image Slider Styles */
        .image-slider {
            position: relative;
            height: 400px;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .slider-container {
            display: flex;
            transition: transform 0.5s ease-in-out;
            height: 100%;
        }

        .slider-slide {
            min-width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slider-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }

        .slider-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
            color: white;
            padding: 20px;
            text-align: center;
        }

        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            background: white;
            transform: scale(1.2);
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .slider-nav:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .slider-nav.prev {
            left: 10px;
        }

        .slider-nav.next {
            right: 10px;
        }

        /* Responsive hero adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
                text-align: center;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                text-align: center;
            }
            
            .hero-section {
                padding: 80px 0;
                background-attachment: scroll;
            }

            .image-slider {
                height: 300px;
                margin-top: 2rem;
            }
            
            /* Mobile promotion banner adjustments */
            .promo-banner {
                padding: 4px 0; /* Reduced from 8px to 4px for mobile */
                font-size: 0.75rem; /* Reduced font size for mobile */
            }
            
            .promo-banner .timer {
                font-size: 0.85rem; /* Reduced timer font size for mobile */
            }
            
            /* Adjust navbar position for smaller promo banner on mobile */
            .navbar {
                top: 30px; /* Reduced from 55px to 30px for mobile */
            }
        }

        /* Product Cards */
        .product-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        .product-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 50px 0 var(--shadow-color);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.2em;
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

        /* Features Section */
        .features-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Footer */
        footer {
            background: var(--surface-color);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .glass-card {
                margin-bottom: 1rem;
            }
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

        /* Promo Banner Styles */
        .promo-banner {
            background: #e53935 !important;
            color: white;
            padding: 12px 10px;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050; /* Higher than navbar */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-weight: 600;
            font-size: 0.9rem;
            animation: slideInFromTop 0.5s ease-out;
        }

        @keyframes slideInFromTop {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .promo-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            height: 100%;
        }

        .promo-text {
            display: flex;
            align-items: center;
            animation: slideRightToLeft 20s linear infinite;
        }

        @keyframes slideRightToLeft {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        .promo-timer {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            min-width: 80px;
            justify-content: center;
        }

        .promo-message {
            display: flex;
            align-items: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .promo-content {
                flex-direction: row;
                gap: 8px;
                font-size: smaller;

            }
            
            .promo-text {
                animation: none;
                text-align: center;
                
            }
            .promo-timer{
                font-size: smaller;
                padding: 0.4rem;

            }
            
            .promo-banner {
                padding: 8px 10px;
                font-size: 0.8rem;
                /* display: flex;
                flex-direction: row; */
                height: 50px;


            }
        }

        /* When promo banner is hidden, adjust navbar position */
        .navbar.no-promo {
            top: 0;
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
                        <a class="nav-link active" href="index.php">Home</a>
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

    <!-- Promotional Banner -->
    <div class="promo-banner" id="promoBanner">
        <div class="promo-content">
            <div class="promo-text">
                <i class="fas fa-fire me-2"></i>
                <span id="promoMessage">25% OFF for shopping before 30 minutes!</span>
            </div>
            <div class="promo-timer">
                <i class="fas fa-clock me-2"></i>
                <span id="timerDisplay">30:00</span>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <!-- Left Content -->
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title">Premium Dry Fruits from Northern Pakistan</h1>
                        <p class="hero-subtitle">Discover the finest quality almonds, walnuts, apricots, and more from the pristine mountains of Gilgit, Hunza, and surrounding regions. Handpicked and naturally dried for your health and wellness.</p>
                        <a href="products_new.php" class="btn btn-light hero-btn">
                            <i class="fas fa-shopping-bag me-2"></i> Shop Now
                        </a>
                    </div>
                </div>
                
                <!-- Right Image Slider -->
                <div class="col-lg-6">
                    <div class="image-slider">
                        <div class="slider-container" id="sliderContainer">
                            <div class="slider-slide">
                                <img src="images/products/kaju.jpg" 
                                     alt="Premium Almonds" class="slider-image">
                                <div class="slider-overlay">
                                    <h5>Premium Almonds</h5>
                                    <p>From the mountains of Northern Pakistan</p>
                                </div>
                            </div>
                            <div class="slider-slide">
                                <img src="images/products/misdry.jpg" 
                                     alt="Fresh Walnuts" class="slider-image">
                                <div class="slider-overlay">
                                    <h5>Fresh Walnuts</h5>
                                    <p>Rich in nutrients and flavor</p>
                                </div>
                            </div>
                            <div class="slider-slide">
                                <img src="images/products/badam.jpg" 
                                     alt="Dried Apricots" class="slider-image">
                                <div class="slider-overlay">
                                    <h5>Dried Apricots</h5>
                                    <p>Naturally sweet and nutritious</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <button class="slider-nav prev" onclick="changeSlide(-1)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="slider-nav next" onclick="changeSlide(1)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        
                        <!-- Dots Navigation -->
                        <div class="slider-dots" id="sliderDots">
                            <div class="slider-dot active" onclick="goToSlide(0)"></div>
                            <div class="slider-dot" onclick="goToSlide(1)"></div>
                            <div class="slider-dot" onclick="goToSlide(2)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Featured Products</h2>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="<?php echo $product['image_url'] ? 'images/products/' . htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/300x200?text=Dry+Fruits'; ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <div class="mt-auto">
                                <div class="mb-3">
                                    <span class="price">Rs. <?php echo number_format($product['price']); ?></span>
                                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <span class="original-price ms-2">Rs. <?php echo number_format($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-cart text-white" 
                                            onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                    </button>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i> Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <a href="products_new.php" class="btn btn-outline-primary btn-lg px-5">
                    <i class="fas fa-th-large me-2"></i> View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Reviews -->
    <section class="py-5 features-section">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Featured Reviews</h2>
            <div class="row">
                <?php foreach ($featured_reviews as $review): ?>
                <div class="col-md-4 mb-4">
                    <div class="glass-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo $review['product_image'] ? 'images/products/' . htmlspecialchars($review['product_image']) : 'https://via.placeholder.com/50x50?text=Product'; ?>" 
                                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h5 class="fw-bold"><?php echo htmlspecialchars($review['product_name']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($review['username']); ?></p>
                            </div>
                        </div>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <p class="card-text text-muted small">Rating: <?php echo htmlspecialchars($review['rating']); ?>/5</p>
                        <p class="card-text text-muted small">Helpful Votes: <?php echo htmlspecialchars($review['helpful_votes']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 features-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="glass-card p-4 h-100">
                        <i class="fas fa-shipping-fast feature-icon"></i>
                        <h4 class="fw-bold">Fast Delivery</h4>
                        <p class="text-muted">Quick delivery across Pakistan with secure packaging</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="glass-card p-4 h-100">
                        <i class="fas fa-leaf feature-icon"></i>
                        <h4 class="fw-bold">100% Natural</h4>
                        <p class="text-muted">Pure, organic dry fruits from Northern Pakistan mountains</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="glass-card p-4 h-100">
                        <i class="fas fa-medal feature-icon"></i>
                        <h4 class="fw-bold">Premium Quality</h4>
                        <p class="text-muted">Handpicked premium quality dry fruits for your health</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">Northern Dry Fruits Store</h5>
                    <p class="text-muted">Bringing you the finest quality dry fruits from the mountains of Northern Pakistan.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="fw-bold">Contact Us</h5>
                    <p class="text-muted">Email: info@northerndryfruits.com<br>
                    Phone: +92-300-1234567</p>
                </div>
            </div>
        </div>
    </footer>

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

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.getElementById('theme-icon');
            const themeText = document.getElementById('theme-text');
            
            if (savedTheme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light';
            }

            // Initialize promotional timer
            initializePromoTimer();

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

        // Promotional Timer Functionality
        function initializePromoTimer() {
            const promoBanner = document.getElementById('promoBanner');
            const promoMessage = document.getElementById('promoMessage');
            const timerDisplay = document.getElementById('timerDisplay');
            let timerData = JSON.parse(localStorage.getItem('promoTimer')) || {
                startTime: Date.now(),
                remainingTime: 30 * 60 * 1000 // 30 minutes in ms
            };
            const now = Date.now();
            const timeSinceStart = now - timerData.startTime;
            if (timeSinceStart >= 30 * 60 * 1000) {
                // Hide banner after 30 minutes
                promoBanner.style.display = 'none';
                document.querySelector('.navbar').classList.add('no-promo');
                document.body.style.paddingTop = '50px';
                return;
            } else {
                timerData.remainingTime = 30 * 60 * 1000 - timeSinceStart;
            }
            updatePromoDisplay(timerData);
            const timer = setInterval(() => {
                timerData.remainingTime -= 1000;
                if (timerData.remainingTime <= 0) {
                    promoBanner.style.display = 'none';
                    document.querySelector('.navbar').classList.add('no-promo');
                    document.body.style.paddingTop = '50px';
                    clearInterval(timer);
                    return;
                }
                updatePromoDisplay(timerData);
                localStorage.setItem('promoTimer', JSON.stringify(timerData));
            }, 1000);
        }

        function updatePromoDisplay(timerData) {
            const promoMessage = document.getElementById('promoMessage');
            const timerDisplay = document.getElementById('timerDisplay');
            const promoBanner = document.getElementById('promoBanner');
            promoMessage.innerHTML = '<i class="fas fa-fire me-2"></i>25% OFF for shopping before 30 minutes!';
            promoBanner.style.background = '#e53935';
            // Format timer display
            const totalSeconds = Math.floor(timerData.remainingTime / 1000);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        // Image Slider Functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slider-slide');
        const dots = document.querySelectorAll('.slider-dot');
        const sliderContainer = document.getElementById('sliderContainer');
        let slideInterval;

        function showSlide(index) {
            if (index >= slides.length) {
                currentSlide = 0;
            } else if (index < 0) {
                currentSlide = slides.length - 1;
            } else {
                currentSlide = index;
            }

            const offset = -currentSlide * 100;
            sliderContainer.style.transform = `translateX(${offset}%)`;

            // Update dots
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function changeSlide(direction) {
            showSlide(currentSlide + direction);
        }

        function goToSlide(index) {
            showSlide(index);
        }

        function startAutoSlide() {
            slideInterval = setInterval(() => {
                changeSlide(1);
            }, 4000); // Change slide every 4 seconds
        }

        function stopAutoSlide() {
            clearInterval(slideInterval);
        }

        // Initialize slider
        document.addEventListener('DOMContentLoaded', function() {
            showSlide(0);
            startAutoSlide();

            // Pause auto-slide on hover
            const slider = document.querySelector('.image-slider');
            slider.addEventListener('mouseenter', stopAutoSlide);
            slider.addEventListener('mouseleave', startAutoSlide);
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
    </script>
</body>
</html> 