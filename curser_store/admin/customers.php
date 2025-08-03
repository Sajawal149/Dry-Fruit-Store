<?php
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'delete') {
        $customer_id = (int)$_POST['customer_id'];
        try {
            // Check if customer has orders
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            $order_count = $stmt->fetchColumn();
            
            if ($order_count > 0) {
                $error = 'Cannot delete customer with existing orders.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
                $stmt->execute([$customer_id]);
                $message = 'Customer deleted successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error deleting customer';
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["role = 'customer'"];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_params = $params;
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where_clause");
$count_stmt->execute($count_params);
$total_customers = $count_stmt->fetchColumn();
$total_pages = ceil($total_customers / $per_page);

// Get customers with order statistics
$limit = (int)$per_page;
$offset = (int)$offset;

$sql = "SELECT u.*, 
               COUNT(o.id) as total_orders,
               SUM(o.total_amount) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        WHERE $where_clause 
        GROUP BY u.id 
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Show single customer details if id is set
$single_customer = null;
$customer_orders = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$customer_id]);
    $single_customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($single_customer) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customer_id]);
        $customer_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Admin Dashboard</title>
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
        .main-content {
            background: var(--background-color);
            min-height: 100vh;
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
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="customers.php">
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
                        <h2>Manage Customers</h2>
                        <div class="d-flex align-items-center">
                            <button class="btn theme-toggle me-3" onclick="toggleTheme()">
                                <i class="fas fa-moon" id="theme-icon"></i>
                                <span id="theme-text">Dark</span>
                            </button>
                            <div class="text-muted">
                                Total Customers: <?php echo $total_customers; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($single_customer): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">Customer Details</h4>
                            </div>
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($single_customer['full_name']); ?></h5>
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($single_customer['username']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($single_customer['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($single_customer['phone']); ?></p>
                                <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($single_customer['created_at'])); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($single_customer['address']); ?></p>
                                <hr>
                                <h6>Order History</h6>
                                <?php if (empty($customer_orders)): ?>
                                    <p class="text-muted">No orders found for this customer.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customer_orders as $order): ?>
                                                    <tr>
                                                        <td><?php echo $order['id']; ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                        <td>Rs. <?php echo number_format($order['total_amount']); ?></td>
                                                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <a href="customers.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i>Back to Customers</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Search -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-8">
                                        <label for="search" class="form-label">Search Customers</label>
                                        <input type="text" class="form-control" id="search" name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>" 
                                               placeholder="Search by name, email, or username...">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Customers List -->
                        <?php if (empty($customers)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4>No customers found</h4>
                                <p class="text-muted">Try adjusting your search criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($customers as $customer): ?>
                                    <div class="col-lg-6 mb-4">
                                        <div class="customer-card">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h5 class="mb-2"><?php echo htmlspecialchars($customer['full_name']); ?></h5>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($customer['username']); ?>
                                                    </p>
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                                    </p>
                                                    <?php if ($customer['phone']): ?>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                                                    </p>
                                                    
                                                    <!-- Customer Statistics -->
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <div class="border-end">
                                                                <h6 class="text-primary mb-1"><?php echo $customer['total_orders']; ?></h6>
                                                                <small class="text-muted">Orders</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="border-end">
                                                                <h6 class="text-success mb-1">Rs. <?php echo number_format($customer['total_spent'] ?: 0); ?></h6>
                                                                <small class="text-muted">Spent</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <h6 class="text-info mb-1">
                                                                <?php echo $customer['last_order_date'] ? date('M d', strtotime($customer['last_order_date'])) : 'Never'; ?>
                                                            </h6>
                                                            <small class="text-muted">Last Order</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <div class="btn-group-vertical w-100">
                                                        <a href="customers.php?id=<?php echo $customer['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary mb-2">
                                                            <i class="fas fa-eye"></i> View Details
                                                        </a>
                                                        <?php if ($customer['total_orders'] == 0): ?>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                                                                <i class="fas fa-lock"></i> Has Orders
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Customers pagination" class="mt-4">
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
                    <?php endif; ?>
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