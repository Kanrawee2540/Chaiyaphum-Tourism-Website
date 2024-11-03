<?php
require_once 'db.php';

// เพิ่มโค้ดนี้ด้านบนของไฟล์ user_menu.php
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$full_name = $user['first_name'] . ' ' . $user['last_name'];

// ฟังก์ชันสำหรับตรวจสอบว่ามีความคิดเห็นที่ยังไม่ได้อ่านหรือไม่
function hasUnreadComments($conn, $user_id) {
    $stmt = $conn->prepare("SELECT 1 FROM comments c
                            JOIN articles a ON c.article_id = a.id
                            WHERE a.user_id = ? AND c.is_read = 0 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// ฟังก์ชันสำหรับอัพเดตสถานะการอ่านความคิดเห็นทั้งหมดของผู้ใช้
function markAllCommentsAsRead($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE comments c
                            JOIN articles a ON c.article_id = a.id
                            SET c.is_read = 1
                            WHERE a.user_id = ? AND c.is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// ตรวจสอบว่ามี user_id ใน session หรือไม่
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $hasUnreadComments = hasUnreadComments($conn, $user_id);

    // ตรวจสอบว่ามีการกดที่เมนูข้อความแสดงความคิดเห็นหรือไม่
    if (isset($_GET['comments_viewed'])) {
        markAllCommentsAsRead($conn, $user_id);
        $hasUnreadComments = false; // อัพเดตสถานะทันที
    }
} else {
    $hasUnreadComments = false; // ถ้าไม่มี user_id ให้ถือว่าไม่มีความคิดเห็นที่ยังไม่ได้อ่าน
}
?>
<nav class="bg-blue-700 p-4 shadow-lg">
    <div class="container mx-auto">
        <div class="flex items-center justify-between flex-wrap">
            <div class="flex items-center flex-shrink-0 text-white mr-6">
                <span class="font-bold text-2xl tracking-tight">Admin</span>
            </div>
            <div class="block lg:hidden">
                <button class="flex items-center px-3 py-2 border rounded text-white border-white hover:text-blue-200 hover:border-blue-200">
                    <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
                </button>
            </div>
            <div class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
                <div class="text-base lg:flex-grow">
                    <a href="admin_home.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        หน้าหลัก
                    </a>
                    <a href="all_articles.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        บทความทั้งหมด
                    </a>
                    <a href="manage_articles.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        จัดการบทความ
                    </a>
                    <a href="manage_categories.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        จัดการหมวดหมู่
                    </a>
                    <a href="manage_user.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        จัดการผู้ใช้
                    </a>
                    <a href="comment_history.php?comments_viewed=1" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out relative">
                        จัดการข้อความแสดงความคิดเห็น
                        <?php if ($hasUnreadComments): ?>
                            <span class="absolute top-0 right-0 -mt-1 -mr-1 w-3 h-3 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-blue-200 mr-6 transition duration-300 ease-in-out">
                        โปรไฟล์
                    </a>
                </div>
                <div class="flex items-center">
        <i class="fas fa-user mr-2 text-white"></i>
        <span class="text-white text-sm font-semibold mr-4"><?php echo htmlspecialchars($full_name); ?></span>
        <a href="logout.php" class="inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-blue-700 hover:bg-white mt-4 lg:mt-0 transition duration-300 ease-in-out">ออกจากระบบ</a>
    </div>
            </div>
        </div>
    </div>
</nav>
<script>
// JavaScript ยังคงเหมือนเดิม
document.querySelector('a[href^="comment_history.php"]').addEventListener('click', function(e) {
    e.preventDefault();
    this.querySelector('span')?.remove(); // ลบวงกลมสีแดง
    fetch(this.href) // ส่งคำขอไปยัง comment_history.php เพื่ออัพเดตสถานะ
        .then(() => window.location.href = 'comment_history.php'); // ไปยังหน้าประวัติความคิดเห็น
});
</script>