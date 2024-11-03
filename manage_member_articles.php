<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
error_log("Debug - Session: " . print_r($_SESSION, true));
require_once 'thai_date_functions.php';
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วและมีสิทธิ์ในการจัดการบทความของสมาชิก
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supper_admin')) {
    error_log("Debug - Access denied. Role: " . ($_SESSION['role'] ?? 'Not set'));
    header("Location: login.php");
    exit();
}

// จำนวนบทความต่อหน้า
$articles_per_page = 10;
echo "<!-- จำนวนบทความต่อหน้า: " . convertToThaiNumerals($articles_per_page) . " -->";

// หน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// คำค้นหา
$search = isset($_GET['search']) ? $_GET['search'] : '';

// คำสั่ง SQL สำหรับนับจำนวนบทความทั้งหมด
$count_sql = "SELECT COUNT(*) as total FROM articles a 
              JOIN categories c ON a.category_id = c.id 
              JOIN users u ON a.user_id = u.id
              WHERE a.title LIKE ? OR u.pen_name LIKE ? OR c.name LIKE ?";
$search_param = "%$search%";

$stmt = $conn->prepare($count_sql);
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$total_articles = $stmt->get_result()->fetch_assoc()['total'];

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_articles / $articles_per_page);

// คำนวณ OFFSET สำหรับ SQL
$offset = ($current_page - 1) * $articles_per_page;

// คำสั่ง SQL สำหรับดึงบทความทั้งหมด
$sql = "SELECT a.*, c.name AS category_name, u.pen_name 
        FROM articles a 
        JOIN categories c ON a.category_id = c.id 
        JOIN users u ON a.user_id = u.id
        WHERE a.title LIKE ? OR u.pen_name LIKE ? OR c.name LIKE ?
        ORDER BY a.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssii", $search_param, $search_param, $search_param, $articles_per_page, $offset);
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ฟังก์ชันลบบทความและส่งการแจ้งเตือน
if (isset($_POST['delete_article'])) {
    $article_id = $_POST['article_id'];
    $reason = $_POST['delete_reason'];
    
    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // ดึงข้อมูลบทความและผู้เขียน
        $stmt = $conn->prepare("SELECT a.title, a.user_id, u.pen_name FROM articles a JOIN users u ON a.user_id = u.id WHERE a.id = ?");        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $article_info = $result->fetch_assoc();

        if (!$article_info) {
            throw new Exception("ไม่พบข้อมูลบทความ");
        }

        // ลบความคิดเห็นที่เกี่ยวข้องกับบทความ
        $delete_comments_sql = "DELETE FROM comments WHERE article_id = ?";
        $stmt = $conn->prepare($delete_comments_sql);
        $stmt->bind_param("i", $article_id);
        $stmt->execute();

        // ลบบทความ
        $delete_article_sql = "DELETE FROM articles WHERE id = ?";
        $stmt = $conn->prepare($delete_article_sql);
        $stmt->bind_param("i", $article_id);
        $stmt->execute();

        // เพิ่มการแจ้งเตือน
        $notification_message = "บทความของคุณเรื่อง '{$article_info['title']}' ถูกลบด้วยเหตุผล: $reason";
        $insert_notification_sql = "INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)";
        $stmt = $conn->prepare($insert_notification_sql);
        $stmt->bind_param("is", $article_info['user_id'], $notification_message);
        $stmt->execute();

        function createNotification($userId, $message) {
            global $conn;
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $message);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => "ลบบทความและความคิดเห็นที่เกี่ยวข้องสำเร็จ และส่งการแจ้งเตือนแล้ว"
        ];
        
    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ rollback การเปลี่ยนแปลงทั้งหมด
        $conn->rollback();
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => "เกิดข้อผิดพลาดในการลบบทความ: " . $e->getMessage()
        ];
    }

    // รีไดเร็กต์กลับไปยังหน้าเดิมพร้อมกับคงค่าการค้นหาและหน้าปัจจุบัน
    header("Location: manage_member_articles.php?page=$current_page&search=" . urlencode($search));
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบทความของสมาชิก - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        textarea#deleteReason {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background-color: #fff;
            resize: vertical;
            min-height: 100px;
        }

        textarea#deleteReason:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        textarea#deleteReason {
            caret-color: auto;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">จัดการบทความของสมาชิก</h1>
        <button onclick="history.back()" class="mb-4 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i>กลับ
        </button>
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

        <form action="" method="GET" class="mb-4">
        <div class="flex">
        <input type="text" name="search" placeholder="ค้นหาบทความ, ชื่อนามปากกา หรือหมวดหมู่" value="<?php echo htmlspecialchars($search); ?>" 
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
    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ชื่อนามปากกา</th>
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
        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($article['pen_name']); ?></td>
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
    <a href="view_article.php?id=<?php echo $article['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">
        <i class="fas fa-eye"></i> ดูบทความ
    </a>
    <button type="button" onclick="showDeleteConfirmation(<?php echo $article['id']; ?>, '<?php echo addslashes($article['title']); ?>', '<?php echo addslashes($article['pen_name']); ?>')" class="text-red-600 hover:text-red-900">
    <i class="fas fa-trash"></i> ลบ
</button>
</td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>
 <!-- Modal for delete confirmation -->
<div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-xl leading-6 font-bold text-gray-900 mb-4" id="modal-title">
                    ยืนยันการลบบทความ
                </h3>
                <div class="mt-2 space-y-4">
                    <p class="text-base text-gray-700">คุณแน่ใจหรือไม่ที่จะลบบทความ "<span id="articleTitle" class="font-semibold"></span>"?</p>
                    <p class="text-base text-gray-700">เจ้าของบทความ: <span id="articleAuthor" class="font-semibold"></span></p>
                    <p class="text-base text-gray-700">กรุณาระบุเหตุผลในการลบ:</p>
                    <textarea id="deleteReason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 bg-gray-50" rows="3" placeholder="พิมพ์เหตุผลที่นี่..."></textarea>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="confirmDelete()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    ลบบทความ
                </button>
                <button type="button" onclick="hideDeleteConfirmation()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
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
<script>
let currentArticleId = null;

function showDeleteConfirmation(articleId, articleTitle, articleAuthor) {
    currentArticleId = articleId;
    document.getElementById('articleTitle').textContent = articleTitle;
    document.getElementById('articleAuthor').textContent = articleAuthor;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteReason').focus();
}

function hideDeleteConfirmation() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteReason').value = '';
}

function confirmDelete() {
    const reason = document.getElementById('deleteReason').value;
    if (reason.trim() === '') {
        alert('กรุณาระบุเหตุผลในการลบบทความ');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'manage_member_articles.php';

    const articleIdInput = document.createElement('input');
    articleIdInput.type = 'hidden';
    articleIdInput.name = 'article_id';
    articleIdInput.value = currentArticleId;

    const deleteReasonInput = document.createElement('input');
    deleteReasonInput.type = 'hidden';
    deleteReasonInput.name = 'delete_reason';
    deleteReasonInput.value = reason;

    const deleteArticleInput = document.createElement('input');
    deleteArticleInput.type = 'hidden';
    deleteArticleInput.name = 'delete_article';
    deleteArticleInput.value = '1';

    form.appendChild(articleIdInput);
    form.appendChild(deleteReasonInput);
    form.appendChild(deleteArticleInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
<br>
    <!-- Footer -->
    <?php require_once 'footer.php'; ?>
</body>
</html>