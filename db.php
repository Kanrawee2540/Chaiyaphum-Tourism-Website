<?php
// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost"; // โฮสต์ของฐานข้อมูล (ปกติคือ localhost)
$username = "root"; // ชื่อผู้ใช้ฐานข้อมูล
$password = ""; // รหัสผ่านฐานข้อมูล
$dbname = "travel_blog"; // ชื่อฐานข้อมูล

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// หากไม่มีข้อผิดพลาด การเชื่อมต่อจะสำเร็จและพร้อมใช้งาน
// echo "เชื่อมต่อสำเร็จ";
?>

