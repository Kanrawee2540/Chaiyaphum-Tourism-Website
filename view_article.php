<?php
require_once 'db.php';
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วและมีสิทธิ์ในการดูบทความ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ตรวจสอบว่ามีการส่ง ID บทความมาหรือไม่
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_member_articles.php");
    exit();
}

$article_id = $_GET['id'];

// ดึงข้อมูลบทความจากฐานข้อมูล
$sql = "SELECT a.*, c.name AS category_name, u.username 
        FROM articles a 
        JOIN categories c ON a.category_id = c.id 
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_member_articles.php");
    exit();
}

$article = $result->fetch_assoc();
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
            aspect-ratio: 16 / 9;
            overflow: hidden;
        }
        .mySlides {
            display: none;
            height: 100%;
        }
        .mySlides img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
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
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <button onclick="history.back()" class="mb-4 bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i>กลับ
        </button>

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
                    <span class="mr-4"><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($article['username']); ?></span>
                    <span class="mr-4"><i class="fas fa-folder mr-2"></i><?php echo htmlspecialchars($article['category_name']); ?></span>
                    <span><i class="fas fa-calendar-alt mr-2"></i><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                </div>
                <p class="mb-4"><strong>สถานะ:</strong> <?php echo $article['visibility'] == 'public' ? 'สาธารณะ' : 'เฉพาะสมาชิก'; ?></p>
                <div class="prose max-w-none">
                    <?php echo htmlspecialchars_decode($article['content']); ?>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="manage_member_articles.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                <i class="fas fa-list mr-2"></i>กลับไปหน้าจัดการบทความ
            </a>
            <form method="POST" action="manage_member_articles.php" class="inline">
                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                <button type="submit" name="delete_article" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทความนี้?');">
                    <i class="fas fa-trash mr-2"></i>ลบบทความ
                </button>
            </form>
        </div>
    </div>

    <script>
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
</body>
</html>