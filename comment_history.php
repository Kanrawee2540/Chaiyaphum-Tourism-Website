<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลคอมเม้นท์ของบทความที่ผู้ใช้เขียน
$stmt = $conn->prepare("
    SELECT c.*, a.title AS article_title, u.pen_name 
    FROM comments c
    JOIN articles a ON c.article_id = a.id
    JOIN users u ON c.user_id = u.id
    WHERE a.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);

// แยกความคิดเห็นที่ยังไม่ได้อ่าน (สมมติว่าความคิดเห็นที่สร้างภายใน 24 ชั่วโมงที่ผ่านมาถือว่าเป็นการแจ้งเตือนใหม่)
$notifications = array_filter($comments, function($comment) {
    return strtotime($comment['created_at']) > strtotime('-24 hours');
});

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติความคิดเห็นและการแจ้งเตือน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php
        require_once 'menu_selector.php'; 
    ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">ประวัติความคิดเห็นและการแจ้งเตือน</h1>

        <!-- แสดงการแจ้งเตือน (ความคิดเห็นใหม่) -->
        <?php if (!empty($notifications)): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
        <p class="font-bold">ความคิดเห็นใหม่ (<?php echo convertToThaiNumerals(count($notifications)); ?> รายการ)</p>
        <?php foreach ($notifications as $notification): ?>
    <p class="mt-2">
        <?php echo htmlspecialchars($notification['pen_name']); ?> แสดงความคิดเห็นในบทความ 
        <a href="article_page.php?id=<?php echo $notification['article_id']; ?>" class="text-blue-700 hover:underline">
            <?php echo htmlspecialchars($notification['article_title']); ?>
        </a>
    </p>
<?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- แสดงประวัติความคิดเห็น -->
        <div class="mb-4 flex justify-between">
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'supper_admin')): ?>
                <a href="manage_comment.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    ข้อความแสดงความคิดเห็นทั้งหมด
                </a>
            <?php endif; ?>
        </div>
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <h2 class="text-2xl font-bold mb-4">ความคิดเห็นล่าสุดในบทความของคุณ (<?php echo convertToThaiNumerals(count($comments)); ?> รายการ)</h2>
            <?php if (empty($comments)): ?>
                <p class="text-gray-600">ยังไม่มีความคิดเห็นในบทความของคุณ</p>
            <?php else: ?>
                <?php foreach ($comments as $index => $comment): ?>
<div class="border-b border-gray-200 py-4">
    <p class="text-sm text-gray-600">
        #<?php echo convertToThaiNumerals($index + 1); ?> 
        <?php echo htmlspecialchars($comment['pen_name']); ?> แสดงความคิดเห็นในบทความ 
        <a href="article_page.php?id=<?php echo $comment['article_id']; ?>" class="text-blue-500 hover:underline">
            <?php echo htmlspecialchars($comment['article_title']); ?>
        </a>
    </p>
    <p class="mt-2"><?php echo htmlspecialchars($comment['content']); ?></p>
    <p class="text-sm text-gray-500 mt-1">
        <?php echo formatThaiDateTime($comment['created_at']); ?>
    </p>
</div>
<?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>

    <script>
        // ในอนาคต คุณสามารถเพิ่ม JavaScript สำหรับการอัปเดตแบบ real-time ได้ที่นี่
    </script>
</body>
</html>