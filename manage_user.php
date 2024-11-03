<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วและเป็น supper_admin หรือ admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'supper_admin' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

// ฟังก์ชันสำหรับลบสมาชิก
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "ลบสมาชิกสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบสมาชิก: " . $conn->error;
    }
    header("Location: manage_user.php");
    exit();
}

// ฟังก์ชันสำหรับเปลี่ยนบทบาท
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['change_role'];
    
    // ตรวจสอบว่า new_role เป็นค่าที่ถูกต้อง
    if ($new_role != 'admin' && $new_role != 'user') {
        $_SESSION['error'] = "บทบาทไม่ถูกต้อง";
        header("Location: manage_user.php");
        exit();
    }
    
    $stmt = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user['role'] != 'supper_admin') {
        $stmt = $conn->prepare("UPDATE users SET role = ?, notification = ?, notification_read = FALSE WHERE id = ?");
        $notification = null;
        
        // สร้างการแจ้งเตือนสำหรับทั้งกรณี user เป็น admin และ admin เป็น user
        if (($user['role'] == 'user' && $new_role == 'admin') || ($user['role'] == 'admin' && $new_role == 'user')) {
            $notification = json_encode([
                'message' => "คุณถูกเปลี่ยนสถานะจาก " . ucfirst($user['role']) . " เป็น " . ucfirst($new_role) . " โดย " . $_SESSION['username'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        $stmt->bind_param("ssi", $new_role, $notification, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "เปลี่ยนบทบาทของ " . $user['username'] . " เป็น " . ucfirst($new_role) . " สำเร็จ";
            if ($notification) {
                $_SESSION['success'] .= " และได้ส่งการแจ้งเตือนแล้ว";
            }
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนบทบาท: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "ไม่สามารถเปลี่ยนบทบาทของ supper_admin ได้";
    }
    header("Location: manage_user.php");
    exit();
}

// ดึงข้อมูลสมาชิกทั้งหมด โดยเรียงลำดับตามบทบาทและรองรับการค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT id, username, first_name, last_name, pen_name, email, role, created_at FROM users";$params = [];

if (!empty($search)) {
    $query .= " WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR pen_name LIKE ? OR role LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
}

$query .= " ORDER BY 
    CASE 
        WHEN role = 'supper_admin' THEN 1
        WHEN role = 'admin' THEN 2
        WHEN role = 'user' THEN 3
        ELSE 4
    END, 
    created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <?php
        require_once 'menu_selector.php'; 
    ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">จัดการสมาชิก</h1>

        <form action="" method="GET" class="mb-8">
    <div class="flex items-center justify-center">
    <input type="text" name="search" placeholder="ค้นหาชื่อผู้ใช้,ชื่อนามปากกา,บทบาท" value="<?php echo htmlspecialchars($search); ?>" class="px-4 py-2 w-64 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-600">        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600">
            <i class="fas fa-search"></i> ค้นหา
        </button>
    </div>
</form>
<?php
// จำนวนสมาชิกต่อหน้า
$users_per_page = 10;

// หน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// คำนวณ OFFSET สำหรับ SQL
$offset = ($current_page - 1) * $users_per_page;

// แก้ไข query เพื่อใช้ LIMIT และ OFFSET
$query .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params)) . "ii";
    $bindParams = array_merge([$types], $params, [$users_per_page, $offset]);
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
} else {
    $stmt->bind_param("ii", $users_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// คำนวณจำนวนสมาชิกทั้งหมด
$count_query = "SELECT COUNT(*) as total FROM users";
if (!empty($search)) {
    $count_query .= " WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR pen_name LIKE ? OR role LIKE ?)";
}
$count_stmt = $conn->prepare($count_query);
if (!empty($search)) {
    $count_stmt->bind_param("sssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
}
$count_stmt->execute();
$total_user = $count_stmt->get_result()->fetch_assoc()['total'];

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_user / $users_per_page);
?>
<?php if (!empty($search)): ?>
            <div class="text-center mb-4">
                <a href="manage_user.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-list"></i> แสดงรายชื่อทั้งหมด
                </a>
            </div>
        <?php endif; ?>

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

        <div class="bg-white shadow-md rounded my-6">
            <table class="text-left w-full border-collapse">
                <thead>
                    <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ชื่อ-นามสกุล</th>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ชื่อนามปากกา</th>
                        
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">อีเมล</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">บทบาท</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">วันที่สมัคร</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($users as $user): ?>
    <tr class="hover:bg-grey-lighter">
        <td class="py-4 px-6 border-b border-grey-light">
            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
        </td>
        <td class="py-4 px-6 border-b border-grey-light">
            <?php echo htmlspecialchars($user['pen_name']); ?>
        </td>
                        
                        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="py-4 px-6 border-b border-grey-light"><?php echo formatThaiDate($user['created_at']); ?></td>
                        <td class="py-4 px-6 border-b border-grey-light">
                            <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'supper_admin'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <button type="submit" name="change_role" value="user" class="text-blue-600 hover:text-blue-900 mr-2">
                                            <i class="fas fa-user"></i> ตั้งเป็น User
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="change_role" value="admin" class="text-blue-600 hover:text-blue-900 mr-2">
                                            <i class="fas fa-user-shield"></i> ตั้งเป็น Admin
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสมาชิกนี้?');">
                                        <i class="fas fa-trash"></i> ลบ
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-500">ไม่สามารถแก้ไขได้</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-center my-4">
    หน้า <?php echo convertToThaiNumerals($current_page); ?> 
    จาก <?php echo convertToThaiNumerals($total_pages); ?> 
    (สมาชิกทั้งหมด <?php echo convertToThaiNumerals($total_user); ?> คน)
</div>
        <!-- Pagination -->
        <div class="flex justify-center mt-8">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
           class="mx-1 px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 <?php echo $i === $current_page ? 'bg-blue-700' : ''; ?>">
            <?php echo convertToThaiNumerals($i); ?>
        </a>
    <?php endfor; ?>
</div>
<br>
    <!-- Footer -->
    <?php require_once 'footer.php'; ?>
</body>
</html>