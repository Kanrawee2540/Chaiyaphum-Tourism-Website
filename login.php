<?php
require_once 'db.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, email, password, role, notification, notification_read FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Debug: แสดงค่า session หลังจากตั้งค่า
            error_log("Debug - Login successful. Session: " . print_r($_SESSION, true));
    
            // Redirect ตามบทบาทของผู้ใช้
            if (in_array($user['role'], ['admin', 'supper_admin', 'user'])) {
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin_home.php");
                        exit();
                    case 'supper_admin':
                        header("Location: supper_admin_home.php");
                        exit();
                    case 'user':
                        header("Location: user_home.php");
                        exit();
                }
            } else {
                // กรณีบทบาทไม่ถูกต้อง
                $_SESSION['error'] = "ไม่มีสิทธิ์เข้าใช้งาน";
                header("Location: login_form.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $_SESSION['error'] = "ไม่พบชื่อผู้ใช้หรืออีเมลนี้ในระบบ";
    }
    header("Location: login_form.php");
    exit();
}
?>