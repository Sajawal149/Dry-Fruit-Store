<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Products Page</h1>";
echo "<p>Testing database connection and queries...</p>";

try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✓ Database config loaded successfully</p>";
    
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test basic query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $total = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✓ Products table accessible. Total products: $total</p>";
    
    // Test categories query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories");
    $stmt->execute();
    $cat_count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✓ Categories table accessible. Total categories: $cat_count</p>";
    
    // Test session
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: green;'>✓ User logged in: " . $_SESSION['username'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No user logged in</p>";
    }
    
    echo "<h2>Navigation Test</h2>";
    echo "<p><a href='index.php'>Go to Home</a></p>";
    echo "<p><a href='products.php'>Go to Products</a></p>";
    echo "<p><a href='about.php'>Go to About</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Debug Products Page</h1>
        <p>This is a debug version to test navigation and database connectivity.</p>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Test Navigation</h3>
                <a href="index.php" class="btn btn-primary">Home</a>
                <a href="products.php" class="btn btn-success">Products</a>
                <a href="about.php" class="btn btn-info">About</a>
            </div>
            
            <div class="col-md-6">
                <h3>Server Info</h3>
                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                <p><strong>Current File:</strong> <?php echo $_SERVER['PHP_SELF']; ?></p>
                <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            </div>
        </div>
    </div>
</body>
</html> 