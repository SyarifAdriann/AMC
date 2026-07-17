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
    .addItem('🛫 Open Apron Map', 'openApronMap')
    .addItem('🖥️ Open Apron Map (Full Screen)', 'openApronMapFullScreen')
    .addItem('🗺️ Build Native Map (in-sheet, quota-free)', 'buildNativeMap')
    .addItem('📊 Generate Charter Report', 'generateCharterReport')
    .addSeparator()
    .addItem('ℹ️ About', 'showAbout')
    .addToUi();
}

function showAbout() {
  SpreadsheetApp.getUi().alert(
    'AMC Live Apron Map v1.0\n\n' +
    'Interactive visual apron map for Halim Perdanakusuma.\n' +
    'Reads from and writes to the daily movement sheets.\n\n' +
    'Color codes:\n' +
    '• Blue stand = Available\n' +
    '• Yellow ✈ above = Planned (no on-block yet)\n' +
    '• Red ✈ below = Occupied (on-block, no off-block)\n' +
    '• Yellow border = RON (remain overnight)\n\n' +
    'Changes auto-save as you type.'
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
      var msg = 'Sheet "' + sheetName + '" not found. Available: ' +
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
        return { success: false, error: 'No empty rows left in sheet "' + sheetName + '" — extend the template.' };
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

    SpreadsheetApp.flush();
    Logger.log('[AMC]   Write committed to row ' + targetRow + '.');

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
    if (!sheet) return { success: false, error: 'Sheet "' + sheetName + '" not found.' };

    var target = stand.toString().trim().toUpperCase();
    if (!row || row < DATA_START_ROW) return { success: false, error: 'Invalid row.' };

    var hVal = safeStr(sheet.getRange(row, COL.STAND).getValue()).trim().toUpperCase();
    if (hVal !== target) {
      return { success: false, error: 'Row ' + row + ' holds stand "' + hVal + '", not "' + target + '" — refresh the map.' };
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
    SpreadsheetApp.flush();

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
  if (!db) { ui.alert('DB sheet not found — cannot generate the charter report.'); return; }
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
  ui.alert('Charter report generated: ' + rows.length + ' visits.\n\n' +
           'Re-run "Generate Charter Report" anytime to refresh it.\n' +
           'Excluded operators are listed in DB column H (editable).');
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
var MAP_SCALE      = 20;   // px per grid cell (source coords are 1920x1080)
var MAP_ROW_OFFSET = 4;    // map area starts below the control rows
var MAP_BLOCK_COLS = 3;    // each stand = 3 cols x 2 rows
var MAP_BLOCK_ROWS = 2;

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

function buildNativeMap() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var ui = SpreadsheetApp.getUi();

  var old = ss.getSheetByName(MAP_SHEET_NAME);
  if (old) {
    var resp = ui.alert('A MAP sheet already exists. Rebuild it?', ui.ButtonSet.YES_NO);
    if (resp !== ui.Button.YES) return;
    ss.deleteSheet(old);
  }
  var sh = ss.insertSheet(MAP_SHEET_NAME, 0);

  var codes = Object.keys(MAP_COORDS);
  var n     = codes.length;                 // 83
  var lastCodeRow = 1 + n;                  // helper rows 2..(n+1)

  // ── Grid geometry ─────────────────────────────────────────────
  var mapCols = 98, mapRows = 55;
  if (sh.getMaxColumns() < 120) sh.insertColumnsAfter(sh.getMaxColumns(), 120 - sh.getMaxColumns());
  if (sh.getMaxRows() < mapRows + MAP_ROW_OFFSET + 5) {
    sh.insertRowsAfter(sh.getMaxRows(), mapRows + MAP_ROW_OFFSET + 5 - sh.getMaxRows());
  }
  sh.setColumnWidths(1, mapCols, MAP_SCALE);
  sh.setRowHeights(MAP_ROW_OFFSET, mapRows, MAP_SCALE);
  sh.setRowHeight(1, 30);
  sh.setRowHeights(2, 2, 24);
  sh.setFrozenRows(3);
  sh.setHiddenGridlines(true);

  // Apron background
  sh.getRange(MAP_ROW_OFFSET, 1, mapRows, mapCols).setBackground('#dbe7f4');

  // ── Header / controls ─────────────────────────────────────────
  sh.getRange(1, 1, 1, mapCols).merge().setValue('🛫  AMC LIVE APRON MAP — VIEW A')
    .setFontWeight('bold').setFontSize(13).setFontColor('white')
    .setBackground('#112D4E').setHorizontalAlignment('left').setVerticalAlignment('middle');

  sh.getRange(2, 2, 1, 2).merge().setValue('DAY:')
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
    .setFormula('="🟦 FREE: "&(COUNTIF($DF$2:$DF$' + lastCodeRow + ',"av")+COUNTIF($DF$2:$DF$' + lastCodeRow + ',"de"))')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#dcfce7');
  sh.getRange(2, 28, 1, 7).merge()
    .setFormula('="🔴 OCCUPIED: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"oc")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#fee2e2');
  sh.getRange(2, 36, 1, 7).merge()
    .setFormula('="🌙 RON: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"ro")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#fef9c3');
  sh.getRange(2, 44, 1, 7).merge()
    .setFormula('="🟡 PLANNED: "&COUNTIF($DF$2:$DF$' + lastCodeRow + ',"pl")')
    .setFontWeight('bold').setHorizontalAlignment('center').setBackground('#ffedd5');

  sh.getRange(3, 2, 1, 60).merge()
    .setValue('Click a stand → it loads in the EDIT PANEL. Type in the yellow fields — each entry saves straight to the day sheet. Colors & counts update live.')
    .setFontStyle('italic').setFontColor('#555555');

  // ── Hidden formula engine (columns DA..DL = 105..116) ─────────
  var C_CODE = 105, C_GNAME = 113, C_EMPTY = 115, C_GIDA = 116;

  sh.getRange(1, C_CODE).setValue('code');
  sh.getRange(2, C_CODE, n, 1).setValues(codes.map(function(c){ return [c]; }));

  var R = '$DA$2:$DA$' + lastCodeRow;
  var dayRef = '"\'"&$D$2&"\'!';

  sh.getRange(2, C_CODE + 1).setFormula(   // DB: latest row index per stand (0 = none)
    '=MAP(' + R + ',LAMBDA(c,IFERROR(MAX(IF(TRIM(UPPER(IFERROR(TO_TEXT(INDIRECT(' + dayRef + '$H$18:$H$367")),"")))=c,SEQUENCE(350),)),0)))');
  sh.getRange(2, C_CODE + 2).setFormula(   // DC: registration
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$B$18:$B$367"),i)))))');
  sh.getRange(2, C_CODE + 3).setFormula(   // DD: on-block
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$D$18:$D$367"),i)))))');
  sh.getRange(2, C_CODE + 4).setFormula(   // DE: off-block
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$F$18:$F$367"),i)))))');
  sh.getRange(2, C_CODE + 5).setFormula(   // DF: status av/pl/oc/ro/de
    '=MAP($DC$2:$DC$' + lastCodeRow + ',$DD$2:$DD$' + lastCodeRow + ',$DE$2:$DE$' + lastCodeRow +
    ',LAMBDA(rg,onv,offv,IF(TRIM(rg)="","av",IF(TRIM(offv)<>"","de",' +
    'LET(o,TRIM(UPPER(onv)),IF(o="","pl",IF(OR(o="-",REGEXMATCH(o,"RON"),REGEXMATCH(o,"\\(")),"ro","oc")))))))');
  sh.getRange(2, C_CODE + 6).setFormula(   // DG: display text with status marker
    '=MAP(' + R + ',$DC$2:$DC$' + lastCodeRow + ',$DF$2:$DF$' + lastCodeRow +
    ',LAMBDA(c,rg,st,IF(OR(st="av",st="de"),c,' +
    'IFS(st="pl","🟡 "&c&CHAR(10)&rg,st="oc","🔴 "&c&CHAR(10)&rg,st="ro","🌙 "&c&CHAR(10)&rg))))');

  // DM/DN/DO/DP (117-120): type / from / to / operator at the latest row —
  // live read-only fields for the edit panel
  sh.getRange(2, 117).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$C$18:$C$367"),i)))))');
  sh.getRange(2, 118).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$I$18:$I$367"),i)))))');
  sh.getRange(2, 119).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$J$18:$J$367"),i)))))');
  sh.getRange(2, 120).setFormula(
    '=MAP($DB$2:$DB$' + lastCodeRow + ',LAMBDA(i,IF(i=0,"",TO_TEXT(INDEX(INDIRECT(' + dayRef + '$M$18:$M$367"),i)))))');

  // DK: first empty template row (link target for free stands)
  sh.getRange(2, C_EMPTY).setFormula(
    '=IFERROR(MIN(FILTER(SEQUENCE(350)+17,' +
    'TRIM(IFERROR(TO_TEXT(INDIRECT(' + dayRef + '$B$18:$B$367")),"x"))="",' +
    'TRIM(IFERROR(TO_TEXT(INDIRECT(' + dayRef + '$H$18:$H$367")),"x"))="")),18)');

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

  // ── EDIT PANEL (rows 26-36, cols 13-34 — verified clear of stands) ──
  buildEditPanel(sh, codes, lastCodeRow);

  // ── Place the stands ──────────────────────────────────────────
  var items = codes.map(function(c) {
    return { code: c,
             col: Math.round(MAP_COORDS[c][0] / MAP_SCALE) + 1,
             row: Math.round(MAP_COORDS[c][1] / MAP_SCALE) + MAP_ROW_OFFSET };
  });
  items.sort(function(a, b) { return a.row - b.row || a.col - b.col; });

  var occ = {};
  // Reserve the edit-panel card region so no stand can be shifted into it
  for (var pr = 26; pr <= 36; pr++)
    for (var pc = 13; pc <= 34; pc++) occ[pr + '_' + pc] = true;

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
      .setBackground('#f59e0b').setFontColor('#111111').setRanges([mapRange]).build(),
    SpreadsheetApp.newConditionalFormatRule().whenTextContains('🌙')
      .setBackground('#EAB308').setFontColor('#111111').setRanges([mapRange]).build()
  ];
  sh.setConditionalFormatRules(rules);

  // Hide the formula engine + trailing columns
  sh.hideColumns(mapCols + 1, 120 - mapCols);

  ui.alert('Native map built.\n\n' +
    '• Viewing is pure formulas — zero quota while the map sits open 24/7.\n' +
    '• Click a stand → it loads in the EDIT PANEL on the map. Type in the ' +
    'yellow fields — each entry saves straight to the day sheet.\n' +
    '• Edits use event triggers: a fraction of a second of script time per ' +
    'actual keystroke, billed to whoever edits. No polling, no idle cost.\n' +
    '• Pick the day in the dropdown (cell D2). Rebuild anytime from AMC Tools.');
}


// ══════════════════════════════════════════════════════════════
//  12. IN-MAP EDIT PANEL + EVENT TRIGGERS
//  Click a stand on the MAP tab → onSelectionChange loads it into
//  the panel. Type into a panel input → onEdit commits it to the
//  day sheet via the same row-anchored write path as the HTML map.
//  Event-driven: script runs ONLY on actual clicks/edits (fractions
//  of a second each, as the acting user) — never in the background.
// ══════════════════════════════════════════════════════════════

// Fixed panel cell addresses — builder and triggers must agree.
var PANEL_DAY    = { r: 2,  c: 4  };   // D2  (day dropdown)
var PANEL_STAND  = { r: 27, c: 18 };   // R27 (stand dropdown)
var PANEL_CLR    = { r: 35, c: 18 };   // R35 (clear checkbox)
var PANEL_ANCHOR = { r: 3,  c: 116 };  // DL3 (hidden row anchor, like selRow)
// input cell -> updateStandField field key
var PANEL_FIELDS = {
  '29_18': 'registration',
  '30_18': 'onBlock',   '30_28': 'offBlock',
  '31_18': 'flightArr', '31_28': 'flightDep',
  '34_18': 'remarks'
};

/** Lays out the edit panel card on the MAP sheet (called by buildNativeMap). */
function buildEditPanel(sh, codes, lastCodeRow) {
  var A = '$DA$2:$DA$' + lastCodeRow;   // engine ranges
  function X(col) { return 'XLOOKUP($R$27,' + A + ',$' + col + '$2:$' + col + '$' + lastCodeRow + ')'; }

  // Card background + frame
  sh.getRange(26, 13, 11, 22).setBackground('#ffffff')
    .setBorder(true, true, true, true, false, false, '#112D4E', SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  function label(r, c, w, txt) {
    sh.getRange(r, c, 1, w).merge().setValue(txt)
      .setFontWeight('bold').setFontSize(8).setFontColor('#4b5563')
      .setHorizontalAlignment('right').setVerticalAlignment('middle');
  }
  function input(r, c, w) {
    return sh.getRange(r, c, 1, w).merge().setBackground('#FFFDE7')
      .setFontSize(9).setHorizontalAlignment('center').setVerticalAlignment('middle')
      .setBorder(true, true, true, true, false, false, '#9aa5b1', SpreadsheetApp.BorderStyle.SOLID);
  }
  function display(r, c, w, formula) {
    sh.getRange(r, c, 1, w).merge().setFormula(formula)
      .setBackground('#EEF3FA').setFontStyle('italic').setFontSize(9)
      .setHorizontalAlignment('center').setVerticalAlignment('middle');
  }

  sh.getRange(26, 13, 1, 22).merge().setValue('✏  EDIT PANEL — click a stand or pick one:')
    .setFontWeight('bold').setFontSize(9).setFontColor('white').setBackground('#112D4E')
    .setHorizontalAlignment('left').setVerticalAlignment('middle');

  label(27, 14, 4, 'STAND:');
  input(27, 18, 3).setFontWeight('bold')
    .setDataValidation(SpreadsheetApp.newDataValidation()
      .requireValueInList(codes.slice().sort(), true).setAllowInvalid(false).build());
  display(27, 23, 5,
    '=IF($R$27="","–",SWITCH(' + X('DF') + ',"av","🟩 AVAILABLE","pl","🟡 PLANNED","oc","🔴 OCCUPIED","ro","🌙 RON","de","⬜ DEPARTED — FREE","–"))');
  display(27, 30, 5,
    '=IF($R$27="","",LET(i,' + X('DB') + ',IF(i=0,"new → row "&$DK$2,"row "&(i+17))))');

  label(29, 14, 4, 'REG:');       input(29, 18, 6);
  label(29, 25, 3, 'TYPE:');      display(29, 28, 6, '=IF($R$27="","",' + X('DM') + ')');
  label(30, 14, 4, 'ON BLK:');    input(30, 18, 3);
  label(30, 25, 3, 'OFF BLK:');   input(30, 28, 3);
  label(31, 14, 4, 'FLT ARR:');   input(31, 18, 3);
  label(31, 25, 3, 'FLT DEP:');   input(31, 28, 3);
  label(32, 14, 4, 'FROM:');      display(32, 18, 3, '=IF($R$27="","",' + X('DN') + ')');
  label(32, 25, 3, 'TO:');        display(32, 28, 6, '=IF($R$27="","",' + X('DO') + ')');
  label(33, 14, 4, 'OPERATOR:');  display(33, 18, 16, '=IF($R$27="","",' + X('DP') + ')');
  label(34, 14, 4, 'REMARKS:');   input(34, 18, 16);

  label(35, 14, 4, 'CLEAR ROW:');
  sh.getRange(35, 18).insertCheckboxes().setHorizontalAlignment('center');
  sh.getRange(35, 19, 1, 15).merge()
    .setValue('tick to reset this stand\'s movement row (like the Clear Stand button)')
    .setFontSize(8).setFontStyle('italic').setFontColor('#777777')
    .setVerticalAlignment('middle');
}

/** Reads the currently selected panel stand. */
function getPanelStand(sh) {
  return safeStr(sh.getRange(PANEL_STAND.r, PANEL_STAND.c).getValue()).trim().toUpperCase();
}

/** Loads a stand's current movement into the panel input cells. */
function loadPanel(sh, stand) {
  var inputs = {
    registration: sh.getRange(29, 18), onBlock: sh.getRange(30, 18),
    offBlock: sh.getRange(30, 28), flightArr: sh.getRange(31, 18),
    flightDep: sh.getRange(31, 28), remarks: sh.getRange(34, 18)
  };
  var anchor = sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c);

  function clearInputs() {
    for (var k in inputs) inputs[k].setValue('');
    anchor.setValue('');
  }
  if (!stand) { clearInputs(); return; }

  var day = safeStr(sh.getRange(PANEL_DAY.r, PANEL_DAY.c).getValue()).trim();
  var ds  = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(day);
  if (!ds) { clearInputs(); return; }

  var row = findLatestStandRow(ds, stand);
  if (!row) { clearInputs(); return; }              // no record today → new movement

  var v = ds.getRange(row, 1, 1, 15).getValues()[0];
  var offBlock = safeStr(v[COL.OFF_BLOCK - 1]).trim();
  if (offBlock) { clearInputs(); return; }          // departed → start fresh (append)

  inputs.registration.setValue(safeStr(v[COL.REGISTRATION - 1]));
  inputs.onBlock.setValue(safeStr(v[COL.ON_BLOCK - 1]));
  inputs.offBlock.setValue(offBlock);
  inputs.flightArr.setValue(safeStr(v[COL.FLIGHT_ARR - 1]));
  inputs.flightDep.setValue(safeStr(v[COL.FLIGHT_DEP - 1]));
  inputs.remarks.setValue(safeStr(v[COL.REMARKS - 1]));
  anchor.setValue(row);
}

/** Simple trigger: routes panel edits to the day sheet. */
function onEdit(e) {
  try {
    if (!e || !e.range) return;
    var sh = e.range.getSheet();
    if (sh.getName() !== MAP_SHEET_NAME) return;
    var r = e.range.getRow(), c = e.range.getColumn();
    var ss = SpreadsheetApp.getActiveSpreadsheet();

    // Day or stand changed → (re)load the panel
    if ((r === PANEL_DAY.r && c === PANEL_DAY.c) ||
        (r === PANEL_STAND.r && c === PANEL_STAND.c)) {
      loadPanel(sh, getPanelStand(sh));
      return;
    }

    // Panel input edited → commit that field
    var key = PANEL_FIELDS[r + '_' + c];
    if (key) {
      var stand = getPanelStand(sh);
      if (!stand) { ss.toast('Pick a stand first', 'AMC Map', 4); return; }
      var day = safeStr(sh.getRange(PANEL_DAY.r, PANEL_DAY.c).getValue()).trim();
      var anchorCell = sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c);
      var anchor = parseInt(anchorCell.getValue(), 10) || null;

      var fm = {}; fm[key] = safeStr(e.range.getValue()).trim();
      var res = updateStandField(day, stand, anchor, fm);
      if (res && res.success) {
        anchorCell.setValue(res.row);
        ss.toast('✓ ' + stand + ' saved → ' + day + ' row ' + res.row, 'AMC Map', 3);
      } else {
        ss.toast('❌ ' + (res && res.error ? res.error : 'save failed'), 'AMC Map', 6);
      }
      return;
    }

    // Clear checkbox ticked → reset the movement row
    if (r === PANEL_CLR.r && c === PANEL_CLR.c && e.range.getValue() === true) {
      e.range.setValue(false);
      var st2 = getPanelStand(sh);
      var day2 = safeStr(sh.getRange(PANEL_DAY.r, PANEL_DAY.c).getValue()).trim();
      var a2 = parseInt(sh.getRange(PANEL_ANCHOR.r, PANEL_ANCHOR.c).getValue(), 10) || null;
      if (!st2 || !a2) { ss.toast('Nothing to clear', 'AMC Map', 3); return; }
      var res2 = clearMovementRow(day2, st2, a2);
      if (res2 && res2.success) {
        loadPanel(sh, st2);
        ss.toast('✓ ' + st2 + ' cleared', 'AMC Map', 3);
      } else {
        ss.toast('❌ ' + (res2 && res2.error ? res2.error : 'clear failed'), 'AMC Map', 6);
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
    loadPanel(sh, m[1]);
  } catch (err) {
    Logger.log('onSelectionChange error: ' + err.message);
  }
}
