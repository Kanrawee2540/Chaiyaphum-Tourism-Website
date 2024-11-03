<?php
require_once 'db.php';
session_start();
// Debug: แสดงค่า session เพื่อตรวจสอบ
echo "<!-- Debug: ";
var_dump($_SESSION);
echo " -->";

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วและมีสิทธิ์ที่เหมาะสม
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'user' && $_SESSION['role'] != 'supper_admin')) {
    header("Location: login_form.php");
    exit();
}

// ดึงข้อมูลหมวดหมู่
$category_sql = "SELECT * FROM categories";
$categories = $conn->query($category_sql)->fetch_all(MYSQLI_ASSOC);

// ตรวจสอบว่ามีการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $category_id = $_POST['category_id'];
    $visibility = $_POST['visibility'];
    $user_id = $_SESSION['user_id'];
    $is_public = ($visibility == 'public') ? 1 : 0;

    // ทำความสะอาดและกรอง HTML ที่อนุญาต
    $allowed_tags = '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote>';
    $content = strip_tags($content, $allowed_tags);

    // เตรียมคำสั่ง SQL สำหรับเพิ่มบทความ
    $stmt = $conn->prepare("INSERT INTO articles (title, content, user_id, category_id, visibility) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiis", $title, $content, $user_id, $category_id, $visibility);

    if ($stmt->execute()) {
        $article_id = $conn->insert_id;
        
        // จัดการอัปโหลดรูปภาพ (สูงสุด 10 รูป)
        $uploaded_images = [];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        for ($i = 0; $i < 10; $i++) {
            if (isset($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] == 0) {
                $file_type = $_FILES['images']['type'][$i];
                $file_size = $_FILES['images']['size'][$i];

                if (!in_array($file_type, $allowed_types)) {
                    $error = "ไฟล์ที่ {$i} ไม่ใช่ประเภทรูปภาพที่อนุญาต (jpg, jpeg, png, gif เท่านั้น)";
                    continue;
                }

                if ($file_size > $max_file_size) {
                    $error = "ไฟล์ที่ {$i} มีขนาดเกิน 5MB";
                    continue;
                }

                $target_dir = "uploads/";
                $file_extension = pathinfo($_FILES["images"]["name"][$i], PATHINFO_EXTENSION);
                $new_filename = "article_{$article_id}_image_{$i}." . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                    $uploaded_images[] = $new_filename;
                }
            }
        }

        // บันทึกชื่อไฟล์รูปภาพลงในฐานข้อมูล
        if (!empty($uploaded_images)) {
            $image_names = implode(',', $uploaded_images);
            $conn->query("UPDATE articles SET image_urls = '$image_names' WHERE id = $article_id");
        }

        $_SESSION['success'] = "เพิ่มบทความสำเร็จ";
        header("Location: manage_articles.php");
        exit();
    } else {
        $error = "เกิดข้อผิดพลาดในการเพิ่มบทความ กรุณาลองใหม่อีกครั้ง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มบทความใหม่ - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!-- เพิ่ม Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- เพิ่ม Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <style>
    select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat;
        background-position: right .7em top 50%;
        background-size: .65em auto;
        padding-right: 1.5em;
    }

    select::-ms-expand {
        display: none;
    }

    select:hover {
        border-color: #888;
    }

    select:focus {
        border-color: #aaa;
        box-shadow: 0 0 1px 3px rgba(59, 153, 252, .7);
        box-shadow: 0 0 0 3px -moz-mac-focusring;
        color: #222;
        outline: none;
    }
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
        <h1 class="text-4xl font-bold mb-8 text-center">เพิ่มบทความใหม่</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="add_article.php" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow">
    <div class="mb-4">
        <label for="title" class="block text-gray-700 text-sm font-bold mb-2">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" required
               placeholder="ใส่ชื่อหัวข้อของคุณ"
               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
    </div>

    <div class="mb-4">
        <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">หมวดหมู่</label>
        <select id="category_id" name="category_id" required
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline cursor-pointer hover:bg-gray-100 transition-colors duration-200">
    <option value="" disabled selected>เลือกหมวดหมู่ท่องเที่ยว</option>
    <?php foreach ($categories as $category): ?>
        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
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
    preview.innerHTML = ''; // Clear existing previews
    if (input.files) {
        [].forEach.call(input.files, function(file, index) {
            if (index >= 10) return; // Limit to 10 images
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

    <div class="mb-4">
        <label for="visibility" class="block text-gray-700 text-sm font-bold mb-2">การมองเห็น</label>
        <select id="visibility" name="visibility" required
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        <option value="" disabled selected>เลือกการมองเห็นบทความ</option>
        <option value="public">สาธารณะ</option>
        <option value="user">เฉพาะผู้ใช้</option>
        </select>
    </div>

    <div class="flex items-center justify-between">
    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
            เพิ่มบทความ
        </button>
        <a href="manage_articles.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
    ยกเลิก
</a>
    </div>
</form>
    </div>

    <!-- Footer -->
    <?php require_once 'footer.php'; ?>

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
        // กำหนดให้ขนาดตัวอักษรเริ่มต้นเป็น 'large'
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

// เมื่อ submit form
document.querySelector('form').onsubmit = function() {
    document.querySelector('#content').value = quill.root.innerHTML;
};
</script>
</body>
</html>