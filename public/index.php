<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$database = new Database();
$db = $database->getConnection();

// Fetch recent orders
$stmt = $db->query("
    SELECT o.id, o.platform_order_id, p.name as platform, o.total_amount, o.status, o.created_at 
    FROM orders o 
    JOIN platforms p ON o.platform_id = p.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$orders = $stmt->fetchAll();

// Fetch low stock products
$stmt = $db->query("SELECT * FROM products WHERE stock < 10");
$lowStock = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omnichannel Inventory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">📦 Omnichannel Inventory Dashboard</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Recent Orders (ออเดอร์ล่าสุด)
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Order ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['platform']) ?></td>
                                    <td><?= htmlspecialchars($order['platform_order_id']) ?></td>
                                    <td><?= number_format($order['total_amount'], 2) ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($order['status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="4" class="text-center">No orders yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        🔄 Sync Logs (จำลองการส่งข้อมูลไป Platform อื่น)
                    </div>
                    <div class="card-body bg-dark text-white" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem;">
                        <?php
                        $logFile = __DIR__ . '/../logs/sync.log';
                        if (file_exists($logFile)) {
                            $logs = array_reverse(file($logFile)); // อ่านไฟล์และกลับลำดับให้ล่าสุดอยู่บน
                            foreach ($logs as $line) {
                                echo htmlspecialchars($line) . "<br>";
                            }
                        } else {
                            echo "No sync activity yet.";
                        }
                        ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-danger text-white">
                        Low Stock Alert (สินค้าใกล้หมด)
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($lowStock as $product): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                                    <span class="badge bg-danger rounded-pill"><?= $product['stock'] ?></span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($lowStock)): ?>
                                <li class="list-group-item text-center">Stock levels are good!</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
