<?php
// กำหนดข้อมูลตั้งต้น (จำลองว่าดึงมาจากฐานข้อมูลหรือเซนเซอร์)
$healthData = [
    'acetone' => ['value' => 0.4, 'unit' => 'ppm', 'status' => 'normal', 'label' => 'ปกติ'],
    'glucose' => ['value' => 110, 'unit' => 'mg/dL', 'status' => 'normal', 'label' => 'ปกติ'],
    'microalbumin' => ['value' => 25, 'unit' => 'mg/L', 'status' => 'warning', 'label' => 'เฝ้าระวัง']
];

$isConnected = true;
$lastSync = 'เพิ่งอัปเดต';

$members = [
    "นายปรรณพันธ์ จันทะวงศ์ศรี",
    "นางสาวช่อลดา นนทะโคตร",
    "นางสาวพิชญธิดา ธรรมมาน้อย",
    "นางสาวสุภิชญา นามปรีดา",
    "นางสาวอภิชญา ภูวศิษฎ์เบญจภา"
];

// ฟังก์ชันจำลองการกำหนดสีตามสถานะ
function getStatusColor($status) {
    switch($status) {
        case 'normal': return 'text-green-600 bg-green-100 border-green-200';
        case 'warning': return 'text-yellow-600 bg-yellow-100 border-yellow-200';
        case 'danger': return 'text-red-600 bg-red-100 border-red-200';
        default: return 'text-gray-600 bg-gray-100 border-gray-200';
    }
}

function getStatusProgressColor($status) {
    switch($status) {
        case 'normal': return 'bg-green-500';
        case 'warning': return 'bg-yellow-500';
        case 'danger': return 'bg-red-500';
        default: return 'bg-gray-500';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiaCheck Duo App</title>
    <!-- นำเข้า Tailwind CSS ผ่าน CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- นำเข้า ไอคอน Lucide ผ่าน CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .pb-safe { padding-bottom: env(safe-area-inset-bottom, 20px); }
        /* ซ่อน Scrollbar แต่ยังเลื่อนได้ */
        main::-webkit-scrollbar { display: none; }
        main { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 flex justify-center font-sans antialiased text-gray-900">

    <!-- Mobile Frame Simulation -->
    <div class="w-full max-w-md bg-gray-50 min-h-screen relative shadow-2xl overflow-hidden flex flex-col">
        
        <!-- App Header -->
        <header class="bg-white px-5 pt-12 pb-4 shadow-sm sticky top-0 z-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight">DiaCheck</h1>
                    <div class="flex items-center mt-1">
                        <div class="w-2 h-2 rounded-full mr-2 <?= $isConnected ? 'bg-green-500' : 'bg-red-500' ?>"></div>
                        <p class="text-xs font-medium text-gray-500">
                            <?= $isConnected ? 'เชื่อมต่อเครื่องมือแล้ว' : 'ไม่ได้เชื่อมต่อ' ?>
                        </p>
                    </div>
                </div>
                
                <button id="sync-btn" onclick="handleSync()" class="p-3 rounded-full flex items-center justify-center transition-all bg-blue-50 text-blue-600 hover:bg-blue-100">
                    <span id="sync-icon"><i data-lucide="bluetooth-connected" class="w-5 h-5"></i></span>
                </button>
            </div>
            
            <div id="sync-status-bar" class="mt-3 text-xs text-gray-400 flex items-center justify-between bg-gray-50 rounded-lg p-2 px-3">
                <span>อัปเดตล่าสุด: <span id="last-sync-time"><?= $lastSync ?></span></span>
                <span><?= date('d M Y') ?></span>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-5 scroll-smooth">
            
            <!-- ===================== หน้าต่าง Dashboard ===================== -->
            <div id="tab-dashboard" class="space-y-4 pb-20 fade-in">
                <!-- AI Assessment Card -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-3xl p-5 text-white shadow-lg">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">การประเมินผลด้วย AI</p>
                            <h2 class="text-2xl font-bold mt-1">ภาพรวมสุขภาพ</h2>
                        </div>
                        <div class="bg-white/20 p-2 rounded-xl backdrop-blur-sm">
                            <i data-lucide="activity" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 bg-white/10 rounded-2xl p-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-300"></i>
                        <p class="text-sm">
                            ระดับน้ำตาลปกติ แต่พบโปรตีนรั่วในปัสสาวะเล็กน้อย แนะนำให้ดื่มน้ำมากขึ้นและลดอาหารเค็ม
                        </p>
                    </div>
                </div>

                <!-- Measurement Cards -->
                <h3 class="text-lg font-bold text-gray-800 px-1 pt-2">ผลการตรวจวัดล่าสุด</h3>
                
                <!-- Acetone Card -->
                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                                <i data-lucide="wind" class="w-5 h-5"></i>
                            </div>
                            <h4 class="font-semibold text-gray-700">อะซิโตนในลมหายใจ</h4>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold border <?= getStatusColor($healthData['acetone']['status']) ?>">
                            <?= $healthData['acetone']['label'] ?>
                        </span>
                    </div>
                    <div class="flex items-baseline space-x-1 mt-2">
                        <span id="acetone-val" class="text-3xl font-extrabold text-gray-900"><?= $healthData['acetone']['value'] ?></span>
                        <span class="text-sm text-gray-500 font-medium"><?= $healthData['acetone']['unit'] ?></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 mt-4">
                        <div class="h-2 rounded-full <?= getStatusProgressColor($healthData['acetone']['status']) ?>" style="width: 20%;"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">บ่งชี้ภาวะคีโตซีสและความเสี่ยง DKA</p>
                </div>

                <!-- Urine Analysis Cards - Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Glucose -->
                    <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-40">
                        <div>
                            <div class="flex justify-between items-start">
                                <div class="p-2 bg-purple-50 text-purple-600 rounded-xl inline-block">
                                    <i data-lucide="droplet" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <h4 class="font-medium text-gray-600 text-sm mt-3">กลูโคส (ปัสสาวะ)</h4>
                        </div>
                        <div>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-2xl font-bold text-gray-900"><?= $healthData['glucose']['value'] ?></span>
                            </div>
                            <p class="text-xs text-gray-500"><?= $healthData['glucose']['unit'] ?></p>
                            <div class="mt-2 text-xs font-semibold <?= $healthData['glucose']['status'] === 'normal' ? 'text-green-500' : 'text-red-500' ?> flex items-center">
                                <i data-lucide="check-circle-2" class="w-3 h-3 mr-1"></i> ปกติดี
                            </div>
                        </div>
                    </div>

                    <!-- Microalbumin -->
                    <div class="bg-white rounded-3xl p-4 shadow-sm border border-gray-100 flex flex-col justify-between h-40">
                        <div>
                            <div class="flex justify-between items-start">
                                <div class="p-2 bg-orange-50 text-orange-600 rounded-xl inline-block">
                                    <i data-lucide="activity" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <h4 class="font-medium text-gray-600 text-sm mt-3">ไมโครอัลบูมิน</h4>
                        </div>
                        <div>
                            <div class="flex items-baseline space-x-1">
                                <span class="text-2xl font-bold text-gray-900"><?= $healthData['microalbumin']['value'] ?></span>
                            </div>
                            <p class="text-xs text-gray-500"><?= $healthData['microalbumin']['unit'] ?></p>
                            <div class="mt-2 text-xs font-semibold <?= $healthData['microalbumin']['status'] === 'warning' ? 'text-yellow-500' : 'text-green-500' ?> flex items-center">
                                <i data-lucide="alert-triangle" class="w-3 h-3 mr-1"></i> เฝ้าระวังไต
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== หน้าต่าง ประวัติ (History) ===================== -->
            <div id="tab-history" class="space-y-4 pb-20 fade-in" style="display: none;">
                <div class="flex justify-between items-end px-1 pb-2">
                    <h2 class="text-2xl font-bold text-gray-800">ประวัติการตรวจ</h2>
                    <button class="text-blue-600 text-sm font-medium">ดูทั้งหมด</button>
                </div>

                <?php foreach([1, 2, 3, 4] as $index => $item): ?>
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <?php if($index === 0): ?>
                                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-yellow-100 text-yellow-600">
                                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm">มีแนวโน้มความเสี่ยง</p>
                                    <p class="text-xs text-gray-500"><?= $item ?> วันที่แล้ว • 08:30 น.</p>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full flex items-center justify-center bg-green-100 text-green-600">
                                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm">ผลปกติ</p>
                                    <p class="text-xs text-gray-500"><?= $item ?> วันที่แล้ว • 08:30 น.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-800"><?= 0.4 + ($index * 0.1) ?> ppm</p>
                            <p class="text-xs text-gray-400">อะซิโตน</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ===================== หน้าต่าง เกี่ยวกับ (About) ===================== -->
            <div id="tab-about" class="space-y-5 pb-20 fade-in" style="display: none;">
                <div class="text-center py-4">
                    <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-3xl mx-auto flex items-center justify-center mb-4 transform rotate-3">
                        <i data-lucide="activity" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">DiaCheck Duo</h2>
                    <p class="text-gray-500 text-sm mt-2 px-4 leading-relaxed">
                        เครื่องวิเคราะห์พกพาสำหรับตรวจวัดอะซิโตนในลมหายใจและไมโครอัลบูมินร่วมกับกลูโคสในปัสสาวะเพื่อเฝ้าระวังโรคเบาหวาน
                    </p>
                </div>

                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">จุดเด่นนวัตกรรม</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-500 mr-3 shrink-0"></i>
                            <span class="text-sm text-gray-700">รวมการตรวจวัดลมหายใจและปัสสาวะในเครื่องเดียว</span>
                        </li>
                        <li class="flex items-start">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-500 mr-3 shrink-0"></i>
                            <span class="text-sm text-gray-700">ไม่ต้องเจ็บตัวใช้เข็มเจาะเลือด (Non-invasive)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-500 mr-3 shrink-0"></i>
                            <span class="text-sm text-gray-700">ประมวลผลแม่นยำด้วย AI (Decision Tree/Random Forest)</span>
                        </li>
                        <li class="flex items-start">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-blue-500 mr-3 shrink-0"></i>
                            <span class="text-sm text-gray-700">น้ำหนักเบาเพียง 220 กรัม พกพาสะดวก</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">คณะผู้จัดทำ</h3>
                    <div class="space-y-2">
                        <?php foreach($members as $index => $name): ?>
                            <p class="text-sm text-gray-800 font-medium"><?= $index + 1 ?>. <?= $name ?> (ม.5)</p>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">คุณครูที่ปรึกษา</p>
                        <p class="text-sm font-bold text-gray-800">นายศิวะ ปินะสา</p>
                        <p class="text-xs text-blue-600 mt-1">โรงเรียนขอนแก่นวิทยายน</p>
                    </div>
                </div>
            </div>

        </main>

        <!-- Bottom Navigation -->
        <nav class="bg-white border-t border-gray-100 px-6 py-4 flex justify-between items-center sticky bottom-0 z-20 pb-safe">
            <button id="nav-dashboard" onclick="switchTab('dashboard')" class="nav-btn flex flex-col items-center p-2 transition-colors text-blue-600">
                <div class="nav-icon-bg p-1.5 rounded-xl mb-1 bg-blue-50">
                    <i data-lucide="home" class="w-6 h-6"></i>
                </div>
                <span class="text-[10px] font-bold">หน้าหลัก</span>
            </button>
            
            <button id="nav-history" onclick="switchTab('history')" class="nav-btn flex flex-col items-center p-2 transition-colors text-gray-400">
                <div class="nav-icon-bg p-1.5 rounded-xl mb-1 bg-transparent">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <span class="text-[10px] font-bold">ประวัติ</span>
            </button>
            
            <button id="nav-about" onclick="switchTab('about')" class="nav-btn flex flex-col items-center p-2 transition-colors text-gray-400">
                <div class="nav-icon-bg p-1.5 rounded-xl mb-1 bg-transparent">
                    <i data-lucide="info" class="w-6 h-6"></i>
                </div>
                <span class="text-[10px] font-bold">เกี่ยวกับ</span>
            </button>
        </nav>
        
    </div>

    <!-- Script สำหรับทำงานส่วนของ Frontend (เปลี่ยนหน้าต่าง, โหลดข้อมูล) -->
    <script>
        // เริ่มต้นการเรนเดอร์ไอคอน Lucide
        lucide.createIcons();

        // ฟังก์ชันสลับหน้า Tab
        function switchTab(tabId) {
            // ซ่อนทุกแท็บ
            document.getElementById('tab-dashboard').style.display = 'none';
            document.getElementById('tab-history').style.display = 'none';
            document.getElementById('tab-about').style.display = 'none';
            
            // โชว์แถบสถานะการเชื่อมต่อแค่หน้าแรก
            document.getElementById('sync-status-bar').style.display = tabId === 'dashboard' ? 'flex' : 'none';

            // รีเซ็ตปุ่มเมนูทั้งหมดให้เป็นสีเทา
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('text-blue-600');
                btn.classList.add('text-gray-400');
                btn.querySelector('.nav-icon-bg').classList.remove('bg-blue-50');
                btn.querySelector('.nav-icon-bg').classList.add('bg-transparent');
            });

            // แสดงแท็บที่ถูกเลือก
            document.getElementById('tab-' + tabId).style.display = 'block';

            // เปลี่ยนสีปุ่มเมนูที่เลือกเป็นสีน้ำเงิน
            const activeBtn = document.getElementById('nav-' + tabId);
            activeBtn.classList.remove('text-gray-400');
            activeBtn.classList.add('text-blue-600');
            activeBtn.querySelector('.nav-icon-bg').classList.remove('bg-transparent');
            activeBtn.querySelector('.nav-icon-bg').classList.add('bg-blue-50');
        }

        // ฟังก์ชันจำลองการกดรับข้อมูล (Sync Data)
        function handleSync() {
            const syncBtn = document.getElementById('sync-btn');
            const syncIcon = document.getElementById('sync-icon');
            const syncTime = document.getElementById('last-sync-time');
            const acetoneVal = document.getElementById('acetone-val');

            // เปลี่ยนหน้าตาปุ่มเป็นกำลังโหลด
            syncBtn.classList.remove('bg-blue-50', 'text-blue-600', 'hover:bg-blue-100');
            syncBtn.classList.add('bg-blue-100', 'text-blue-400', 'cursor-not-allowed');
            syncBtn.disabled = true;
            syncIcon.innerHTML = '<i data-lucide="refresh-cw" class="w-5 h-5 animate-spin"></i>';
            lucide.createIcons();

            // จำลองการโหลด 2 วินาที
            setTimeout(() => {
                // คืนค่าหน้าตาปุ่มเดิม
                syncBtn.classList.add('bg-blue-50', 'text-blue-600', 'hover:bg-blue-100');
                syncBtn.classList.remove('bg-blue-100', 'text-blue-400', 'cursor-not-allowed');
                syncBtn.disabled = false;
                syncIcon.innerHTML = '<i data-lucide="bluetooth-connected" class="w-5 h-5"></i>';
                lucide.createIcons();
                
                // อัปเดตข้อมูลบนหน้าจอ
                syncTime.innerText = 'เพิ่งอัปเดต';
                // สุ่มตัวเลขใหม่แสดงผล
                const randomAcetone = (Math.random() * 0.8).toFixed(1);
                acetoneVal.innerText = randomAcetone;
                
            }, 2000);
        }
    </script>
</body>
</html>