<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
if (isset($_GET['date'])) {
    echo formatThaiDate($_GET['date']);
}

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id); // Bind the user ID
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    echo "No user data found.";
}

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, role, created_at, pen_name, first_name, last_name, birth_date, gender, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
// ตรวจสอบว่ามีข้อมูลผู้ใช้หรือไม่
if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}

// ประมวลผลการอัปเดตข้อมูล
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fields_to_update = array();
    $params = array();
    $types = "";

    // ตรวจสอบและเพิ่มฟิลด์ที่มีการเปลี่ยนแปลง
    if (!empty($_POST['email']) && $_POST['email'] !== $user_data['email']) {
        $fields_to_update[] = "email = ?";
        $params[] = $_POST['email'];
        $types .= "s";
    }

    if (!empty($_POST['pen_name']) && $_POST['pen_name'] !== $user_data['pen_name']) {
        $fields_to_update[] = "pen_name = ?";
        $params[] = $_POST['pen_name'];
        $types .= "s";
    }

    if (!empty($_POST['first_name']) && $_POST['first_name'] !== $user_data['first_name']) {
        $fields_to_update[] = "first_name = ?";
        $params[] = $_POST['first_name'];
        $types .= "s";
    }

    if (!empty($_POST['last_name']) && $_POST['last_name'] !== $user_data['last_name']) {
        $fields_to_update[] = "last_name = ?";
        $params[] = $_POST['last_name'];
        $types .= "s";
    }

    if (!empty($_POST['gender']) && $_POST['gender'] !== $user_data['gender']) {
        $fields_to_update[] = "gender = ?";
        $params[] = $_POST['gender'];
        $types .= "s";
    }

    // ตรวจสอบและอัปเดตวันเกิด
    if (!empty($_POST['birth_day']) && !empty($_POST['birth_month']) && !empty($_POST['birth_year'])) {
        $new_birth_date = sprintf('%04d-%02d-%02d', $_POST['birth_year'], $_POST['birth_month'], $_POST['birth_day']);
        if ($new_birth_date !== $user_data['birth_date']) {
            if (checkdate($_POST['birth_month'], $_POST['birth_day'], $_POST['birth_year'])) {
                $fields_to_update[] = "birth_date = ?";
                $params[] = $new_birth_date;
                $types .= "s";
            } else {
                $_SESSION['error'] = "วันเกิดไม่ถูกต้อง";
            }
        }
    }

    // ตรวจสอบรหัสผ่านใหม่
    if (!empty($_POST['new_password'])) {
        $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $fields_to_update[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    // จัดการอัปโหลดรูปโปรไฟล์ (ถ้ามี)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        // โค้ดจัดการอัปโหลดรูปภาพ (คงเดิม)
        // ...
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $fields_to_update[] = "profile_picture = ?";
            $params[] = $upload_path;
            $types .= "s";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปโหลดรูปโปรไฟล์";
        }
    }

    // ดำเนินการอัปเดตถ้ามีการเปลี่ยนแปลง
    if (!empty($fields_to_update) && !isset($_SESSION['error'])) {
        $update_sql = "UPDATE users SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตข้อมูลสำเร็จ";
            // อัปเดตข้อมูลในตัวแปร $user_data
            foreach ($_POST as $key => $value) {
                if (isset($user_data[$key]) && $value !== "") {
                    $user_data[$key] = $value;
                }
            }
            if (isset($upload_path)) {
                $user_data['profile_picture'] = $upload_path;
            }
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
        }
    } elseif (empty($fields_to_update) && !isset($_SESSION['error'])) {
        $_SESSION['info'] = "ไม่มีข้อมูลที่ต้องอัปเดต";
    }
}

// นับจำนวนบทความที่เขียน
$stmt = $conn->prepare("SELECT COUNT(*) as article_count FROM articles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$article_count = $stmt->get_result()->fetch_assoc()['article_count'];

// นับจำนวนความคิดเห็นที่ได้รับ
$stmt = $conn->prepare("SELECT COUNT(*) as comment_count FROM comments c JOIN articles a ON c.article_id = a.id WHERE a.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$comment_count = $stmt->get_result()->fetch_assoc()['comment_count'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">โปรไฟล์ของคุณ</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>


        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 flex flex-col my-2">
            <form action="profile.php" method="POST">
                <div class="-mx-3 md:flex mb-6">
                <div class="md:w-1/2 px-3 mb-6 md:mb-0">
    <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="username">
        ชื่อผู้ใช้
    </label>
    <input class="appearance-none block w-full bg-gray-100 text-gray-700 border border-gray-300 rounded py-3 px-4 mb-1 leading-tight focus:outline-none cursor-not-allowed" 
           id="username" 
           name="username" 
           type="text" 
           value="<?php echo htmlspecialchars($user_data['username']); ?>" 
           readonly 
           disabled>
    <p class="text-gray-600 text-xs italic">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</p>
</div>
                    <div class="md:w-1/2 px-3">
                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="email">
                            อีเมล
                        </label>
                        <input class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" id="email" name="email" type="email" name="email" value="<?php echo $user_data['email']; ?>">
                        </div>
                </div>
                <div class="-mx-3 md:flex mb-6">
                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="pen_name">
                            ชื่อนามปากกา
                        </label>
                        <input class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" id="pen_name" name="pen_name" type="text" name="pen_name" value="<?php echo $user_data['pen_name']; ?>">
                        </div>
                    <div class="md:w-1/2 px-3">
                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="new_password">
                            รหัสผ่านใหม่ (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)
                        </label>
                        <input class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" id="new_password" name="new_password" type="password">
                    </div>
                </div>
                <div class="-mx-3 md:flex mb-6">
                    <div class="md:w-1/2 px-3 mb-6 md:mb-0">
                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="first_name">
                            ชื่อจริง
                        </label>
                        <input class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" id="first_name" name="first_name" type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>">
                        </div>
                    <div class="md:w-1/2 px-3">
                        <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="last_name">
                            นามสกุล
                        </label>
                        <input class="appearance-none block w-full bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" id="last_name" name="last_name" type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>">
</div>
                </div>
                <div class="-mx-3 md:flex mb-6">
                <div class="md:w-1/2 px-3 mb-6 md:mb-0">
    <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="birth_date">
        วันเกิด
    </label>
    <div class="flex">
    <?php
$birth_day = $birth_month = $birth_year = '';
if (!empty($user_data['birth_date'])) {
    $birth_date = new DateTime($user_data['birth_date']);
    $birth_day = $birth_date->format('d');
    $birth_month = $birth_date->format('m');
    $birth_year = (int)$birth_date->format('Y') + 543;
}
?>
        <select name="birth_day" id="birth_day" class="appearance-none block w-1/3 bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4 mr-2" required>
    <option value="">วัน</option>
    <?php
    for ($i = 1; $i <= 31; $i++) {
        $selected = ($birth_day == $i) ? 'selected' : '';
        echo "<option value=\"$i\" $selected>" . convertToThaiNumerals($i) . "</option>";
    }
    ?>
</select>
<select name="birth_month" id="birth_month" class="appearance-none block w-1/3 bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4 mr-2" required>
    <option value="">เดือน</option>
    <?php
    for ($i = 1; $i <= 12; $i++) {
        $selected = ($birth_month == $i) ? 'selected' : '';
        echo "<option value=\"$i\" $selected>" . thaiMonth($i) . "</option>";
    }
    ?>
</select>
<select name="birth_year" id="birth_year" class="appearance-none block w-1/3 bg-grey-lighter text-grey-darker border border-grey-lighter rounded py-3 px-4" required>
    <option value="">ปี พ.ศ.</option>
    <?php
    $currentYear = (int)date('Y') + 543;
    for ($i = $currentYear - 100; $i <= $currentYear - 10; $i++) {
        $selected = ($birth_year == $i) ? 'selected' : '';
        echo "<option value=\"" . ($i - 543) . "\" $selected>" . convertToThaiNumerals($i) . "</option>";
    }
    ?>
</select>
    </div>
</div>
<div class="md:w-1/2 px-3">
    <label class="block uppercase tracking-wide text-grey-darker text-xs font-bold mb-2" for="gender">
        เพศ
    </label>
    <select class="block appearance-none w-full bg-grey-lighter border border-grey-lighter text-grey-darker py-3 px-4 pr-8 rounded" id="gender" name="gender" required>
        <option value="">เลือกเพศ</option>
        <option value="male" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'male') ? 'selected' : ''; ?>>ชาย</option>
        <option value="female" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'female') ? 'selected' : ''; ?>>หญิง</option>
        <option value="other" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'other') ? 'selected' : ''; ?>>อื่นๆ</option>
    </select>
</div>
                </div>
                <div class="md:flex md:items-center">
                    <div class="md:w-1/3">
                        <button class="shadow bg-blue-500 hover:bg-blue-600 focus:shadow-outline focus:outline-none text-white font-bold py-2 px-4 rounded" type="submit">
                            บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                    <div class="md:w-2/3"></div>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 flex flex-col my-2">
    <h2 class="text-2xl font-bold mb-4">ข้อมูลบัญชี</h2>
    <p><strong>บทบาท:</strong> <?php echo isset($user_data['role']) ? ucfirst($user_data['role']) : 'N/A'; ?></p>
    <p><strong>วันที่สมัคร:</strong> <?php echo isset($user_data['created_at']) ? formatThaiDate($user_data['created_at']) : 'N/A'; ?></p>
    <p><strong>วันเกิด:</strong> <?php echo isset($user_data['birth_date']) ? formatThaiDate($user_data['birth_date']) : 'N/A'; ?></p>
    <p><strong>จำนวนบทความที่เขียน:</strong> <?php echo convertToThaiNumerals($article_count); ?> บทความ</p>
    <p><strong>จำนวนความคิดเห็นที่ได้รับ:</strong> <?php echo convertToThaiNumerals($comment_count); ?> ความคิดเห็น</p>
</div>
    </div>
        <!-- Footer -->
        <?php
    require_once 'footer.php';
    ?>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const thaiDateDisplay = birthDateInput.nextElementSibling;

    birthDateInput.addEventListener('change', function() {
        if (this.value) {
            // ส่งคำขอ AJAX เพื่อแปลงวันที่เป็นรูปแบบไทย
            fetch('get_thai_date.php?date=' + this.value)
                .then(response => response.text())
                .then(thaiDate => {
                    thaiDateDisplay.textContent = thaiDate;
                });
        } else {
            thaiDateDisplay.textContent = '';
        }
    });
});
</script>
</body>
</html>