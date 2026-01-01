# Omnichannel Inventory Sync

ระบบจัดการสต็อกรวมสำหรับพ่อค้าแม่ค้าออนไลน์ (MVP)

## Features
- **Unified Dashboard**: ดูออเดอร์จากทุกแพลตฟอร์มในที่เดียว
- **Auto-sync Stock**: ตัดสต็อกทันทีเมื่อมีออเดอร์เข้าผ่าน Webhook
- **Database Migration**: สร้างตาราง Database อัตโนมัติ

## Setup

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Edit `.env` with your database credentials (XAMPP or TiDB)

3. **Setup Database**
   - Run the setup script to create tables automatically:
   ```bash
   php setup_database.php
   ```

4. **Run Locally**
   - Start PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```
   - Visit `http://localhost:8000`

## Deployment (Render)
- Connect your repository to Render.
- Set Environment Variables in Render dashboard matching your `.env`.
- Render will automatically detect `composer.json` and install dependencies.
