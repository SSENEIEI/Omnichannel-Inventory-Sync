<?php
// 1. Setup basic error handling immediately
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Helper to log to a file that is definitely writable or just use system log
function debug_log($message) {
    error_log("[Webhook Debug] " . $message);
    // Also try to write to a local file if possible
    @file_put_contents(__DIR__ . '/../logs/webhook_debug.log', $message . "\n", FILE_APPEND);
}

debug_log("Script started.");

// 2. Check dependencies
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    debug_log("Autoload not found at: $autoloadPath");
    http_response_code(500);
    echo json_encode(['error' => 'Backend configuration error: Autoload missing']);
    exit;
}

try {
    require_once $autoloadPath;
} catch (\Throwable $e) {
    debug_log("Autoload failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Backend error: Autoload failed']);
    exit;
}

use Dotenv\Dotenv;
use App\Database;

// 3. Load Env
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Throwable $e) {
    debug_log("Env load failed: " . $e->getMessage());
    // Continue anyway, maybe env vars are set in server
}

// 4. Process Request
try {
    $input = file_get_contents('php://input');
    debug_log("Input received: " . substr($input, 0, 100) . "...");
    
    $data = json_decode($input, true);

    if (!$data) {
        debug_log("Invalid JSON payload");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload: JSON decode failed']);
        exit;
    }

    debug_log("Connecting to DB...");
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        debug_log("DB Connection failed (getConnection returned null)");
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    debug_log("DB Connected. Starting transaction...");
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
        $stmt = $db->prepare("SELECT id, stock FROM products WHERE sku = ? FOR UPDATE");
        $stmt->execute([$item['sku']]);
        $product = $stmt->fetch();

        if (!$product) {
            debug_log("Product not found: " . $item['sku']);
            continue; 
        }

        $newStock = $product['stock'] - $item['quantity'];
        if ($newStock < 0) {
            throw new Exception("Insufficient stock for SKU: " . $item['sku']);
        }

        $updateStmt = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $updateStmt->execute([$newStock, $product['id']]);

        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $itemStmt->execute([$orderId, $product['id'], $item['quantity'], $item['price']]);
    }

    $db->commit();
    debug_log("Transaction committed.");

    // Mock Sync
    $mockPlatforms = ['Shopee', 'Lazada', 'TikTok', 'Website'];
    foreach ($mockPlatforms as $targetPlatform) {
        if (strcasecmp($targetPlatform, $platformName) === 0) continue;
        $syncService = new \App\Services\MockPlatformService($targetPlatform);
        foreach ($data['items'] as $item) {
             $syncService->updateStock($item['sku'], $newStock ?? 0);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Order processed']);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    debug_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
