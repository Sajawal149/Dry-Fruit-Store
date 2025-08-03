<?php
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Products Test - Northern Dry Fruits Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
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
                        <a class="nav-link active" href="simple_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4">Simple Products Test</h1>
        
        <div class="alert alert-success">
            <h4>✓ Navigation Test Successful!</h4>
            <p>If you can see this page, then the navigation links are working correctly.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Test Links</h3>
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="index.php" class="btn btn-primary">← Back to Home</a>
                    </li>
                    <li class="list-group-item">
                        <a href="products.php" class="btn btn-success">Go to Full Products Page</a>
                    </li>
                    <li class="list-group-item">
                        <a href="about.php" class="btn btn-info">Go to About Page</a>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h3>Current Status</h3>
                <ul class="list-group">
                    <li class="list-group-item">
                        <strong>Current Page:</strong> Simple Products Test
                    </li>
                    <li class="list-group-item">
                        <strong>File:</strong> simple_products.php
                    </li>
                    <li class="list-group-item">
                        <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Next Steps</h3>
            <p>If this page loads correctly, then the issue might be with:</p>
            <ul>
                <li>Database queries in the main products.php file</li>
                <li>PHP errors preventing the page from loading</li>
                <li>Browser cache issues</li>
                <li>Server configuration problems</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 