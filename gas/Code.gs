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


// ── 2. Dialog Launcher ───────────────────────────────────────
function openApronMap() {
  // Before opening, attempt RON carry-over for the current day
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var activeSheetName = ss.getActiveSheet().getName();

  // Trigger RON carry-over if we're on a valid day sheet
  if (DAY_SHEETS[activeSheetName]) {
    var dayNum = parseInt(activeSheetName, 10);
    if (dayNum > 1) {
      var prevDay = dayNum < 11 ? '0' + (dayNum - 1) : '' + (dayNum - 1);
      try {
        carryOverRON(prevDay, activeSheetName);
      } catch (e) {
        // Silent fail — don't block map opening
        Logger.log('RON carry-over error: ' + e.message);
      }
    }
  }

  var html = HtmlService.createHtmlOutputFromFile('ApronMap')
    .setWidth(4096)
    .setHeight(4096)
    .setTitle('AMC Live Apron Map');

  SpreadsheetApp.getUi().showModelessDialog(html, '🛫 AMC Live Apron Map');
}


// ── 3. Get Active Sheet Name ──────────────────────────────────
function getActiveSheetName() {
  return SpreadsheetApp.getActiveSpreadsheet().getActiveSheet().getName();
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
 * Writes movement fields for a specific stand to the day sheet.
 * Autofill (type, operator, from, to) is now done client-side from DB data
 * so we only write raw input fields here, plus any manual overrides.
 *
 * @param {string} sheetName  e.g. '10'
 * @param {string} stand      Stand code e.g. 'SA01'
 * @param {Object} fieldMap   { registration, onBlock, offBlock, flightArr, flightDep, remarks }
 * @returns {Object}          { success: true } | { success: false, error: string }
 */
function updateStandField(sheetName, stand, fieldMap) {
  try {
    var ss    = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(sheetName);

    if (!sheet) {
      var msg = 'Sheet "' + sheetName + '" not found. Available: ' +
                ss.getSheets().map(function(s){ return s.getName(); }).join(', ');
      Logger.log('[AMC] ERROR: ' + msg);
      return { success: false, error: msg };
    }

    // Find the row for this stand in column H
    var targetRow = findStandRow(sheet, stand);
    Logger.log('[AMC] updateStandField: sheet=' + sheetName +
               ', stand=' + stand + ', row=' + targetRow);

    if (!targetRow) {
      var err = 'Stand "' + stand + '" not found in column H of sheet "' + sheetName + '".';
      Logger.log('[AMC] ERROR: ' + err);
      return { success: false, error: err };
    }

    // ── Write raw input fields ──────────────────────────────────
    if (fieldMap.hasOwnProperty('registration')) {
      sheet.getRange(targetRow, COL.REGISTRATION).setValue(fieldMap.registration);
      Logger.log('[AMC]   Col B (REG) = "' + fieldMap.registration + '"');
    }
    if (fieldMap.hasOwnProperty('onBlock')) {
      sheet.getRange(targetRow, COL.ON_BLOCK).setValue(fieldMap.onBlock);
      Logger.log('[AMC]   Col D (ON) = "' + fieldMap.onBlock + '"');
    }
    if (fieldMap.hasOwnProperty('offBlock')) {
      sheet.getRange(targetRow, COL.OFF_BLOCK).setValue(fieldMap.offBlock);
      Logger.log('[AMC]   Col F (OFF) = "' + fieldMap.offBlock + '"');
    }
    if (fieldMap.hasOwnProperty('flightArr')) {
      sheet.getRange(targetRow, COL.FLIGHT_ARR).setValue(fieldMap.flightArr);
      Logger.log('[AMC]   Col K (ARR) = "' + fieldMap.flightArr + '"');
    }
    if (fieldMap.hasOwnProperty('flightDep')) {
      sheet.getRange(targetRow, COL.FLIGHT_DEP).setValue(fieldMap.flightDep);
      Logger.log('[AMC]   Col L (DEP) = "' + fieldMap.flightDep + '"');
    }
    if (fieldMap.hasOwnProperty('remarks')) {
      sheet.getRange(targetRow, COL.REMARKS).setValue(fieldMap.remarks);
      Logger.log('[AMC]   Col N (REM) = "' + fieldMap.remarks + '"');
    }

    // ── Manual overrides for VLOOKUP fields (only if explicitly passed) ─
    if (fieldMap.hasOwnProperty('type')) {
      sheet.getRange(targetRow, COL.TYPE).setValue(fieldMap.type);
      Logger.log('[AMC]   Col C (TYPE override) = "' + fieldMap.type + '"');
    }
    if (fieldMap.hasOwnProperty('operator')) {
      sheet.getRange(targetRow, COL.OPERATOR).setValue(fieldMap.operator);
      Logger.log('[AMC]   Col M (OP override) = "' + fieldMap.operator + '"');
    }
    if (fieldMap.hasOwnProperty('from')) {
      sheet.getRange(targetRow, COL.FROM).setValue(fieldMap.from);
      Logger.log('[AMC]   Col I (FROM override) = "' + fieldMap.from + '"');
    }
    if (fieldMap.hasOwnProperty('to')) {
      sheet.getRange(targetRow, COL.TO).setValue(fieldMap.to);
      Logger.log('[AMC]   Col J (TO override) = "' + fieldMap.to + '"');
    }

    // Flush to commit all writes to the sheet
    SpreadsheetApp.flush();
    Logger.log('[AMC]   Write committed successfully.');

    return { success: true };

  } catch(e) {
    Logger.log('[AMC] EXCEPTION in updateStandField: ' + e.message + ' | Stack: ' + e.stack);
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

  // Find RON rows in previous day
  var ronRows = prevData.filter(function(item) {
    return item.status === 'RON' && item.registration && item.registration.trim() !== '';
  });

  if (ronRows.length === 0) return;

  // For each RON aircraft, find matching stand in current day
  for (var i = 0; i < ronRows.length; i++) {
    var ron = ronRows[i];
    var targetRow = findStandRow(toSheet, ron.stand);
    if (!targetRow) continue;

    // Only carry over if current day row has no registration yet
    var existingReg = safeStr(toSheet.getRange(targetRow, COL.REGISTRATION).getValue());
    if (existingReg && existingReg.trim() !== '') continue;

    // Write: registration + "EX RON" on-block, leave off-block empty
    toSheet.getRange(targetRow, COL.REGISTRATION).setValue(ron.registration);
    toSheet.getRange(targetRow, COL.ON_BLOCK).setValue('EX RON');
    toSheet.getRange(targetRow, COL.OFF_BLOCK).setValue('');
    // Also carry remarks if any
    if (ron.remarks) {
      toSheet.getRange(targetRow, COL.REMARKS).setValue(ron.remarks);
    }
  }

  SpreadsheetApp.flush();
  Logger.log('RON carry-over complete: ' + ronRows.length + ' aircraft from sheet ' + fromSheetName + ' to ' + toSheetName);
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


// ── Helper: Find Stand Row ────────────────────────────────────
/**
 * Scans column H from row 18 downward to find the row
 * that contains the given stand code.
 *
 * @param {Sheet}  sheet
 * @param {string} stand  Stand code e.g. 'SA01'
 * @returns {number|null} Row number or null if not found
 */
function findStandRow(sheet, stand) {
  var lastRow = sheet.getLastRow();
  if (lastRow < DATA_START_ROW) return null;

  var numRows = lastRow - DATA_START_ROW + 1;
  var standValues = sheet.getRange(DATA_START_ROW, COL.STAND, numRows, 1).getValues();
  var target = stand.toString().trim().toUpperCase();

  for (var i = 0; i < standValues.length; i++) {
    var cell = safeStr(standValues[i][0]).trim().toUpperCase();
    if (cell === target) {
      return DATA_START_ROW + i;
    }
  }
  return null;
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
