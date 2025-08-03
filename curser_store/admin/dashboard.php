<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pdo = getDBConnection();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stmt->execute();
$total_products = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stmt->execute();
$total_customers = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stmt->execute();
$total_revenue = $stmt->fetch()['total'] ?: 0;

// Get recent orders
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products
$stmt = $pdo->prepare("SELECT * FROM products WHERE stock_quantity <= 10 AND is_active = 1 ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Northern Dry Fruits Store</title>
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

        .sidebar h4 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .sidebar .nav-link {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        .stat-card h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            letter-spacing: -0.02em;
        }

        .stat-card p {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        .card-header h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .theme-toggle {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
            border: 1px solid var(--glass-border);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .main-content {
            background: var(--background-color);
            min-height: 100vh;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
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

        /* Card styling */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .card-header {
            background: var(--surface-color);
            border-bottom: 1px solid var(--glass-border);
        }

        .table {
            color: var(--text-primary);
        }

        .table th {
            background: var(--surface-color);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-mountain"></i> Admin Panel
                        </h4>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link" href="customers.php">
                            <i class="fas fa-users"></i> Customers
                        </a>
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home"></i> View Store
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dashboard</h2>
                        <div class="d-flex align-items-center">
                            <button class="btn theme-toggle me-3" onclick="toggleTheme()">
                                <i class="fas fa-moon" id="theme-icon"></i>
                                <span id="theme-text">Dark</span>
                            </button>
                            <div class="text-muted">
                                Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-box text-primary stat-icon"></i>
                                <h3 class="mb-2"><?php echo $total_products; ?></h3>
                                <p class="text-muted mb-0">Total Products</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-users text-success stat-icon"></i>
                                <h3 class="mb-2"><?php echo $total_customers; ?></h3>
                                <p class="text-muted mb-0">Total Customers</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-clock text-warning stat-icon"></i>
                                <h3 class="mb-2"><?php echo $pending_orders; ?></h3>
                                <p class="text-muted mb-0">Pending Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-money-bill-wave text-danger stat-icon"></i>
                                <h3 class="mb-2">Rs. <?php echo number_format($total_revenue); ?></h3>
                                <p class="text-muted mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Recent Orders -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-shopping-cart"></i> Recent Orders
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_orders)): ?>
                                        <p class="text-muted text-center">No orders yet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Order ID</th>
                                                        <th>Customer</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_orders as $order): ?>
                                                        <tr>
                                                            <td>#<?php echo $order['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                                            <td>Rs. <?php echo number_format($order['total_amount']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'delivered' ? 'success' : 'secondary'); ?>">
                                                                    <?php echo ucfirst($order['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="orders.php" class="btn btn-primary">View All Orders</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Low Stock Products -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Alert
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($low_stock_products)): ?>
                                        <p class="text-muted text-center">All products are well stocked!</p>
                                    <?php else: ?>
                                        <?php foreach ($low_stock_products as $product): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                                                </div>
                                                <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-center mt-3">
                                            <a href="products.php" class="btn btn-primary">Manage Products</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
    </script>
</body>
</html>