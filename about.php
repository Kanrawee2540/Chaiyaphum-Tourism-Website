<?php
require_once 'db.php';
session_start();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา - ท่องเที่ยวชัยภูมิ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Navigation -->
    <?php require_once 'menu_selector.php'; ?>

    <!-- Header -->
    <div class="py-20 bg-blue-600">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl font-bold mb-2 text-white">
                เกี่ยวกับเรา
            </h2>
            <h3 class="text-2xl mb-8 text-blue-200">
                ค้นพบเรื่องราวของเว็บไซต์ท่องเที่ยวชัยภูมิ
            </h3>
        </div>
    </div>

    <!-- Main Content -->
    <section class="container mx-auto px-6 p-10">
    <div class="flex flex-wrap">
        <div class="w-full md:w-1/2 p-6">
            <h2 class="text-3xl font-bold mb-4">เกี่ยวกับโครงงาน</h2>
            <p class="mb-4">ยินดีต้อนรับสู่เว็บไซต์ส่งเสริมการท่องเที่ยวจังหวัดชัยภูมิ! โครงงานนี้เป็นส่วนหนึ่งของการศึกษาระดับปริญญาตรี โดยมีวัตถุประสงค์เพื่อพัฒนาระบบบริหารจัดการบทความที่มีประสิทธิภาพในการนำเสนอข้อมูลการท่องเที่ยวของจังหวัดชัยภูมิ</p>
            <p>เว็บไซต์นี้ถูกพัฒนาขึ้นเพื่อรวบรวมและนำเสนอข้อมูลสถานที่ท่องเที่ยวในจังหวัดชัยภูมิอย่างเป็นระบบและครอบคลุม เพื่อส่งเสริมและเผยแพร่แหล่งท่องเที่ยวของจังหวัดให้เป็นที่รู้จักในวงกว้างมากขึ้น</p>
        </div>
        <div class="w-full md:w-1/2 p-6">
            <img src="background/ปรางกู่_2.jpg" alt="" class="rounded-lg shadow-lg">
        </div>
    </div>

    <div class="mt-10">
        <h2 class="text-3xl font-bold mb-4">เป้าหมายของเว็บไซต์</h2>
        <ul class="list-disc list-inside mb-4">
            <li>นำเสนอข้อมูลการท่องเที่ยวที่มีคุณภาพและน่าเชื่อถือเกี่ยวกับจังหวัดชัยภูมิ</li>
            <li>อำนวยความสะดวกให้นักท่องเที่ยวในการค้นหาและเข้าถึงข้อมูลสถานที่ท่องเที่ยว</li>
            <li>สร้างแรงบันดาลใจให้ผู้คนมาเยือนชัยภูมิและค้นพบความมหัศจรรย์ของธรรมชาติ ประวัติศาสตร์ และวัฒนธรรม</li>
            <li>เป็นแหล่งข้อมูลที่มีประโยชน์สำหรับการวางแผนการเดินทาง</li>
            <li>สร้างพื้นที่แลกเปลี่ยนประสบการณ์ระหว่างผู้ที่รักการท่องเที่ยวในชัยภูมิ</li>
        </ul>
    </div>

    <div class="mt-10">
        <h2 class="text-3xl font-bold mb-4">ผู้พัฒนาโครงงาน</h2>
        <p class="mb-4">โครงงานนี้พัฒนาโดย นางสาวกานต์รวี จตุรงค์ รหัสนักศึกษา ๖๔๐๑๐๕๑๒๐๐๐๓ นักศึกษาระดับปริญญาตรี สาขาวิชาเทคโนโลยีสารสนเทศ คณะบริหารธุรกิจ วิทยาลัยนครราชสีมา</p>
    </div>

    <div class="mt-10">
        <h2 class="text-3xl font-bold mb-4">ขอบคุณที่มาเยี่ยมชม</h2>
        <p>ขอบคุณทุกท่านที่แวะมาเยี่ยมชมเว็บไซต์ของเรา เราหวังว่าข้อมูลและเนื้อหาที่นำเสนอจะเป็นประโยชน์และสร้างแรงบันดาลใจให้คุณได้มาสัมผัสความงามของจังหวัดชัยภูมิด้วยตัวเอง หากคุณมีข้อเสนอแนะหรือคำแนะนำใดๆ เราพร้อมรับฟังเพื่อนำไปพัฒนาเว็บไซต์ให้ดียิ่งขึ้นต่อไป</p>
    </div>
</section>

    <!-- Footer -->
    <?php require_once 'footer.php'; ?>

    <script>
        // Toggle mobile menu
        const menuButton = document.querySelector('nav button');
        const menu = document.querySelector('nav div.w-full');
        menuButton.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>