<?php require_once 'thai_date_functions.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Travel Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<script>
function previewImage(input) {
    var preview = document.getElementById('image_preview');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'max-w-xs max-h-48 mt-2';
            preview.appendChild(img);
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <div class="container mx-auto">
        <div class="flex justify-center px-6 my-12">
            <div class="w-full xl:w-3/4 lg:w-11/12 flex">
            <div class="w-full h-auto bg-gray-400 hidden lg:block lg:w-1/2 bg-cover rounded-l-lg" style="background-image: url('background/ทุ่งกระมัง.jpg')"></div>
                <div class="w-full lg:w-7/12 bg-white p-5 rounded-lg lg:rounded-l-none">
                    <h3 class="pt-4 text-2xl text-center">สมัครสมาชิก</h3>
                    <form class="px-8 pt-6 pb-8 mb-4 bg-white rounded" action="signup.php" method="POST" enctype="multipart/form-data" >
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700" for="username">
                                ชื่อผู้ใช้
                            </label>
                            <input
                                class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                id="username"
                                type="text"
                                placeholder="ชื่อผู้ใช้"
                                name="username"
                                required
                            />
                        </div>
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700" for="email">
                                อีเมล
                            </label>
                            <input
                                class="w-full px-3 py-2 mb-3 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                id="email"
                                type="email"
                                placeholder="อีเมล"
                                name="email"
                                required
                            />
                        </div>
                        <div class="mb-4 md:flex md:justify-between">
                            <div class="mb-4 md:mr-2 md:mb-0">
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
                            <div class="md:ml-2">
                                <label class="block mb-2 text-sm font-bold text-gray-700" for="c_password">
                                    ยืนยันรหัสผ่าน
                                </label>
                                <input
                                    class="w-full px-3 py-2 mb-3 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                    id="c_password"
                                    type="password"
                                    placeholder="******************"
                                    name="c_password"
                                    required
                                />
                            </div>
                        </div>
                        <div class="mb-4">
        <label class="block mb-2 text-sm font-bold text-gray-700" for="pen_name">
            ชื่อนามปากกา
        </label>
        <input
            class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
            id="pen_name"
            type="text"
            placeholder="ชื่อนามปากกา"
            name="pen_name"
            required
        />
    </div>

    <div class="mb-4 md:flex md:justify-between">
        <div class="mb-4 md:mr-2 md:mb-0">
            <label class="block mb-2 text-sm font-bold text-gray-700" for="first_name">
                ชื่อจริง 
            </label>
            <input
                class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                id="first_name"
                type="text"
                placeholder="ชื่อจริง"
                name="first_name"
            />
        </div>
        <div class="md:ml-2">
            <label class="block mb-2 text-sm font-bold text-gray-700" for="last_name">
                นามสกุล 
            </label>
            <input
                class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                id="last_name"
                type="text"
                placeholder="นามสกุล"
                name="last_name"
            />
        </div>
    </div>


    <div class="mb-4">
        <label class="block mb-2 text-sm font-bold text-gray-700" for="birth_date">
            วันเดือนปีเกิด (พ.ศ.)
        </label>
        <div class="flex">
            <select id="birth_day" name="birth_day" class="w-1/4 px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline mr-2" required>
                <option value="">วัน</option>
                <?php
                for ($i = 1; $i <= 31; $i++) {
                    echo "<option value=\"$i\">" . convertToThaiNumerals($i) . "</option>";
                }
                ?>
            </select>
            <select id="birth_month" name="birth_month" class="w-1/4 px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline mr-2" required>
                <option value="">เดือน</option>
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    echo "<option value=\"$i\">" . thaiMonth($i) . "</option>";
                }
                ?>
            </select>
            <select id="birth_year" name="birth_year" class="w-1/4 px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline" required>
                <option value="">ปี พ.ศ.</option>
                <?php
                $current_year = date("Y") + 543;
                for ($i = $current_year - 100; $i <= $current_year - 10; $i++) {
                    echo "<option value=\"$i\">" . convertToThaiNumerals($i) . "</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="mb-4">
        <label class="block mb-2 text-sm font-bold text-gray-700" for="gender">
            เพศ 
        </label>
        <select
            class="w-full px-3 py-2 text-sm leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
            id="gender"
            name="gender"
        >
            <option value="">เลือกเพศ</option>
            <option value="male">ชาย</option>
            <option value="female">หญิง</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div class="mb-4">
       
        <div id="image_preview" class="mt-2"></div>
        </div>
                        <div class="mb-6 text-center">
                            <button
                                class="w-full px-4 py-2 font-bold text-white bg-blue-500 rounded-full hover:bg-blue-700 focus:outline-none focus:shadow-outline"
                                type="submit"
                            >
                                สมัครสมาชิก
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
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>