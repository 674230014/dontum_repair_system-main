
<?php

// เรียกใช้การตั้งค่าระบบและการเชื่อมต่อฐานข้อมูล
require 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
$u = login_required();


// =====================================================
// ดึงรายการแจ้งซ่อมตามสิทธิ์ของผู้ใช้งาน
// =====================================================

if ($u['role'] === 'admin') {

    // Admin: ดูรายการแจ้งซ่อมทั้งหมด
    $sql = "
        SELECT
            t.*,
            u.name AS customer,
            tech.name AS techname
        FROM tickets t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN users tech ON tech.id = t.technician_id
        ORDER BY t.created_at DESC
    ";

    $q = $pdo->query($sql);

} elseif ($u['role'] === 'technician') {

    // Technician: ดูเฉพาะงานที่ได้รับมอบหมาย
    $q = $pdo->prepare("
        SELECT
            t.*,
            u.name AS customer,
            tech.name AS techname
        FROM tickets t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN users tech ON tech.id = t.technician_id
        WHERE t.technician_id = ?
        ORDER BY t.created_at DESC
    ");

    $q->execute([$u['id']]);

} else {

    // User: ดูเฉพาะรายการแจ้งซ่อมของตนเอง
    $q = $pdo->prepare("
        SELECT
            t.*,
            u.name AS customer,
            tech.name AS techname
        FROM tickets t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN users tech ON tech.id = t.technician_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");

    $q->execute([$u['id']]);
}

// เก็บผลลัพธ์รายการแจ้งซ่อมทั้งหมดในตัวแปร
$tickets = $q->fetchAll();


// กำหนดชื่อหน้าเว็บและเรียกส่วนหัว
$title = 'หน้าหลัก';
require 'partials/header.php';

?>

<!-- =====================================================
     ส่วนหัวของหน้าหลัก
     แสดงชื่อผู้ใช้และปุ่มแจ้งซ่อมสำหรับ User
====================================================== -->
<div class="heading">

    <div>
        <small class="gold">
            <?= strtoupper($u['role']) ?> WORKSPACE
        </small>

        <h1>
            สวัสดี <?= e($u['name']) ?>
        </h1>

        <p class="muted">
            จัดการและติดตามงานแจ้งซ่อมเครื่องใช้ไฟฟ้า
        </p>
    </div>

    <?php if ($u['role'] === 'user'): ?>
        <!-- แสดงปุ่มแจ้งซ่อมใหม่เฉพาะผู้ใช้ทั่วไป -->
        <a class="button" href="ticket_create.php">
            ＋ แจ้งซ่อมใหม่
        </a>
    <?php endif; ?>

</div>


<!-- =====================================================
     สรุปจำนวนงานตามสถานะ
====================================================== -->
<section class="stats cards">

    <?php
    // รายการสถานะและข้อความภาษาไทย
    $statusLabels = [
        'open'        => 'รอมอบหมายงาน',
        'assigned'    => 'มอบหมายแล้ว',
        'in_progress' => 'กำลังซ่อม',
        'resolved'    => 'ซ่อมเสร็จแล้ว'
    ];

    foreach ($statusLabels as $statusKey => $label):

        // เริ่มนับจำนวนงานของสถานะนี้
        $count = 0;

        // นับจากรายการที่ดึงมาตามสิทธิ์ของผู้ใช้
        foreach ($tickets as $ticket) {
            if ($ticket['status'] === $statusKey) {
                $count++;
            }
        }
    ?>

        <div class="metric">
            <small><?= e($label) ?></small>
            <b><?= $count ?></b>
        </div>

    <?php endforeach; ?>

</section>


<!-- =====================================================
     ตารางรายการแจ้งซ่อม
====================================================== -->
<section class="card wide">

    <h2>
        <?php
        // เปลี่ยนหัวข้อตามบทบาทของผู้ใช้งาน
        if ($u['role'] === 'admin') {
            echo 'รายการแจ้งซ่อมทั้งหมด';
        } elseif ($u['role'] === 'technician') {
            echo 'งานที่ได้รับมอบหมาย';
        } else {
            echo 'ประวัติการแจ้งซ่อม';
        }
        ?>
    </h2>

    <?php if (!$tickets): ?>

        <!-- แสดงข้อความเมื่อไม่มีรายการแจ้งซ่อม -->
        <div class="empty">
            ยังไม่มีรายการแจ้งซ่อม
        </div>

    <?php else: ?>

        <div class="tablewrap">

            <table>

                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>อุปกรณ์ / อาการ</th>

                        <?php if ($u['role'] !== 'user'): ?>
                            <th>ผู้แจ้ง</th>
                        <?php endif; ?>

                        <th>ช่าง</th>
                        <th>สถานะ</th>
                        <th>วันที่แจ้ง</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($tickets as $ticket): ?>

                        <tr>

                            <!-- หมายเลขรายการแจ้งซ่อม -->
                            <td>
                                #<?= $ticket['id'] ?>
                            </td>

                            <!-- ชื่ออุปกรณ์และรายละเอียดอาการ -->
                            <td>
                                <b>
                                    <?= e($ticket['title']) ?>
                                </b>

                                <small>
                                    <?= e(
                                        mb_strimwidth(
                                            $ticket['description'],
                                            0,
                                            55,
                                            '…',
                                            'UTF-8'
                                        )
                                    ) ?>
                                </small>
                            </td>

                            <!-- แสดงชื่อผู้แจ้งสำหรับ Admin และ Technician -->
                            <?php if ($u['role'] !== 'user'): ?>
                                <td>
                                    <?= e($ticket['customer']) ?>
                                </td>
                            <?php endif; ?>

                            <!-- ชื่อช่างที่รับผิดชอบ -->
                            <td>
                                <?= e(
                                    $ticket['techname'] ?? 'ยังไม่มอบหมาย'
                                ) ?>
                            </td>

                            <!-- สถานะงานซ่อม -->
                            <td>
                                <span class="status <?= status_css($ticket['status']) ?>">
                                    <?= status_th($ticket['status']) ?>
                                </span>
                            </td>

                            <!-- วันที่และเวลาที่แจ้งซ่อม -->
                            <td>
                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($ticket['created_at'])
                                ) ?>
                            </td>

                            <!-- ลิงก์เปิดรายละเอียดงาน -->
                            <td>
                                <a href="ticket_view.php?id=<?= $ticket['id'] ?>">
                                    ดูงาน →
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>


<?php
// =====================================================
// แสดงรายชื่อทีมช่างสำหรับ Admin
// =====================================================

if ($u['role'] === 'admin'):

    // ดึงชื่อและอีเมลของผู้ใช้งานที่มีบทบาทเป็นช่าง
    $techs = $pdo->query("
        SELECT name, email
        FROM users
        WHERE role = 'technician'
    ")->fetchAll();

?>

    <section class="card wide">

        <h2>ทีมช่าง 3 คน</h2>

        <div class="team">

            <?php foreach ($techs as $tech): ?>

                <div>
                    <b><?= e($tech['name']) ?></b>
                    <small><?= e($tech['email']) ?></small>
                </div>

            <?php endforeach; ?>

        </div>

    </section>

<?php
endif;

// เรียกส่วนท้ายของเว็บไซต์
require 'partials/footer.php';
?>
