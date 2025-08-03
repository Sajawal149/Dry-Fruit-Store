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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $error = 'Please enter a category name';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $message = 'Category added successfully!';
            } catch (Exception $e) {
                $error = 'Error adding category';
            }
        }
    } elseif ($action == 'delete') {
        $category_id = (int)$_POST['category_id'];
        try {
            // Check if category is being used by any products
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $product_count = $stmt->fetchColumn();
            
            if ($product_count > 0) {
                $error = 'Cannot delete category: It is being used by ' . $product_count . ' product(s)';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $message = 'Category deleted successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error deleting category';
        }
    }
}

// Get categories with product count
$stmt = $pdo->prepare("
    SELECT c.id AS category_id, c.name, c.description, c.created_at,
           COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Dashboard</title>
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
    <style>
        [data-theme="dark"] .modal-content {
            background: #1e293b !important;
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] input[type="file"] {
            color: #f8fafc !important;
            border-color: #a0aec0 !important;
        }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus,
        [data-theme="dark"] textarea:focus,
        [data-theme="dark"] input[type="file"]:focus {
            border-color: #c7d2fe !important;
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
        }
        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] textarea::placeholder {
            color: #cbd5e1 !important;
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
                        <a class="nav-link active" href="categories.php">
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
                        <h2>Manage Categories</h2>
                        <div class="d-flex align-items-center">
                            <button class="btn theme-toggle me-3" onclick="toggleTheme()">
                                <i class="fas fa-moon" id="theme-icon"></i>
                                <span id="theme-text">Dark</span>
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <!-- Categories Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">All Categories</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Products</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?php echo $category['category_id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $category['product_count']; ?> products</span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($category['product_count'] == 0): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">Cannot delete (has products)</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
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