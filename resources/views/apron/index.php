<?php
$title = 'AMC MONITORING SYSTEM';
$styles = [
    'assets/css/tailwind.css',
    'assets/css/styles.css?v=2.5',
    'assets/vendor/fontawesome/css/all.min.css'
];
$head = '';
$bodyClass = 'gradient-bg min-h-screen font-sans';
$scripts = [
    'assets/js/mobile-adaptations.js',
    'assets/js/amc-http.js?v=1.0',
    'assets/js/apron.js?v=2.4'
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
            <div class="mb-6 bg-amc-bg rounded-xl p-3 border border-amc-light shadow-lg">
                <div class="overflow-x-auto">
                    <table class="w-full border-separate" style="border-spacing: 0 4px;" id="roster-table">
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
                                <td colspan="3" class="text-right pt-2">
                                    <button id="save-roster" class="nav-btn-gradient text-white px-4 py-2 text-sm rounded-md font-semibold transition-all duration-300 hover:-translate-y-1 <?php if ($user_role==='viewer') echo 'bg-gray-400 cursor-not-allowed'; ?>">Save Roster</button>
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

                <!-- VIEW TOGGLE + FREEHAND -->
                <div style="position:absolute; top:10px; right:10px; z-index:50; display:flex; gap:6px; align-items:center; background:rgba(255,255,255,0.92); border-radius:8px; padding:5px 8px; box-shadow:0 2px 10px rgba(0,0,0,0.18); pointer-events:auto;">
                    <button id="toggle-view-a" onclick="switchApronView('a')" style="padding:4px 14px; font-size:11px; font-weight:700; border-radius:5px; border:none; cursor:pointer; background:#112D4E; color:white; transition:all 0.2s;">View A</button>
                    <button id="toggle-view-b" onclick="switchApronView('b')" style="padding:4px 14px; font-size:11px; font-weight:700; border-radius:5px; border:1px solid #ccc; cursor:pointer; background:white; color:#112D4E; transition:all 0.2s;">View B</button>
                    <button id="toggle-view-c" onclick="switchApronView('c')" style="padding:4px 14px; font-size:11px; font-weight:700; border-radius:5px; border:1px solid #ccc; cursor:pointer; background:white; color:#112D4E; transition:all 0.2s;">View C</button>
                    <?php if ($user_role !== 'viewer'): ?>
                    <button id="freehand-toggle" title="Event mode: drag plane icons anywhere on the apron. Deactivating snaps them back to their stands." style="padding:4px 14px; font-size:11px; font-weight:700; border-radius:5px; border:1px solid #ccc; cursor:pointer; background:white; color:#112D4E; transition:all 0.2s;">✋ Freehand: OFF</button>
                    <?php endif; ?>
                </div>

                <!-- LIVE CONNECTION INDICATOR -->
                <div id="realtime-indicator" style="position:absolute; top:10px; left:10px; z-index:50; display:flex; gap:6px; align-items:center; background:rgba(255,255,255,0.92); border-radius:8px; padding:5px 10px; box-shadow:0 2px 10px rgba(0,0,0,0.18); font-size:11px; font-weight:600; color:#334155;">
                    <span id="realtime-dot" style="width:9px; height:9px; border-radius:50%; background:#9ca3af; display:inline-block;"></span>
                    <span id="realtime-label">Connecting…</span>
                    <span id="realtime-updated" style="color:#64748b; font-weight:400;"></span>
                </div>

                <div class="relative" id="apron-container" style="width: 1920px; height: 1080px;">

                    <!-- ═══ Ground decorations (painted beneath stands & icons) ═══ -->

                    <!-- View C: classic AMS gray backdrop -->
                    <div class="apron-decor apron-view apron-view--c" style="left:0; top:0; width:2000px; height:1080px; background:#b9c0c7; display:none;"></div>
                    <div class="apron-section-label apron-view apron-view--c" style="left:640px; top:10px; display:none;">South Apron</div>
                    <div class="apron-section-label apron-view apron-view--c" style="left:1665px; top:455px; display:none;">New South Apron</div>
                    <div class="apron-section-label apron-view apron-view--c" style="left:1030px; top:1038px; display:none;">Main Apron</div>

                    <!-- View A: section labels only -->
                    <div class="apron-section-label apron-view apron-view--a" style="left:640px; top:10px;">South Apron</div>
                    <div class="apron-section-label apron-view apron-view--a" style="left:1665px; top:455px;">New South Apron</div>
                    <div class="apron-section-label apron-view apron-view--a" style="left:1030px; top:1038px;">Main Apron</div>

                    <!-- View B: real-layout markings — section zones, taxiways, runway -->
                    <!-- Section zones (tinted + dashed boundary) -->
                    <div class="apron-decor apron-zone apron-view apron-view--b" style="left:8px; top:60px; width:830px; height:392px; display:none;"></div>
                    <div class="apron-decor apron-zone apron-view apron-view--b" style="left:925px; top:160px; width:605px; height:292px; display:none;"></div>
                    <div class="apron-decor apron-zone apron-view apron-view--b" style="left:730px; top:655px; width:1182px; height:330px; display:none;"></div>

                    <!-- Taxiway connectors (aprons ↔ runway) -->
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:180px; top:448px; width:70px; height:36px; display:none;"></div>
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:560px; top:448px; width:70px; height:36px; display:none;"></div>
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:1200px; top:448px; width:70px; height:36px; display:none;"></div>
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:1030px; top:552px; width:80px; height:107px; display:none;"></div>
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:1560px; top:552px; width:80px; height:107px; display:none;"></div>
                    <!-- Diagonal taxiway toward the west remote / main apron -->
                    <div class="apron-decor apron-taxiway apron-view apron-view--b" style="left:775px; top:548px; width:210px; height:34px; transform:rotate(33deg); transform-origin:left top; display:none;"></div>

                    <!-- Runway -->
                    <div class="apron-decor apron-runway apron-view apron-view--b" style="left:0; top:480px; width:1920px; height:76px; display:none;">
                        <div class="apron-runway-centerline"></div>
                        <div class="apron-runway-text">Runway</div>
                    </div>

                    <!-- View B: section labels -->
                    <div class="apron-section-label apron-view apron-view--b" style="left:490px; top:3px; display:none;">South Apron</div>
                    <div class="apron-section-label apron-view apron-view--b" style="left:1220px; top:40px; display:none;">New South Apron</div>
                    <div class="apron-section-label apron-view apron-view--b" style="left:1420px; top:1035px; display:none;">Main Apron</div>

                    <?php
                    $baseStandClass = "stand-gradient absolute border-2 border-amc-dark-blue rounded-lg px-2 py-1 lg:px-3 lg:py-2 font-bold cursor-pointer select-none text-xs lg:text-sm text-center leading-tight text-white shadow-lg transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:bg-gradient-to-br hover:from-amc-light hover:to-amc-blue hover:text-amc-dark-blue hover:shadow-xl active:translate-y-0 active:scale-100";

                    // ── VIEW A: Current stylized layout ──────────────────────────────────
                    $standsViewA = [
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
                    foreach($standsViewA as $code => $pos) {
                        echo "<div class=\"{$baseStandClass} apron-view apron-view--a\" data-stand=\"$code\" style=\"left:{$pos[0]}px; top:{$pos[1]}px;\" title=\"Click to edit $code\">$code</div>";
                    }

                    // ── VIEW B: Real Halim Perdanakusuma layout ──────────────────────────
                    $standsViewB = [
                        // MAIN APRON
                        'A0'=>[1875,903],'A1'=>[1818,903],'A2'=>[1761,903],'A3'=>[1704,903],
                        'B1'=>[1647,903],'B2'=>[1590,903],'B3'=>[1533,903],'B4'=>[1476,903],
                        'B5'=>[1419,903],'B6'=>[1362,903],'B7'=>[1305,903],'B8'=>[1248,903],
                        'B9'=>[1191,903],'B10'=>[1134,903],'B11'=>[1077,903],'B12'=>[1020,903],'B13'=>[963,903],
                        // REMOTE WEST
                        'RW01'=>[1760,773],'RW02'=>[1705,773],'RW03'=>[1650,773],'RW04'=>[1595,773],
                        'RW05'=>[1540,773],'RW06'=>[1485,773],'RW07'=>[1430,773],'RW08'=>[1375,773],
                        'RW09'=>[1320,773],'RW10'=>[1265,773],'RW11'=>[1210,773],
                        // REMOTE EAST
                        'RE01'=>[1150,773],'RE02'=>[1100,773],'RE03'=>[1050,773],'RE04'=>[1000,773],
                        'RE05'=>[950,773],'RE06'=>[900,773],'RE07'=>[850,773],
                        // SOUTH APRON
                        'SA01'=>[42,93],'SA02'=>[185,93],'SA03'=>[328,93],'SA04'=>[463,93],
                        'SA05'=>[600,93],'SA06'=>[731,93],
                        'SA07'=>[0,195],'SA08'=>[80,195],'SA09'=>[141,195],'SA10'=>[228,195],
                        'SA11'=>[289,195],'SA12'=>[366,195],'SA13'=>[427,195],'SA14'=>[504,195],
                        'SA15'=>[565,195],'SA16'=>[642,195],'SA17'=>[703,195],'SA18'=>[777,195],
                        'SA19'=>[0,297],'SA20'=>[80,297],'SA21'=>[141,297],'SA22'=>[228,297],
                        'SA23'=>[289,297],'SA24'=>[366,297],'SA25'=>[427,297],'SA26'=>[504,297],
                        'SA27'=>[565,297],'SA28'=>[642,297],'SA29'=>[703,297],'SA30'=>[777,297],
                        // NEW SOUTH APRON
                        'NSA01'=>[943,195],'NSA02'=>[1015,195],'NSA03'=>[1087,195],'NSA04'=>[1159,195],
                        'NSA05'=>[1231,195],'NSA06'=>[1303,195],'NSA07'=>[1376,195],
                        'NSA08'=>[1448,231],'NSA09'=>[1448,307],
                        'NSA10'=>[1015,343],'NSA11'=>[1087,343],'NSA12'=>[1159,343],
                        'NSA13'=>[1231,343],'NSA14'=>[1303,343],'NSA15'=>[1376,343],
                        // WEST REMOTE
                        'WR01'=>[760,680],'WR02'=>[760,770],'WR03'=>[760,860]
                    ];
                    foreach($standsViewB as $code => $pos) {
                        echo "<div class=\"{$baseStandClass} apron-view apron-view--b\" data-stand=\"$code\" style=\"left:{$pos[0]}px; top:{$pos[1]}px; display:none;\" title=\"Click to edit $code\">$code</div>";
                    }

                    // ── VIEW C: Classic AMS style (same coordinates as View A) ───────────
                    foreach($standsViewA as $code => $pos) {
                        echo "<div class=\"stand-gradient stand-classic apron-view apron-view--c absolute cursor-pointer select-none text-center\" data-stand=\"$code\" style=\"left:{$pos[0]}px; top:{$pos[1]}px; display:none;\" title=\"Click to edit $code\">$code</div>";
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
            userRole: <?= json_encode($user_role, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            csrfToken: <?= json_encode($csrf_token ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            initialMovements: <?= json_encode($currentMovements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            endpoints: {
                apron: 'api/apron',
                refreshApron: 'api/apron/status',
                refreshMovements: 'api/apron/movements',
                stream: 'api/apron/stream',
                freehand: 'api/apron/freehand',
                recommend: 'api/apron/recommend'
            }
        };

        // ── Apron View Toggle ────────────────────────────────────────────────
        window.currentApronView = 'a';
        function switchApronView(view) {
            window.currentApronView = view;
            ['a', 'b', 'c'].forEach(function(v) {
                document.querySelectorAll('.apron-view--' + v).forEach(function(el) {
                    el.style.display = view === v ? '' : 'none';
                });
                var btn = document.getElementById('toggle-view-' + v);
                if (btn) {
                    if (view === v) {
                        btn.style.background = '#112D4E'; btn.style.color = 'white'; btn.style.border = 'none';
                    } else {
                        btn.style.background = 'white'; btn.style.color = '#112D4E'; btn.style.border = '1px solid #ccc';
                    }
                }
            });
            // View C restyles icons/labels via a container class
            var ctr = document.getElementById('apron-container');
            if (ctr) { ctr.classList.toggle('view-c', view === 'c'); }
            // Re-render all movement icons so they follow the newly active view's positions.
            // Dispatching a custom event keeps this decoupled from apron.js scope.
            document.dispatchEvent(new CustomEvent('apronViewSwitch'));
        }
    </script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
