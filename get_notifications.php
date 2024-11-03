<?php
require_once 'db.php';
require_once 'thai_date_functions.php'; // เพิ่มการเรียกใช้ไฟล์ที่มีฟังก์ชัน convertToThaiNumerals
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// ฟังก์ชันดึงการแจ้งเตือน
function getNotifications($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ตรวจสอบว่ามีการส่ง POST request เพื่อทำเครื่องหมายว่าอ่านแล้ว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    exit();
}

// ดึงการแจ้งเตือน
$notifications = getNotifications($user_id);

// แปลงวันที่เป็นรูปแบบที่อ่านง่ายและใช้เลขไทย
foreach ($notifications as &$notification) {
    $date = date('d/m/Y H:i', strtotime($notification['created_at']));
    $thaiDate = preg_replace_callback('/(\d+)/', function($matches) {
        return convertToThaiNumerals($matches[1]);
    }, $date);
    $notification['created_at'] = $thaiDate;
}

// ส่งการแจ้งเตือนเป็น JSON
header('Content-Type: application/json');
echo json_encode($notifications);
?>