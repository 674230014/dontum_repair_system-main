<?php
require 'config.php';
$accounts=[
['ผู้ดูแลระบบ','admin@dontum.local','admin'],
['ช่างซ่อมคนที่ 1','tech1@dontum.local','technician'],
['ช่างซ่อมคนที่ 2','tech2@dontum.local','technician'],
['ช่างซ่อมคนที่ 3','tech3@dontum.local','technician'],
['ผู้ใช้ทดลอง','user@dontum.local','user']];
foreach($accounts as [$name,$email,$role]){
 $q=$pdo->prepare('SELECT id FROM users WHERE email=?');$q->execute([$email]);
 if(!$q->fetch()){$ins=$pdo->prepare('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)');$ins->execute([$name,$email,password_hash('password123',PASSWORD_DEFAULT),$role]);}
}
echo 'สร้างบัญชีทดลองเรียบร้อยแล้ว ลบไฟล์ seed.php หลังใช้งาน';
