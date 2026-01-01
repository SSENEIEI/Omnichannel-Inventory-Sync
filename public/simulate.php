<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$database = new Database();
$db = $database->getConnection();

// Get products for the dropdown
$stmt = $db->query("SELECT * FROM products");
$products = $stmt->fetchAll();

// If no products, seed one
if (empty($products)) {
    $db->exec("INSERT INTO products (sku, name, price, stock) VALUES ('TSHIRT-001', 'เสื้อยืดสีดำ Size L', 250.00, 100)");
    header("Refresh:0");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Simulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .platform-btn { cursor: pointer; transition: transform 0.1s; }
        .platform-btn:active { transform: scale(0.95); }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h4>🎮 Marketplace Order Simulator</h4>
                        <small>จำลองการยิงออเดอร์จากแพลตฟอร์มต่างๆ เข้ามาที่ระบบของเรา</small>
                    </div>
                    <div class="card-body">
                        <form id="orderForm">
                            <div class="mb-3">
                                <label class="form-label">เลือกสินค้า (Product)</label>
                                <select class="form-select" id="productSku">
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['sku'] ?>" data-price="<?= $p['price'] ?>">
                                            <?= $p['name'] ?> (Stock: <?= $p['stock'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">จำนวน (Quantity)</label>
                                <input type="number" class="form-control" id="quantity" value="1" min="1">
                            </div>

                            <hr>
                            <label class="form-label mb-3">กดปุ่มเพื่อจำลองว่ามีออเดอร์เข้ามาจาก:</label>
                            <div class="d-grid gap-2 d-md-block">
                                <button type="button" class="btn btn-warning btn-lg platform-btn" onclick="sendOrder('Shopee')">🧡 Shopee</button>
                                <button type="button" class="btn btn-primary btn-lg platform-btn" onclick="sendOrder('Lazada')">💙 Lazada</button>
                                <button type="button" class="btn btn-dark btn-lg platform-btn" onclick="sendOrder('TikTok')">🖤 TikTok</button>
                            </div>
                        </form>

                        <div id="result" class="mt-4 alert" style="display:none;"></div>
                    </div>
                    <div class="card-footer text-muted">
                        เมื่อกดปุ่ม ระบบจะยิง Webhook ไปที่ <code>/webhook.php</code> เหมือนของจริง
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">⬅️ กลับไปดู Dashboard (ดูสต็อกลดลง)</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function sendOrder(platform) {
        const sku = document.getElementById('productSku').value;
        const qty = parseInt(document.getElementById('quantity').value);
        const price = document.querySelector('#productSku option:checked').getAttribute('data-price');
        
        // สร้างข้อมูลจำลอง (Payload) เหมือนที่ Shopee/Lazada ส่งมาให้เรา
        const payload = {
            platform: platform,
            order_id: platform.toUpperCase() + '-' + Date.now(), // สร้างเลข Order มั่วๆ
            customer_name: 'ลูกค้าทดสอบ ' + Math.floor(Math.random() * 1000),
            total_amount: price * qty,
            items: [
                {
                    sku: sku,
                    quantity: qty,
                    price: price
                }
            ]
        };

        // ยิง Webhook
        fetch('webhook.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            const text = await response.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                // Not JSON
            }

            if (!response.ok) {
                const error = (data && data.error) || text || response.statusText;
                return Promise.reject(error);
            }
            
            if (!data) {
                return Promise.reject("Invalid server response (not JSON): " + text.substring(0, 100));
            }

            return data;
        })
        .then(data => {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            if (data.status === 'success') {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = `✅ <b>สำเร็จ!</b> ออเดอร์จาก ${platform} เข้าแล้ว <br> สต็อกถูกตัดเรียบร้อย!`;
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = `❌ <b>ผิดพลาด:</b> ${data.error}`;
            }
        })
        .catch(err => {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `❌ <b>Error connecting to webhook:</b> <br> ${err}`;
            console.error('Webhook Error:', err);
        });
    }
    </script>
</body>
</html>
