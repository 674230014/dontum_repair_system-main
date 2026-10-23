
<?php
// เรียกใช้การตั้งค่าระบบและการเชื่อมต่อฐานข้อมูล
require 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
$user = login_required();

// รับเลขที่งานจาก URL เช่น ticket_view.php?id=1
$id = (int) ($_GET['id'] ?? 0);

// ค้นหาข้อมูลงานแจ้งซ่อม พร้อมชื่อผู้แจ้งและชื่อช่าง
$stmt = $pdo->prepare("
    SELECT
        t.*,
        a.name AS customer,
        b.name AS techname
    FROM tickets t
    JOIN users a ON a.id = t.user_id
    LEFT JOIN users b ON b.id = t.technician_id
    WHERE t.id = ?
");

$stmt->execute([$id]);
$ticket = $stmt->fetch();

// ตรวจสอบว่ามีงานแจ้งซ่อมนี้อยู่จริงหรือไม่
if (!$ticket) {
    http_response_code(404);
    exit('ไม่พบงานแจ้งซ่อม');
}

// ตรวจสอบสิทธิ์การดูรายละเอียดงาน
// ผู้ใช้ทั่วไปดูได้เฉพาะงานของตนเอง
// ช่างดูได้เฉพาะงานที่ได้รับมอบหมาย
if (
    ($user['role'] === 'user'
        && (int) $ticket['user_id'] !== (int) $user['id'])
    ||
    ($user['role'] === 'technician'
        && (int) $ticket['technician_id'] !== (int) $user['id'])
) {
    http_response_code(403);
    exit('คุณไม่มีสิทธิ์ดูงานนี้');
}

// ดึงประวัติการเปลี่ยนสถานะของงาน
$stmt = $pdo->prepare("
    SELECT
        l.*,
        u.name AS actor
    FROM status_logs l
    LEFT JOIN users u ON u.id = l.changed_by
    WHERE l.ticket_id = ?
    ORDER BY l.created_at DESC
");

$stmt->execute([$id]);
$logs = $stmt->fetchAll();

// กำหนดชื่อหน้าเว็บ
$title = 'รายละเอียดงาน';

// เรียกส่วนหัวเว็บไซต์
require 'partials/header.php';
?>

<!-- ส่วนหัวรายละเอียดงาน -->
<div class="heading">
    <div>
        <small class="gold">
            REPAIR TICKET #<?= (int) $id ?>
        </small>

        <h1><?= e($ticket['title']) ?></h1>

        <p class="muted">
            แจ้งเมื่อ
            <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
        </p>
    </div>

    <!-- ปุ่มกลับหน้าหลัก -->
    <a class="outline" href="dashboard.php">
        ← กลับหน้าหลัก
    </a>
</div>

<div class="detail">

    <!-- แสดงรายละเอียดงานแจ้งซ่อม -->
    <section class="card wide">

        <div class="row">
            <h2>รายละเอียดการแจ้งซ่อม</h2>

            <!-- แสดงสถานะปัจจุบัน -->
            <span class="status <?= e(status_css($ticket['status'])) ?>">
                <?= e(status_th($ticket['status'])) ?>
            </span>
        </div>

        <!-- แสดงข้อมูลแต่ละรายการ -->
        <dl>
            <?php
            $details = [
                'ประเภท' => $ticket['category'],
                'อาการเสีย' => $ticket['description'],
                'ที่อยู่' => $ticket['location'],
                'เบอร์ติดต่อ' => $ticket['contact_phone'] ?: '-',
                'ผู้แจ้ง' => $ticket['customer'],
                'ช่างผู้รับผิดชอบ' =>
                    $ticket['techname'] ?? 'รอ Admin มอบหมาย',
                'บันทึกจากช่าง' => $ticket['repair_note'] ?? '-'
            ];

            foreach ($details as $label => $value):
            ?>
                <dt><?= e($label) ?></dt>
                <dd><?= nl2br(e($value)) ?></dd>
            <?php endforeach; ?>
        </dl>

        <!-- แสดงรูปภาพที่ผู้ใช้แนบไว้ หากมี -->
        <?php if (!empty($ticket['image_path'])): ?>
            <div class="ticket-image-section">
                <h2>รูปภาพประกอบการแจ้งซ่อม</h2>
                <a href="<?= e($ticket['image_path']) ?>" target="_blank" rel="noopener noreferrer">
                    <img
                        class="ticket-image"
                        src="<?= e($ticket['image_path']) ?>"
                        alt="รูปภาพประกอบงานแจ้งซ่อม #<?= (int) $id ?>"
                        loading="lazy"
                    >
                </a>
                <p class="muted">คลิกที่รูปเพื่อเปิดดูขนาดเต็ม</p>
            </div>
        <?php endif; ?>

        <!-- Admin สามารถมอบหมายงานที่ยังรอมอบหมาย -->
        <?php if (
            $user['role'] === 'admin'
            && $ticket['status'] === 'open'
        ): ?>
            <a class="button" href="admin_assign.php?id=<?= (int) $id ?>">
                มอบหมายงานให้ช่าง →
            </a>
        <?php endif; ?>

        <!-- ช่างสามารถเปิดหน้าอัปเดตสถานะงานของตนเอง -->
        <?php if (
            $user['role'] === 'technician'
            && in_array($ticket['status'], ['assigned', 'in_progress'], true)
        ): ?>
            <a class="button" href="tech_update.php?id=<?= (int) $id ?>">
                อัปเดตสถานะงาน →
            </a>
        <?php endif; ?>

    </section>

    <!-- แสดงประวัติสถานะ -->
    <section class="card">
        <h2>ประวัติสถานะ</h2>

        <?php if (empty($logs)): ?>
            <p class="muted">ยังไม่มีประวัติสถานะ</p>
        <?php else: ?>

            <?php foreach ($logs as $log): ?>
                <div class="log">

                    <b><?= e(status_th($log['to_status'])) ?></b>

                    <p><?= e($log['note'] ?? '-') ?></p>

                    <small>
                        <?= e($log['actor'] ?? 'ระบบ') ?>
                        ·
                        <?= date(
                            'd/m/Y H:i',
                            strtotime($log['created_at'])
                        ) ?>
                    </small>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</div>

<?php
// เรียกส่วนท้ายเว็บไซต์
require 'partials/footer.php';
?>
