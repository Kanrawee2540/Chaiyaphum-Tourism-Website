<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
// ปรับปรุง SQL query
$sql = "SELECT a.*, u.username, u.profile_picture, u.pen_name, c.name AS category_name 
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        JOIN categories c ON a.category_id = c.id 
        WHERE a.visibility = 'public'";

if (isset($_SESSION['user_id'])) {
    $sql .= " OR (a.visibility = 'user' AND (a.user_id = ? OR ? IN ('admin', 'supper_admin')))";
}

$sql .= " ORDER BY a.created_at DESC LIMIT 6";

$stmt = $conn->prepare($sql);

if (isset($_SESSION['user_id'])) {
    $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['role']);
}

$stmt->execute();
$result = $stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blog - หน้าหลัก</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
    .hero-section {
        height: 400px; /* ปรับความสูงตามที่คุณต้องการ */
        position: relative;
        overflow: hidden;
    }
    .slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        transition: opacity 1s ease-in-out;
    }
    .slide:not(:first-child) {
        opacity: 0;
    }
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .content {
        text-align: center;
        color: white;
    }
    .nav-button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: rgba(255, 255, 255, 0.5);
        color: black;
        padding: 10px;
        text-decoration: none;
        font-size: 18px;
    }
    #prevSlide { left: 10px; }
    #nextSlide { right: 10px; }
</style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <?php
        require_once 'menu_selector.php'; 
    ?>
    <!-- Hero Section -->
    <div class="hero-section">
    <div id="slideshow">
        <div class="slide" style="background-image: url('background/ทุ่งกระมัง.jpg');"></div>
        <div class="slide" style="background-image: url('background/น้ำตกตาดโตน_3.jpg');"></div>
        <div class="slide" style="background-image: url('background/ปรางกู่_2.jpg');"></div>
        <div class="slide" style="background-image: url('background/มอหินขาว_1.jpg');"></div>
    </div>
    <div class="overlay">
        <div class="content">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-2">สัมผัสมนต์เสน่ห์แห่งชัยภูมิ</h2>
            <h3 class="text-xl md:text-2xl mb-4 md:mb-8">ดินแดนมหัศจรรย์แห่งอีสาน</h3>
            <p class="text-base md:text-lg mb-4 md:mb-8">แบ่งปันประสบการณ์ท่องเที่ยว ค้นพบสถานที่ซ่อนเร้นในชัยภูมิ</p>
            <a href="login_form.php" class="bg-white text-black font-bold py-2 px-4 rounded-full hover:bg-gray-200 transition duration-300">
                เข้าสู่ระบบเพื่อเขียนบทความ
            </a>
        </div>
    </div>
    <button id="prevSlide" class="nav-button" aria-label="Previous slide">&lt;</button>
    <button id="nextSlide" class="nav-button" aria-label="Next slide">&gt;</button>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;

    function showSlide(n) {
        slides[currentSlide].style.opacity = 0;
        currentSlide = (n + totalSlides) % totalSlides;
        slides[currentSlide].style.opacity = 1;
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    document.getElementById('nextSlide').addEventListener('click', (e) => {
        e.preventDefault();
        nextSlide();
    });
    document.getElementById('prevSlide').addEventListener('click', (e) => {
        e.preventDefault();
        prevSlide();
    });

    // Auto-advance slides
    setInterval(nextSlide, 5000);
</script>
    <!-- Featured Articles -->
    <section class="container mx-auto px-6 p-10">
    <h2 class="text-4xl font-bold text-center text-gray-800 mb-8">
        บทความล่าสุด
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($articles as $article): ?>
        <div class="flex flex-col bg-white rounded-lg shadow-lg overflow-hidden">
            <a href="article_page.php?id=<?php echo $article['id']; ?>" class="flex-shrink-0">
                <?php
                    $image_url = !empty($article['image_urls']) ? 'uploads/' . explode(',', $article['image_urls'])[0] : 'https://via.placeholder.com/400x300?text=Travel+Blog';
                ?>
                <img class="h-48 w-full object-cover" src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
            </a>
            <div class="flex-1 p-6 flex flex-col justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-indigo-600">
                        <?php echo htmlspecialchars($article['category_name']); ?>
                    </p>
                    <a href="article_page.php?id=<?php echo $article['id']; ?>" class="block mt-2">
                        <p class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($article['title']); ?></p>
                        <p class="mt-3 text-base text-gray-500">
                            <?php
                            $plainText = strip_tags($article['content']);
                            echo htmlspecialchars(mb_substr($plainText, 0, 100)) . '...';
                            ?>
                        </p>
                    </a>
                </div>
                <div class="flex items-center">
    <div class="flex-shrink-0">
        <i class="fas fa-user text-gray-500 text-2xl mr-2"></i>
    </div>
    <div class="ml-3">
        <p class="text-sm font-medium text-gray-900">
            <?php echo htmlspecialchars($article['pen_name'] ?? $article['username']); ?>
        </p>
        <div class="flex space-x-1 text-sm text-gray-500">
            <time datetime="<?php echo htmlspecialchars($article['created_at']); ?>">
                <?php echo formatThaiDate($article['created_at']); ?>
            </time>
            <span aria-hidden="true">&middot;</span>
            <span><?php echo $article['visibility'] == 'public' ? 'สาธารณะ' : 'เฉพาะสมาชิก'; ?></span>
        </div>
    </div>
</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

    <!-- Call to Action -->
    <section class="bg-blue-700 py-8">
        <div class="container mx-auto px-2 pt-4 pb-12 text-gray-100">
            <h2 class="w-full my-2 text-5xl font-bold leading-tight text-center">
                เริ่มต้นการเดินทางของคุณวันนี้
            </h2>
            <div class="w-full mb-4">
                <div class="h-1 mx-auto bg-white w-1/6 opacity-25 my-0 py-0 rounded-t"></div>
            </div>
            <h3 class="my-4 text-3xl leading-tight text-center">
            ร่วมเป็นส่วนหนึ่งในการเล่าเรื่องราวมหัศจรรย์แห่งชัยภูมิ
แบ่งปันประสบการณ์ ภาพถ่ายสุดประทับใจ และเส้นทางลับสู่สถานที่ซ่อนเร้น
สร้างแรงบันดาลใจให้ผู้อื่นได้สัมผัสความงามของดินแดนอีสานแห่งนี้
สมัครสมาชิกวันนี้ และร่วมสร้างชุมชนนักเดินทางผู้หลงใหลในเสน่ห์ของชัยภูมิ
            </h3>
            <div class="flex justify-center mt-8">
            <a href="signup_form.php" class="mx-auto lg:mx-0">
                <button class="hover:underline bg-white text-gray-800 font-bold rounded-full my-6 py-4 px-8 shadow-lg">
                    สมัครสมาชิก
                </button>
            </a>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php
    require_once 'footer.php';
    ?>

    <script>
        // Toggle mobile menu
        const menuButton = document.querySelector('nav button');
        const menu = document.querySelector('nav div.w-full');
        menuButton.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>