
<?php
// เรียกใช้การตั้งค่าและเริ่มต้น Session
require 'config.php';

// ล้างข้อมูล Session ของผู้ใช้ที่เข้าสู่ระบบ
$_SESSION = [];

// ทำลาย Session เพื่อออกจากระบบ
session_destroy();

// เปลี่ยนเส้นทางกลับไปยังหน้าเข้าสู่ระบบ
header('Location: index.php');

// หยุดการทำงานของ PHP หลังเปลี่ยนหน้า
exit;
?>
