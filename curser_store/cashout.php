<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get cart items with product details
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.stock_quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = 0; // Free shipping
$total = $subtotal + $shipping;

// Handle cashout form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    $phone = trim($_POST['phone']);
    $delivery_instructions = trim($_POST['delivery_instructions']);
    
    if (empty($shipping_address) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, phone, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $total, $shipping_address, $phone]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $new_stock = $item['stock_quantity'] - $item['quantity'];
                $update_stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $update_stmt->execute([$new_stock, $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?order_id=$order_id&payment=cod");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error processing order. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash on Delivery - Northern Dry Fruits Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            --card-bg: #ffffff;
            --sidebar-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            --card-bg: #1e293b;
            --sidebar-bg: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background: var(--background-color);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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

        /* Cashout Section Styling */
        .cashout-section {
            background: var(--surface-color);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .order-summary {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--glass-border);
        }

        /* Form styling */
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

        .btn-cashout {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: bold;
            color: white;
        }

        .btn-cashout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .cod-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
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

    <div class="container mt-4">
        <div class="text-center mb-4">
            <h1 class="mb-3">
                <i class="fas fa-money-bill-wave"></i> Cash on Delivery
            </h1>
            <div class="cod-badge d-inline-block">
                <i class="fas fa-hand-holding-usd"></i> Pay on Delivery
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="cashout-section">
                    <h3 class="mb-4">Delivery Information</h3>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Delivery Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required 
                                      placeholder="Enter your complete delivery address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                            <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="2" 
                                      placeholder="Any special instructions for delivery (optional)"></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Cash on Delivery:</strong> You will pay the full amount when your order is delivered.
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-cashout text-white">
                                <i class="fas fa-check-circle"></i> Place Order (Cash on Delivery)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary">
                    <h4 class="mb-3">Order Summary</h4>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <br>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                            </div>
                            <span>Rs. <?php echo number_format($item['price'] * $item['quantity']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Rs. <?php echo number_format($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span class="text-success">Free</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary">Rs. <?php echo number_format($total); ?></strong>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-hand-holding-usd"></i>
                        <strong>Pay on Delivery</strong><br>
                        No payment required now!
                    </div>
                    
                    <a href="cart.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Northern Dry Fruits Store</h5>
                    <p>Bringing you the finest quality dry fruits from the mountains of Northern Pakistan.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact Us</h5>
                    <p>Email: info@northerndryfruits.com<br>
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
    </script>
</body>
</html> 