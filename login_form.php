<?php
require_once 'db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <div class="container mx-auto">
        <div class="flex justify-center px-6 my-12">
            <div class="w-full xl:w-3/4 lg:w-11/12 flex">
                <div class="w-full h-auto bg-gray-400 hidden lg:block lg:w-1/2 bg-cover rounded-l-lg" style="background-image: url('background/ทุ่งกระมัง.jpg')"></div>
                <div class="w-full lg:w-1/2 bg-white p-5 rounded-lg lg:rounded-l-none">
                    <h3 class="pt-4 text-2xl text-center">ยินดีต้อนรับ</h3>
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
                    <form class="px-8 pt-6 pb-8 mb-4 bg-white rounded" action="login.php" method="POST">
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700" for="username">
                                ชื่อผู้ใช้หรืออีเมล
                            </label>
                            <input
                                class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                id="username"
                                type="text"
                                placeholder="กรอกชื่อผู้ใช้หรืออีเมล"
                                name="username"
                                required
                            />
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700" for="password">
                                รหัสผ่าน
                            </label>
                            <input
                                class="w-full px-3 py-2 mb-3 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                id="password"
                                type="password"
                                placeholder="******************"
                                name="password"
                                required
                            />
                        </div>
                        <div class="mb-6 text-center">
                            <button
                                class="w-full px-4 py-2 font-bold text-white bg-blue-500 rounded-full hover:bg-blue-700 focus:outline-none focus:shadow-outline"
                                type="submit"
                            >
                                เข้าสู่ระบบ
                            </button>
                        </div>
                        <hr class="mb-6 border-t" />
                        <div class="text-center mb-4">
                            <a
                                class="inline-block text-sm text-blue-500 align-baseline hover:text-blue-800"
                                href="signup_form.php"
                            >
                                ยังไม่มีบัญชี? สมัครสมาชิก
                            </a>
                        </div>
                        <div class="text-center mb-4">
                        </div>
                        <div class="text-center">
                            <a
                                href="index.php"
                                class="inline-block px-4 py-2 font-bold text-white bg-gray-500 rounded-full hover:bg-gray-700 focus:outline-none focus:shadow-outline"
                            >
                                กลับไปยังหน้าหลัก
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>