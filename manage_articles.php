<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// จำนวนบทความต่อหน้า
$articles_per_page = 10;
echo "<!-- จำนวนบทความต่อหน้า: " . convertToThaiNumerals($articles_per_page) . " -->";

// หน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// คำค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';

// คำสั่ง SQL สำหรับนับจำนวนบทความของสมาชิก
$count_sql = "SELECT COUNT(*) as total FROM articles WHERE user_id = ? AND title LIKE ?";
$search_param = "%$search%";

$stmt = $conn->prepare($count_sql);
$stmt->bind_param("is", $user_id, $search_param);
$stmt->execute();
$total_articles = $stmt->get_result()->fetch_assoc()['total'];

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_articles / $articles_per_page);

// คำนวณ OFFSET สำหรับ SQL
$offset = ($current_page - 1) * $articles_per_page;

// คำสั่ง SQL สำหรับดึงบทความของสมาชิก
$sql = "SELECT a.*, c.name AS category_name 
        FROM articles a 
        JOIN categories c ON a.category_id = c.id 
        WHERE a.user_id = ? AND a.title LIKE ? 
        ORDER BY a.created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isii", $user_id, $search_param, $articles_per_page, $offset);
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function getNotifications($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ฟังก์ชันลบบทความ
if (isset($_POST['delete_article'])) {
    $article_id = $_POST['article_id'];
    $delete_sql = "DELETE FROM articles WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $article_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "ลบบทความสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบบทความ";
    }
    header("Location: manage_articles.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความของฉัน - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php
        require_once 'menu_selector.php'; 
    ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">บทความของฉัน</h1>
        <div id="notificationArea" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" style="display: none;">
    <p class="font-bold">การแจ้งเตือน</p>
    <ul id="notificationList"></ul>
</div>
        <?php
        // แสดงข้อความแจ้งเตือน
        if (isset($_SESSION['success'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<span class="block sm:inline">' . $_SESSION['success'] . '</span>';
            echo '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<span class="block sm:inline">' . $_SESSION['error'] . '</span>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <div class="mb-4 flex justify-between">
            <a href="add_article.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                เพิ่มบทความใหม่
            </a>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'supper_admin')): ?>
                <a href="manage_member_articles.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    จัดการบทความของสมาชิก
                </a>
            <?php endif; ?>
        </div>

        <form action="" method="GET" class="mb-4">
            <div class="flex">
                <input type="text" name="search" placeholder="ค้นหาบทความ" value="<?php echo htmlspecialchars($search); ?>" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2">
                    ค้นหา
                </button>
            </div>
        </form>

        <div class="bg-white shadow-md rounded my-6">
            <table class="text-left w-full border-collapse">
            <thead>
    <tr>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">หัวข้อ</th>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">หมวดหมู่</th>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">วันที่สร้าง</th>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">เวลา</th>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">สถานะ</th>
        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">การจัดการ</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($articles as $article): ?>
    <tr class="hover:bg-grey-lighter">
        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($article['title']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($article['category_name']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light"><?php echo formatThaiDate($article['created_at']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light">
    <?php 
    $time = date('H:i', strtotime($article['created_at']));
    echo convertToThaiNumerals($time); 
    ?>
</td>
        <td class="py-4 px-6 border-b border-grey-light">
            <?php echo $article['visibility'] == 'public' ? 'สาธารณะ' : 'เฉพาะสมาชิก'; ?>
        </td>
                        <td class="py-4 px-6 border-b border-grey-light">
                            <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                            <form method="POST" class="inline">
                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                <button type="submit" name="delete_article" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทความนี้?');">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center my-4">
    หน้า <?php echo convertToThaiNumerals($current_page); ?> 
    จาก <?php echo convertToThaiNumerals($total_pages); ?> 
    (บทความทั้งหมด <?php echo convertToThaiNumerals($total_articles); ?> บทความ)
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
    </div>
    <script>
function fetchNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notificationArea = document.getElementById('notificationArea');
            const notificationList = document.getElementById('notificationList');
            notificationList.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(notification => {
                    const li = document.createElement('li');
                    li.className = 'mb-2 p-2 bg-yellow-200 rounded';
                    li.setAttribute('data-notification-id', notification.id);
                    li.innerHTML = `
                        <p>${notification.message}</p>
                        <p class="text-sm text-gray-600">${notification.created_at}</p>
                        <button onclick="markAsRead(${notification.id})" class="mt-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded">
                            ตกลง
                        </button>
                    `;
                    notificationList.appendChild(li);
                });
                notificationArea.style.display = 'block';
            } else {
                notificationArea.style.display = 'none';
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function markAsRead(notificationId) {
    const formData = new FormData();
    formData.append('mark_as_read', '1');
    formData.append('notification_id', notificationId);

    fetch('get_notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // ลบการแจ้งเตือนออกจาก UI ทันที
            removeNotificationFromUI(notificationId);
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

function removeNotificationFromUI(notificationId) {
    const notification = document.querySelector(`li[data-notification-id="${notificationId}"]`);
    if (notification) {
        notification.remove();
    }
    // ตรวจสอบว่ายังมีการแจ้งเตือนเหลืออยู่หรือไม่
    const notificationList = document.getElementById('notificationList');
    if (notificationList.children.length === 0) {
        document.getElementById('notificationArea').style.display = 'none';
    }
}

// เรียกใช้ฟังก์ชันครั้งแรกเมื่อโหลดหน้า
fetchNotifications();
    </script>
    <br>
    <!-- Footer -->
    <?php require_once 'footer.php'; ?>
</body>
</html>