
<?php
// เรียกใช้การตั้งค่าและเชื่อมต่อฐานข้อมูล
require 'config.php';

// ตรวจสอบว่าผู้ใช้งานเป็นช่างซ่อม
$u = role_required('technician');

// รับรหัสงานจาก URL
$id = (int) ($_GET['id'] ?? 0);

// ค้นหางานที่มอบหมายให้ช่างคนนี้
// อนุญาตเฉพาะงานที่มอบหมายแล้วหรือกำลังซ่อม
$q = $pdo->prepare("
    SELECT *
    FROM tickets
    WHERE id = ?
      AND technician_id = ?
      AND status IN ('assigned', 'in_progress')
");

$q->execute([$id, $u['id']]);
$t = $q->fetch();

// หากไม่พบงาน ให้กลับไปหน้าหลัก
if (!$t) {
    flash('ไม่พบงานที่อัปเดตได้', 'error');
    go('dashboard.php');
}

$err = '';

// ตรวจสอบเมื่อช่างกดบันทึกแบบฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับสถานะใหม่และบันทึกผลการซ่อม
    $s = $_POST['status'] ?? '';
    $note = trim($_POST['repair_note'] ?? '');

    // ตรวจสอบลำดับการเปลี่ยนสถานะ
    $valid = (
        $t['status'] === 'assigned'
        && $s === 'in_progress'
    ) || (
        $t['status'] === 'in_progress'
        && in_array($s, ['in_progress', 'resolved'], true)
    );

    if (!$valid) {
        $err = 'สถานะที่เลือกไม่ถูกต้อง';

    } elseif ($s === 'resolved' && !$note) {
        // ต้องบันทึกผลการซ่อมก่อนปิดงาน
        $err = 'กรุณาระบุผลการซ่อมก่อนบันทึก';

    } else {
        // เริ่ม Transaction เพื่อให้ข้อมูลทั้งสองส่วนบันทึกพร้อมกัน
        $pdo->beginTransaction();

        try {
            // อัปเดตสถานะและรายละเอียดผลการซ่อม
            $pdo->prepare("
                UPDATE tickets
                SET status = ?,
                    repair_note = ?,
                    updated_at = NOW(),
                    resolved_at = ?
                WHERE id = ?
            ")->execute([
                $s,
                $note,
                $s === 'resolved' ? date('Y-m-d H:i:s') : null,
                $id
            ]);

            // บันทึกประวัติการเปลี่ยนสถานะ
            $pdo->prepare("
                INSERT INTO status_logs
                (ticket_id, changed_by, from_status, to_status, note)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $id,
                $u['id'],
                $t['status'],
                $s,
                $note ?: status_th($s)
            ]);

            // ยืนยันการบันทึกข้อมูล
            $pdo->commit();

            // แจ้งผลสำเร็จและไปหน้ารายละเอียดงาน
            flash('อัปเดตสถานะงานเรียบร้อย');
            go('ticket_view.php?id=' . $id);

        } catch (Throwable $e) {
            // ยกเลิกการบันทึกเมื่อเกิดข้อผิดพลาด
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // ไม่แสดงรายละเอียดข้อผิดพลาดภายในระบบต่อผู้ใช้
            error_log($e->getMessage());
            $err = 'บันทึกไม่สำเร็จ กรุณาตรวจสอบฐานข้อมูล';
        }
    }
}

// กำหนดชื่อหน้าเว็บ
$title = 'อัปเดตสถานะ';
require 'partials/header.php';
?>

<!-- ส่วนหัวหน้าจัดการสถานะ -->
<div class="heading">
    <div>
        <small class="gold">TECHNICIAN · UPDATE</small>
        <h1>อัปเดตสถานะการซ่อม</h1>
        <p class="muted">
            งาน #<?= (int) $id ?> · <?= e($t['title']) ?>
        </p>
    </div>

    <a class="outline" href="dashboard.php">
        ← กลับหน้าหลัก
    </a>
</div>

<!-- กล่องแบบฟอร์มอัปเดตสถานะ -->
<section class="card formcard">

    <div class="form-intro">
        <div class="form-icon">🔧</div>
        <div>
            <h2>จัดการงานซ่อม</h2>
            <p class="muted">
                อัปเดตความคืบหน้าและบันทึกผลการตรวจสอบ
            </p>
        </div>
    </div>

    <!-- แสดงข้อความผิดพลาด -->
    <?php if ($err): ?>
        <div class="alert error">
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <!-- แบบฟอร์มส่งสถานะใหม่ -->
    <form method="post" class="tech-form">

        <label>
            <span>สถานะใหม่ <b class="required">*</b></span>

            <select name="status" required>
                <?php if ($t['status'] === 'assigned'): ?>
                    <option value="in_progress">
                        รับงานและเริ่มซ่อม
                    </option>
                <?php else: ?>
                    <option value="in_progress">
                        กำลังซ่อมต่อ
                    </option>
                    <option value="resolved">
                        ซ่อมเสร็จแล้ว
                    </option>
                <?php endif; ?>
            </select>
        </label>

        <label>
            <span>บันทึกการซ่อม / ผลการตรวจสอบ
                <b class="required">*</b>
                <small class="muted">
                    (จำเป็นเมื่อเลือกซ่อมเสร็จแล้ว)
                </small>
            </span>

            <textarea
                name="repair_note"
                rows="6"
                placeholder="ตัวอย่าง: ตรวจสอบสายไฟและเปลี่ยนอะไหล่ที่ชำรุด ผลการทดสอบใช้งานปกติ"
            ><?= e($t['repair_note'] ?? '') ?></textarea>
        </label>

        <!-- ปุ่มบันทึกและกลับหน้าหลัก -->
        <div class="actions">
            <button type="submit">
                บันทึกสถานะ →
            </button>

            <a class="outline" href="dashboard.php">
                ยกเลิก
            </a>
        </div>

    </form>
</section>

<?php
// เรียกส่วนท้ายเว็บไซต์
require 'partials/footer.php';
?>
