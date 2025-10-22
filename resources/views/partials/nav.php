<?php
$currentPage = $current_page ?? '';
$userRole = $user_role ?? 'viewer';
?>
<div class="flex flex-col lg:flex-row justify-between items-center mb-6 lg:mb-8 pb-4 border-b-2 border-amc-light gap-4">
    <div class="flex items-center cursor-pointer transition-transform duration-300 hover:scale-105" onclick="window.location.href='index.php'">
        <svg class="w-8 h-8 lg:w-10 lg:h-10 mr-2 lg:mr-3 fill-amc-dark-blue" viewBox="0 0 24 24">
            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
        </svg>
        <span class="text-lg lg:text-xl font-bold text-amc-dark-blue">AMC MONITORING</span>
    </div>
    <div class="flex flex-wrap justify-center lg:justify-end gap-2 lg:gap-4">
        <button class="nav-btn-gradient text-white border-none rounded-md text-sm font-semibold px-3 py-2 lg:px-4 lg:py-2 transition-all duration-300 hover:-translate-y-1 <?= ($currentPage === 'index.php') ? 'shadow-inner transform translate-y-px' : '' ?>" onclick="window.location.href='index.php'">Apron Map</button>
        <button class="nav-btn-gradient text-white border-none rounded-md text-sm font-semibold px-3 py-2 lg:px-4 lg:py-2 transition-all duration-300 hover:-translate-y-1 <?= ($currentPage === 'master-table.php') ? 'shadow-inner transform translate-y-px' : '' ?>" onclick="window.location.href='master-table.php'">Master Table</button>
        <?php if ($userRole !== 'viewer'): ?>
        <button class="nav-btn-gradient text-white border-none rounded-md text-sm font-semibold px-3 py-2 lg:px-4 lg:py-2 transition-all duration-300 hover:-translate-y-1 <?= ($currentPage === 'dashboard.php') ? 'shadow-inner transform translate-y-px' : '' ?>" onclick="window.location.href='dashboard.php'">Dashboard</button>
        <?php endif; ?>
        <button class="nav-btn-gradient text-white border-none rounded-md text-sm font-semibold px-3 py-2 lg:px-4 lg:py-2 transition-all duration-300 hover:-translate-y-1" onclick="window.location.href='logout.php'">Logout</button>
    </div>
</div>
