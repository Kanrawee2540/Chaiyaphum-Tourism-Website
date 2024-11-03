<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'supper_admin')) {
    header("Location: login.php");
    exit();
}

// จัดการการลบความคิดเห็น
if (isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "ลบความคิดเห็นสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบความคิดเห็น";
    }
    header("Location: manage_comment.php");
    exit();
}

// ดึงข้อมูลความคิดเห็นทั้งหมด
$stmt = $conn->prepare("
    SELECT c.*, a.title AS article_title, u.pen_name 
    FROM comments c
    JOIN articles a ON c.article_id = a.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการความคิดเห็น - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">จัดการความคิดเห็น</h1>
        <button onclick="history.back()" class="mb-4 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i>กลับ
        </button>

        <?php
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

        <div class="bg-white shadow-md rounded my-6">
            <table class="text-left w-full border-collapse">
                <thead>
                    <tr>
                    <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ชื่อนามปากกา</th>                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">บทความ</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ความคิดเห็น</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">วันที่แสดงความคิดเห็น</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
    <?php foreach ($comments as $comment): ?>
    <tr class="hover:bg-grey-lighter">
        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($comment['pen_name']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light">
            <a href="article_page.php?id=<?php echo $comment['article_id']; ?>" class="text-blue-500 hover:underline">
                <?php echo htmlspecialchars($comment['article_title']); ?>
            </a>
        </td>
        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($comment['content']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light"><?php echo formatThaiDateTime($comment['created_at']); ?></td>
        <td class="py-4 px-6 border-b border-grey-light">
            <button onclick="showDeleteConfirmation(<?php echo $comment['id']; ?>)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-trash"></i> ลบ
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </table>
        </div>
    </div>

    <!-- Modal for delete confirmation -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        ยืนยันการลบความคิดเห็น
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้? การกระทำนี้ไม่สามารถย้อนกลับได้
                        </p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="delete_comment" value="1">
                        <input type="hidden" name="comment_id" id="commentIdToDelete">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            ลบ
                        </button>
                    </form>
                    <button type="button" onclick="hideDeleteConfirmation()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDeleteConfirmation(commentId) {
            document.getElementById('commentIdToDelete').value = commentId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function hideDeleteConfirmation() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
    </script>

    <?php require_once 'footer.php'; ?>
</body>
</html>