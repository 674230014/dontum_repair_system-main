
<?php
require 'config.php';

// ตรวจสอบว่าผู้ใช้มีสิทธิ์แจ้งซ่อม
$u = role_required('user');
$err = '';
$maxImageSize = 5 * 1024 * 1024; // จำกัดขนาดรูปไม่เกิน 5 MB
$uploadedImagePath = null;

// รับข้อมูลจากแบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = [
        trim($_POST['title'] ?? ''),
        trim($_POST['category'] ?? ''),
        trim($_POST['description'] ?? ''),
        trim($_POST['location'] ?? ''),
        trim($_POST['phone'] ?? '')
    ];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!$v[0] || !$v[1] || !$v[2] || !$v[3]) {
        $err = 'กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบ';
    }

    // ตรวจสอบรูปภาพที่แนบ (ไม่บังคับแนบรูป)
    $imageFile = $_FILES['repair_image'] ?? null;
    if (!$err && $imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($imageFile['error'] !== UPLOAD_ERR_OK) {
            $err = 'อัปโหลดรูปภาพไม่สำเร็จ กรุณาลองใหม่';
        } elseif ($imageFile['size'] > $maxImageSize) {
            $err = 'รูปภาพต้องมีขนาดไม่เกิน 5 MB';
        } else {
            // ตรวจชนิดไฟล์จากเนื้อหาจริง ไม่เชื่อเฉพาะนามสกุลไฟล์
            $imageInfo = @getimagesize($imageFile['tmp_name']);
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];
            $imageMime = $imageInfo['mime'] ?? '';

            if (!$imageInfo || !isset($allowedMimeTypes[$imageMime])) {
                $err = 'รองรับเฉพาะรูปภาพ JPG, PNG และ WEBP';
            } else {
                // สร้างโฟลเดอร์เก็บรูป หากยังไม่มี
                $uploadDirectory = __DIR__ . '/uploads/repairs';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                    $err = 'ไม่สามารถสร้างโฟลเดอร์เก็บรูปภาพได้';
                } elseif (!is_writable($uploadDirectory)) {
                    $err = 'โฟลเดอร์ uploads/repairs ไม่มีสิทธิ์เขียนไฟล์';
                } else {
                    // สุ่มชื่อไฟล์เพื่อไม่ให้ชื่อซ้ำและไม่ใช้ชื่อเดิมจากผู้ใช้
                    $imageFileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$imageMime];
                    if (move_uploaded_file($imageFile['tmp_name'], $uploadDirectory . '/' . $imageFileName)) {
                        $uploadedImagePath = 'uploads/repairs/' . $imageFileName;
                    } else {
                        $err = 'บันทึกไฟล์รูปภาพไม่สำเร็จ';
                    }
                }
            }
        }
    }

    if (!$err) {
        try {
            // บันทึกรายการแจ้งซ่อมและตำแหน่งรูปภาพลงฐานข้อมูล
            $pdo->prepare("
            INSERT INTO tickets
            (user_id, title, category, description, location, contact_phone, status, image_path)
            VALUES (?, ?, ?, ?, ?, ?, 'open', ?)
        ")->execute([$u['id'], ...$v, $uploadedImagePath]);

        // รับเลขที่งานที่เพิ่งสร้าง
        $id = $pdo->lastInsertId();

        // บันทึกประวัติการสร้างงาน
        $pdo->prepare("
            INSERT INTO status_logs
            (ticket_id, changed_by, from_status, to_status, note)
            VALUES (?, ?, NULL, 'open', 'ผู้ใช้สร้างรายการแจ้งซ่อม')
        ")->execute([$id, $u['id']]);

        // แจ้งผลและไปหน้ารายละเอียดงาน
        flash('แจ้งซ่อมสำเร็จ เลขที่งาน #' . $id);
        go('ticket_view.php?id=' . $id);
        } catch (Throwable $exception) {
            // หากบันทึกฐานข้อมูลไม่สำเร็จ ให้ลบรูปที่อัปโหลดไว้เพื่อไม่ให้มีไฟล์ค้าง
            if ($uploadedImagePath && is_file(__DIR__ . '/' . $uploadedImagePath)) {
                @unlink(__DIR__ . '/' . $uploadedImagePath);
            }
            error_log('Create repair ticket failed: ' . $exception->getMessage());
            $err = 'บันทึกข้อมูลแจ้งซ่อมไม่สำเร็จ กรุณาลองใหม่';
        }
    }
}

// ตั้งชื่อหน้า
$title = 'แจ้งซ่อมใหม่';
require 'partials/header.php';
?>

<!-- ส่วนหัวหน้าแจ้งซ่อม -->
<div class="heading">
    <div>
        <small class="gold">NEW REPAIR REQUEST</small>
        <h1>แจ้งซ่อมเครื่องใช้ไฟฟ้า</h1>
        <p class="muted">
            กรอกรายละเอียดอุปกรณ์และอาการเสีย
            เพื่อให้ช่างสามารถตรวจสอบงานได้
        </p>
    </div>

    <a class="outline" href="dashboard.php">
        ← กลับหน้าหลัก
    </a>
</div>

<!-- กล่องแบบฟอร์มแจ้งซ่อม -->
<section class="card formcard">

    <?php if ($err): ?>
        <div class="alert error"><?= e($err) ?></div>
    <?php endif; ?>

    <div class="form-intro">
        <div class="form-icon">🔧</div>
        <div>
            <h2>รายละเอียดการแจ้งซ่อม</h2>
            <p class="muted">กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน</p>
        </div>
    </div>

    <form method="post" class="grid" enctype="multipart/form-data">

        <!-- ชื่ออุปกรณ์ -->
        <label>
            <span>ชื่ออุปกรณ์ <b class="required">*</b></span>
            <input
                name="title"
                required
                placeholder="เช่น พัดลม ตู้เย็น เครื่องซักผ้า"
            >
        </label>

        <!-- ประเภทอุปกรณ์ -->
        <label>
            <span>ประเภทอุปกรณ์ <b class="required">*</b></span>
            <select name="category" required>
                <option value="">เลือกประเภทอุปกรณ์</option>
                <?php foreach ([
                    'เครื่องใช้ไฟฟ้าในครัว',
                    'เครื่องปรับอากาศ',
                    'พัดลม',
                    'เครื่องซักผ้า',
                    'ตู้เย็น',
                    'โทรทัศน์',
                    'อื่น ๆ'
                ] as $c): ?>
                    <option><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- รายละเอียดอาการเสีย -->
        <label class="span">
            <span>อาการเสีย / รายละเอียด <b class="required">*</b></span>
            <textarea
                name="description"
                rows="5"
                required
                placeholder="อธิบายอาการเสีย เช่น เปิดไม่ติด มีเสียงดัง หรือทำงานผิดปกติ"
            ></textarea>
        </label>

        <!-- สถานที่เข้าซ่อม -->
        <label class="span">
            <span>ที่อยู่สำหรับเข้าซ่อมในเขตดอนตูม <b class="required">*</b></span>
            <input
                name="location"
                required
                placeholder="บ้านเลขที่ หมู่ ตำบล อำเภอดอนตูม จังหวัดนครปฐม"
            >
        </label>

        <!-- เบอร์ติดต่อ -->
        <label>
            <span>เบอร์โทรศัพท์ติดต่อ</span>
            <input
                name="phone"
                type="tel"
                placeholder="เช่น 08X-XXX-XXXX"
            >
        </label>

        <!-- แนบรูปภาพอาการเสีย (ไม่บังคับ) -->
        <label class="span upload-field">
            <span>รูปภาพอุปกรณ์ / อาการเสีย (ไม่บังคับ)</span>
            <input
                type="file"
                name="repair_image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >
            <small class="muted">รองรับ JPG, PNG และ WEBP ขนาดไม่เกิน 5 MB</small>
        </label>

        <!-- ปุ่มดำเนินการ -->
        <div class="span actions">
            <button type="submit">ส่งแจ้งซ่อม →</button>
            <a class="outline" href="dashboard.php">ยกเลิก</a>
        </div>

    </form>
</section>

<?php require 'partials/footer.php'; ?>
