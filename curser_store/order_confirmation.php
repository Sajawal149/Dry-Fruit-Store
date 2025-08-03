<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$payment_method = isset($_GET['payment']) ? $_GET['payment'] : 'online';

if (!$order_id) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get order details with items

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Northern Dry Fruits Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .confirmation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .success-header {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .order-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="confirmation-card">
                    <div class="success-header text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h2>Order Confirmed!</h2>
                        <p class="mb-0">
                            <?php if ($payment_method == 'cod'): ?>
                                Thank you for your order. Pay on delivery when your order arrives.
                            <?php else: ?>
                                Thank you for your order. We'll start processing it right away.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="p-4">
                        <div class="row mb-4 text-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h5>Order Details</h5>
                                <p class="mb-1"><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                                <p class="mb-1"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong> 
                                    <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span>
                                </p>
                                <p class="mb-0"><strong>Total Amount:</strong> Rs. <?php echo number_format($order['total_amount']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Shipping Information</h5>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                <p class="mb-0"><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                            </div>
                        </div>
                        
                        <h5 class="text-center">Order Items</h5>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center text-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo $item['image_url'] ? 'images/products/' . htmlspecialchars($item['image_url']) : 'https://via.placeholder.com/60x60?text=Product'; ?>" 
                                             class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <strong>Rs. <?php echo number_format($item['price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="alert alert-info mt-4 text-center">
                            <h6><i class="fas fa-info-circle"></i> What's Next?</h6>
                            <ul class="mb-0 d-inline-block text-start" style="text-align:left !important;">
                                <li>You will receive an email confirmation shortly</li>
                                <li>Our team will process your order within 24 hours</li>
                                <li>You'll receive updates on your order status</li>
                                <li>Delivery typically takes 3-5 business days</li>
                                <?php if ($payment_method == 'cod'): ?>
                                    <li><strong>Payment: Pay the full amount when your order is delivered</strong></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary me-2">
                                <i class="fas fa-home"></i> Continue Shopping
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user"></i> View My Orders
                            </a>
                        </div>
                    </div>
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
</body>
</html> 