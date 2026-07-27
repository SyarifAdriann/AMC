// ============================================================
//  AMC LIVE APRON MAP — Code.gs
//  Google Apps Script (server-side)
//  Paste this entire file into your GAS project's Code.gs
// ============================================================

// ── Constants ────────────────────────────────────────────────
var DATA_START_ROW = 18;   // Movement data begins at row 18 in all day sheets
var COL = {
  NO:           1,   // A
  REGISTRATION: 2,   // B
  TYPE:         3,   // C  (VLOOKUP — computed)
  ON_BLOCK:     4,   // D
  ON_BLOCK_CB:  5,   // E  (checkbox)
  OFF_BLOCK:    6,   // F
  OFF_BLOCK_CB: 7,   // G  (checkbox)
  STAND:        8,   // H
  FROM:         9,   // I  (VLOOKUP — computed)
  TO:           10,  // J  (VLOOKUP — computed)
  FLIGHT_ARR:   11,  // K
  FLIGHT_DEP:   12,  // L
  OPERATOR:     13,  // M  (VLOOKUP — computed)
  REMARKS:      14,  // N
  STATUS:       15   // O  (formula: =IF(ISBLANK(F),"RON",""))
};

// Valid day sheet names ('01' through '31')
var DAY_SHEETS = (function() {
  var d = {};
  for (var i = 1; i <= 31; i++) {
    d[i < 10 ? '0' + i : '' + i] = true;
  }
  return d;
})();


// ── 1. Menu Setup ────────────────────────────────────────────
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('AMC Tools')
    .addItem('🗺️ Build Native Map', 'buildNativeMap')
    .addItem('🔎 Build Search Tab', 'buildSearchTab')
    .addItem('📈 Build Dashboard', 'buildDashboard')
    .addSeparator()
    .addItem('🌙 Enable Daily RON Carry-Over', 'enableDailyRon')
    .addItem('📊 Generate Charter Report', 'generateCharterReport')
    .addSeparator()
    .addItem('ℹ️ About', 'showAbout')
    .addToUi();
}

function showAbout() {
  SpreadsheetApp.getUi().alert(
    'AMC Live Apron Map v1.0\n\n' +
    'Peta apron visual interaktif untuk Halim Perdanakusuma.\n' +
    'Membaca dan menulis langsung ke sheet pergerakan harian.\n\n' +
    'Kode warna (tab MAP):\n' +
    '• Stand biru = Kosong\n' +
    '• 🟡 Kuning = Rencana (belum on-block)\n' +
    '• 🔴 Merah = Terisi / RON (ada pesawat di stand)\n\n' +
    'Edit lewat panel samping tab MAP atau langsung di sheet harian.'
  );
}


// ── 2. Map Launcher ──────────────────────────────────────────
/**
 * Opens the apron map. If the script is deployed as a web app, the map
 * opens in its OWN BROWSER TAB (truly full screen — GAS dialogs are
 * hard-capped below full screen and cannot be made bigger). Falls back
 * to a maximized dialog when not deployed yet.
 * RON carry-over runs client-side (carryOverForDay) in both modes.
 */
function openApronMap() {
  var url = null;
  try { url = ScriptApp.getService().getUrl(); } catch (e) {}

  if (url) {
    // Web app deployed — open the full-screen tab and close the helper
    var opener = HtmlService.createHtmlOutput(
      '<style>body{font-family:sans-serif;font-size:14px}</style>' +
      '<p>Opening the apron map in a new tab...</p>' +
      '<p>If nothing happens (popup blocked), <a href="' + url + '" target="_blank" rel="noopener">click here</a>.</p>' +
      '<script>window.open(' + JSON.stringify(url) + ',"_blank");' +
      'setTimeout(function(){google.script.host.close();},1500);<\/script>'
    ).setWidth(360).setHeight(110);
    SpreadsheetApp.getUi().showModalDialog(opener, '🛫 AMC Live Apron Map');
    return;
  }

  // Not deployed — maximized dialog fallback
  var html = HtmlService.createHtmlOutputFromFile('ApronMap')
    .setWidth(4096)
    .setHeight(4096)
    .setTitle('AMC Live Apron Map');
  SpreadsheetApp.getUi().showModelessDialog(html, '🛫 AMC Live Apron Map');
}


// ── 2b. Full-Screen Mode (web app) ───────────────────────────
/**
 * GAS caps in-sheet dialogs below true full screen. For a real
 * full-window map, the script is also a web app: doGet() serves the
 * same ApronMap.html in its own browser tab.
 *
 * One-time setup: Deploy → New deployment → Web app
 *   (Execute as: Me · Access: anyone in your organisation / with link)
 */
function doGet() {
  return HtmlService.createHtmlOutputFromFile('ApronMap')
    .setTitle('AMC Live Apron Map');
}

function openApronMapFullScreen() {
  var url = ScriptApp.getService().getUrl();
  if (!url) {
    SpreadsheetApp.getUi().alert(
      'Full-screen mode needs a one-time web app deployment:\n\n' +
      '1. Extensions → Apps Script\n' +
      '2. Deploy → New deployment → type: Web app\n' +
      '3. Execute as: Me — Who has access: anyone with the link\n' +
      '4. Click Deploy, authorize, done.\n\n' +
      'Then this menu item opens the map in its own full browser tab.'
    );
    return;
  }
  var html = HtmlService.createHtmlOutput(
    '<style>body{font-family:sans-serif;font-size:14px}</style>' +
    '<p>Opening the apron map in a new tab...</p>' +
    '<p>If nothing happens (popup blocked), <a href="' + url + '" target="_blank" rel="noopener">click here</a>.</p>' +
    '<script>window.open(' + JSON.stringify(url) + ',"_blank");<\/script>'
  ).setWidth(360).setHeight(120);
  SpreadsheetApp.getUi().showModalDialog(html, 'Full Screen Apron Map');
}

/**
 * Runs the RON carry-over into the given day (from the previous day).
 * Called by the client when the map loads a day or the user switches
 * days — needed because the web app has no "open" hook like the menu.
 */
function carryOverForDay(sheetName) {
  if (!DAY_SHEETS[sheetName]) return;
  var dayNum = parseInt(sheetName, 10);
  if (dayNum <= 1) return;
  var prevDay = dayNum <= 10 ? '0' + (dayNum - 1) : '' + (dayNum - 1);
  try {
    carryOverRON(prevDay, sheetName);
  } catch (e) {
    Logger.log('RON carry-over error: ' + e.message);
  }
}


// ── 2c. Daily RON Carry-Over (time trigger) ──────────────────
/** Trigger handler: carries yesterday's RON aircraft into today's sheet. */
function dailyRonCarryOver() {
  var d = new Date().getDate();
  carryOverForDay((d < 10 ? '0' : '') + d);
}

/**
 * Menu: installs the daily trigger (idempotent — re-running replaces it).
 * Runs once between 00:00-01:00. Day 01 is skipped by design (the previous
 * day lives in last month's workbook).
 */
function enableDailyRon() {
  ScriptApp.getProjectTriggers().forEach(function(t) {
    if (t.getHandlerFunction() === 'dailyRonCarryOver') ScriptApp.deleteTrigger(t);
  });
  ScriptApp.newTrigger('dailyRonCarryOver').timeBased().everyDays(1).atHour(0).create();
  SpreadsheetApp.getUi().alert(
    'Carry-over RON harian AKTIF.\n\n' +
    'Setiap malam antara pukul 00:00-01:00, pesawat RON yang masih di ground ' +
    'otomatis disalin ke sheet hari baru.\n\n' +
    'PENTING: trigger TIDAK ikut tersalin saat workbook diduplikasi untuk ' +
    'bulan baru — jalankan menu ini lagi di file bulan barunya.');
}


// ── 3. Get Active Sheet Name ──────────────────────────────────
function getActiveSheetName() {
  try {
    return SpreadsheetApp.getActiveSpreadsheet().getActiveSheet().getName();
  } catch (e) {
    return '';   // web app context may have no active sheet
  }
}


// ── 4. Get All Stand Data (batch read) ───────────────────────
/**
 * Reads all movement rows from a day sheet in one batch.
 * Returns array of stand objects.
 *
 * @param {string} sheetName  e.g. '01', '15', '31'
 * @returns {Array} Array of stand data objects
 */
function getAllStandData(sheetName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);

  if (!sheet) {
    return { error: 'Sheet "' + sheetName + '" not found.' };
  }

  var lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) {
    return [];
  }

  // Read all data from row 18 to lastRow in one call (columns A–O = 1–15)
  var numRows = lastRow - DATA_START_ROW + 1;
  var range = sheet.getRange(DATA_START_ROW, 1, numRows, 15);
  var values = range.getValues();

  var result = [];

  for (var i = 0; i < values.length; i++) {
    var row = values[i];
    var standCode = row[COL.STAND - 1]; // col H (0-indexed: 7)

    // Skip rows with no stand code
    if (!standCode || standCode.toString().trim() === '') continue;

    var registration = safeStr(row[COL.REGISTRATION - 1]);
    var type         = safeStr(row[COL.TYPE - 1]);
    var onBlock      = safeStr(row[COL.ON_BLOCK - 1]);
    var offBlock     = safeStr(row[COL.OFF_BLOCK - 1]);
    var from         = safeStr(row[COL.FROM - 1]);
    var to           = safeStr(row[COL.TO - 1]);
    var flightArr    = safeStr(row[COL.FLIGHT_ARR - 1]);
    var flightDep    = safeStr(row[COL.FLIGHT_DEP - 1]);
    var operator     = safeStr(row[COL.OPERATOR - 1]);
    var remarks      = safeStr(row[COL.REMARKS - 1]);
    var status       = safeStr(row[COL.STATUS - 1]);

    result.push({
      stand:        standCode.toString().trim().toUpperCase(),
      registration: registration,
      type:         type,
      onBlock:      onBlock,
      offBlock:     offBlock,
      from:         from,
      to:           to,
      flightArr:    flightArr,
      flightDep:    flightDep,
      operator:     operator,
      remarks:      remarks,
      status:       status,   // 'RON' or ''
      row:          DATA_START_ROW + i
    });
  }

  return result;
}


// ── 5. Update Stand Field ────────────────────────────────────────────
/**
 * Writes movement fields to the day sheet.
 *
 * The day sheets are chronological movement LOGS — one stand can appear
 * on many rows per day. Writes are therefore ROW-ANCHORED:
 *   • row given  → verify col H at that row still matches the stand,
 *                  then write there (editing an existing movement).
 *   • row null   → NEW movement: append at the first empty template row
 *                  (col B and col H both blank) and write the stand code.
 * The resolved row is returned so the client can anchor follow-up saves.
 *
 * @param {string}      sheetName  e.g. '10'
 * @param {string}      stand      Stand code e.g. 'SA01'
 * @param {number|null} row        Anchor row from getAllStandData, or null
 * @param {Object}      fieldMap   { registration, onBlock, offBlock, flightArr, flightDep, remarks, [type, operator, from, to] }
 * @returns {Object}    { success: true, row: n, appended: bool } | { success: false, error }
 */
function updateStandField(sheetName, stand, row, fieldMap) {
  try {
    var ss    = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);

    if (!sheet) {
      var msg = 'Sheet "' + sheetName + '" tidak ditemukan. Tersedia: ' +
                ss.getSheets().map(function(s){ return s.getName(); }).join(', ');
      Logger.log('[AMC] ERROR: ' + msg);
      return { success: false, error: msg };
    }

    var target = stand.toString().trim().toUpperCase();
    var targetRow = null;
    var appended  = false;

    // ── Resolve target row ──────────────────────────────────────
    if (row && row >= DATA_START_ROW) {
      var hVal = safeStr(sheet.getRange(row, COL.STAND).getValue()).trim().toUpperCase();
      if (hVal === target) {
        targetRow = row;
      } else {
        // Rows shifted since the client last polled — re-locate the
        // latest (bottom-most) row for this stand.
        targetRow = findLatestStandRow(sheet, target);
        Logger.log('[AMC] Anchor row ' + row + ' no longer matches "' + target +
                   '" (found "' + hVal + '") — relocated to ' + targetRow);
      }
    }

    if (!targetRow) {
      // New movement — append at first empty template row
      targetRow = findFirstEmptyRow(sheet);
      if (!targetRow) {
        return { success: false, error: 'Baris kosong habis di sheet "' + sheetName + '" — tambah baris template.' };
      }
      sheet.getRange(targetRow, COL.STAND).setValue(target);
      appended = true;
    }

    Logger.log('[AMC] updateStandField: sheet=' + sheetName + ', stand=' + target +
               ', row=' + targetRow + (appended ? ' (appended)' : ''));

    // ── Write raw input fields ──────────────────────────────────
    if (fieldMap.hasOwnProperty('registration')) {
      sheet.getRange(targetRow, COL.REGISTRATION).setValue(fieldMap.registration);
    }
    if (fieldMap.hasOwnProperty('onBlock')) {
      sheet.getRange(targetRow, COL.ON_BLOCK).setValue(fieldMap.onBlock);
      // Checkbox E mirrors "on-block time captured" — tick only for real times
      sheet.getRange(targetRow, COL.ON_BLOCK_CB).setValue(isRealTime(fieldMap.onBlock));
    }
    if (fieldMap.hasOwnProperty('offBlock')) {
      sheet.getRange(targetRow, COL.OFF_BLOCK).setValue(fieldMap.offBlock);
      sheet.getRange(targetRow, COL.OFF_BLOCK_CB).setValue(isRealTime(fieldMap.offBlock));
    }
    if (fieldMap.hasOwnProperty('flightArr')) {
      sheet.getRange(targetRow, COL.FLIGHT_ARR).setValue(fieldMap.flightArr);
    }
    if (fieldMap.hasOwnProperty('flightDep')) {
      sheet.getRange(targetRow, COL.FLIGHT_DEP).setValue(fieldMap.flightDep);
    }
    if (fieldMap.hasOwnProperty('remarks')) {
      sheet.getRange(targetRow, COL.REMARKS).setValue(fieldMap.remarks);
    }

    // ── Manual overrides for VLOOKUP fields (only if explicitly passed) ─
    if (fieldMap.hasOwnProperty('type')) {
      sheet.getRange(targetRow, COL.TYPE).setValue(fieldMap.type);
    }
    if (fieldMap.hasOwnProperty('operator')) {
      sheet.getRange(targetRow, COL.OPERATOR).setValue(fieldMap.operator);
    }
    if (fieldMap.hasOwnProperty('from')) {
      sheet.getRange(targetRow, COL.FROM).setValue(fieldMap.from);
    }
    if (fieldMap.hasOwnProperty('to')) {
      sheet.getRange(targetRow, COL.TO).setValue(fieldMap.to);
    }

    // No flush() — Apps Script auto-commits when the execution ends, and the
    // extra forced round-trip only made every save slower.
    Logger.log('[AMC]   Write queued for row ' + targetRow + '.');

    return { success: true, row: targetRow, appended: appended };

  } catch(e) {
    Logger.log('[AMC] EXCEPTION in updateStandField: ' + e.message + ' | Stack: ' + e.stack);
    return { success: false, error: e.message };
  }
}


// ── 5b. Clear Movement Row ───────────────────────────────────────────
/**
 * Resets a movement row back to template state (clears B, D, F, H, K, L, N
 * and unticks the E/G checkboxes). Formula columns C, I, J, M, O are left
 * alone. Verifies the row still belongs to the stand before clearing.
 *
 * @returns {Object} { success: true } | { success: false, error }
 */
function clearMovementRow(sheetName, stand, row) {
  try {
    var ss    = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) return { success: false, error: 'Sheet "' + sheetName + '" tidak ditemukan.' };

    var target = stand.toString().trim().toUpperCase();
    if (!row || row < DATA_START_ROW) return { success: false, error: 'Baris tidak valid.' };

    var hVal = safeStr(sheet.getRange(row, COL.STAND).getValue()).trim().toUpperCase();
    if (hVal !== target) {
      return { success: false, error: 'Baris ' + row + ' berisi stand "' + hVal + '", bukan "' + target + '" — muat ulang peta.' };
    }

    sheet.getRange(row, COL.REGISTRATION).setValue('');
    sheet.getRange(row, COL.ON_BLOCK).setValue('');
    sheet.getRange(row, COL.ON_BLOCK_CB).setValue(false);
    sheet.getRange(row, COL.OFF_BLOCK).setValue('');
    sheet.getRange(row, COL.OFF_BLOCK_CB).setValue(false);
    sheet.getRange(row, COL.STAND).setValue('');
    sheet.getRange(row, COL.FLIGHT_ARR).setValue('');
    sheet.getRange(row, COL.FLIGHT_DEP).setValue('');
    sheet.getRange(row, COL.REMARKS).setValue('');

    Logger.log('[AMC] clearMovementRow: sheet=' + sheetName + ', stand=' + target + ', row=' + row);
    return { success: true };

  } catch(e) {
    Logger.log('[AMC] EXCEPTION in clearMovementRow: ' + e.message);
    return { success: false, error: e.message };
  }
}


// ── 6. RON Carry-Over ────────────────────────────────────────
/**
 * Copies RON aircraft from fromSheetName to toSheetName.
 * Only writes to rows in toSheetName that have NO registration yet.
 * Silent — no prompts.
 *
 * @param {string} fromSheetName  Previous day e.g. '09'
 * @param {string} toSheetName    Current day e.g. '10'
 */
function carryOverRON(fromSheetName, toSheetName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var fromSheet = ss.getSheetByName(fromSheetName);
  var toSheet   = ss.getSheetByName(toSheetName);

  if (!fromSheet || !toSheet) return;

  // Read all rows from previous day
  var prevData = getAllStandData(fromSheetName);
  if (!prevData || prevData.error) return;

  // True RONs: still on the ground at end of day — arrived (on-block set)
  // but never departed. Planned-only rows (no on-block) are NOT carried.
  var ronRows = prevData.filter(function(item) {
    return item.status === 'RON' &&
           item.registration && item.registration.trim() !== '' &&
           item.onBlock && item.onBlock.trim() !== '';
  });

  if (ronRows.length === 0) return;

  // Registrations already present in the target sheet (any row) — skip those
  // so re-opening the map doesn't duplicate carry-overs.
  var lastRow = toSheet.getLastRow();
  var existing = {};
  if (lastRow >= DATA_START_ROW) {
    var regs = toSheet.getRange(DATA_START_ROW, COL.REGISTRATION, lastRow - DATA_START_ROW + 1, 1).getValues();
    for (var r = 0; r < regs.length; r++) {
      var v = safeStr(regs[r][0]).trim().toUpperCase();
      if (v) existing[v] = true;
    }
  }

  var written = 0;
  for (var i = 0; i < ronRows.length; i++) {
    var ron = ronRows[i];
    if (existing[ron.registration.trim().toUpperCase()]) continue;

    // Append at the next empty template row (the day sheets are logs —
    // template rows have no stand code, so we always append)
    var targetRow = findFirstEmptyRow(toSheet);
    if (!targetRow) break;

    toSheet.getRange(targetRow, COL.REGISTRATION).setValue(ron.registration);
    toSheet.getRange(targetRow, COL.ON_BLOCK).setValue('-');   // matches manual practice for carried RONs
    toSheet.getRange(targetRow, COL.STAND).setValue(ron.stand);
    if (ron.remarks) {
      toSheet.getRange(targetRow, COL.REMARKS).setValue(ron.remarks);
    }
    SpreadsheetApp.flush(); // commit so findFirstEmptyRow advances next iteration
    written++;
  }

  Logger.log('RON carry-over complete: ' + written + ' of ' + ronRows.length +
             ' aircraft from sheet ' + fromSheetName + ' to ' + toSheetName);
}


// ── 7. Get Date for Sheet ─────────────────────────────────────
/**
 * Returns the date label for a day sheet (from row 8, col C).
 *
 * @param {string} sheetName
 * @returns {string} Formatted date string
 */
function getSheetDate(sheetName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);
  if (!sheet) return sheetName;

  var dateVal = sheet.getRange(8, 3).getValue(); // Row 8, Col C
  if (!dateVal) return sheetName;

  if (dateVal instanceof Date) {
    var d = dateVal;
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
  }
  return dateVal.toString();
}


// ── 8. Get Staff Roster ───────────────────────────────────────
/**
 * Returns staff roster from rows 9-10 for display in the map header.
 *
 * @param {string} sheetName
 * @returns {Object} { dayShift, nightShift }
 */
function getSheetRoster(sheetName) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(sheetName);
  if (!sheet) return { dayShift: '', nightShift: '' };

  // Row 10 has shift names in columns C and E (based on sheet structure)
  var row10 = sheet.getRange(10, 1, 1, 8).getValues()[0];
  return {
    dayShift:   safeStr(row10[2]),  // col C
    nightShift: safeStr(row10[4])   // col E
  };
}


// ── Helper: Find Latest Stand Row ─────────────────────────────
/**
 * Scans column H from the BOTTOM up to find the most recent
 * movement row for the given stand. (Day sheets are logs — a
 * stand can appear many times; the last row is the current one.)
 *
 * @param {Sheet}  sheet
 * @param {string} stand  Stand code e.g. 'SA01' (already uppercased)
 * @returns {number|null} Row number or null if not found
 */
function findLatestStandRow(sheet, stand) {
  var lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) return null;

  var numRows = lastRow - DATA_START_ROW + 1;
  var standValues = sheet.getRange(DATA_START_ROW, COL.STAND, numRows, 1).getValues();
  var target = stand.toString().trim().toUpperCase();

  for (var i = standValues.length - 1; i >= 0; i--) {
    if (safeStr(standValues[i][0]).trim().toUpperCase() === target) {
      return DATA_START_ROW + i;
    }
  }
  return null;
}


// ── Helper: Find First Empty Template Row ─────────────────────
/**
 * Returns the first row (>= DATA_START_ROW) where both the
 * registration (B) and stand (H) cells are blank — i.e. the next
 * free template row to append a new movement into.
 *
 * @param {Sheet} sheet
 * @returns {number|null} Row number or null if the template is full
 */
function findFirstEmptyRow(sheet) {
  // getLastRow() includes the pre-formatted template rows (they hold NO
  // numbers + formulas). Never append past them — those rows would have
  // no VLOOKUPs.
  var lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) return null;

  var numRows = lastRow - DATA_START_ROW + 1;
  var vals = sheet.getRange(DATA_START_ROW, 1, numRows, COL.STAND).getValues();

  for (var i = 0; i < vals.length; i++) {
    var reg   = safeStr(vals[i][COL.REGISTRATION - 1]).trim();
    var stand = safeStr(vals[i][COL.STAND - 1]).trim();
    if (!reg && !stand) return DATA_START_ROW + i;
  }
  return null;
}


// ── Helper: Real Time Check ───────────────────────────────────
/**
 * True when a block-time value contains an actual time (digits),
 * false for '', '-', 'EX RON' etc. Drives the E/G checkboxes,
 * which mark "time captured" in the sheet.
 */
function isRealTime(val) {
  return /\d/.test(safeStr(val));
}


// ── Helper: Safe String Conversion ───────────────────────────
function safeStr(val) {
  if (val === null || val === undefined) return '';
  if (val instanceof Date) {
    // Format times as HHMM
    var h = val.getHours().toString().padStart(2, '0');
    var m = val.getMinutes().toString().padStart(2, '0');
    return h + m;
  }
  return val.toString();
}


// ── 9. Get DB Lookup Data (for client-side autofill) ─────────
/**
 * Returns aircraft and flight lookup tables from the DB sheet.
 * Called once on dialog load so autofill can run client-side
 * without server round-trips (mirrors VLOOKUP behavior).
 *
 * @returns {Object} { aircraft: { 'PK-VCD': {type, operator} }, flights: { 'GA101': 'WIII' } }
 */
function getDBData() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var db = ss.getSheetByName('DB');
  if (!db) return { aircraft: {}, flights: {} };

  var lastRow = db.getLastRow();
  if (lastRow < 4) return { aircraft: {}, flights: {} };

  // Read all DB rows at once (cols A-F, rows 4 onward)
  var numRows = lastRow - 3;
  var values  = db.getRange(4, 1, numRows, 6).getValues();

  var aircraft = {};
  var flights  = {};

  for (var i = 0; i < values.length; i++) {
    var row = values[i];

    // Cols A-B: Flight No -> Route
    var flightNo = safeStr(row[0]).trim().toUpperCase();
    var route    = safeStr(row[1]).trim();
    if (flightNo && route) flights[flightNo] = route;

    // Cols D-F: Registration -> Type, Operator
    var reg      = safeStr(row[3]).trim().toUpperCase();
    var type     = safeStr(row[4]).trim();
    var operator = safeStr(row[5]).trim();
    if (reg) aircraft[reg] = { type: type, operator: operator };
  }

  return { aircraft: aircraft, flights: flights };
}


// ══════════════════════════════════════════════════════════════
//  10. CHARTER / CIP FLIGHT REPORT (PERGERAKAN format)
//  Menu: AMC Tools → Generate Charter Report.
//  Rebuilds the CHARTER FLIGHT tab from all day sheets, merging
//  each aircraft VISIT into one row (MASUK … KELUAR) exactly like
//  the unit's manual PERGERAKAN PESAWAT report.
//
//  Rules (validated against the manual May 2025 report — 158/172
//  rows matched exactly; residual diffs were manual-entry errors):
//    • Foreign-registered aircraft only (registration NOT PK-…).
//    • Operator not on the excluded list (DB!H: commercial, cargo,
//      TNI AU — military goes to VIP/VVIP, not charter).
//    • MASUK  = arrival row whose FROM is a 4-letter ICAO code.
//    • KELUAR = departure row whose TO  is a 4-letter ICAO code.
//    • Tows/repositions (TO/FROM = HGR or a stand) don't split a visit.
//    • '-' when the arrival/departure falls outside this month.
// ══════════════════════════════════════════════════════════════

// Operators excluded from the charter report. Seeded into DB!H so
// the unit can edit the list in-sheet.
var EXCLUDED_OPERATORS = [
  'GARUDA', 'BATIK AIR', 'CITILINK', 'PELITA', 'SUSI AIR',   // commercial
  'AIRNESIA', 'JAYAWIJAYA', 'TRI MG', 'TRIGANA',              // cargo
  'TNI AU'                                                    // military (VIP/VVIP)
];

// Remarks that mark a flight as VVIP/state — those visits belong in the
// VIP/VVIP report, not CHARTER FLIGHT. Matched as whole words against the
// REMARKS column; the list is seeded into DB!I and editable in-sheet.
// NOTE: minister-level flights (MENPORA, MENT ESDM, MENTAN, ...) ARE
// charter/CIP in the manual report — keep this list head-of-state only.
var VVIP_KEYWORDS = [
  'RI 1', 'RI 2', 'RI 3', 'RI 4', 'VVIP',
  'PM', 'PRESIDENT', 'PRESIDEN', 'RAJA', 'KING', 'QUEEN', 'SULTAN'
];

function buildVvipMatcher(keywords) {
  var parts = keywords.map(function(k) {
    return k.toUpperCase().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  });
  var re = new RegExp('\\b(' + parts.join('|') + ')\\b');
  return function(remark) { return re.test((remark || '').toUpperCase()); };
}

var CHARTER_SHEET_NAME = 'CHARTER FLIGHT';
var MONTHS_ID = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                 'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

function isForeignReg(reg) {
  return reg.replace(/[-\s]/g, '').indexOf('PK') !== 0;
}
function isAirportCode(s) {
  return /^[A-Z]{4}$/.test((s || '').trim());
}
function fmtTime(v) {
  if (v instanceof Date) {
    return ('0' + v.getHours()).slice(-2) + ':' + ('0' + v.getMinutes()).slice(-2);
  }
  var s = safeStrRaw(v).trim();
  var m = s.match(/^(\d{1,2})[:.]?(\d{2})$/);
  return m ? ('0' + m[1]).slice(-2) + ':' + m[2] : s;
}
function isTimeLike(v) {
  if (v instanceof Date) return true;
  return /\d/.test(safeStrRaw(v));
}
// Like safeStr but without the Date→HHMM conversion (we need raw values here)
function safeStrRaw(val) {
  if (val === null || val === undefined) return '';
  return val.toString();
}

function generateCharterReport() {
  var ui = SpreadsheetApp.getUi();
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  // ── Excluded-operator list from DB!H (seed if absent) ─────────
  var db = ss.getSheetByName('DB');
  if (!db) { ui.alert('Sheet DB tidak ditemukan — laporan charter tidak bisa dibuat.'); return; }
  if (!safeStr(db.getRange(4, 8).getValue()).trim()) {
    db.getRange(3, 8).setValue('EXCLUDED OPERATORS').setFontWeight('bold');
    db.getRange(4, 8, EXCLUDED_OPERATORS.length, 1)
      .setValues(EXCLUDED_OPERATORS.map(function(o){ return [o]; }));
  }
  var excl = {};
  var exclVals = db.getRange(4, 8, Math.max(db.getLastRow() - 3, 1), 1).getValues();
  for (var e = 0; e < exclVals.length; e++) {
    var ev = safeStr(exclVals[e][0]).trim().toUpperCase();
    if (ev) excl[ev] = true;
  }

  // ── VVIP keyword list from DB!I (seed if absent) ──────────────
  if (!safeStr(db.getRange(4, 9).getValue()).trim()) {
    db.getRange(3, 9).setValue('VVIP KEYWORDS').setFontWeight('bold');
    db.getRange(4, 9, VVIP_KEYWORDS.length, 1)
      .setValues(VVIP_KEYWORDS.map(function(k){ return [k]; }));
  }
  var kwVals = db.getRange(4, 9, Math.max(db.getLastRow() - 3, 1), 1).getValues();
  var keywords = [];
  for (var q = 0; q < kwVals.length; q++) {
    var kv = safeStr(kwVals[q][0]).trim();
    if (kv) keywords.push(kv);
  }
  var isVvip = buildVvipMatcher(keywords.length ? keywords : VVIP_KEYWORDS);

  // ── Walk all day sheets chronologically, chaining visits ──────
  var openV  = {};    // reg -> open visit
  var visits = [];
  var monthDate = null;

  for (var d = 1; d <= 31; d++) {
    var name  = d < 10 ? '0' + d : '' + d;
    var sheet = ss.getSheetByName(name);
    if (!sheet) continue;

    var dateVal = sheet.getRange(8, 3).getValue();
    var date    = (dateVal instanceof Date) ? dateVal : null;
    if (!monthDate && date) monthDate = date;

    var lastRow = sheet.getLastRow();
    if (lastRow < DATA_START_ROW) continue;
    var vals = sheet.getRange(DATA_START_ROW, 1, lastRow - DATA_START_ROW + 1, 15).getValues();

    for (var i = 0; i < vals.length; i++) {
      var row = vals[i];
      var reg = safeStr(row[COL.REGISTRATION - 1]).trim().toUpperCase();
      if (!reg || !isForeignReg(reg)) continue;

      var op = safeStr(row[COL.OPERATOR - 1]).trim().toUpperCase();
      if (excl[op]) continue;

      var typ  = safeStr(row[COL.TYPE - 1]).trim();
      var onb  = row[COL.ON_BLOCK - 1];
      var offb = row[COL.OFF_BLOCK - 1];
      var frm  = safeStr(row[COL.FROM - 1]).trim().toUpperCase();
      var to   = safeStr(row[COL.TO - 1]).trim().toUpperCase();
      var rem  = safeStr(row[COL.REMARKS - 1]).trim();

      var v = openV[reg];

      if (isTimeLike(onb)) {
        if (isAirportCode(frm)) {
          // Real airport arrival — close any dangling visit, start a new one
          if (v) { visits.push(v); }
          v = { op: op, reg: reg, typ: typ, mdate: date, mtime: fmtTime(onb), ex: frm,
                kdate: null, ktime: '', to: '' };
          openV[reg] = v;
        } else if (!v) {
          // Reposition/tow on-block with no open visit — aircraft was
          // already on the ground (carried from a previous month)
          v = { op: op, reg: reg, typ: typ, mdate: null, mtime: '', ex: '',
                kdate: null, ktime: '', to: '' };
          openV[reg] = v;
        }
      } else if (!v) {
        // Carry-over / first sighting without an on-block time
        v = { op: op, reg: reg, typ: typ, mdate: null, mtime: '', ex: '',
              kdate: null, ktime: '', to: '' };
        openV[reg] = v;
      }

      if (!v.op  && op)  v.op  = op;
      if (!v.typ && typ) v.typ = typ;
      if (rem && isVvip(rem)) v.vvip = true;   // state flight → VIP/VVIP report

      if (isTimeLike(offb) && isAirportCode(to)) {
        // Real airport departure — close the visit
        v.kdate = date; v.ktime = fmtTime(offb); v.to = to;
        visits.push(v);
        delete openV[reg];
      }
    }
  }

  // Aircraft still on the ground at month end
  for (var r in openV) visits.push(openV[r]);

  // Keep only non-VVIP visits with at least one in-month event
  var rows = visits.filter(function(v) { return (v.mdate || v.kdate) && !v.vvip; });

  // Chronological by first in-month event
  rows.sort(function(a, b) {
    var ka = (a.mdate || a.kdate).getTime();
    var kb = (b.mdate || b.kdate).getTime();
    if (ka !== kb) return ka - kb;
    return (a.mtime || a.ktime) < (b.mtime || b.ktime) ? -1 : 1;
  });

  writeCharterSheet(ss, rows, monthDate);
  ui.alert('Laporan charter selesai: ' + rows.length + ' kunjungan.\n\n' +
           'Jalankan "Generate Charter Report" lagi kapan saja untuk memperbarui.\n' +
           'Daftar operator yang dikecualikan ada di kolom H sheet DB (bisa diedit).');
}

/** Writes and formats the CHARTER FLIGHT tab in the PERGERAKAN layout. */
function writeCharterSheet(ss, rows, monthDate) {
  var old = ss.getSheetByName(CHARTER_SHEET_NAME);
  if (old) ss.deleteSheet(old);
  var sh = ss.insertSheet(CHARTER_SHEET_NAME, ss.getSheets().length);

  // ── Titles ────────────────────────────────────────────────────
  var monthLbl = monthDate
    ? 'BULAN ' + MONTHS_ID[monthDate.getMonth()] + ' ' + monthDate.getFullYear()
    : '';
  sh.getRange(1, 1, 1, 12).merge().setValue('CHARTER FLIGHT / CIP FLIGHT')
    .setFontWeight('bold').setFontSize(13);
  sh.getRange(2, 1, 1, 12).merge().setValue(monthLbl)
    .setFontWeight('bold').setFontSize(12);

  // ── Two-tier header (rows 4-5), same fields as the manual ─────
  sh.getRange(4, 1, 2, 1).merge().setValue('NO');
  sh.getRange(4, 2, 2, 1).merge().setValue('OPERATOR');
  sh.getRange(4, 3, 2, 1).merge().setValue('REG');
  sh.getRange(4, 4, 2, 1).merge().setValue('TYPE AC');
  sh.getRange(4, 5, 1, 4).merge().setValue('MASUK');
  sh.getRange(4, 9, 1, 4).merge().setValue('KELUAR');
  sh.getRange(5, 5).setValue('TANGGAL MASUK');
  sh.getRange(5, 6).setValue('TIME');
  sh.getRange(5, 7, 1, 2).merge().setValue('ARRIVAL');
  sh.getRange(5, 9).setValue('TANGGAL KELUAR');
  sh.getRange(5, 10).setValue('TIME');
  sh.getRange(5, 11, 1, 2).merge().setValue('DEPARTURE');

  sh.getRange(4, 1, 2, 12)
    .setFontWeight('bold').setBackground('#D9D9D9')
    .setHorizontalAlignment('center').setVerticalAlignment('middle')
    .setWrap(true);
  sh.setFrozenRows(5);

  // ── Data ──────────────────────────────────────────────────────
  if (rows.length) {
    // Time columns as text so 07:47 doesn't render as a duration/serial
    sh.getRange(6, 6, rows.length, 1).setNumberFormat('@');
    sh.getRange(6, 10, rows.length, 1).setNumberFormat('@');
    sh.getRange(6, 5, rows.length, 1).setNumberFormat('dd-mm-yyyy');
    sh.getRange(6, 9, rows.length, 1).setNumberFormat('dd-mm-yyyy');

    var out = rows.map(function(v, i) {
      return [
        i + 1, v.op || '', v.reg, v.typ || '',
        v.mdate ? v.mdate : '-', v.mdate ? v.mtime : '-',
        v.mdate ? 'EX' : '-',    v.mdate ? v.ex : '-',
        v.kdate ? v.kdate : '-', v.kdate ? v.ktime : '-',
        v.kdate ? 'TO' : '-',    v.kdate ? v.to : '-'
      ];
    });
    sh.getRange(6, 1, out.length, 12).setValues(out);
  }

  // ── Formatting: borders, centered, autofit ────────────────────
  var full = sh.getRange(4, 1, Math.max(rows.length, 1) + 2, 12);
  full.setBorder(true, true, true, true, true, true);
  full.setHorizontalAlignment('center').setVerticalAlignment('middle');
  sh.autoResizeColumns(1, 12);
  // A little breathing room on top of autofit
  for (var c = 1; c <= 12; c++) {
    sh.setColumnWidth(c, sh.getColumnWidth(c) + 14);
  }
}


// ══════════════════════════════════════════════════════════════
//  11. NATIVE IN-SHEET APRON MAP (quota-free)
//  Menu: AMC Tools → Build Native Map. Runs ONCE to lay out a
//  cell-grid MAP tab; afterwards NO script runs — colors, labels
//  and counts are live formulas + conditional formatting, synced
//  by Sheets' own realtime collaboration. Unlimited operators,
//  24/7, zero Apps Script quota.
//
//  Interaction: pick the day in the dropdown (D2). Click a stand,
//  then click the link chip → jumps to that stand's movement row
//  in the day sheet (or the first empty row for a free stand) to
//  edit with the sheet's own VLOOKUP autofill.
// ══════════════════════════════════════════════════════════════

var MAP_SHEET_NAME = 'MAP';
var MAP_SCALE      = 20;   // source-coord units per grid cell (coords are 1920x1080)
var CELL_PX        = 15;   // px per cell column — map ≈1470px wide, leaving room for the side panel
var CELL_PY        = 12;   // px per cell row — map ≈540px tall so it fits on screen with no vertical scroll
var MAP_Y_TRIM     = 5;    // empty grid rows shaved off the top of the source layout
var MAP_ROW_OFFSET = 4;    // map area starts below the control rows
var MAP_BLOCK_COLS = 3;    // each stand = 3 cols x 3 rows (code / reg / arr flight)
var MAP_BLOCK_ROWS = 3;    // 3 x 12px rows = 36px — still shorter than the original 40px blocks

// View A coordinates (same as the HTML map)
var MAP_COORDS = {
  'A0':[1785,923],'A1':[1712,923],'A2':[1621,923],'A3':[1518,923],
  'B1':[1414,923],'B2':[1321,923],'B3':[1229,923],'B4':[1136,923],
  'B5':[1043,923],'B6':[950,923],'B7':[859,923],'B8':[768,923],
  'B9':[673,923],'B10':[577,923],'B11':[483,923],'B12':[394,923],'B13':[306,923],
  'SA01':[152,125],'SA02':[365,125],'SA03':[578,125],'SA04':[791,125],
  'SA05':[1004,125],'SA06':[1218,125],'SA07':[87,250],'SA08':[210,250],
  'SA09':[300,250],'SA10':[423,250],'SA11':[514,250],'SA12':[635,250],
  'SA13':[726,250],'SA14':[849,250],'SA15':[940,250],'SA16':[1062,250],
  'SA17':[1153,250],'SA18':[1275,250],'SA19':[87,399],'SA20':[208,399],
  'SA21':[300,399],'SA22':[421,399],'SA23':[513,399],'SA24':[635,399],
  'SA25':[726,399],'SA26':[848,399],'SA27':[939,399],'SA28':[1061,399],
  'SA29':[1153,399],'SA30':[1275,399],
  'NSA01':[1460,146],'NSA02':[1520,146],'NSA03':[1584,146],'NSA04':[1643,146],
  'NSA05':[1702,146],'NSA06':[1761,146],'NSA07':[1819,146],'NSA08':[1883,180],
  'NSA09':[1883,293],'NSA10':[1520,328],'NSA11':[1584,328],'NSA12':[1643,328],
  'NSA13':[1702,328],'NSA14':[1761,328],'NSA15':[1819,328],
  'WR01':[115,627],'WR02':[115,784],'WR03':[115,941],
  'RE01':[703,700],'RE02':[637,700],'RE03':[568,700],'RE04':[499,700],
  'RE05':[431,700],'RE06':[363,700],'RE07':[296,700],
  'RW01':[1647,700],'RW02':[1580,700],'RW03':[1513,700],'RW04':[1446,700],
  'RW05':[1379,700],'RW06':[1307,700],'RW07':[1241,700],'RW08':[1173,700],
  'RW09':[1107,700],'RW10':[1039,700],'RW11':[970,700]
};

function buildNativeMap() { buildMapSheet(MAP_SHEET_NAME, MAP_COORDS, 'VIEW A'); }

/** Core map-tab builder: grid, engine, edit panel, stand blocks. */
function buildMapSheet(name, coords, title) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  var old = ss.getSheetByName(name);
  if (old) {
    var resp = ui.alert('Sheet ' + name + ' sudah ada. Bangun ulang?', ui.ButtonSet.YES_NO);
    if (resp !== ui.Button.YES) return;
    ss.deleteSheet(old);
  }
  var sh = ss.insertSheet(name, 0);

  var codes = Object.keys(coords);
  var n     = codes.length;                 // 83
  var lastCodeRow = 1 + n;                  // helper rows 2..(n+1)

  // ── Grid geometry ─────────────────────────────────────────────
  var mapCols = 98, mapRows = 45;   // 45: top dead-space is trimmed via MAP_Y_TRIM
  if (sh.getMaxColumns() < 150) sh.insertColumnsAfter(sh.getMaxColumns(), 150 - sh.getMaxColumns());
  if (sh.getMaxRows() < 355) {      // the staging spill reaches down to row 351
    sh.insertRowsAfter(sh.getMaxRows(), 355 - sh.getMaxRows());
  }
  sh.setColumnWidths(1, mapCols, CELL_PX);
  sh.setRowHeights(MAP_ROW_OFFSET, mapRows, CELL_PY);
  sh.setRowHeight(1, 30);
  sh.setRowHeights(2, 2, 24);
  sh.setFrozenRows(3);
  sh.setHiddenGridlines(true);

  // Apron background
  sh.getRange(MAP_ROW_OFFSET, 1, mapRows, mapCols).setBackground('#dbe7f4');

  // ── Header / controls ─────────────────────────────────────────
  sh.getRange(1, 1, 1, mapCols).merge().setValue('🛫  AMC LIVE APRON MAP — ' + title)
    .setFontWeight('bold').setFontSize(13).setFontColor('white')
    .setBackground('#112D4E').setHorizontalAlignment('left').setVerticalAlignment('middle');

  sh.getRange(2, 2, 1, 2).merge().setValue('TGL:')
    .setFontWeight('bold').setHorizontalAlignment('right');
  var dayCell = sh.getRange(2, 4, 1, 3).merge();
  var dayList = [];
  for (var d = 1; d <= 31; d++) dayList.push(d < 10 ? '0' + d : '' + d);
  dayCell.setNumberFormat('@')
    .setDataValidation(SpreadsheetApp.newDataValidation()
      .requireValueInList(dayList, true).setAllowInvalid(false).build())
    .setValue(dayList[Math.min(new Date().getDate(), 31) - 1])
    .setFontWeight('bold').setHorizontalAlignment('center')
    .setBackground('#F9F7F7').setBorder(true, true, true, true, false, false);

  sh.getRange(2, 8, 1, 10).merge()
    .setFormula('=IFERROR("📅 "&TEXT(INDIRECT("\'"&$D$2&"\'!$C$8"),"ddd, dd mmm yyyy"),"—")')
    .setFontWeight('bold');

  // Live counters
  sh.getRange(2, 20, 1, 7).merge()
    .setFormula('="🟦 KOSONG: "&(COUNTIF($DF$2:$DF$' + lastCodeRow + ',"av")+COUNTIF($DF$2:$DF$' + lastCodeRow + ',"de"))')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#dcfce7');
  sh.getRange(2, 28, 1, 7).merge()
    .setFormula('="🔴 TERISI: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"oc")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#fee2e2');
  sh.getRange(2, 36, 1, 7).merge()
    .setFormula('="🌙 RON: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"ro")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#fef9c3');
  sh.getRange(2, 44, 1, 7).merge()
    .setFormula('="🟡 RENCANA: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"pl")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#ffedd5');

  sh.getRange(3, 2, 1, 60).merge()
    .setValue('Klik stand → panel samping langsung menampilkan datanya. Ketik di kolom kuning — setiap isian langsung tersimpan ke sheet harian. Warna & hitungan diperbarui otomatis.')
    .setFontStyle('italic').setFontColor('#555555');

  // ── Hidden formula engine (columns DA..DS = 105..123) ─────────
  var C_CODE = 105, C_GNAME = 113, C_EMPTY = 115, C_GIDA = 116;

  sh.getRange(1, C_CODE).setValue('code');
  sh.getRange(2, C_CODE, n, 1).setValues(codes.map(function(c){ return [c]; }));

  var R = '$DA$2:$DA$' + lastCodeRow;
  var dayRef = '"\'"&$D$2&"\'!';

  // ── Staging spill (cols ED..ER = 134..148): ONE volatile INDIRECT pulls
  // the whole day block A18:O367; every other engine formula reads this
  // local spill. INDIRECT recalcs on every edit anywhere in the workbook —
  // one copy instead of thirteen is what keeps the map refresh fast.
  sh.getRange(1, 134).setValue('day A18:O367');
  sh.getRange(2, 134).setFormula('=IFERROR(INDIRECT(' + dayRef + '$A$18:$O$367"),"")');

  sh.getRange(2, C_CODE + 1).setFormula(   // DB: latest row index per stand (0 = none)
    // XMATCH bottom-up (match 0, search -1) over spill col H (EK) — native
    // range lookup, case-insensitive, no array-broadcast needed.
    // ponytail: no TRIM on col H — a stand code typed with stray spaces won't match
    '=MAP(' + R + ',LAMBDA(c,IFERROR(XMATCH(c,$EK$2:$EK$351,0,-1),0)))');
  sh.getRange(2, C_CODE + 2).setFormula(   // DC: registration (spill col B = EE)
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EE$2:$EE$351,i)))))');
  // On/off block: real time values must render hh:mm, not the raw serial
  // (TO_TEXT(0.576…) is what showed "0.5763888889" in the panel)
  sh.getRange(2, C_CODE + 3).setFormula(   // DD: on-block (D = EG)
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",LET(v,INDEX($EG$2:$EG$351,i),IF(ISNUMBER(v),TEXT(v,"hh:mm"),TO_TEXT(v))))))');
  sh.getRange(2, C_CODE + 4).setFormula(   // DE: off-block (F = EI)
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",LET(v,INDEX($EI$2:$EI$351,i),IF(ISNUMBER(v),TEXT(v,"hh:mm"),TO_TEXT(v))))))');
  sh.getRange(2, C_CODE + 5).setFormula(   // DF: status av/pl/oc/ro/de
    '=MAP($DC$2:$DC$' + lastCodeRow + ',$DD$2:$DD$' + lastCodeRow + ',$DE$2:$DE$' + lastCodeRow +
    ',LAMBDA(rg,onv,offv,IF(TRIM(rg)="","av",IF(TRIM(offv)<>"","de",' +
    'LET(o,TRIM(UPPER(onv)),IF(o="","pl",IF(OR(o="-",REGEXMATCH(o,"RON"),REGEXMATCH(o,"\\(")),"ro","oc")))))))');
  sh.getRange(2, C_CODE + 6).setFormula(   // DG: display text with status marker
    // RON renders red like any occupied stand (distinct gold was confusing);
    // the RON counter and the panel status line still identify it.
    // Line 3 = arrival flight number, when present (DQ).
    '=MAP(' + R + ',$DC$2:$DC$' + lastCodeRow + ',$DF$2:$DF$' + lastCodeRow + ',$DQ$2:$DQ$' + lastCodeRow +
    ',LAMBDA(c,rg,st,fa,IF(OR(st="av",st="de"),c,' +
    'IF(st="pl","🟡 ","🔴 ")&c&CHAR(10)&rg&IF(fa="","",CHAR(10)&fa))))');

  // DM/DN/DO/DP (117-120): type / from / to / operator at the latest row —
  // live read-only fields for the edit panel (spill cols C=EF, I=EL, J=EM, M=EP)
  sh.getRange(2, 117).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EF$2:$EF$351,i)))))');
  sh.getRange(2, 118).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EL$2:$EL$351,i)))))');
  sh.getRange(2, 119).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EM$2:$EM$351,i)))))');
  sh.getRange(2, 120).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EP$2:$EP$351,i)))))');

  // DQ/DR/DS (121-123): flight arr / flight dep / remarks at the latest row —
  // live sources for the side panel's input fields (spill cols K=EN, L=EO, N=EQ)
  sh.getRange(2, 121).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EN$2:$EN$351,i)))))');
  sh.getRange(2, 122).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EO$2:$EO$351,i)))))');
  sh.getRange(2, 123).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($EQ$2:$EQ$351,i)))))');

  // DK: first empty template row (link target for free stands)
  sh.getRange(2, C_EMPTY).setFormula(
    '=IFERROR(MIN(FILTER(SEQUENCE(350)+17,' +
    'ARRAYFORMULA(TRIM(IFERROR(TO_TEXT($EE$2:$EE$351),"x"))=""),' +
    'ARRAYFORMULA(TRIM(IFERROR(TO_TEXT($EK$2:$EK$351),"x"))=""))),18)');

  // DI/DJ: day-sheet GID table, DL: active gid
  var gidRows = [];
  for (var g = 1; g <= 31; g++) {
    var nm = g < 10 ? '0' + g : '' + g;
    var ds = ss.getSheetByName(nm);
    if (ds) gidRows.push([nm, ds.getSheetId()]);
  }
  if (gidRows.length) sh.getRange(2, C_GNAME, gidRows.length, 2).setValues(gidRows);
  sh.getRange(2, C_GIDA).setFormula(
    '=IFERROR(VLOOKUP($D$2,$DI$2:$DJ$' + (1 + gidRows.length) + ',2,0),"")');

  // ── EDIT PANEL ──────────────────────────────────────────────
  buildEditPanel(sh, codes, lastCodeRow);

  // ── Place the stands ──────────────────────────────────────────
  var items = codes.map(function(c) {
    return { code: c,
             col: Math.round(coords[c][0] / MAP_SCALE) + 1,
             row: Math.round(coords[c][1] / MAP_SCALE) + MAP_ROW_OFFSET - MAP_Y_TRIM };
  });
  items.sort(function(a, b) { return a.row - b.row || a.col - b.col; });

  var occ = {};

  items.forEach(function(it) {
    // greedy collision avoidance (verified offline: no shifts needed for View A)
    var tries = 0;
    while (tries < 10) {
      var clash = false;
      for (var r = it.row; r < it.row + MAP_BLOCK_ROWS && !clash; r++)
        for (var c = it.col; c < it.col + MAP_BLOCK_COLS; c++)
          if (occ[r + '_' + c]) { clash = true; break; }
      if (!clash) break;
      tries++;
      if (tries <= 4) it.col++; else { it.row++; it.col -= 4; }
    }
    for (var r2 = it.row; r2 < it.row + MAP_BLOCK_ROWS; r2++)
      for (var c2 = it.col; c2 < it.col + MAP_BLOCK_COLS; c2++)
        occ[r2 + '_' + c2] = true;

    var rng = sh.getRange(it.row, it.col, MAP_BLOCK_ROWS, MAP_BLOCK_COLS);
    rng.merge()
       .setBackground('#35619c').setFontColor('white')
       .setFontWeight('bold').setFontSize(8)
       .setHorizontalAlignment('center').setVerticalAlignment('middle')
       .setWrapStrategy(SpreadsheetApp.WrapStrategy.WRAP)
       .setBorder(true, true, true, true, false, false, '#112D4E', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

    rng.setFormula(
      '=LET(i,XLOOKUP("' + it.code + '",' + R + ',$DB$2:$DB$' + lastCodeRow + '),' +
      't,XLOOKUP("' + it.code + '",' + R + ',$DG$2:$DG$' + lastCodeRow + '),' +
      'r,IF(i>0,i+17,$DK$2),' +
      'IF($DL$2="",t,HYPERLINK("#gid="&$DL$2&"&range=B"&r,t)))');
  });

  // ── Conditional formatting: status colors from the marker emoji ─
  var mapRange = sh.getRange(MAP_ROW_OFFSET, 1, mapRows, mapCols);
  var rules = [
    SpreadsheetApp.newConditionalFormatRule().whenTextContains('🔴')
      .setBackground('#c62828').setFontColor('#ffffff').setRanges([mapRange]).build(),
    SpreadsheetApp.newConditionalFormatRule().whenTextContains('🟡')
      .setBackground('#f59e0b').setFontColor('#111111').setRanges([mapRange]).build()
  ];
  sh.setConditionalFormatRules(rules);

  // Hide the gap + engine (99-124) and the staging spill etc. (133-150)
  sh.hideColumns(mapCols + 1, PANEL_COL0 - mapCols - 1);
  sh.hideColumns(PANEL_COL0 + 8, 150 - PANEL_COL0 - 7);

  ui.alert('Sheet ' + name + ' selesai dibangun.\n\n' +
    '• Tampilan murni formula — nol kuota walau dibuka 24 jam.\n' +
    '• Klik stand (atau pilih di panel samping) — datanya langsung tampil di ' +
    'panel. Ketik di kolom kuning — setiap isian langsung tersimpan ke sheet harian.\n' +
    '• Pilih tanggal di dropdown (sel D2). Bangun ulang kapan saja dari AMC Tools.');
}


// ══════════════════════════════════════════════════════════════
//  12. SIDE EDIT PANEL + EVENT TRIGGERS
//  The panel sits to the RIGHT of the map (cols 125-132), always
//  visible. Every panel field is a LIVE FORMULA over the engine —
//  clicking a stand shows its current movement instantly, with
//  zero script latency, and the fields keep tracking the day sheet
//  as others edit it. Typing into a yellow input fires onEdit,
//  which commits the value to the day sheet and then restores the
//  live formula, so the cell goes right back to tracking the sheet.
//  Script runs ONLY on actual edits — never in the background.
// ══════════════════════════════════════════════════════════════

// Fixed panel cell addresses — builder and triggers must agree.
var PANEL_COL0   = 125;                // first visible panel column (DU = labels)
var PANEL_DAY    = { r: 2,  c: 4   };  // D2  (day dropdown)
var PANEL_STAND  = { r: 6,  c: 126 };  // DV6 (stand dropdown)
var PANEL_CLR    = { r: 32, c: 126 };  // clear checkbox
var PANEL_ANCHOR = { r: 2,  c: 124 };  // DT2 (hidden row anchor for follow-up saves)
// input cell (row_col of the merged top-left) -> updateStandField field key
var PANEL_FIELDS = {
  '12_126': 'registration',
  '16_126': 'onBlock',   '18_126': 'offBlock',
  '20_126': 'flightArr', '22_126': 'flightDep',
  '30_126': 'remarks'
};

/** Live formulas for the panel input cells (set at build, restored after every edit). */
function panelFormulas(lastCodeRow) {
  var S = '$DV$6';
  function live(col) {
    return '=IF(' + S + '="","",IFERROR(XLOOKUP(' + S + ',$DA$2:$DA$' + lastCodeRow +
           ',$' + col + '$2:$' + col + '$' + lastCodeRow + '),""))';
  }
  return {
    registration: live('DC'), onBlock: live('DD'), offBlock: live('DE'),
    flightArr: live('DQ'), flightDep: live('DR'), remarks: live('DS')
  };
}

/** Lays out the fixed side panel (called by buildNativeMap). */
function buildEditPanel(sh, codes, lastCodeRow) {
  var P = PANEL_COL0;   // label column; values merge P+1..P+7
  var S = '$DV$6';
  function X(col) {
    return 'IFERROR(XLOOKUP(' + S + ',$DA$2:$DA$' + lastCodeRow +
           ',$' + col + '$2:$' + col + '$' + lastCodeRow + '),"")';
  }

  sh.setColumnWidth(P, 66);
  sh.setColumnWidths(P + 1, 7, 38);

  // Card background + frame + header
  sh.getRange(4, P, 30, 8).setBackground('#ffffff')
    .setBorder(true, true, true, true, false, false, '#112D4E', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);
  sh.getRange(4, P, 2, 8).merge().setValue('✏  PANEL EDIT')
    .setFontWeight('bold').setFontSize(10).setFontColor('white').setBackground('#112D4E')
    .setHorizontalAlignment('left').setVerticalAlignment('middle');

  // Each panel line spans 2 of the CELL_PY map rows (~24px tall).
  function label(r, txt) {
    sh.getRange(r, P, 2, 1).merge().setValue(txt)
      .setFontWeight('bold').setFontSize(8).setFontColor('#4b5563')
      .setHorizontalAlignment('right').setVerticalAlignment('middle');
  }
  function input(r, formula) {
    return sh.getRange(r, P + 1, 2, 7).merge().setBackground('#FFFDE7')
      .setFormula(formula)
      .setFontSize(9).setHorizontalAlignment('center').setVerticalAlignment('middle')
      .setBorder(true, true, true, true, false, false, '#9aa5b1', SpreadsheetApp.BorderStyle.SOLID);
  }
  function display(r, formula) {
    sh.getRange(r, P + 1, 2, 7).merge().setFormula(formula)
      .setBackground('#EEF3FA').setFontStyle('italic').setFontSize(9)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');
  }

  var F = panelFormulas(lastCodeRow);

  label(6, 'STAND:');
  sh.getRange(6, P + 1, 2, 7).merge().setBackground('#FFFDE7')
    .setFontWeight('bold').setFontSize(10)
    .setHorizontalAlignment('center').setVerticalAlignment('middle')
    .setBorder(true, true, true, true, false, false, '#9aa5b1', SpreadsheetApp.BorderStyle.SOLID)
    .setDataValidation(SpreadsheetApp.newDataValidation()
      .requireValueInList(codes.slice().sort(), true).setAllowInvalid(false).build());

  label(8, 'STATUS:');
  display(8, '=IF(' + S + '="","–",SWITCH(' + X('DF') + ',"av","🟩 KOSONG","pl","🟡 RENCANA","oc","🔴 TERISI","ro","🌙 RON","de","⬜ BERANGKAT — KOSONG","–"))');
  label(10, 'ROW:');
  display(10, '=IF(' + S + '="","",LET(i,' + X('DB') + ',IF(i=0,"baru → baris "&$DK$2,"baris "&(i+17))))');

  label(12, 'REG:');       input(12, F.registration);
  label(14, 'TYPE:');      display(14, '=IF(' + S + '="","",' + X('DM') + ')');
  label(16, 'ON BLK:');    input(16, F.onBlock);
  label(18, 'OFF BLK:');   input(18, F.offBlock);
  label(20, 'FLT ARR:');   input(20, F.flightArr);
  label(22, 'FLT DEP:');   input(22, F.flightDep);
  label(24, 'FROM:');      display(24, '=IF(' + S + '="","",' + X('DN') + ')');
  label(26, 'TO:');        display(26, '=IF(' + S + '="","",' + X('DO') + ')');
  label(28, 'OPERATOR:');  display(28, '=IF(' + S + '="","",' + X('DP') + ')');
  label(30, 'REMARKS:');   input(30, F.remarks);

  label(32, 'CLEAR:');
  sh.getRange(32, P + 1, 2, 1).merge().insertCheckboxes().setHorizontalAlignment('center');
  sh.getRange(32, P + 2, 2, 6).merge()
    .setValue('centang untuk mengosongkan baris movement stand ini')
    .setFontSize(8).setFontStyle('italic').setFontColor('#777777')
    .setVerticalAlignment('middle');
}

/**
 * Latest OPEN movement row for a stand — the row follow-up edits should
 * land on. A departed latest row (off-block set) means the next edit
 * starts a NEW movement (append), so it returns null.
 */
function currentMovementRow(sheet, stand) {
  var row = findLatestStandRow(sheet, stand);
  if (!row) return null;
  var off = safeStr(sheet.getRange(row, COL.OFF_BLOCK).getValue()).trim();
  return off ? null : row;
}

/** Reads the currently selected panel stand. */
function getPanelStand(sh) {
  return safeStr(sh.getRange(PANEL_STAND.r, PANEL_STAND.c).getValue()).trim().toUpperCase();
}

/** Simple trigger: routes panel edits to the day sheet. */
function onEdit(e) {
  try {
    if (!e || !e.range) return;
    var sh = e.range.getSheet();
    var r = e.range.getRow(), c = e.range.getColumn();

    // SEARCH tab: after a new query, auto-fit columns + border the results
    if (sh.getName() === SEARCH_SHEET_NAME) {
      if (r === 2 && c === 2) styleSearchResults(sh);
      return;
    }
    if (sh.getName() !== MAP_SHEET_NAME) return;
    var ss = SpreadsheetApp.getActiveSpreadsheet();

    // Day or stand changed → just drop the row anchor; the panel fields are
    // live formulas and follow the new selection on their own.
    if ((r === PANEL_DAY.r && c === PANEL_DAY.c) ||
        (r === PANEL_STAND.r && c === PANEL_STAND.c)) {
      sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c).setValue('');
      return;
    }

    // Panel input edited → commit that field, then restore the live formula
    var key = PANEL_FIELDS[r + '_' + c];
    if (key) {
      var formula = panelFormulas(1 + Object.keys(MAP_COORDS).length)[key];
      var stand = getPanelStand(sh);
      if (!stand) {
        e.range.setFormula(formula);
        ss.toast('Pilih stand dulu', 'AMC Map', 4);
        return;
      }
      var day = safeStr(sh.getRange(PANEL_DAY.r, PANEL_DAY.c).getValue()).trim();
      var val = safeStr(e.range.getValue()).trim();
      // Normalize times to HH:MM — "930", "0930" and time-parsed entries all
      // land as "09:30", matching how times are typed in the day sheets.
      if ((key === 'onBlock' || key === 'offBlock') && /^\d{1,4}$/.test(val)) {
        val = ('000' + val).slice(-4);
        val = val.slice(0, 2) + ':' + val.slice(2);
      }

      // Row anchor: sticky per stand so follow-up edits (incl. typo fixes
      // after an off-block) land on the same row; otherwise the latest open
      // movement, or null → append a new movement.
      var anchorCell = sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c);
      var row = parseInt(anchorCell.getValue(), 10) || null;
      if (!row) {
        var ds = ss.getSheetByName(day);
        row = ds ? currentMovementRow(ds, stand) : null;
      }
      if (!row && !val) { e.range.setFormula(formula); return; }   // nothing to write

      var fm = {}; fm[key] = val;
      var res = updateStandField(day, stand, row, fm);
      e.range.setFormula(formula);   // back to tracking the sheet
      if (res && res.success) {
        anchorCell.setValue(res.row);
        ss.toast('✓ ' + stand + ' tersimpan → ' + day + ' baris ' + res.row, 'AMC Map', 3);
      } else {
        ss.toast('❌ ' + (res && res.error ? res.error : 'gagal menyimpan'), 'AMC Map', 6);
      }
      return;
    }

    // Clear checkbox ticked → reset the movement row
    if (r === PANEL_CLR.r && c === PANEL_CLR.c && e.range.getValue() === true) {
      e.range.setValue(false);
      var st2 = getPanelStand(sh);
      var day2 = safeStr(sh.getRange(PANEL_DAY.r, PANEL_DAY.c).getValue()).trim();
      var ds2 = ss.getSheetByName(day2);
      var a2 = parseInt(sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c).getValue(), 10) ||
               (ds2 && st2 ? currentMovementRow(ds2, st2) : null);
      if (!st2 || !a2) { ss.toast('Tidak ada yang dihapus', 'AMC Map', 3); return; }
      var res2 = clearMovementRow(day2, st2, a2);
      sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c).setValue('');
      if (res2 && res2.success) {
        ss.toast('✓ ' + st2 + ' dibersihkan', 'AMC Map', 3);
      } else {
        ss.toast('❌ ' + (res2 && res2.error ? res2.error : 'gagal menghapus'), 'AMC Map', 6);
      }
    }
  } catch (err) {
    try { SpreadsheetApp.getActiveSpreadsheet().toast('❌ ' + err.message, 'AMC Map', 6); } catch (x) {}
    Logger.log('onEdit error: ' + err.message);
  }
}

/** Simple trigger: clicking a stand block selects it in the panel. */
function onSelectionChange(e) {
  try {
    if (!e || !e.range) return;
    var sh = e.range.getSheet();
    if (sh.getName() !== MAP_SHEET_NAME) return;
    var r = e.range.getRow(), c = e.range.getColumn();
    if (r < MAP_ROW_OFFSET || c > 98) return;                  // outside map area

    // Stand blocks are merged formula cells whose text starts with the code
    var txt = safeStr(e.range.getDisplayValue());
    if (!txt) return;
    var m = txt.split('\n')[0].match(/(NSA\d{2}|SA\d{2}|WR\d{2}|RE\d{2}|RW\d{2}|A\d|B\d{1,2})/);
    if (!m || !MAP_COORDS[m[1]]) return;

    var standCell = sh.getRange(PANEL_STAND.r, PANEL_STAND.c);
    if (safeStr(standCell.getValue()).trim().toUpperCase() === m[1]) return;   // already loaded
    standCell.setValue(m[1]);
    // Script-made edits don't fire onEdit — clear the anchor here ourselves.
    // The panel fields are live formulas; they show the stand instantly.
    sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c).setValue('');
  } catch (err) {
    Logger.log('onSelectionChange error: ' + err.message);
  }
}


// ══════════════════════════════════════════════════════════════
//  13. SEARCH TAB + DASHBOARD TAB (pure formulas, quota-free)
//  Menu: AMC Tools → Build Search Tab / Build Dashboard.
//  One-time builders like the maps — afterwards no script runs.
// ══════════════════════════════════════════════════════════════

var SEARCH_SHEET_NAME = 'SEARCH';
var DASH_SHEET_NAME   = 'DASH';
// Fixed SEARCH column widths — builder sets them, styleSearchResults
// re-applies them (autofit blew up DATE/OFF BLK on junk cell text).
var SEARCH_COL_WIDTHS = [40, 90, 70, 58, 28, 58, 28, 60, 58, 58, 70, 70, 110, 180, 55];

// Operator → movement category (seeded into DB!J:K, editable in-sheet).
// Anything not listed counts as Charter — same airline groups the charter
// report uses.
var OPERATOR_CATEGORIES = [
  ['GARUDA', 'Commercial'], ['BATIK AIR', 'Commercial'], ['CITILINK', 'Commercial'],
  ['PELITA', 'Commercial'], ['SUSI AIR', 'Commercial'],
  ['AIRNESIA', 'Cargo'], ['JAYAWIJAYA', 'Cargo'], ['TRI MG', 'Cargo'], ['TRIGANA', 'Cargo'],
  ['TNI AU', 'Military']
];

/** Confirm-and-replace an existing tab; returns the fresh sheet or null. */
function freshSheet(ss, ui, name, index) {
  var old = ss.getSheetByName(name);
  if (old) {
    var resp = ui.alert('Sheet ' + name + ' sudah ada. Bangun ulang?', ui.ButtonSet.YES_NO);
    if (resp !== ui.Button.YES) return null;
    ss.deleteSheet(old);
  }
  return ss.insertSheet(name, index);
}

/**
 * SEARCH tab: type anything (date / registration / stand / flight no /
 * operator) in B2 → INSTANT formula results. DATE column = day sheet
 * (tanggal 01-31) the movement came from.
 *
 * v4 design — every array op here is a pattern PROVEN working in this
 * workbook: the haystack for matching is built per day sheet from REAL
 * RANGE concatenation inside ARRAYFORMULA (same as the dashboard's
 * busiest-stands filter), stacked with VSTACK. No LAMBDA, no BYROW, no
 * INDEX-slicing of in-memory arrays (v3's failure: INDEX(all,,n) on a
 * LET array misbehaved → size-mismatch #N/A → looked like "no results").
 * The date token is part of each row's haystack, so searching "18" finds
 * everything on the 18th. IFNA keeps real error codes visible.
 * onEdit only styles: borders + auto-fit after each query.
 */
function buildSearchTab() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  var parts = [], hays = [];
  for (var d = 1; d <= 31; d++) {
    var nm = d < 10 ? '0' + d : '' + d;
    if (ss.getSheetByName(nm)) {
      // Date column: ARRAYFORMULA(IF(SEQUENCE(350),"nm")) — 350 truthy rows,
      // each "nm". (EXPAND's pad didn't work in Sheets: padded cells came out
      // #N/A, and element-wise IFNA rendered every DATE cell "Tidak ada hasil".)
      parts.push('HSTACK(ARRAYFORMULA(IF(SEQUENCE(350),"' + nm + '")),\'' + nm + '\'!$B$18:$O$367)');
      // Searchable text per row: date + reg + stand + flights + operator
      hays.push('ARRAYFORMULA("' + nm + ' "&\'' + nm + '\'!$B$18:$B$367&" "&\'' + nm +
                '\'!$H$18:$H$367&" "&\'' + nm + '\'!$K$18:$K$367&" "&\'' + nm +
                '\'!$L$18:$L$367&" "&\'' + nm + '\'!$M$18:$M$367)');
    }
  }
  if (!parts.length) { ui.alert('Sheet harian (01-31) tidak ditemukan.'); return; }

  var sh = freshSheet(ss, ui, SEARCH_SHEET_NAME, 1);
  if (!sh) return;
  sh.setHiddenGridlines(true);

  sh.getRange(1, 1, 1, 15).merge().setValue('🔎  CARI PERGERAKAN — SEMUA TANGGAL')
    .setFontWeight('bold').setFontSize(13).setFontColor('white')
    .setBackground('#112D4E').setHorizontalAlignment('left').setVerticalAlignment('middle');
  sh.setRowHeight(1, 30);

  sh.getRange(2, 1).setValue('CARI:').setFontWeight('bold').setHorizontalAlignment('right');
  sh.getRange(2, 2, 1, 3).merge().setNumberFormat('@').setBackground('#FFFDE7')
    .setFontWeight('bold').setHorizontalAlignment('center')
    .setBorder(true, true, true, true, false, false, '#9aa5b1', SpreadsheetApp.BorderStyle.SOLID);
  sh.getRange(2, 6, 1, 10).merge()
    .setValue('tanggal / registrasi / stand / no. penerbangan / operator — hasil langsung tampil. Kolom DATE = tanggal (sheet harian) asal pergerakan.')
    .setFontStyle('italic').setFontColor('#555555');

  var headers = ['DATE', 'REG', 'TYPE', 'ON BLK', '✓', 'OFF BLK', '✓', 'STAND',
                 'FROM', 'TO', 'FLT ARR', 'FLT DEP', 'OPERATOR', 'REMARKS', 'STATUS'];
  sh.getRange(4, 1, 1, 15).setValues([headers])
    .setFontWeight('bold').setBackground('#DBE2EF').setHorizontalAlignment('center');
  sh.setFrozenRows(4);

  // `all` and `hay` are built from identical 350-row blocks, so their row
  // counts always match. ARRAY_CONSTRAIN caps output at 900 rows.
  sh.getRange(5, 1).setFormula(
    '=LET(q,TRIM(UPPER($B$2)),IF(q="","",LET(' +
    'all,VSTACK(' + parts.join(',') + '),' +
    'hay,VSTACK(' + hays.join(',') + '),' +
    'IFNA(ARRAY_CONSTRAIN(FILTER(all,ARRAYFORMULA(ISNUMBER(SEARCH(q,UPPER(hay))))),900,15),' +
    '"Tidak ada hasil"))))');

  // Real time values render hh:mm (text like "0930" passes through)
  sh.getRange('D5:D').setNumberFormat('hh:mm');
  sh.getRange('F5:F').setNumberFormat('hh:mm');

  SEARCH_COL_WIDTHS.forEach(function(w, i) { sh.setColumnWidth(i + 1, w); });
  if (sh.getMaxColumns() > 15) sh.hideColumns(16, sh.getMaxColumns() - 15);

  ui.alert('Tab pencarian selesai dibangun.\n\n' +
    'Ketik apa saja di sel kuning — tanggal (misal 18), registrasi, stand, ' +
    'nomor penerbangan, atau operator — hasil langsung tampil.\n' +
    'Kolom DATE = tanggal (sheet harian) asal pergerakan.\n\n' +
    'Kalau hasil menampilkan kode error (#...), screenshot dan laporkan.');
}

/**
 * Borders + auto-fits the current results. Runs from onEdit only when the
 * query cell (B2) changes — a fraction of a second per search.
 */
function styleSearchResults(sh) {
  SpreadsheetApp.flush();   // make sure the FILTER spill is computed
  var vals = sh.getRange(5, 1, 900, 1).getDisplayValues();
  var n = 0;
  for (var i = vals.length - 1; i >= 0; i--) {
    if (vals[i][0] !== '') { n = i + 1; break; }
  }
  sh.getRange(5, 1, 900, 15).setBorder(false, false, false, false, false, false);
  if (!n) return;
  var first = vals[0][0];
  if (first === 'Tidak ada hasil' || first.charAt(0) === '#') return;
  sh.getRange(5, 1, n, 15).setBorder(true, true, true, true, true, true);
  // No autofit — it sized columns to junk text; fixed widths stay stable.
  SEARCH_COL_WIDTHS.forEach(function(w, i) { sh.setColumnWidth(i + 1, w); });
}

/**
 * DASH tab — the PHP dashboard's metrics as pure formulas:
 *   • KPI row 1: movements / arrivals / departures / on-ground
 *   • KPI row 2: live apron status (stands free/occupied/RON/planned),
 *     computed by a per-stand engine identical to the map's rules
 *   • arrivals/departures by category  • busiest stands (top 10)
 *   • movements by hour (table + column chart)
 *   • stand-usage GANTT: stands used today × hours 00-23, red = occupied
 * Day dropdown in D2. One INDIRECT staging spill; everything else local.
 */
function buildDashboard() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  var sh = freshSheet(ss, ui, DASH_SHEET_NAME, 1);
  if (!sh) return;

  // Seed operator→category into DB!J:K (editable; unlisted = Charter)
  var db = ss.getSheetByName('DB');
  if (db && !safeStr(db.getRange(4, 10).getValue()).trim()) {
    db.getRange(3, 10).setValue('OPERATOR').setFontWeight('bold');
    db.getRange(3, 11).setValue('CATEGORY').setFontWeight('bold');
    db.getRange(4, 10, OPERATOR_CATEGORIES.length, 2).setValues(OPERATOR_CATEGORIES);
  }

  if (sh.getMaxRows() < 355) sh.insertRowsAfter(sh.getMaxRows(), 355 - sh.getMaxRows());
  if (sh.getMaxColumns() < 80) sh.insertColumnsAfter(sh.getMaxColumns(), 80 - sh.getMaxColumns());
  sh.setHiddenGridlines(true);
  sh.setFrozenRows(2);

  // ── Header + day selector ─────────────────────────────────────
  sh.getRange(1, 1, 1, 26).merge().setValue('📈  AMC DAILY DASHBOARD')
    .setFontWeight('bold').setFontSize(13).setFontColor('white')
    .setBackground('#112D4E').setHorizontalAlignment('left').setVerticalAlignment('middle');
  sh.setRowHeight(1, 30);

  sh.getRange(2, 2, 1, 2).merge().setValue('TGL:').setFontWeight('bold').setHorizontalAlignment('right');
  var dayList = [];
  for (var d = 1; d <= 31; d++) dayList.push(d < 10 ? '0' + d : '' + d);
  sh.getRange(2, 4).setNumberFormat('@')
    .setDataValidation(SpreadsheetApp.newDataValidation()
      .requireValueInList(dayList, true).setAllowInvalid(false).build())
    .setValue(dayList[Math.min(new Date().getDate(), 31) - 1])
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#F9F7F7')
    .setBorder(true, true, true, true, false, false);
  sh.getRange(2, 6, 1, 8).merge()
    .setFormula('=IFERROR("📅 "&TEXT(INDIRECT("\'"&$D$2&"\'!$C$8"),"ddd, dd mmm yyyy"),"—")')
    .setFontWeight('bold');

  // ── Hidden engine ─────────────────────────────────────────────
  // Spill AO..BC (41-55 = day cols A..O); hour/category helpers BE..BG
  // (57-59); per-stand status engine BI..BN (61-66); Gantt bounds BO/BP.
  sh.getRange(2, 41).setFormula('=IFERROR(INDIRECT("\'"&$D$2&"\'!$A$18:$O$367"),"")');

  // Hour-of-day extractor: real times → HOUR, "09:30" → 9, "0930" → 9,
  // "-"/"EX RON"/blank → nothing
  function hourExpr(col) {
    return '=MAP($' + col + '$2:$' + col + '$351,LAMBDA(v,LET(t,TRIM(TO_TEXT(v)),' +
      'IF(t="",,IF(ISNUMBER(v),HOUR(v),' +
      'IF(REGEXMATCH(t,"^\\d{1,2}[:.]\\d{2}"),VALUE(REGEXEXTRACT(t,"^(\\d{1,2})")),' +
      'IF(REGEXMATCH(t,"^\\d{3,4}$"),VALUE(LEFT(t,LEN(t)-2)),)))))))';
  }
  sh.getRange(2, 57).setFormula(hourExpr('AR'));   // BE: arrival hour   (on-block, D)
  sh.getRange(2, 58).setFormula(hourExpr('AT'));   // BF: departure hour (off-block, F)
  sh.getRange(2, 59).setFormula(                   // BG: category per movement row
    '=MAP($BA$2:$BA$351,$AP$2:$AP$351,LAMBDA(op,rg,' +
    'IF(TRIM(TO_TEXT(rg))="",,LET(o,TRIM(UPPER(TO_TEXT(op))),' +
    'IF(o="","Charter",IFERROR(VLOOKUP(o,\'DB\'!$J$4:$K$200,2,0),"Charter"))))))');

  // Per-stand status engine (same rules as the map): BI codes, BJ latest
  // row, BK/BL/BM reg-on-off at that row, BN status av/pl/oc/ro/de
  var codes = Object.keys(MAP_COORDS);
  var lastC = 1 + codes.length;
  sh.getRange(2, 61, codes.length, 1).setValues(codes.map(function(c){ return [c]; }));
  sh.getRange(2, 62).setFormula(
    '=MAP($BI$2:$BI$' + lastC + ',LAMBDA(c,IFERROR(XMATCH(c,$AV$2:$AV$351,0,-1),0)))');
  sh.getRange(2, 63).setFormula(
    '=MAP($BJ$2:$BJ$' + lastC + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($AP$2:$AP$351,i)))))');
  sh.getRange(2, 64).setFormula(
    '=MAP($BJ$2:$BJ$' + lastC + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($AR$2:$AR$351,i)))))');
  sh.getRange(2, 65).setFormula(
    '=MAP($BJ$2:$BJ$' + lastC + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX($AT$2:$AT$351,i)))))');
  sh.getRange(2, 66).setFormula(
    '=MAP($BK$2:$BK$' + lastC + ',$BL$2:$BL$' + lastC + ',$BM$2:$BM$' + lastC +
    ',LAMBDA(rg,onv,offv,IF(TRIM(rg)="","av",IF(TRIM(offv)<>"","de",' +
    'LET(o,TRIM(UPPER(onv)),IF(o="","pl",IF(OR(o="-",REGEXMATCH(o,"RON"),REGEXMATCH(o,"\\(")),"ro","oc")))))))');

  // Gantt occupancy bounds per movement row: effOn/effOff hour. Blank when
  // the row has no stand or hasn't arrived; "-"/"EX RON" count from 00;
  // still on the ground counts until 23.
  sh.getRange(2, 67).setFormula(
    '=MAP($AV$2:$AV$351,$AR$2:$AR$351,$BE$2:$BE$351,LAMBDA(st,onv,h,' +
    'IF(OR(TRIM(TO_TEXT(st))="",TRIM(TO_TEXT(onv))=""),,IF(ISNUMBER(h),h,0))))');
  sh.getRange(2, 68).setFormula(
    '=MAP($AV$2:$AV$351,$AR$2:$AR$351,$BF$2:$BF$351,LAMBDA(st,onv,h,' +
    'IF(OR(TRIM(TO_TEXT(st))="",TRIM(TO_TEXT(onv))=""),,IF(ISNUMBER(h),h,23))))');

  // ── KPI rows ──────────────────────────────────────────────────
  function kpi(row, col, label, formula, color) {
    sh.getRange(row, col, 1, 4).merge().setValue(label)
      .setFontWeight('bold').setFontSize(9).setFontColor('#4b5563').setHorizontalAlignment('center');
    sh.getRange(row + 1, col, 2, 4).merge().setFormula(formula)
      .setFontWeight('bold').setFontSize(20).setHorizontalAlignment('center')
      .setVerticalAlignment('middle').setBackground(color);
  }
  // Row 1: today's movements
  kpi(4, 2,  'MOVEMENTS',
    '=IFERROR(ROWS(FILTER($AP$2:$AP$351,ARRAYFORMULA(TRIM(TO_TEXT($AP$2:$AP$351))<>""))),0)', '#DBE2EF');
  kpi(4, 7,  'ARRIVALS',   '=COUNT($BE$2:$BE$351)', '#dcfce7');
  kpi(4, 12, 'DEPARTURES', '=COUNT($BF$2:$BF$351)', '#ffedd5');
  kpi(4, 17, 'ON GROUND',
    '=IFERROR(ROWS(FILTER($AP$2:$AP$351,ARRAYFORMULA((TRIM(TO_TEXT($AP$2:$AP$351))<>"")*' +
    '(TRIM(TO_TEXT($AR$2:$AR$351))<>"")*(TRIM(TO_TEXT($AT$2:$AT$351))="")))),0)', '#fee2e2');
  // Row 2: live apron status (the PHP dashboard's status card)
  kpi(8, 2,  'STANDS FREE',
    '=COUNTIF($BN$2:$BN$' + lastC + ',"av")+COUNTIF($BN$2:$BN$' + lastC + ',"de")', '#dcfce7');
  kpi(8, 7,  'STANDS OCCUPIED', '=COUNTIF($BN$2:$BN$' + lastC + ',"oc")', '#fee2e2');
  kpi(8, 12, 'STANDS RON',      '=COUNTIF($BN$2:$BN$' + lastC + ',"ro")', '#fef9c3');
  kpi(8, 17, 'STANDS PLANNED',  '=COUNTIF($BN$2:$BN$' + lastC + ',"pl")', '#ffedd5');

  // ── By category (rows 12-16) ──────────────────────────────────
  sh.getRange(12, 2, 1, 3).setValues([['CATEGORY', 'ARR', 'DEP']])
    .setFontWeight('bold').setBackground('#DBE2EF').setHorizontalAlignment('center');
  ['Commercial', 'Cargo', 'Military', 'Charter'].forEach(function(cat, i) {
    var r = 13 + i;
    sh.getRange(r, 2).setValue(cat).setFontWeight('bold');
    sh.getRange(r, 3).setFormula('=COUNTIFS($BG$2:$BG$351,"' + cat + '",$BE$2:$BE$351,">=0")')
      .setHorizontalAlignment('center');
    sh.getRange(r, 4).setFormula('=COUNTIFS($BG$2:$BG$351,"' + cat + '",$BF$2:$BF$351,">=0")')
      .setHorizontalAlignment('center');
  });

  // ── Busiest stands (rows 19-29) ───────────────────────────────
  sh.getRange(19, 2, 1, 3).merge().setValue('BUSIEST STANDS (TOP 10)')
    .setFontWeight('bold').setBackground('#DBE2EF').setHorizontalAlignment('center');
  sh.getRange(20, 2).setFormula(
    '=IFERROR(ARRAY_CONSTRAIN(SORT(LET(' +
    's,FILTER($AV$2:$AV$351,ARRAYFORMULA(TRIM(TO_TEXT($AV$2:$AV$351))<>"")),' +
    'u,UNIQUE(s),HSTACK(u,MAP(u,LAMBDA(x,COUNTIF(s,x))))),2,FALSE),10,2),"—")');

  // ── Movements by hour: table G12:I36 + chart ──────────────────
  sh.getRange(12, 7, 1, 3).setValues([['HOUR', 'ARR', 'DEP']])
    .setFontWeight('bold').setBackground('#DBE2EF').setHorizontalAlignment('center');
  var hrs = [], fArr = [], fDep = [];
  for (var h = 0; h < 24; h++) {
    var r2 = 13 + h;
    hrs.push([h]);
    fArr.push(['=COUNTIF($BE$2:$BE$351,$G$' + r2 + ')']);
    fDep.push(['=COUNTIF($BF$2:$BF$351,$G$' + r2 + ')']);
  }
  sh.getRange(13, 7, 24, 1).setValues(hrs).setHorizontalAlignment('center');
  sh.getRange(13, 8, 24, 1).setFormulas(fArr).setHorizontalAlignment('center');
  sh.getRange(13, 9, 24, 1).setFormulas(fDep).setHorizontalAlignment('center');

  sh.insertChart(sh.newChart().asColumnChart()
    .addRange(sh.getRange(12, 7, 25, 3))
    .setPosition(12, 11, 0, 0)
    .setOption('title', 'Movements by hour')
    .setOption('legend', { position: 'top' })
    .build());

  // ── Stand-usage Gantt chart (floats below, from row 31) ───────
  // Sheets has no native Gantt chart type — classic trick instead: a
  // STACKED BAR per stand, with an invisible (white) lead segment = start
  // hour and a red segment = occupied duration. Data table at BS:BU.
  // ALL 83 stands are listed (static values, map order); unused stands
  // get 0/0 = a zero-length bar, so the chart can never drop their rows.
  // ponytail: one bar per stand (earliest arrival → latest departure);
  // gaps between two visits on the same stand merge into one bar
  sh.getRange(1, 71, 1, 3).setValues([['STAND', 'MULAI', 'DURASI']]);
  var allCodes = Object.keys(MAP_COORDS);
  sh.getRange(2, 71, allCodes.length, 1).setValues(allCodes.map(function(c){ return [c]; }));
  // MULAI/DURASI per row with COUNTIFS/MINIFS/MAXIFS — static native
  // formulas, no LAMBDA anywhere in the chart's data path (the MAP/FILTER
  // version rendered once and then went blank on recalc).
  var GHAS = 'COUNTIFS($AV$2:$AV$351,$BS$ROW,$BO$2:$BO$351,">=0")=0';
  var fStart = [], fDur = [];
  for (var g = 2; g < 2 + allCodes.length; g++) {
    var has = GHAS.replace(/ROW/g, g);
    fStart.push(['=IF(' + has + ',0,MINIFS($BO$2:$BO$351,$AV$2:$AV$351,$BS$' + g + '))']);
    fDur.push(['=IF(' + has + ',0,MAXIFS($BP$2:$BP$351,$AV$2:$AV$351,$BS$' + g + ')-$BT$' + g + '+1)']);
  }
  sh.getRange(2, 72, allCodes.length, 1).setFormulas(fStart);
  sh.getRange(2, 73, allCodes.length, 1).setFormulas(fDur);

  sh.insertChart(sh.newChart().asBarChart()
    .addRange(sh.getRange(1, 71, 1 + allCodes.length, 3))
    .setPosition(31, 2, 0, 0)
    .setOption('title', 'Stand Usage — Gantt (jam 00-23, semua stand)')
    .setOption('isStacked', true)
    .setOption('legend', { position: 'none' })
    .setOption('series', { 0: { color: 'white' }, 1: { color: '#c62828' } })
    .setOption('hAxis', { minValue: 0, maxValue: 24, ticks: [0, 4, 8, 12, 16, 20, 24] })
    .setOption('height', 1400)
    .build());

  sh.setColumnWidth(2, 90);

  // Charts ignore hidden columns — the Gantt data (BS:BU, 71-73) must stay
  // visible. It lands right after the dashboard content as a small table.
  sh.hideColumns(27, 44);   // 27-70: gap + spill + helpers + stand engine
  sh.hideColumns(74, 7);    // 74-80
  sh.getRange(1, 71, 1, 3).setFontWeight('bold').setBackground('#EEF3FA');

  ui.alert('Dashboard selesai dibangun.\n\n' +
    '• Pilih tanggal di D2 — semua angka, grafik jam, dan Gantt langsung diperbarui.\n' +
    '• Pemetaan operator→kategori ada di kolom J:K sheet DB (bisa diedit; ' +
    'operator di luar daftar dihitung Charter).\n' +
    '• Murni formula — nol kuota, aman dibuka seharian.');
}
