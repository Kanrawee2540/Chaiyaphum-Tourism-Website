<?php
require_once 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $c_password = $_POST['c_password'];
    $pen_name = $_POST['pen_name'];
    $first_name = $_POST['first_name'] ?? null;
    $last_name = $_POST['last_name'] ?? null;
    $birth_date = $_POST['birth_year'] && $_POST['birth_month'] && $_POST['birth_day'] ? 
        date('Y-m-d', strtotime($_POST['birth_year'].'-'.$_POST['birth_month'].'-'.$_POST['birth_day'])) : null;
    $gender = $_POST['gender'] ?? null;

    // ตรวจสอบว่ารหัสผ่านตรงกัน
    if ($password !== $c_password) {
        $_SESSION['error'] = "รหัสผ่านไม่ตรงกัน กรุณาลองอีกครั้ง";
        header("Location: signup_form.php");
        exit();
    }

    // ตรวจสอบว่า username หรือ email ซ้ำหรือไม่
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว กรุณาใช้ชื่อผู้ใช้หรืออีเมลอื่น";
        header("Location: signup_form.php");
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // จัดการอัปโหลดรูปโปรไฟล์
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "ไฟล์รูปโปรไฟล์ต้องเป็นประเภท JPG, PNG หรือ GIF เท่านั้น";
            header("Location: signup_form.php");
            exit();
        }

        if ($file_size > $max_file_size) {
            $_SESSION['error'] = "ขนาดไฟล์รูปโปรไฟล์ต้องไม่เกิน 5MB";
            header("Location: signup_form.php");
            exit();
        }

        $upload_dir = 'uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $profile_picture = $upload_path;
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปโปรไฟล์";
            header("Location: signup_form.php");
            exit();
        }
    }

    // บันทึกข้อมูลลงในฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, pen_name, first_name, last_name, birth_date, gender, profile_picture, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param("sssssssss", $username, $email, $hashed_password, $pen_name, $first_name, $last_name, $birth_date, $gender, $profile_picture);

    if ($stmt->execute()) {
        $_SESSION['success'] = "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ";
        header("Location: login_form.php");
        exit();
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง";
        header("Location: signup_form.php");
        exit();
    }
}
?>

