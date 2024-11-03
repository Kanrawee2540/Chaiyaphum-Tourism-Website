<?php
require_once 'db.php';
session_start();
// ตรวจสอบสิทธิ์การเข้าถึง
$allowed_roles = ['admin', 'supper_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}

// ฟังก์ชันสำหรับเพิ่มหมวดหมู่
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        // ตรวจสอบว่ามีหมวดหมู่ซ้ำหรือไม่
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $check_stmt->bind_param("s", $name);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $_SESSION['error'] = "หมวดหมู่ '{$name}' มีอยู่แล้ว ไม่สามารถเพิ่มซ้ำได้";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $_SESSION['success'] = "เพิ่มหมวดหมู่ '{$name}' สำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกชื่อหมวดหมู่";
    }
    header("Location: manage_categories.php");
    exit();
}

// ฟังก์ชันสำหรับแก้ไขหมวดหมู่
if (isset($_POST['edit_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        // ตรวจสอบว่ามีหมวดหมู่ซ้ำหรือไม่ (ยกเว้นหมวดหมู่ปัจจุบัน)
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $check_stmt->bind_param("si", $name, $id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $_SESSION['error'] = "หมวดหมู่ '{$name}' มีอยู่แล้ว ไม่สามารถแก้ไขเป็นชื่อนี้ได้";
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "แก้ไขหมวดหมู่เป็น '{$name}' สำเร็จ";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกชื่อหมวดหมู่";
    }
    header("Location: manage_categories.php");
    exit();
}

// ฟังก์ชันสำหรับลบหมวดหมู่
if (isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    
    // ตรวจสอบว่ามีบทความในหมวดหมู่นี้หรือไม่
    $check_articles_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
    $check_articles_stmt->bind_param("i", $id);
    $check_articles_stmt->execute();
    $check_articles_stmt->bind_result($article_count);
    $check_articles_stmt->fetch();
    $check_articles_stmt->close();

    if ($article_count > 0) {
        $_SESSION['error'] = "ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากมีบทความที่เกี่ยวข้องอยู่";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "ลบหมวดหมู่สำเร็จ";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบหมวดหมู่";
        }
        $stmt->close();
    }
    header("Location: manage_categories.php");
    exit();
}

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT categories.*, COUNT(articles.id) as article_count 
                            FROM categories 
                            LEFT JOIN articles ON categories.id = articles.category_id 
                            GROUP BY categories.id 
                            ORDER BY categories.name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหมวดหมู่ - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <?php require_once 'menu_selector.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-8 text-center">จัดการหมวดหมู่</h1>

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
        <!-- ฟอร์มเพิ่มหมวดหมู่ -->
        <form action="manage_categories.php" method="POST" class="mb-8">
            <div class="flex items-center">
                <input type="text" name="category_name" placeholder="ชื่อหมวดหมู่ใหม่" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <button type="submit" name="add_category" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2">
                    เพิ่มหมวดหมู่
                </button>
            </div>
        </form>

        <!-- ตารางแสดงหมวดหมู่ -->
        <div class="bg-white shadow-md rounded my-6">
            <table class="text-left w-full border-collapse">
                <thead>
                    <tr>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">ชื่อหมวดหมู่</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">จำนวนบทความ</th>
                        <th class="py-4 px-6 bg-grey-lightest font-bold uppercase text-sm text-grey-dark border-b border-grey-light">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr class="hover:bg-grey-lighter">
                        <td class="py-4 px-6 border-b border-grey-light"><?php echo htmlspecialchars($category['name']); ?></td>
                        <td class="py-4 px-6 border-b border-grey-light"><?php echo $category['article_count']; ?></td>
                        <td class="py-4 px-6 border-b border-grey-light">
                            <button onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" class="text-blue-600 hover:text-blue-900 mr-2">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบหมวดหมู่นี้?');">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับแก้ไขหมวดหมู่ -->
    <div id="editModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="manage_categories.php" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">แก้ไขหมวดหมู่</h3>
                        <div class="mt-2">
                            <input type="hidden" id="edit_category_id" name="category_id">
                            <input type="text" id="edit_category_name" name="category_name" required
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" name="edit_category" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            บันทึก
                        </button>
                        <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    function editCategory(id, name) {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(id, name, articleCount) {
        document.getElementById('delete_category_id').value = id;
        const modalContent = document.getElementById('delete-modal-content');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        if (articleCount > 0) {
            modalContent.innerHTML = `ไม่สามารถลบหมวดหมู่ "${name}" ได้ เนื่องจากมีบทความที่เกี่ยวข้องอยู่ ${articleCount} บทความ`;
            confirmDeleteBtn.style.display = 'none';
        } else {
            modalContent.innerHTML = `คุณแน่ใจหรือไม่ที่จะลบหมวดหมู่ "${name}"?`;
            confirmDeleteBtn.style.display = 'inline-flex';
        }

        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    </script>
    
    <!-- Footer -->
    <?php require_once 'footer.php'; ?>
</body>
</html>