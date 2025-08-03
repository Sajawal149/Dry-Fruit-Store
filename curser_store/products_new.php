<?php
require_once 'config/database.php';

$pdo = getDBConnection();

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions and parameters
$where_conditions = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

$where_clause = implode(" AND ", $where_conditions);

// Get total product count for pagination
$count_query = "SELECT COUNT(*) FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Determine sort order
switch ($sort) {
    case 'price_low':
        $order_clause = 'ORDER BY p.price ASC';
        break;
    case 'price_high':
        $order_clause = 'ORDER BY p.price DESC';
        break;
    case 'name':
        $order_clause = 'ORDER BY p.name ASC';
        break;
    default:
        $order_clause = 'ORDER BY p.created_at DESC';
        break;
}

// Final product query with named LIMIT/OFFSET
$product_query = "SELECT p.*, c.name AS category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE $where_clause 
                  $order_clause 
                  LIMIT :limit OFFSET :offset";

// Add limit and offset to parameters
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($product_query);

// Bind all parameters manually to ensure correct types
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Products - Northern Dry Fruits Store</title>
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
            padding-top: 90px; /* Reduced from 100px to 90px due to smaller promo banner */
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
            top: 40px; /* Reduced from 50px to 40px due to smaller promo banner */
            left: 0;
            right: 0;
            z-index: 1040;
            transition: top 0.3s ease;
            min-height:80px; /* Increased navbar height by 10px */
        }

        /* When promo banner is hidden, adjust navbar position */
        .navbar.no-promo {
            top: 0;
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
            height: 540px; /* Reduced from 600px to 540px for a more compact card */
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

        /* Promo Banner Styles */
        .promo-banner {
            background: #e53935 !important;
            color: white;
            padding: 8px 20px; /* Reduced from 12px to 8px */
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050; /* Higher than navbar */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            animation: slideInFromTop 0.5s ease-out;
        }

        .promo-banner .timer {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1rem;
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
        }

        .promo-text {
            display: flex;
            align-items: center;
            animation: slideRightToLeft 20s linear infinite;
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

        @keyframes slideRightToLeft {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .promo-content {
                flex-direction:row;
                gap: 6px;
            }
            
            .promo-text {
                animation: none;
                text-align: center;
            }
            .promo-timer{

                font-size: smaller;
                padding: 0.4rem;
            }
            }
            .promo-banner {
                padding: 4px 12px; /* Reduced from 8px 15px to 4px 12px for mobile */
                font-size: 0.75rem; 
                display: flex;
                flex-direction: row;/* Reduced from 0.8rem to 0.75rem */
            }
            
            .promo-banner .timer {
                font-size: 0.85rem; /* Reduced timer font size for mobile */
            }
            
            /* Adjust navbar position for smaller promo banner on mobile */
            .navbar {
                top: 30px; /* Reduced from 40px to 30px for mobile */
            }
            
            /* Adjust body padding for smaller promo banner on mobile */
            body {
                padding-top: 80px; /* Reduced from 90px to 80px for mobile */
            }
        }

        /* Product Cards */
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

        /* Rating Stars */
        .rating-stars {
            color: #ffc107;
            font-size: 1.2em;
        }

        .rating-stars .far {
            color: #e4e5e9;
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

        /* Filter Section */
        .filter-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

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

        /* Enhanced form styling for better visibility */
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Filter section styling */
        .filter-section {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
        }

        /* Button styling */
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Pagination */
        .pagination .page-link {
            background: var(--surface-color);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            backdrop-filter: blur(10px);
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
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
                        <a class="nav-link active" href="products_new.php">Products</a>
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

    <!-- Page Header -->
    <div class="container mt-4">
        <h1 class="text-center mb-4 fw-bold">Our Products</h1>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label fw-bold">Search Products</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label fw-bold">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label fw-bold">Sort By</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price High to Low</option>
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="fas fa-search me-2"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4>No products found</h4>
                <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                <a href="products_new.php" class="btn btn-primary">View All Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card product-card">
                        <img src="<?php echo $product['image_url'] ? 'images/products/' . htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/300x200?text=Dry+Fruits'; ?>" 
                             class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                        <div class="card-body">
                            <h5 class="card-title product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            
                            <!-- Rating Display -->
                            <?php if ($product['average_rating'] > 0): ?>
                                <div class="mb-2">
                                    <div class="rating-stars" style="font-size: 0.9em;">
                                        <?php
                                        $rating = $product['average_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <span class="ms-1 text-muted small">(<?php echo $product['review_count']; ?>)</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                            
                            <div class="product-price">
                                <span class="price">Rs. <?php echo number_format($product['price']); ?></span>
                                <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                    <span class="original-price ms-2">Rs. <?php echo number_format($product['original_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-cart text-white fw-bold" 
                                        onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                </button>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>"
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
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