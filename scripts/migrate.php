<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

echo "Starting migration...\n";

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.\n");
}

// SQL to create tables
$sql = "
    -- Products Table (Central Inventory)
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Platforms Table (e.g., Shopee, Lazada, TikTok)
    CREATE TABLE IF NOT EXISTS platforms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        api_key VARCHAR(255),
        api_secret VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Orders Table
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform_id INT,
        platform_order_id VARCHAR(100) NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) NOT NULL,
        customer_name VARCHAR(255),
        shipping_address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (platform_id) REFERENCES platforms(id)
    );

    -- Order Items Table
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    );

    -- Product Mappings (Link Central SKU to Platform Product ID)
    CREATE TABLE IF NOT EXISTS product_mappings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        platform_id INT,
        platform_product_id VARCHAR(100) NOT NULL,
        platform_sku VARCHAR(100),
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (platform_id) REFERENCES platforms(id),
        UNIQUE KEY unique_mapping (platform_id, platform_product_id)
    );
";

try {
    $db->exec($sql);
    echo "Tables created successfully!\n";
    
    // Seed some initial data for testing
    $check = $db->query("SELECT COUNT(*) FROM platforms")->fetchColumn();
    if ($check == 0) {
        $db->exec("INSERT INTO platforms (name) VALUES ('Shopee'), ('Lazada'), ('TikTok'), ('Website')");
        echo "Seeded platforms data.\n";
    }

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
