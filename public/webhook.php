<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');

// Simulate receiving a webhook payload
// In reality, you'd verify signatures from Shopee/Lazada/TikTok here
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Identify Platform
    $platformName = $data['platform'] ?? 'Website';
    $stmt = $db->prepare("SELECT id FROM platforms WHERE name = ?");
    $stmt->execute([$platformName]);
    $platformId = $stmt->fetchColumn();

    if (!$platformId) {
        throw new Exception("Unknown platform: $platformName");
    }

    // 2. Create Order
    $stmt = $db->prepare("INSERT INTO orders (platform_id, platform_order_id, total_amount, status, customer_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $platformId,
        $data['order_id'],
        $data['total_amount'],
        'paid',
        $data['customer_name']
    ]);
    $orderId = $db->lastInsertId();

    // 3. Process Items & Deduct Stock
    foreach ($data['items'] as $item) {
        // Find product by SKU (assuming SKU is synced)
        $stmt = $db->prepare("SELECT id, stock FROM products WHERE sku = ? FOR UPDATE");
        $stmt->execute([$item['sku']]);
        $product = $stmt->fetch();

        if (!$product) {
            // Log error: Product not found
            continue; 
        }

        // Deduct Stock
        $newStock = $product['stock'] - $item['quantity'];
        if ($newStock < 0) {
            throw new Exception("Insufficient stock for SKU: " . $item['sku']);
        }

        $updateStmt = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $updateStmt->execute([$newStock, $product['id']]);

        // Record Order Item
        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $itemStmt->execute([$orderId, $product['id'], $item['quantity'], $item['price']]);
    }

    $db->commit();

    // TODO: Trigger async job to push new stock levels to OTHER platforms
    // e.g., pushToShopee($sku, $newStock), pushToLazada($sku, $newStock)

    echo json_encode(['status' => 'success', 'message' => 'Order processed and stock updated']);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
