
<?php

// เรียกใช้การตั้งค่าและการเชื่อมต่อฐานข้อมูล
require 'config.php';

// ตรวจสอบสิทธิ์: เฉพาะผู้ดูแลระบบเท่านั้นที่มอบหมายงานได้
$a = role_required('admin');

// รับหมายเลขงานจาก URL และแปลงเป็นจำนวนเต็ม
$id = (int) ($_GET['id'] ?? 0);


// =====================================================
// ค้นหางานที่ยังรอมอบหมาย
// =====================================================
$q = $pdo->prepare("
    SELECT *
    FROM tickets
    WHERE id = ?
      AND status = 'open'
");

$q->execute([$id]);

// เก็บข้อมูลรายการแจ้งซ่อม
$t = $q->fetch();

// หากไม่พบงานหรือสถานะไม่ใช่ open ให้กลับหน้าหลัก
if (!$t) {
    flash('ไม่พบงานที่รอมอบหมาย', 'error');
    go('dashboard.php');
}


// =====================================================
// ดึงรายชื่อช่างที่เปิดใช้งาน
// =====================================================
$techs = $pdo->query("
    SELECT id, name, email
    FROM users
    WHERE role = 'technician'
      AND active = 1
")->fetchAll();

// ตัวแปรเก็บข้อความแจ้งข้อผิดพลาด
$err = '';


// =====================================================
// ประมวลผลเมื่อ Admin ส่งแบบฟอร์ม
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // รับรหัสช่างที่เลือก
    $tid = (int) ($_POST['technician_id'] ?? 0);

    // ตรวจสอบว่ารหัสช่างอยู่ในรายชื่อช่างที่ใช้งานได้
    $valid = false;

    foreach ($techs as $x) {
        if ($x['id'] == $tid) {
            $valid = true;
        }
    }

    // หากไม่ได้เลือกช่างที่ถูกต้อง ให้แสดงข้อผิดพลาด
    if (!$valid) {

        $err = 'กรุณาเลือกช่าง';

    } else {

        // เริ่ม Transaction เพื่อบันทึกข้อมูลที่เกี่ยวข้อง
        $pdo->beginTransaction();

        try {

            // กำหนดช่างผู้รับผิดชอบและเปลี่ยนสถานะเป็น assigned
            $pdo->prepare("
                UPDATE tickets
                SET technician_id = ?,
                    status = 'assigned',
                    admin_note = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $tid,
                trim($_POST['note'] ?? ''),
                $id
            ]);

            // บันทึกประวัติการเปลี่ยนสถานะงาน
            $pdo->prepare("
                INSERT INTO status_logs (
                    ticket_id,
                    changed_by,
                    from_status,
                    to_status,
                    note
                )
                VALUES (?, ?, 'open', 'assigned', ?)
            ")->execute([
                $id,
                $a['id'],
                'Admin มอบหมายงานให้ช่าง'
            ]);

            // ยืนยันการบันทึกข้อมูลทั้งหมด
            $pdo->commit();

            // แจ้งผลสำเร็จและไปยังหน้ารายละเอียดงาน
            flash('มอบหมายงานเรียบร้อย');
            go('ticket_view.php?id=' . $id);

        } catch (Throwable $e) {

            // ยกเลิกการบันทึกเมื่อเกิดข้อผิดพลาด
            $pdo->rollBack();

            // แสดงข้อความแจ้งเตือนแก่ผู้ใช้
            $err = 'บันทึกไม่สำเร็จ';
        }
    }
}


// =====================================================
// แสดงหน้าแบบฟอร์มมอบหมายงาน
// =====================================================
$title = 'มอบหมายงาน';
require 'partials/header.php';

?>

<!-- ส่วนหัวของหน้า -->
<div class="heading">

    <div>
        <small class="gold">ADMIN · ASSIGN JOB</small>

        <h1>มอบหมายงานให้ช่าง</h1>

        <p class="muted">
            งาน #<?= $id ?> · <?= e($t['title']) ?>
        </p>
    </div>

    <!-- ปุ่มกลับหน้าหลัก -->
    <a class="outline" href="dashboard.php">
        ← กลับ
    </a>

</div>


<!-- =====================================================
     แบบฟอร์มมอบหมายงาน
====================================================== -->
<section class="card formcard">

    <!-- แสดงข้อความเมื่อเกิดข้อผิดพลาด -->
    <?php if ($err): ?>
        <div class="alert error">
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <!-- รายละเอียดอาการที่แจ้งซ่อม -->
    <p>
        <?= e($t['description']) ?>
    </p>

    <form method="post">

        <!-- เลือกช่างผู้รับผิดชอบ -->
        <label>
            เลือกช่างผู้รับผิดชอบ *

            <select name="technician_id" required>
                <option value="">
                    -- เลือกช่าง --
                </option>

                <?php foreach ($techs as $x): ?>
                    <option value="<?= $x['id'] ?>">
                        <?= e($x['name']) ?>
                        (<?= e($x['email']) ?>)
                    </option>
                <?php endforeach; ?>

            </select>
        </label>


        <!-- หมายเหตุจากผู้ดูแลระบบถึงช่าง -->
        <label>
            หมายเหตุถึงช่าง

            <textarea name="note" rows="3"></textarea>
        </label>


        <!-- ปุ่มยืนยันการมอบหมายงาน -->
        <button type="submit">
            ยืนยันมอบหมายงาน →
        </button>

    </form>

</section>


<?php
// เรียกส่วนท้ายของเว็บไซต์
require 'partials/footer.php';
?>
