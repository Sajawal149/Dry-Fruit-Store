<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    switch ($action) {
        case 'update':
            if ($quantity > 0) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $user_id]);
            }
            break;
        case 'remove':
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            break;
    }
    
    header('Location: cart.php');
    exit;
}

// Get cart items with product details
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_url, p.stock_quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Northern Dry Fruits Store</title>
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

        .cart-item h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            letter-spacing: -0.01em;
        }

        .price {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: -0.01em;
        }

        .total-price {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.01em;
        }

        .btn-cart {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
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

        /* Cart Items */
        .cart-item {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            transition: all 0.3s ease;
            height: 200px; /* Fixed height for all cart items */
            display: flex;
            align-items: center;
        }

        .cart-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px 0 var(--shadow-color);
        }

        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 8px 0 var(--shadow-color);
            flex-shrink: 0;
        }

        .cart-item-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding-left: 20px;
        }

        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cart-item-title {
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

        .cart-item-price {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
            letter-spacing: -0.01em;
            margin-bottom: 0.5rem;
        }

        .cart-item-quantity {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-secondary);
            letter-spacing: -0.01em;
            margin-bottom: 1rem;
        }

        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-shrink: 0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 15px;
        }

        .quantity-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .quantity-display {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .remove-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(255, 107, 107, 0.3);
        }

        .price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.2em;
        }

        .total-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5em;
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

        .btn-outline-cart {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-cart:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
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

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .quantity-input {
            background: var(--surface-color);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            width: 60px;
            color: var(--text-primary);
        }

        /* Summary Card */
        .summary-card {
            background: var(--surface-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px 0 var(--shadow-color);
            position: sticky;
            top: 20px;
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
                        <a class="nav-link" href="products_new.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
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
                            <a class="nav-link active" href="cart.php">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-badge"><?php echo $total_items; ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
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

    <!-- Page Header -->
    <div class="container mt-4">
        <h1 class="text-center mb-4 fw-bold">Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4>Your cart is empty</h4>
                <p class="text-muted">Add some products to your cart to get started.</p>
                <a href="products_new.php" class="btn btn-primary fw-bold">
                    <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo $item['image_url'] ? 'images/products/' . htmlspecialchars($item['image_url']) : 'https://via.placeholder.com/80x80?text=Dry+Fruits'; ?>" 
                                 class="cart-item-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="cart-item-content">
                                <div class="cart-item-details">
                                    <h5 class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="cart-item-price">Rs. <?php echo number_format($item['price']); ?></p>
                                    <p class="cart-item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="cart-item-actions">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <button class="remove-btn" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                        <i class="fas fa-trash me-1"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="mb-4 fw-bold">Order Summary</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-bold">Rs. <?php echo number_format($subtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Shipping:</span>
                            <span class="fw-bold">Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold">Total:</span>
                            <span class="total-price">Rs. <?php echo number_format($subtotal); ?></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-cart text-white fw-bold">
                                <i class="fas fa-credit-card me-2"></i> Proceed to Checkout
                            </a>
                            <a href="cashout.php" class="btn btn-outline-cart fw-bold">
                                <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                            </a>
                            <a href="products_new.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
        });

        function updateQuantity(cartId, change) {
            fetch('ajax/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_id: cartId,
                    change: change
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });
        }

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('ajax/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_id: cartId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error removing item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }
    </script>
</body>
</html> 