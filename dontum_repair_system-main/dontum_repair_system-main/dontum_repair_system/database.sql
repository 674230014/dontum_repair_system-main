
-- =====================================================
-- ฐานข้อมูลระบบแจ้งซ่อม DON TUM REPAIR
-- =====================================================

-- สร้างฐานข้อมูล และกำหนดให้รองรับภาษาไทย
CREATE DATABASE IF NOT EXISTS dontum_repair
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- เลือกฐานข้อมูลที่จะใช้งาน
USE dontum_repair;

-- ปิดการตรวจสอบ Foreign Key ชั่วคราว
SET FOREIGN_KEY_CHECKS = 0;

-- ลบตารางเดิม หากมีอยู่แล้ว
DROP TABLE IF EXISTS status_logs;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS users;

-- เปิดการตรวจสอบ Foreign Key กลับคืน
SET FOREIGN_KEY_CHECKS = 1;


-- =====================================================
-- ตาราง users: ข้อมูลผู้ใช้งานระบบ
-- =====================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,

    -- บทบาทผู้ใช้งาน: ผู้ดูแลระบบ ช่าง และผู้แจ้งซ่อม
    role ENUM('admin', 'technician', 'user') NOT NULL,

    -- 1 = ใช้งานได้, 0 = ปิดใช้งาน
    active TINYINT(1) NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- =====================================================
-- ตาราง tickets: ข้อมูลรายการแจ้งซ่อม
-- =====================================================
CREATE TABLE tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- รหัสผู้แจ้งซ่อม
    user_id INT UNSIGNED NOT NULL,

    -- รหัสช่างที่ได้รับมอบหมาย (ยังไม่มอบหมายให้ช่างได้)
    technician_id INT UNSIGNED NULL,

    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(40) NULL,

    -- ตำแหน่งไฟล์รูปภาพที่แนบกับรายการแจ้งซ่อม (ไม่บังคับ)
    image_path VARCHAR(255) NULL,

    -- สถานะงานซ่อม
    -- open = รอรับงาน
    -- assigned = มอบหมายงานแล้ว
    -- in_progress = กำลังดำเนินการ
    -- resolved = ซ่อมเสร็จแล้ว
    -- closed = ปิดงาน
    status ENUM(
        'open',
        'assigned',
        'in_progress',
        'resolved',
        'closed'
    ) NOT NULL DEFAULT 'open',

    -- หมายเหตุจากผู้ดูแลระบบ
    admin_note TEXT NULL,

    -- บันทึกรายละเอียดการซ่อมจากช่าง
    repair_note TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,

    -- เชื่อมโยงกับผู้แจ้งซ่อมและช่างผู้รับผิดชอบ
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (technician_id) REFERENCES users(id),

    -- สร้างดัชนีเพื่อช่วยค้นหาข้อมูล
    INDEX (status),
    INDEX (user_id),
    INDEX (technician_id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- =====================================================
-- ตาราง status_logs: ประวัติการเปลี่ยนสถานะงานซ่อม
-- =====================================================
CREATE TABLE status_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- รหัสรายการแจ้งซ่อม
    ticket_id INT UNSIGNED NOT NULL,

    -- รหัสผู้เปลี่ยนสถานะ (อนุญาตให้เป็น NULL ได้)
    changed_by INT UNSIGNED NULL,

    -- สถานะก่อนเปลี่ยน และสถานะหลังเปลี่ยน
    from_status ENUM(
        'open',
        'assigned',
        'in_progress',
        'resolved',
        'closed'
    ) NULL,

    to_status ENUM(
        'open',
        'assigned',
        'in_progress',
        'resolved',
        'closed'
    ) NOT NULL,

    -- หมายเหตุประกอบการเปลี่ยนสถานะ
    note TEXT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- ลบประวัติเมื่อรายการแจ้งซ่อมถูกลบ
    FOREIGN KEY (ticket_id)
        REFERENCES tickets(id)
        ON DELETE CASCADE,

    -- หากผู้เปลี่ยนสถานะถูกลบ ให้เปลี่ยน changed_by เป็น NULL
    FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX (ticket_id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
