<?php
require_once 'db.php';
session_start();
// ตรวจสอบว่ามีการส่ง ID บทความมาหรือไม่
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
require_once 'thai_date_functions.php';

$article_id = $_GET['id'];

// ดึงข้อมูลบทความจากฐานข้อมูล
$stmt = $conn->prepare("SELECT a.*, u.username,u.pen_name, c.name AS category_name 
                        FROM articles a 
                        JOIN users u ON a.user_id = u.id 
                        JOIN categories c ON a.category_id = c.id 
                        WHERE a.id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$article = $result->fetch_assoc();

// ฟังก์ชันสำหรับเพิ่มความคิดเห็น
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "กรุณาเข้าสู่ระบบเพื่อแสดงความคิดเห็น";
    } else {
        $comment_content = trim($_POST['comment_content']);
        if (!empty($comment_content)) {
            $stmt = $conn->prepare("INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $article_id, $_SESSION['user_id'], $comment_content);
            if ($stmt->execute()) {
                $_SESSION['success'] = "เพิ่มความคิดเห็นสำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มความคิดเห็น";
            }
        } else {
            $_SESSION['error'] = "กรุณากรอกความคิดเห็น";
        }
    }
    header("Location: article_page.php?id=" . $article_id);
    exit();
}

// ฟังก์ชันสำหรับลบความคิดเห็น
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] == 'admin')) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "ลบความคิดเห็นสำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบความคิดเห็น";
        }
    } else {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์ลบความคิดเห็นนี้";
    }
    header("Location: article_page.php?id=" . $article_id);
    exit();
}

// ฟังก์ชันสำหรับแก้ไขความคิดเห็น
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_comment'])) {
    $comment_id = $_POST['comment_id'];
    $new_content = trim($_POST['edit_comment_content']);
    $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();

    if ($comment && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] == 'admin')) {
        if (!empty($new_content)) {
            $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
            $stmt->bind_param("si", $new_content, $comment_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "แก้ไขความคิดเห็นสำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขความคิดเห็น";
            }
        } else {
            $_SESSION['error'] = "กรุณากรอกเนื้อหาความคิดเห็น";
        }
    } else {
        $_SESSION['error'] = "คุณไม่มีสิทธิ์แก้ไขความคิดเห็นนี้";
    }
    header("Location: article_page.php?id=" . $article_id);
    exit();
}

// ดึงความคิดเห็นทั้งหมดของบทความ
$stmt = $conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.article_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$image_urls = explode(',', $article['image_urls']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
         .slideshow-container {
            position: relative;
            max-width: 800px;
            margin: auto;
            aspect-ratio: 16 / 9; /* กำหนดอัตราส่วนของ container */
            overflow: hidden; /* ป้องกันไม่ให้รูปภาพล้นออกนอก container */
        }
        .mySlides {
            display: none;
            height: 100%; /* ให้ slide มีความสูงเท่ากับ container */
        }
        .mySlides img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* ปรับขนาดรูปภาพให้พอดีกับ container โดยไม่ตัดส่วนใดออก */
            object-position: center; /* จัดตำแหน่งรูปภาพให้อยู่ตรงกลาง */
        }
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 16px;
            margin-top: -22px;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
            background-color: rgba(0,0,0,0.8);
        }
        .next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }
        .prev:hover, .next:hover {
            background-color: rgba(0,0,0,0.9);
        }
        .article-content {
        font-size: 20px; /* เพิ่มขนาดตัวอักษรเป็น 20px */
        line-height: 1.6;
        }
        .article-content h1 { font-size: 2.5em; margin-top: 0.67em; margin-bottom: 0.67em; }
        .article-content h2 { font-size: 2em; margin-top: 0.83em; margin-bottom: 0.83em; }
        .article-content h3 { font-size: 1.5em; margin-top: 1em; margin-bottom: 1em; }
        .article-content h4 { font-size: 1em; margin-top: 1.33em; margin-bottom: 1.33em; }
        .article-content h5 { font-size: 0.83em; margin-top: 1.67em; margin-bottom: 1.67em; }
        .article-content h6 { font-size: 0.67em; margin-top: 2.33em; margin-bottom: 2.33em; }
        .article-content p { margin-top: 1em; margin-bottom: 1em; }
        .article-content ul, .article-content ol { padding-left: 2em; margin-top: 1em; margin-bottom: 1em; }
        .article-content blockquote { 
            border-left: 4px solid #ccc; 
            margin-left: 0;
            padding-left: 1em;
            color: #666;
        }
        .article-content * {
        font-size: inherit !important;
    }
    .article-content p, .article-content li, .article-content div {
        font-size: 20px !important;
    }
    .article-content h1 { font-size: 32px !important; }
    .article-content h2 { font-size: 28px !important; }
    .article-content h3 { font-size: 24px !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <?php
        require_once 'menu_selector.php'; 
    ?>

    <div class="container mx-auto px-4 py-8">
        <button onclick="history.back()" class="mb-4 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i>กลับ
        </button>

       <!-- แสดงข้อความแจ้งเตือน -->
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

        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="slideshow-container">
                <?php foreach ($image_urls as $index => $image_url): ?>
                    <div class="mySlides">
                        <img src="uploads/<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($article['title']) . ' - Image ' . ($index + 1); ?>">
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($image_urls) > 1): ?>
                    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                    <a class="next" onclick="plusSlides(1)">&#10095;</a>
                <?php endif; ?>
            </div>
            <div class="p-6">
    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($article['title']); ?></h1>
    <div class="flex items-center text-sm text-gray-600 mb-4">
        <span class="mr-4"><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($article['pen_name']); ?></span>
        <span class="mr-4"><i class="fas fa-folder mr-2"></i><?php echo htmlspecialchars($article['category_name']); ?></span>
        <span><i class="fas fa-calendar-alt mr-2"></i><?php echo formatThaiDate($article['created_at']); ?></span>
    </div>
    <div class="prose max-w-none article-content">
        <?php echo html_entity_decode($article['content']); ?>
    </div>
</div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold mb-4">ความคิดเห็น (<?php echo count($comments); ?>)</h2>
            
            <!-- Comment Form -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="article_page.php?id=<?php echo $article_id; ?>" method="POST" class="mb-8">
                    <textarea name="comment_content" rows="4" class="w-full p-2 border rounded" placeholder="แสดงความคิดเห็นของคุณ" required></textarea>
                    <button type="submit" name="add_comment" class="mt-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        ส่งความคิดเห็น
                    </button>
                </form>
            <?php else: ?>
                <p class="mb-4">กรุณา <a href="login_form.php" class="text-blue-500 hover:text-blue-700">เข้าสู่ระบบ</a> เพื่อแสดงความคิดเห็น</p>
            <?php endif; ?>

            <!-- List of comments -->
           <!-- List of comments -->
<?php foreach ($comments as $comment): ?>
    <div class="bg-white p-4 rounded-lg shadow mb-4">
        <div class="flex justify-between items-center mb-2">
            <span class="font-bold"><?php echo htmlspecialchars($comment['username']); ?></span>
            <span class="text-sm text-gray-500"><?php echo formatThaiDate($comment['created_at']); ?></span>        </div>
        <p id="comment-content-<?php echo $comment['id']; ?>" class="mb-2"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] == 'admin')): ?>
            <div class="flex space-x-2">
                <button onclick="showEditForm(<?php echo $comment['id']; ?>)" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-edit"></i> แก้ไข
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <button type="submit" name="delete_comment" class="text-red-500 hover:text-red-700" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้?');">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                </form>
            </div>
            <form id="edit-form-<?php echo $comment['id']; ?>" method="POST" class="mt-2 hidden">
                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                <textarea name="edit_comment_content" rows="3" class="w-full p-2 border rounded"><?php echo htmlspecialchars($comment['content']); ?></textarea>
                <div class="flex justify-end mt-2">
                    <button type="button" onclick="hideEditForm(<?php echo $comment['id']; ?>)" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-2 rounded mr-2">
                        ยกเลิก
                    </button>
                    <button type="submit" name="edit_comment" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded">
                        บันทึก
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<script>
function showEditForm(commentId) {
    document.getElementById('comment-content-' + commentId).style.display = 'none';
    document.getElementById('edit-form-' + commentId).style.display = 'block';
}

function hideEditForm(commentId) {
    document.getElementById('comment-content-' + commentId).style.display = 'block';
    document.getElementById('edit-form-' + commentId).style.display = 'none';
}

let slideIndex = 1;
    showSlides(slideIndex);

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        if (n > slides.length) {slideIndex = 1}
        if (n < 1) {slideIndex = slides.length}
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        slides[slideIndex-1].style.display = "block";
    }
</script>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
</body>
</html>