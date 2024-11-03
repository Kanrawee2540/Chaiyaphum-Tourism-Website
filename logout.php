<?php
require_once 'db.php';
session_start();
// ล้างข้อมูลทั้งหมดใน session
$_SESSION = array();

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// Redirect ไปยังหน้าหลักหรือหน้าล็อกอิน
header("Location: index.php");
exit();
?>