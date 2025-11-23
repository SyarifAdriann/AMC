<?php
$title = 'AMC MONITORING SYSTEM';
$styles = [
    'assets/css/tailwind.css',
    'assets/css/styles.css?v=1.6',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'
];
$head = '';
$bodyClass = 'gradient-bg min-h-screen font-sans';
$scripts = [
    'assets/js/mobile-adaptations.js',
    'assets/js/apron.js'
];
ob_start();
?>
    <div class="p-4 lg:p-5 min-h-screen">
        <div class="max-w-7xl mx-auto container-bg rounded-xl p-4 lg:p-8 shadow-2xl">
            
            <?php require __DIR__ . '/../partials/nav.php'; ?>



            <!-- MAIN TITLE -->
            <h1 class="text-center mb-6 lg:mb-8 text-2xl lg:text-4xl font-bold text-amc-dark-blue tracking-wide" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1); letter-spacing: 2px;">AMC MONITORING SYSTEM</h1>

            <!-- WELCOME MESSAGE -->
            <div class="text-center mb-6 lg:mb-8 text-base lg:text-lg text-gray-700">
                Welcome, <strong class="text-amc-dark-blue"><?= htmlspecialchars($username) ?></strong> (<?= htmlspecialchars(ucfirst($user_role)) ?>)
            </div>

            <!-- STAFF ROSTER -->
            <div class="mb-6 lg:mb-8 bg-amc-bg rounded-xl p-4 lg:p-6 border border-amc-light shadow-lg">
                <div class="overflow-x-auto">
                    <table class="w-full border-separate" style="border-spacing: 0 10px;" id="roster-table">
                        <tbody>
                            <tr>
                                <th class="text-amc-dark-blue text-left font-bold text-sm lg:text-base w-32 lg:w-40 px-2 py-2">AERODROME</th>
                                <td class="bg-white rounded-md px-2 py-2" colspan="2">
                                    <input type="text" value="WIHH" id="aerodrome-input" placeholder="Enter aerodrome code" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th class="text-amc-dark-blue text-left font-bold text-sm lg:text-base px-2 py-2">TANGGAL</th>
                                <td class="bg-white rounded-md px-2 py-2" colspan="2">
                                    <input type="date" id="roster-date" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                            </tr>
                            <tr class="text-center">
                                <td></td>
                                <th class="bg-gray-100 text-gray-700 text-xs lg:text-sm rounded-md px-2 py-1">PAGI SIANG (07:00 - 18:59)</th>
                                <th class="bg-gray-100 text-gray-700 text-xs lg:text-sm rounded-md px-2 py-1">MALAM (19:00 - 06:59)</th>
                            </tr>
                            <tr>
                                <th class="text-amc-dark-blue font-bold text-sm lg:text-base px-2 py-2">PETUGAS AMC</th>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Day Shift Staff" id="day-staff-1" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Night Shift Staff" id="night-staff-1" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Day Shift Staff" id="day-staff-2" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Night Shift Staff" id="night-staff-2" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Day Shift Staff" id="day-staff-3" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                                <td class="bg-white rounded-md px-2 py-2">
                                    <input placeholder="Night Shift Staff" id="night-staff-3" 
                                           class="w-full text-sm lg:text-base border border-gray-300 rounded-md px-3 py-2 focus:border-amc-blue focus:bg-white focus:shadow-sm transition-all duration-300 <?php if ($user_role==='viewer') echo 'bg-amc-light text-amc-dark-blue cursor-not-allowed'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right pt-4">
                                    <button id="save-roster" class="nav-btn-gradient text-white px-4 py-2 lg:px-6 lg:py-3 text-sm lg:text-base rounded-md font-semibold transition-all duration-300 hover:-translate-y-1 <?php if ($user_role==='viewer') echo 'bg-gray-400 cursor-not-allowed'; ?>">???? Save Roster</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- LIVE APRON STATUS -->
            <div class="flex flex-wrap justify-center lg:justify-around items-center bg-amc-bg rounded-xl p-4 lg:p-6 mb-6 lg:mb-8 border border-amc-light shadow-lg gap-4" id="live-apron-status-container">
                <div class="text-center min-w-0 flex-1 lg:flex-initial">
                    <span class="block text-xs lg:text-sm font-semibold text-gray-600 mb-1">Total Stands</span>
                    <span class="block text-2xl lg:text-4xl font-bold text-amc-dark-blue" id="apron-total"><?= htmlspecialchars($apronStatus['total']) ?></span>
                </div>
                
                <div class="text-center min-w-0 flex-1 lg:flex-initial">
                    <span class="block text-xs lg:text-sm font-semibold text-gray-600 mb-1">Available</span>
                    <span class="block text-2xl lg:text-4xl font-bold text-green-600" id="apron-available"><?= htmlspecialchars($apronStatus['available']) ?></span>
                </div>
                
                <div class="text-center min-w-0 flex-1 lg:flex-initial">
                    <span class="block text-xs lg:text-sm font-semibold text-gray-600 mb-1">Occupied</span>
                    <span class="block text-2xl lg:text-4xl font-bold text-red-600" id="apron-occupied"><?= htmlspecialchars($apronStatus['occupied']) ?></span>
                </div>
                
                <div class="text-center min-w-0 flex-1 lg:flex-initial">
                    <span class="block text-xs lg:text-sm font-semibold text-gray-600 mb-1">Live RON</span>
                    <span class="block text-2xl lg:text-4xl font-bold text-yellow-500" id="apron-ron"><?= htmlspecialchars($apronStatus['ron']) ?></span>
                </div>
                
                <div class="flex flex-col gap-2 w-full lg:w-auto mt-4 lg:mt-0">
                    <button id="set-ron-btn" class="nav-btn-gradient text-white px-4 py-2 lg:px-6 lg:py-3 text-sm lg:text-base rounded-md font-semibold transition-all duration-300 hover:-translate-y-1 <?php if ($user_role==='viewer') echo 'bg-gray-400 cursor-not-allowed'; ?>">Set RON</button>
                    <button id="refresh-btn" class="nav-btn-gradient text-white px-4 py-2 lg:px-6 lg:py-3 text-sm lg:text-base rounded-md font-semibold transition-all duration-300 hover:-translate-y-1" onclick="window.location.reload()">Refresh</button>
                </div>
            </div>

            <!-- APRON MAP -->
            <div class="w-full mx-auto mb-8 lg:mb-10 relative overflow-hidden rounded-xl shadow-lg border-2 border-amc-light apron-checkerboard" id="apron-wrapper">
                <div class="relative" id="apron-container" style="width: 1920px; height: 1080px;">
                    <?php
                    $stands = [
                        'A0'=>[1785,923],'A1'=>[1712,923],'A2'=>[1621,923],'A3'=>[1518,923],
                        'B1'=>[1414,923],'B2'=>[1321,923],'B3'=>[1229,923],'B4'=>[1136,923],
                        'B5'=>[1043,923],'B6'=>[950,923],'B7'=>[859,923],'B8'=>[768,923],
                        'B9'=>[673,923],'B10'=>[577,923],'B11'=>[483,923],'B12'=>[394,923],'B13'=>[306,923],
                        'SA01'=>[152,125],'SA02'=>[365,125],'SA03'=>[578,125],'SA04'=>[791,125],
                        'SA05'=>[1004,125],'SA06'=>[1218,125],'SA07'=>[87,250],'SA08'=>[210,250],
                        'SA09'=>[300,250],'SA10'=>[423,250],'SA11'=>[514,250],'SA12'=>[635,250],
                        'SA13'=>[726,250],'SA14'=>[849,250],'SA15'=>[940,250],'SA16'=>[1062,250],
                        'SA17'=>[1153,250],'SA18'=>[1275,250],'SA19'=>[87,399],'SA20'=>[208,399],
                        'SA21'=>[300,399],'SA22'=>[421,399],'SA23'=>[513,399],'SA24'=>[635,399],
                        'SA25'=>[726,399],'SA26'=>[848,399],'SA27'=>[939,399],'SA28'=>[1061,399],
                        'SA29'=>[1153,399],'SA30'=>[1275,399],
                        'NSA01'=>[1460,146],'NSA02'=>[1520,146],'NSA03'=>[1584,146],'NSA04'=>[1643,146],
                        'NSA05'=>[1702,146],'NSA06'=>[1761,146],'NSA07'=>[1819,146],'NSA08'=>[1883,180],
                        'NSA09'=>[1883,293],'NSA10'=>[1520,328],'NSA11'=>[1584,328],'NSA12'=>[1643,328],
                        'NSA13'=>[1702,328],'NSA14'=>[1761,328],'NSA15'=>[1819,328],
                        'WR01'=>[115,627],'WR02'=>[115,784],'WR03'=>[115,941],
                        'RE01'=>[703,700],'RE02'=>[637,700],'RE03'=>[568,700],'RE04'=>[499,700],
                        'RE05'=>[431,700],'RE06'=>[363,700],'RE07'=>[296,700],
                        'RW01'=>[1647,700],'RW02'=>[1580,700],'RW03'=>[1513,700],'RW04'=>[1446,700],
                        'RW05'=>[1379,700],'RW06'=>[1307,700],'RW07'=>[1241,700],'RW08'=>[1173,700],
                        'RW09'=>[1107,700],'RW10'=>[1039,700],'RW11'=>[970,700]
                    ];
                    foreach($stands as $code => $pos) {
                        echo "<div class=\"stand-gradient absolute border-2 border-amc-dark-blue rounded-lg px-2 py-1 lg:px-3 lg:py-2 font-bold cursor-pointer select-none text-xs lg:text-sm text-center leading-tight text-white shadow-lg transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:bg-gradient-to-br hover:from-amc-light hover:to-amc-blue hover:text-amc-dark-blue hover:shadow-xl active:translate-y-0 active:scale-100\" data-stand=\"$code\" style=\"left:{$pos[0]}px; top:{$pos[1]}px;\" title=\"Click to edit $code\">$code</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <!-- Stand Modal -->
    <?php require __DIR__ . '/partials/stand-modal.php'; ?>


    <!-- Keep existing JavaScript but add mobile adaptations -->
    
    
    <script>
        window.apronConfig = {
            userRole: <?= json_encode($user_role) ?>,
            initialMovements: <?= json_encode($currentMovements); ?>,
            endpoints: {
                apron: 'api/apron',
                refreshApron: 'api/apron/status',
                recommend: 'api/apron/recommend'
            }
        };
    </script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
