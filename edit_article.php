<?php
require_once 'db.php';
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ตรวจสอบว่ามีการส่ง ID บทความมา
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_articles.php");
    exit();
}

$article_id = $_GET['id'];

// ดึงข้อมูลบทความ
$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_articles.php");
    exit();
}

$article = $result->fetch_assoc();

// ดึงข้อมูลหมวดหมู่
$categories = $conn->query("SELECT * FROM categories")->fetch_all(MYSQLI_ASSOC);

// ประมวลผลการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $visibility = $_POST['visibility'];
    $user_id = $_SESSION['user_id'];
    
    // ทำความสะอาดและกรอง HTML ที่อนุญาต
    $allowed_tags = '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote>';
    $content = strip_tags($content, $allowed_tags);

    // เตรียมคำสั่ง SQL สำหรับอัปเดตบทความ
    $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, category_id = ?, visibility = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssissi", $title, $content, $category_id, $visibility, $article_id, $user_id);

    if ($stmt->execute()) {
        // จัดการรูปภาพ
        $current_images = !empty($article['image_urls']) ? explode(',', $article['image_urls']) : [];
        $images_to_keep = isset($_POST['keep_images']) ? $_POST['keep_images'] : [];
        $new_image_urls = [];

        // เก็บรูปภาพที่ต้องการเก็บไว้
        foreach ($current_images as $image) {
            if (in_array($image, $images_to_keep)) {
                $new_image_urls[] = $image;
            } else {
                // ลบไฟล์รูปภาพที่ไม่ต้องการ
                unlink("uploads/" . $image);
            }
        }

        // จัดการอัปโหลดรูปภาพใหม่ (ถ้ามี)
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $target_dir = "uploads/";
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['images']['name'][$key];
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = uniqid() . "." . $file_extension;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $new_image_urls[] = $new_file_name;
                }
            }
        }

        // อัปเดตรายการรูปภาพในฐานข้อมูล
        $image_urls = implode(',', $new_image_urls);
        $conn->query("UPDATE articles SET image_urls = '$image_urls' WHERE id = $article_id");

        $_SESSION['success'] = "อัปเดตบทความสำเร็จ";
        header("Location: manage_articles.php");
        exit();
    } else {
        $error = "เกิดข้อผิดพลาดในการอัปเดตบทความ";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขบทความ - Travel Blog</title>
    <!-- เพิ่ม Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- เพิ่ม Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php
        require_once 'menu_selector.php'; 
    ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">แก้ไขบทความ</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">หัวข้อบทความ</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">หมวดหมู่</label>
                <select id="category_id" name="category_id" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $article['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
    <label for="content" class="block text-gray-700 text-sm font-bold mb-2">เนื้อหาบทความ</label>
    <div id="editor" style="height: 400px;"></div>
    <input type="hidden" name="content" id="content">
</div>

<div class="mb-4">
    <label for="images" class="block text-gray-700 text-sm font-bold mb-2">
        รูปภาพประกอบ (สูงสุด ๑๐ รูป, .jpg, .jpeg, .png, .gif เท่านั้น, ขนาดไม่เกิน ๕ MB ต่อไฟล์)
    </label>
    <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.gif" multiple
           class="hidden" onchange="previewImages(this)">
    <label for="images" class="cursor-pointer bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-block">
        เลือกรูปภาพ
    </label>
    <p class="text-sm text-gray-600 mt-1">
        * อัพโหลดได้สูงสุด ๑๐ ไฟล์ ขนาดรวมไม่เกิน ๕๐ MB
    </p>
    <div id="image-preview" class="mt-4 flex flex-wrap gap-4"></div>
</div>

<script>
function previewImages(input) {
    var preview = document.getElementById('image-preview');
    if (input.files) {
        [].forEach.call(input.files, function(file, index) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var imgContainer = document.createElement("div");
                imgContainer.className = "relative";
                
                var img = document.createElement("img");
                img.src = e.target.result;
                img.className = "w-32 h-32 object-cover rounded";
                
                var deleteBtn = document.createElement("button");
                deleteBtn.innerHTML = "&#x2715;"; // X symbol
                deleteBtn.className = "absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center cursor-pointer";
                deleteBtn.onclick = function() { removeImage(this, index); };
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(deleteBtn);
                preview.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    }
}

function removeImage(button, index) {
    var container = button.parentNode;
    container.parentNode.removeChild(container);
    
    // Remove the file from the input
    var input = document.getElementById('images');
    var files = Array.from(input.files);
    files.splice(index, 1);
    
    // Create a new FileList object (browser-dependent)
    var dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
}
</script>

            <?php if (!empty($article['image_urls'])): ?>
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2">รูปภาพปัจจุบัน</label>
        <div class="flex flex-wrap -mx-2">
            <?php foreach (explode(',', $article['image_urls']) as $image): ?>
                <div class="w-1/2 sm:w-1/3 md:w-1/4 px-2 mb-4">
                    <div class="relative">
                        <img src="uploads/<?php echo $image; ?>" alt="Article image" class="w-full h-auto rounded">
                        <div class="absolute top-0 right-0 p-2 bg-white bg-opacity-75 rounded-bl">
                            <input type="checkbox" name="keep_images[]" value="<?php echo $image; ?>" id="img_<?php echo $image; ?>" checked class="form-checkbox h-5 w-5 text-blue-600">
                            <label for="img_<?php echo $image; ?>" class="ml-2 text-sm text-gray-700">เก็บไว้</label>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

            <div class="mb-4">
                <label for="visibility" class="block text-gray-700 text-sm font-bold mb-2">การมองเห็น</label>
                <select id="visibility" name="visibility" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="public" <?php echo $article['visibility'] == 'public' ? 'selected' : ''; ?>>สาธารณะ</option>
                    <option value="members" <?php echo $article['visibility'] == 'members' ? 'selected' : ''; ?>>เฉพาะสมาชิก</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    บันทึกการเปลี่ยนแปลง
                </button>
                <a href="manage_articles.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    ยกเลิก
                </a>
            </div>
        </form>
        <script>
var quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'เขียนเนื้อหาบทความของคุณที่นี่...',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'header': 1 }, { 'header': 2 }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'direction': 'rtl' }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'font': [] }],
            [{ 'align': [] }],
            ['clean'],
        ]
    },
    formats: {
        size: 'large'
    }
});

// เพิ่ม CSS สำหรับปรับขนาดตัวอักษรทั้งหมดใน editor
var style = document.createElement('style');
style.innerHTML = `
    .ql-editor {
        font-size: 18px;
    }
    .ql-editor .ql-size-small {
        font-size: 14px;
    }
    .ql-editor .ql-size-large {
        font-size: 20px;
    }
    .ql-editor .ql-size-huge {
        font-size: 24px;
    }
`;
document.head.appendChild(style);

// โหลดเนื้อหาเดิมเข้า Quill editor
var content = <?php echo json_encode($article['content']); ?>;
quill.root.innerHTML = content;

// เมื่อ submit form
document.querySelector('form').onsubmit = function() {
    document.querySelector('#content').value = quill.root.innerHTML;
};
</script>
</body>
</html>