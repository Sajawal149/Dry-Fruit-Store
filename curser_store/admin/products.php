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
    
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $original_price = (float)$_POST['original_price'];
        $stock_quantity = (int)$_POST['stock_quantity'];
        $category_id = (int)$_POST['category_id'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_url = '';
        
        // Handle file upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $fileSize = $_FILES['product_image']['size'];
            $fileType = $_FILES['product_image']['type'];
            $fileNameCmps = explode('.', $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = uniqid('prod_', true) . '.' . $fileExtension;
                $uploadFileDir = '../images/products/';
                $dest_path = $uploadFileDir . $newFileName;
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $image_url = $newFileName;
                } else {
                    $error = 'Error moving uploaded file.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
            }
        } else if ($action == 'edit' && !empty($_POST['existing_image_url'])) {
            // Keep the old image if no new file is uploaded
            $image_url = $_POST['existing_image_url'];
        }
        
        if (empty($name) || $price <= 0) {
            $error = 'Please fill in all required fields correctly';
        } else if (empty($image_url)) {
            $error = 'Please upload a product image.';
        } else {
            try {
                if ($action == 'add') {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, original_price, stock_quantity, category_id, is_featured, is_active, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $original_price, $stock_quantity, $category_id, $is_featured, $is_active, $image_url]);
                    $message = 'Product added successfully!';
                } else {
                    $product_id = (int)$_POST['product_id'];
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, original_price = ?, stock_quantity = ?, category_id = ?, is_featured = ?, is_active = ?, image_url = ? WHERE product_id = ?");
                    $stmt->execute([$name, $description, $price, $original_price, $stock_quantity, $category_id, $is_featured, $is_active, $image_url, $product_id]);
                    $message = 'Product updated successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error saving product';
            }
        }
    } elseif ($action == 'delete') {
        $product_id = (int)$_POST['product_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $message = 'Product deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting product';
        }
    }
}

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with category names
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product details for editing
if (isset($_GET['edit'])) {
    $product_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $edit_product = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin Dashboard</title>
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

        /* Product image styling */
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--glass-border);
        }
        .table-hover tbody tr:hover {
            background-color: var(--primary-color) !important;
            color: #fff !important;
        }
        .table-hover tbody tr:hover td, .table-hover tbody tr:hover th {
            color: #fff !important;
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] input[type="file"] {
            background: #232a36 !important;
            color: #f1f5f9 !important;
            border-color: #444a5a !important;
        }
        [data-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] textarea::placeholder {
            color: #a0aec0 !important;
        }
        [data-theme="dark"] .modal-content {
            background: #1e293b !important;
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] textarea,
        [data-theme="dark"] input[type="file"] {
            background: #232a36 !important;
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
                        <a class="nav-link active" href="products.php">
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
                        <h2>Manage Products</h2>
                        <div class="d-flex align-items-center">
                            <button class="btn theme-toggle me-3" onclick="toggleTheme()">
                                <i class="fas fa-moon" id="theme-icon"></i>
                                <span id="theme-text">Dark</span>
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">All Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <img src="../images/products/<?php echo $product['image_url'] ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/60x60?text=No+Image'; ?>" 
                                                         class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <?php if ($product['is_featured']): ?>
                                                        <span class="badge bg-warning ms-1">Featured</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                                <td>
                                                    Rs. <?php echo number_format($product['price']); ?>
                                                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                                        <br><small class="text-muted">Was: Rs. <?php echo number_format($product['original_price']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $product['stock_quantity'] <= 10 ? 'danger' : 'success'; ?>">
                                                        <?php echo $product['stock_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? $product['product_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="product_id" id="productId">
                        <input type="hidden" name="existing_image_url" id="existing_image_url">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (Rs.) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="original_price" class="form-label">Original Price (Rs.)</label>
                                <input type="number" class="form-control" id="original_price" name="original_price" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="product_image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                <small class="text-muted">Upload a new image to replace the current one.</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
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

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('productId').value = product.product_id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('original_price').value = product.original_price;
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('existing_image_url').value = product.image_url;
            document.getElementById('is_featured').checked = product.is_featured == 1;
            document.getElementById('is_active').checked = product.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('addProductModal')).show();
        }
        
        // Reset form when modal is closed
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.querySelector('#addProductModal form').reset();
            document.getElementById('existing_image_url').value = ''; // Clear existing image URL on modal close
        });
    </script>
</body>
</html> 