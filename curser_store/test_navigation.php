<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Navigation Test</h1>
        <p>Testing if navigation links work correctly:</p>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Direct Links</h3>
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="index.php" class="btn btn-primary">Home Page</a>
                    </li>
                    <li class="list-group-item">
                        <a href="products.php" class="btn btn-success">Products Page</a>
                    </li>
                    <li class="list-group-item">
                        <a href="about.php" class="btn btn-info">About Page</a>
                    </li>
                    <li class="list-group-item">
                        <a href="login.php" class="btn btn-warning">Login Page</a>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-6">
                <h3>Navigation Bar</h3>
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
                                    <a class="nav-link" href="products.php">Products</a>
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
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Current URL Information</h3>
            <p><strong>Current File:</strong> <?php echo $_SERVER['PHP_SELF']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p><strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
            <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 