<?php

namespace App\Services;

use App\Interfaces\PlatformInterface;

class MockPlatformService implements PlatformInterface
{
    private $platformName;
    private $logFile;

    public function __construct($platformName)
    {
        $this->platformName = $platformName;
        $this->logFile = __DIR__ . '/../../logs/sync.log';
    }

    public function updateStock($sku, $quantity)
    {
        // จำลองการยิง API ไปยัง Shopee/Lazada
        // ในของจริง ตรงนี้จะเป็นโค้ด cURL หรือ GuzzleHttp
        
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] [SYNC-OUT] 🚀 Sending update to {$this->platformName} API: Set SKU '{$sku}' to Stock: {$quantity}\n";
        
        // บันทึกลงไฟล์ log แทนการยิงจริง
        file_put_contents($this->logFile, $message, FILE_APPEND);
        
        return true;
    }
}
