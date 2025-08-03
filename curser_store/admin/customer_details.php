<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pdo = getDBConnection();
$customer = null;
$orders = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customer_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <a href="customers.php" class="btn btn-secondary mb-4"><i class="fas fa-arrow-left me-2"></i>Back to Customers</a>
    <?php if ($customer): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Customer Details</h4>
            </div>
            <div class="card-body">
                <h5><?php echo htmlspecialchars($customer['full_name']); ?></h5>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($customer['created_at'])); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
                <hr>
                <h6>Order History</h6>
                <?php if (empty($orders)): ?>
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
                                <?php foreach ($orders as $order): ?>
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
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Customer not found.</div>
    <?php endif; ?>
</div>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html> 