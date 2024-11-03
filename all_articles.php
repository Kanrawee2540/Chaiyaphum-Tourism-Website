<?php
require_once 'db.php';
session_start();
require_once 'thai_date_functions.php';
// จำนวนบทความต่อหน้า
$articles_per_page = 9;

// หน้าปัจจุบัน
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// หมวดหมู่ที่เลือก (ถ้ามี)
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : null;

// คำค้นหา (ถ้ามี)
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// กำหนดค่าเริ่มต้นให้กับ $param_types และ $param_values
$param_types = '';
$param_values = array();

// คำสั่ง SQL สำหรับดึงบทความ
$sql = "SELECT a.id, a.title, a.content, a.created_at, a.image_urls, a.visibility,u.profile_picture, u.pen_name, u.username, c.name AS category_name 
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        JOIN categories c ON a.category_id = c.id 
        WHERE 1=1";  // เริ่มต้นด้วยเงื่อนไขที่เป็นจริงเสมอ

//var_dump($selected_category);
$where_conditions = array();
$visibility_conditions = array("a.visibility = 'public'");

if (isset($_SESSION['user_id'])) {
    $visibility_conditions[] = "(a.visibility = 'user' AND (a.user_id = ? OR ? IN ('admin', 'supper_admin')))";
    $param_types .= 'is';
    $param_values[] = $_SESSION['user_id'];
    $param_values[] = $_SESSION['role'];
}

$where_conditions[] = "(" . implode(" OR ", $visibility_conditions) . ")";

// เพิ่มเงื่อนไขหมวดหมู่ถ้ามีการเลือก
if ($selected_category !== null) {
    $where_conditions[] = "c.id = ?";
    $param_types .= 'i';
    $param_values[] = $selected_category;
}

// เพิ่มเงื่อนไขการค้นหา
if ($search_query) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR u.username LIKE ?)";
    $param_types .= 'sss';
    $search_param = "%$search_query%";
    $param_values[] = $search_param;
    $param_values[] = $search_param;
    $param_values[] = $search_param;
}

// รวมเงื่อนไขทั้งหมดเข้าด้วยกัน
if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY a.created_at DESC";
// คำสั่ง SQL สำหรับนับจำนวนบทความทั้งหมด
$count_sql = "SELECT COUNT(*) as total FROM ($sql) AS subquery";

// เตรียมและ execute คำสั่ง SQL สำหรับนับจำนวนบทความ
$stmt = $conn->prepare($count_sql);
if (!empty($param_types)) {
    $stmt->bind_param($param_types, ...$param_values);
}
$stmt->execute();
$result = $stmt->get_result();
$total_articles = $result->fetch_assoc()['total'];

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_articles / $articles_per_page);

// คำนวณ OFFSET สำหรับ SQL
$offset = ($current_page - 1) * $articles_per_page;

// เพิ่ม LIMIT และ OFFSET ให้กับคำสั่ง SQL หลัก
$sql .= " LIMIT ? OFFSET ?";

// ก่อน execute SQL, ให้แสดง SQL query และ parameters เพื่อการ debug
/*echo "Debug: SQL Query: " . $sql . "<br>";
echo "Debug: Param Types: " . $param_types . "ii<br>";
echo "Debug: Param Values: " . implode(", ", array_merge($param_values, [$articles_per_page, $offset])) . "<br>";*/

// เตรียมและ execute คำสั่ง SQL หลัก
$stmt = $conn->prepare($sql);
$param_types .= 'ii';
$param_values[] = $articles_per_page;
$param_values[] = $offset;
$stmt->bind_param($param_types, ...$param_values);
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ดึงรายการหมวดหมู่ทั้งหมด
$category_sql = "SELECT * FROM categories";
$categories = $conn->query($category_sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความทั้งหมด - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .article-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8 max-w-full">
        <h1 class="text-4xl font-bold mb-8 text-center">บทความทั้งหมด</h1>

          <!-- ช่องค้นหา -->
          <div class="mb-8">
            <form action="all_articles.php" method="GET" class="flex items-center">
            <input type="text" name="search" placeholder="ค้นหาบทความ, ผู้เขียน" value="<?php echo htmlspecialchars($search_query); ?>"
            class="flex-grow p-2 border rounded-l focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-r hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </form>
        </div>

        <!-- หมวดหมู่ -->
        <div class="mb-8">
    <h2 class="text-2xl font-bold mb-4">หมวดหมู่</h2>
    <div class="flex flex-wrap gap-2">
        <a href="all_articles.php" class="px-4 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 <?php echo $selected_category === null ? 'bg-blue-700' : ''; ?>">ทั้งหมด</a>
        <?php foreach ($categories as $category): ?>
            <a href="all_articles.php?category=<?php echo $category['id']; ?>" 
               class="px-4 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 <?php echo $selected_category === (int)$category['id'] ? 'bg-blue-700' : ''; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

    

        <!-- บทความ -->
        <div class="article-grid">
            <?php foreach ($articles as $article): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                    <?php
                        $image_url = !empty($article['image_urls']) 
                            ? 'uploads/' . explode(',', $article['image_urls'])[0] 
                            : 'https://via.placeholder.com/400x300?text=Travel+Blog';
                    ?>
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>" 
                         class="w-full h-48 object-cover">
                    <div class="p-6 flex-grow">
                        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($article['title']); ?></h2>
                        <p class="text-gray-600 text-sm mb-4">
                        <div class="text-gray-600 text-sm mb-4 flex flex-col">
            <span class="mb-1"><i class="fas fa-user mr-2"></i><?php echo htmlspecialchars($article['pen_name']); ?></span>
            <span class="mb-1"><i class="fas fa-folder mr-2"></i><?php echo htmlspecialchars($article['category_name']); ?></span>
            <?php if ($article['visibility'] == 'public'): ?>
            <span><i class="fas fa-globe mr-1"></i> สาธารณะ</span>
            <?php else: ?>
            <span><i class="fas fa-user-lock mr-1"></i> เฉพาะสมาชิก</span>
            <?php endif; ?>
        </div>
                        </p>
                        <p class="text-gray-700 mb-4">
    <?php
    $plainText = strip_tags($article['content']);
    echo htmlspecialchars(mb_substr($plainText, 0, 100)) . '...';
    ?>
</p>                    </div>
                    <div class="p-6 pt-0">
                        <a href="article_page.php?id=<?php echo $article['id']; ?>" class="inline-block w-full text-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">อ่านต่อ</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center my-4">
            หน้า <?php echo convertToThaiNumerals($current_page); ?> จาก <?php echo convertToThaiNumerals($total_pages); ?>
        </div>
        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $selected_category ? '&category='.$selected_category : ''; ?><?php echo $search_query ? '&search='.urlencode($search_query) : ''; ?>" 
                class="mx-1 px-3 py-2 bg-blue-500 text-white rounded <?php echo $i === $current_page ? 'bg-blue-700' : ''; ?>">
            <?php echo convertToThaiNumerals($i); ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
</body>
</html>