
<?php
// เรียกใช้ไฟล์ตั้งค่าและเชื่อมต่อฐานข้อมูล
require 'config.php';

// หากเข้าสู่ระบบแล้ว ให้ไปยังหน้าหลักทันที
if (me()) {
    go('dashboard.php');
}

// ตัวแปรเก็บข้อความแจ้งเตือนเมื่อเข้าสู่ระบบไม่สำเร็จ
$err = '';

// ตรวจสอบเมื่อผู้ใช้ส่งแบบฟอร์มเข้าสู่ระบบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ค้นหาบัญชีจากอีเมลและตรวจสอบว่าบัญชียังใช้งานอยู่
    $q = $pdo->prepare(
        'SELECT * FROM users WHERE email = ? AND active = 1'
    );

    $q->execute([
        trim($_POST['email'] ?? '')
    ]);

    // รับข้อมูลบัญชีผู้ใช้จากฐานข้อมูล
    $a = $q->fetch();

    // ตรวจสอบบัญชีและรหัสผ่าน
    if (
        $a &&
        password_verify(
            $_POST['password'] ?? '',
            $a['password_hash']
        )
    ) {
        // สร้าง Session ID ใหม่หลังเข้าสู่ระบบสำเร็จ
        session_regenerate_id(true);

        // เก็บข้อมูลผู้ใช้ที่จำเป็นไว้ใน Session
        $_SESSION['user'] = [
            'id'    => $a['id'],
            'name'  => $a['name'],
            'email' => $a['email'],
            'role'  => $a['role']
        ];

        // เปลี่ยนไปยังหน้าหลักตามระบบเดิม
        go('dashboard.php');
    }

    // แสดงข้อความเมื่ออีเมลหรือรหัสผ่านไม่ถูกต้อง
    $err = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
}

// กำหนดชื่อหน้าเว็บไซต์
$title = 'เข้าสู่ระบบ';

// เรียกส่วนหัวเว็บไซต์
require 'partials/header.php';
?>

<!-- ส่วนหลักของหน้าเข้าสู่ระบบ -->
<section class="login">

    <!-- ฝั่งแนะนำระบบ -->
    <div class="hero">

        <small>LOCAL SERVICE · DON TUM</small>

        <h1>
            แจ้งซ่อมง่าย<br>
            <em>ติดตามงานได้</em>
        </h1>

        <p>
            ระบบแจ้งซ่อมเครื่องใช้ไฟฟ้าในเขตอำเภอดอนตูม
            เชื่อมต่อผู้ใช้ ผู้ดูแลระบบ และทีมช่าง
        </p>

        <!-- แสดงข้อมูลสรุปของระบบ -->
        <div class="stats">
            <span><b>03</b> ทีมช่าง</span>
            <span><b>24/7</b> แจ้งงานได้</span>
            <span><b>LIVE</b> ติดตามสถานะ</span>
        </div>

    </div>

    <!-- ฝั่งแบบฟอร์มเข้าสู่ระบบ -->
    <div class="card">

        <div class="mark">↗</div>

        <small class="gold">WELCOME BACK</small>

        <h2>เข้าสู่ระบบ</h2>

        <p class="muted">
            กรอกบัญชีเพื่อเข้าใช้งานระบบ
        </p>

        <!-- แสดงข้อความผิดพลาดเมื่อเข้าสู่ระบบไม่สำเร็จ -->
        <?php if ($err): ?>
            <div class="alert error">
                <?= e($err) ?>
            </div>
        <?php endif; ?>

        <!-- แบบฟอร์มส่งอีเมลและรหัสผ่าน -->
        <form method="post">

            <label>
                อีเมล
                <input
                    name="email"
                    type="email"
                    required
                    placeholder="name@example.com"
                    autocomplete="username"
                >
            </label>

            <label>
                รหัสผ่าน
                <input
                    name="password"
                    type="password"
                    required
                    placeholder="กรอกรหัสผ่าน"
                    autocomplete="current-password"
                >
            </label>

            <!-- ปุ่มเข้าสู่ระบบ -->
            <button type="submit">
                เข้าสู่ระบบ →
            </button>

        </form>

        <!-- แสดงบัญชีสำหรับทดลองระบบ -->
        <div class="demos">

            <b>
                บัญชีทดลอง (รหัสผ่านทุกบัญชี: password123)
            </b>

            <?php
            // รายชื่อบทบาทและอีเมลบัญชีทดลอง
            $demoAccounts = [
                'Admin' => 'admin@dontum.local',
                'ช่าง 1' => 'tech1@dontum.local',
                'ช่าง 2' => 'tech2@dontum.local',
                'ช่าง 3' => 'tech3@dontum.local',
                'ผู้ใช้' => 'user@dontum.local'
            ];

            // วนลูปแสดงบัญชีทดลองแต่ละบทบาท
            foreach ($demoAccounts as $name => $email):
            ?>
                <p>
                    <span><?= e($name) ?></span>
                    <code><?= e($email) ?></code>
                </p>
            <?php endforeach; ?>

        </div>

    </div>

</section>

<?php
// เรียกส่วนท้ายเว็บไซต์
require 'partials/footer.php';
?>
