<?php
declare(strict_types=1);
session_start();
const DB_HOST='127.0.0.1'; const DB_NAME='dontum_repair'; const DB_USER='root'; const DB_PASS='';
try {$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}
catch(PDOException $e){exit('เชื่อมต่อฐานข้อมูลไม่ได้ ตรวจ config.php และ import database.sql ก่อน');}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function me(){return $_SESSION['user']??null;}
function login_required(){if(!me()){header('Location:index.php');exit;}return me();}
function role_required(...$roles){$u=login_required();if(!in_array($u['role'],$roles,true)){http_response_code(403);exit('ไม่มีสิทธิ์เข้าถึง');}return $u;}
function flash($s,$type='success'){$_SESSION['flash']=[$s,$type];}
function status_th($s){return ['open'=>'รอมอบหมายงาน','assigned'=>'มอบหมายแล้ว','in_progress'=>'กำลังซ่อม','resolved'=>'ซ่อมเสร็จแล้ว','closed'=>'ปิดงาน'][$s]??$s;}
function status_css($s){return ['open'=>'open','assigned'=>'assigned','in_progress'=>'progress','resolved'=>'resolved','closed'=>'closed'][$s]??'';}
function go($url){header('Location:'.$url);exit;}
