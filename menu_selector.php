<?php
if (isset($_SESSION['user_id'])) {
    // ผู้ใช้เข้าสู่ระบบแล้ว
    switch ($_SESSION['role']) {
        case 'admin':
            include 'admin_menu.php';
            break;
        case 'user': 
            include 'user_menu.php';
            break;
        case 'supper_admin':
            include 'supper_admin_menu.php';
            break;
        default:
            // กรณีที่ role ไม่ตรงกับที่กำหนด
            include 'user_menu.php';  // ใช้ member_menu เป็นค่าเริ่มต้น
            break;
    }
} else {
    // ผู้ใช้ยังไม่ได้เข้าสู่ระบบ
    include 'nav.php';
}
?>