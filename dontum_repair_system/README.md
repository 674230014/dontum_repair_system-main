# ระบบแจ้งซ่อมเครื่องใช้ไฟฟ้า เขตดอนตูม
PHP 8 + MySQL/MariaDB + PDO | ธีมสีดำ-เหลือง | Login-first

## วิธีรันด้วย XAMPP
1. แตกไฟล์ ZIP ไปไว้ `C:\xampp\htdocs\dontum_repair_system`
2. เปิด XAMPP แล้ว Start Apache และ MySQL
3. เข้า `http://localhost/phpmyadmin` แล้ว Import ไฟล์ `database.sql`
4. ค่าเชื่อมต่อเริ่มต้นใน `config.php`: host `127.0.0.1`, database `dontum_repair`, user `root`, password ว่าง
5. เปิด `http://localhost/dontum_repair_system/`

## บัญชีทดลอง
รหัสผ่านทุกบัญชี `password123`
- Admin: `admin@dontum.local`
- ช่าง 1: `tech1@dontum.local`
- ช่าง 2: `tech2@dontum.local`
- ช่าง 3: `tech3@dontum.local`
- ผู้ใช้: `user@dontum.local`

## Workflow
ผู้ใช้แจ้งซ่อม → Admin มอบหมายช่าง → ช่างอัปเดตเป็นกำลังซ่อม → ช่างบันทึกผลและเปลี่ยนเป็นซ่อมเสร็จ
ระบบบันทึกประวัติสถานะทุกครั้ง และจำกัดการเห็นงานตาม role

## Phase 1: Design
โครงสร้างฐานข้อมูลมี users, tickets, status_logs และแยกบทบาท admin/technician/user
## Phase 2: Development
สร้างระบบ PHP แบบแยกไฟล์ เชื่อม MySQL ผ่าน PDO, prepared statements และ session login
## Phase 3: Testing & Deploy
ทดสอบด้วยบัญชีตัวอย่างในเครื่องก่อนนำขึ้นเว็บเซิร์ฟเวอร์จริง ควรเปลี่ยนรหัสผ่านตัวอย่างและตั้ง HTTPS ก่อนใช้งานจริง

หมายเหตุ: เวอร์ชันนี้ยังไม่ได้เชื่อม LINE API และยังไม่มีอัปโหลดรูปหลักฐาน/CSRF token เหมาะสำหรับต้นแบบและการเรียน

หลัง import database.sql ให้เปิด `http://localhost/dontum_repair_system/seed.php` หนึ่งครั้งเพื่อสร้างบัญชีทดลอง จากนั้นควรลบไฟล์ `seed.php` ออก


## แนบรูปภาพในรายการแจ้งซ่อม

- แบบฟอร์ม `ticket_create.php` รองรับการแนบรูป JPG, PNG และ WEBP ขนาดไม่เกิน 5 MB (ไม่บังคับแนบรูป)
- ระบบเก็บไฟล์ไว้ใน `uploads/repairs/` และบันทึกพาธในคอลัมน์ `tickets.image_path`
- หน้า `ticket_view.php` แสดงรูปประกอบรายการแจ้งซ่อม
- หากฐานข้อมูลเดิมยังไม่มีคอลัมน์ ให้รัน `ALTER TABLE tickets ADD COLUMN image_path VARCHAR(255) NULL;` เพียงครั้งเดียวใน phpMyAdmin
