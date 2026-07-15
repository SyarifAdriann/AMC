# PRD: Live Apron Map View — Google Sheets Integration
**Product:** AMC Apron Movement Sheet (Google Sheets)  
**Feature:** Live Interactive Apron Map Panel  
**Version:** 1.0 (GAS HTML Dialog)  
**Date:** 2026-07-10  
**Status:** READY FOR IMPLEMENTATION

---

## 1. Overview

A live, interactive apron map view embedded inside the existing Google Sheets "APRON MOVEMENT SHEET JULI 2025" document. Operators can visualize all parking stands with accurate coordinate positioning, see aircraft occupancy colored by status, and click any stand to edit its data — all syncing directly to the master day sheet in real time.

---

## 2. Goals & Must-Haves

| # | Requirement | Detail |
|---|-------------|--------|
| G1 | Accurate apron map | Coordinate-accurate stand placement matching the AMC web app (View A & B coordinates) |
| G2 | Click-to-edit | Clicking any stand opens a floating edit panel; all fields editable |
| G3 | Per-day data sync | Reads from and writes to the correct daily sheet ('01'–'31') |
| G4 | Same color code logic | Matches the AMC PHP web app color system exactly |
| G5 | AMC visual design | Same dark blue color palette, fonts, and styling as the web app |

---

## 3. Scope & Platform

### 3.1 Platform
- **Google Sheets** (the existing "APRON MOVEMENT SHEET JULI 2025" document)
- **Google Apps Script (GAS)** — single project file attached to the spreadsheet
- **Implementation type:** HTML Service Dialog — full-screen modal panel triggered from a custom menu

### 3.2 V1 Scope (This PRD)
- HTML dialog panel (≈ full browser window)
- Apron map with all stands rendered as interactive buttons
- Per-stand floating edit panel (overlays the map on the right)
- Auto-save on field blur
- 15-second polling for live sync
- RON carry-over automation (silent)
- View A / View B toggle

### 3.3 V2 Backpocket (NOT in scope)
- Native cell-based visual map using conditional formatting + cell sizing to visually represent stand positions within the sheet grid itself (no GAS dialog needed)

---

## 4. File Structure (GAS Project)

**Single GAS project** attached to the Google Sheet:

```
Google Apps Script Project
│
├── Code.gs          ← All server-side GAS logic
└── ApronMap.html    ← Full HTML dialog (CSS + JS + HTML inline)
```

**Why single file:** Simpler to manage, easier for one developer to maintain. All CSS and JS are embedded inline in `ApronMap.html`.

---

## 5. Sheet Structure Reference

### 5.1 Daily Sheets ('01' through '31')

Each sheet represents one day (July 2025 day 1–31). All sheets follow the **same template**:

| Row | Content |
|-----|---------|
| 1 | Title: "APRON MOVEMENT SHEET HALIM PERDANAKUSUMA" |
| 7 | AERODROME : WIHH |
| 8 | TANGGAL (date) — date value is in column C |
| 9–10 | Staff roster (PETUGAS AMC — pagi/malam shift) |
| 15–16 | Column headers (NO, REGISTRATION, TYPE, ON BLOCK, OFF BLOCK, etc.) |
| 17 | Sub-headers |
| **18+** | **Movement data rows — one entry per stand** |

### 5.2 Column Mapping (Movement Data Rows)

| Column | Letter | Content | Editable | Notes |
|--------|--------|---------|----------|-------|
| 1 | A | NO (row number) | ❌ | Auto-incremented |
| 2 | B | REGISTRATION | ✅ | Primary input |
| 3 | C | TYPE | ✅ (override) | Auto via VLOOKUP: `=VLOOKUP(B,DB!$D$4:$E$800,2,0)` |
| 4 | D | ON BLOCK time | ✅ | Raw time input (e.g. "1430") |
| 5 | E | ON BLOCK checkbox | ❌ | Boolean |
| 6 | F | OFF BLOCK time | ✅ | Raw time input |
| 7 | G | OFF BLOCK checkbox | ❌ | Boolean |
| 8 | H | PARKING STAND | ❌ | Stand code (SA01, B1, etc.) — used for lookup |
| 9 | I | FROM | ✅ (override) | Auto via VLOOKUP from flight no ARR |
| 10 | J | TO | ✅ (override) | Auto via VLOOKUP from flight no DEP |
| 11 | K | FLIGHT NO ARR | ✅ | Raw input |
| 12 | L | FLIGHT NO DEP | ✅ | Raw input |
| 13 | M | OPERATOR / AIRLINES | ✅ (override) | Auto via VLOOKUP: `=VLOOKUP(B,DB!$D$4:$F$800,3,0)` |
| 14 | N | REMARKS | ✅ | Free text |
| 15 | O | STATUS | ❌ | Formula: `=IF(ISBLANK(F),"RON","")` |

### 5.3 DB Sheet

The DB sheet has two main lookup tables:
1. **Flight references** (cols A–B): FLIGHT NO → ROUTE
2. **Aircraft details** (cols D–F): REGISTRATION → TYPE, OPERATOR

---

## 6. GAS Server-Side Functions (`Code.gs`)

### 6.1 Menu Setup

```javascript
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('AMC Tools')
    .addItem('🛫 Open Apron Map', 'openApronMap')
    .addToUi();
}
```

### 6.2 `openApronMap()`

```javascript
function openApronMap() {
  const html = HtmlService.createHtmlOutputFromFile('ApronMap')
    .setWidth(1600)
    .setHeight(900);
  SpreadsheetApp.getUi().showModalDialog(html, '🛫 AMC Live Apron Map');
}
```

### 6.3 `getAllStandData(sheetName)`

Reads all movement data from the specified day sheet in one batch call.

**Logic:**
1. Open sheet by name (e.g. `'01'`)
2. Read all rows from row 18 downward until empty
3. For each row, extract: registration (B), type (C — resolved VLOOKUP value), onBlock (D), offBlock (F), stand (H), from (I), to (J), flightArr (K), flightDep (L), operator (M), remarks (N), status (O)
4. Return as JSON array of stand objects

**Returns:**
```javascript
[
  {
    stand: "SA01",
    registration: "PK-VCD",
    type: "B738",
    onBlock: "0800",
    offBlock: "",
    from: "WIII",
    to: "",
    flightArr: "GA101",
    flightDep: "",
    operator: "GARUDA",
    remarks: "",
    status: "RON",
    row: 29  // actual row number in sheet
  },
  ...
]
```

### 6.4 `updateStandField(sheetName, stand, fieldMap)`

Writes one or more fields to the sheet for a specific stand.

**Logic:**
1. Open sheet by name
2. Scan column H (rows 18+) to find the row where H = stand code
3. Write each field in `fieldMap` to its corresponding column
4. After writing registration → wait for VLOOKUP to compute → read back C (type) and M (operator) and return them
5. After writing flightArr/flightDep → read back I (from) and J (to) and return them

**Parameters:**
```javascript
updateStandField('01', 'SA01', {
  registration: 'PK-ABC',
  onBlock: '1430',
  offBlock: '',
  flightArr: 'GA101',
  flightDep: '',
  remarks: 'Test'
})
```

**Returns:**
```javascript
{ 
  success: true,
  autoFilled: {
    type: 'B738',
    operator: 'GARUDA',
    from: 'WIII',
    to: ''
  }
}
```

### 6.5 `getActiveSheetName()`

Returns the name of the sheet tab the user was on when they triggered the menu (the "context sheet").

```javascript
function getActiveSheetName() {
  return SpreadsheetApp.getActiveSpreadsheet().getActiveSheet().getName();
}
```

### 6.6 `carryOverRON(fromSheetName, toSheetName)`

Carries over RON aircraft from one day to the next.

**Logic:**
1. Read all rows from `fromSheetName` where STATUS (col O) = "RON"
2. For each RON row, find the matching row in `toSheetName` where PARKING STAND (col H) matches
3. If the `toSheetName` row has no registration yet (col B is empty):
   - Write registration (col B) from the RON row
   - Write onBlock = "EX RON" (col D)
   - Leave offBlock (col F) empty
4. Called silently on `openApronMap()` when the active sheet is a new day

**When to trigger:**
- On opening the apron map, if activeSheet = day `N`, check if day `N-1` has any RON rows
- If yes, and if the matching stand rows in day `N` have no registration → apply carry-over silently

---

## 7. Client-Side: `ApronMap.html`

### 7.1 Dialog Structure

```
+--------------------------------------------------------------+
| AMC LIVE APRON MAP   Day: 01 July 2025     [View A] [View B]|
| Status: 42 Occupied | 18 Available | 3 RON  [synced 5s ago] |
+--------------------------------------------------------------+
|                                                              |
|  [   APRON MAP — full width canvas, scrollable/zoomable   ] |
|                                                              |
|       + floating edit panel when stand is clicked           |
|         (right side overlay, semi-transparent backdrop)     |
+--------------------------------------------------------------+
```

### 7.2 Apron Canvas

- **Canvas size:** 1920 × 1080px (same as AMC web app)
- **Responsive scaling:** Same `resizeApron()` function logic from AMC's `apron.js`
  - `baseScale = wrapperWidth / 1920 * 0.98`
  - `container.style.transform = scale(baseScale)`
- **Ctrl+Scroll zoom:** Supported (same logic as experiment HTML)
- **View toggle:** "View A" / "View B" buttons — switches stand coordinate sets

### 7.3 Stand Coordinates

**Copy exactly from the AMC app:**

**View A stands** (from `resources/views/apron/index.php` lines 140–163):
```javascript
const standsViewA = {
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
```

**View B stands** (from `resources/views/apron/index.php` lines 169–199):
```javascript
const standsViewB = {
  // MAIN APRON
  'A0':[1875,903],'A1':[1818,903],'A2':[1761,903],'A3':[1704,903],
  'B1':[1647,903],'B2':[1590,903],'B3':[1533,903],'B4':[1476,903],
  'B5':[1419,903],'B6':[1362,903],'B7':[1305,903],'B8':[1248,903],
  'B9':[1191,903],'B10':[1134,903],'B11':[1077,903],'B12':[1020,903],'B13':[963,903],
  // REMOTE WEST
  'RW01':[1760,773],'RW02':[1705,773],'RW03':[1650,773],'RW04':[1595,773],
  'RW05':[1540,773],'RW06':[1485,773],'RW07':[1430,773],'RW08':[1375,773],
  'RW09':[1320,773],'RW10':[1265,773],'RW11':[1210,773],
  // REMOTE EAST
  'RE01':[1150,773],'RE02':[1100,773],'RE03':[1050,773],'RE04':[1000,773],
  'RE05':[950,773],'RE06':[900,773],'RE07':[850,773],
  // SOUTH APRON
  'SA01':[42,93],'SA02':[185,93],'SA03':[328,93],'SA04':[463,93],
  'SA05':[600,93],'SA06':[731,93],
  'SA07':[0,195],'SA08':[80,195],'SA09':[141,195],'SA10':[228,195],
  'SA11':[289,195],'SA12':[366,195],'SA13':[427,195],'SA14':[504,195],
  'SA15':[565,195],'SA16':[642,195],'SA17':[703,195],'SA18':[777,195],
  'SA19':[0,297],'SA20':[80,297],'SA21':[141,297],'SA22':[228,297],
  'SA23':[289,297],'SA24':[366,297],'SA25':[427,297],'SA26':[504,297],
  'SA27':[565,297],'SA28':[642,297],'SA29':[703,297],'SA30':[777,297],
  // NEW SOUTH APRON
  'NSA01':[943,195],'NSA02':[1015,195],'NSA03':[1087,195],'NSA04':[1159,195],
  'NSA05':[1231,195],'NSA06':[1303,195],'NSA07':[1376,195],
  'NSA08':[1448,231],'NSA09':[1448,307],
  'NSA10':[1015,343],'NSA11':[1087,343],'NSA12':[1159,343],
  'NSA13':[1231,343],'NSA14':[1303,343],'NSA15':[1376,343],
  // WEST REMOTE
  'WR01':[760,680],'WR02':[760,770],'WR03':[760,860]
};
```

---

## 8. Color Code Logic

**Must match the AMC web app exactly:**

| State | Visual | Trigger Condition |
|-------|--------|-------------------|
| **Empty / Available** | Blue gradient stand button (default) `linear-gradient(135deg, rgba(63,114,175,0.9), rgba(17,45,78,0.9))` | No registration in col B |
| **Planned** | Blue stand + **yellow ✈ icon ABOVE** stand | Registration exists, col D (on_block) is empty |
| **Occupied / Current** | Blue stand + **red ✈ icon BELOW** stand | Registration exists, col D (on_block) has value, col F (off_block) is empty |
| **RON** | **Yellow-highlighted stand** border + red ✈ icon | col O (STATUS) = "RON" (i.e., off_block is blank and this is a carry-over) |
| **Departed** | Stand returns to blue (no aircraft icon) | col F (off_block) has a value |

**Stand label shows:** Stand code (e.g., "SA01")  
**Aircraft icon label shows:** Registration + Flight No ARR (same as AMC web app)

---

## 9. Edit Panel (Floating Overlay)

### 9.1 Trigger
- Click any stand on the map → floating edit panel appears overlaying the right ~380px of the dialog
- Click empty space on the map (or X button) → panel closes

### 9.2 Panel Layout

```
+------------------------------------+
| ← [Stand Code]  SA01    [X close] |
|------------------------------------|
| Registration:  [PK-VCD         ]  |
| Type:          [B738           ]  |
| ON Block:      [0800           ]  |
| OFF Block:     [               ]  |
| From:          [WIII           ]  |
| To:            [               ]  |
| Arr Flight:    [               ]  |
| Dep Flight:    [               ]  |
| Operator:      [GARUDA         ]  |
| Remarks:       [               ]  |
|------------------------------------|
| Status: ● RON                      |
| 💾 Saving...  ✓ Saved             |
+------------------------------------+
```

### 9.3 Auto-Save Behavior

- Each input field: on **blur** event → call `updateStandField()` via `google.script.run`
- Show "Saving..." spinner while GAS call is in flight
- Show "✓ Saved [timestamp]" on success
- Show "⚠ Error saving" on failure (with red indicator)

### 9.4 Autofill Chain

**When REGISTRATION changes (blur):**
1. Write col B (registration) to sheet
2. GAS reads back col C (type) and col M (operator) VLOOKUP results
3. Fill TYPE and OPERATOR fields in panel
4. Map icon updates to reflect new registration

**When FLIGHT NO ARR changes (blur):**
1. Write col K (flightArr) to sheet
2. GAS reads back col I (from) VLOOKUP result
3. Fill FROM field in panel

**When FLIGHT NO DEP changes (blur):**
1. Write col L (flightDep) to sheet
2. GAS reads back col J (to) VLOOKUP result
3. Fill TO field in panel

**All other fields:** Write directly on blur, no autofill side effects.

---

## 10. Live Sync / Polling

### 10.1 Strategy
- `setInterval()` every **15 seconds**
- Calls `google.script.run.getAllStandData(currentSheetName)` 
- On response: re-render all stand icons on the map
- Show "Last synced: Xs ago" indicator in the header

### 10.2 Sync Indicator (Header)
```
🟢 Live  |  Last synced: 8s ago  |  🔴 3 Occupied  |  🟡 1 RON  |  🟢 79 Available
```

### 10.3 Edit Panel vs Poll Conflict
- When a user is actively editing (edit panel is open), pause polling to avoid overwriting in-progress changes
- Resume polling when edit panel is closed

---

## 11. RON Carry-Over Logic

### 11.1 Trigger
- Fires automatically when `openApronMap()` is called
- Determines current day N from active sheet name (e.g., "10" → day 10)
- Checks day N-1 sheet (e.g., "09") for RON rows

### 11.2 Logic Steps
```
1. currentDay = activeSheet.getName() (e.g., "10")
2. prevDay = (parseInt(currentDay) - 1).toString().padStart(2, '0') (e.g., "09")
3. If prevDay sheet exists:
   a. Read all rows from prevDay where col O = "RON"
   b. For each RON row:
      - Get stand code (col H), registration (col B), type (col C), operator (col M)
      - Find matching row in currentDay sheet where col H = stand code
      - If currentDay row col B is empty (no registration set yet):
        → Write registration to col B
        → Write "EX RON" to col D (on_block)
        → Leave col F (off_block) empty
4. After carry-over, refresh the map display
```

### 11.3 Edge Cases
- Day 1: no prev day → skip carry-over
- If currentDay row already has a registration → do NOT overwrite (don't disturb existing data)

---

## 12. How to Open the Dialog (Operator Workflow)

1. Open the Google Sheets document
2. Click **AMC Tools** in the menu bar
3. Click **🛫 Open Apron Map**
4. Dialog opens full-screen (1600×900px)
5. The map auto-loads data from the currently active sheet tab
6. If it's a new day and previous day had RON aircraft → carry-over happens silently
7. Apron map renders with color-coded stands
8. Click any stand → floating edit panel appears
9. Edit fields → auto-save on blur
10. Close edit panel → click another stand or empty space
11. Close dialog → everything is already saved in the sheet

---

## 13. Implementation Steps (For Developer)

### Step 1: Create GAS Project
1. In the Google Sheet: **Extensions → Apps Script**
2. Rename the default `Code.gs` file
3. Create `ApronMap.html` file

### Step 2: Implement `Code.gs`
Implement these functions in order:
1. `onOpen()` — menu setup
2. `openApronMap()` — dialog launcher
3. `getActiveSheetName()` — returns current sheet tab name
4. `getAllStandData(sheetName)` — batch read of all stand rows
5. `updateStandField(sheetName, stand, fieldMap)` — write + read-back autofill
6. `carryOverRON(fromSheet, toSheet)` — RON carry-over logic

### Step 3: Implement `ApronMap.html`
Build in this order:
1. HTML structure (dialog header, map wrapper, canvas container)
2. CSS: AMC color palette, stand button styling, floating edit panel, header status bar
3. JavaScript: stand coordinates (View A + B), stand rendering function, color logic
4. JavaScript: click handler → show edit panel
5. JavaScript: edit panel auto-save (blur listeners + `google.script.run` calls)
6. JavaScript: autofill chain (registration → type/operator, flight → from/to)
7. JavaScript: polling loop (15-second setInterval)
8. JavaScript: zoom/scale (copy from apron-experiment.html)
9. JavaScript: view toggle (View A / View B)

### Step 4: Test Sequence
1. Test `onOpen()` → verify AMC Tools menu appears
2. Test `getAllStandData('01')` → verify all stands read correctly from sheet 01
3. Test `updateStandField('01', 'SA01', {registration: 'PK-TEST'})` → verify write + VLOOKUP read-back
4. Test `carryOverRON('01', '02')` → verify RON rows carry over
5. Test dialog rendering → verify all stands appear at correct positions
6. Test color logic → verify empty/planned/occupied/RON colors
7. Test edit panel → verify all fields save correctly
8. Test autofill → type a known registration, verify type/operator autofill
9. Test polling → change a cell directly in the sheet, verify map updates within 15s
10. Test View A/B toggle → verify coordinate switch

---

## 14. Technical Constraints & Notes

### GAS Execution Limits
- GAS scripts have a **6-minute** execution time limit (batch reads/writes are well under this)
- `google.script.run` calls are **asynchronous** — always use `.withSuccessHandler().withFailureHandler()` pattern
- GAS does not support `await`/`async` in HTML service client code — use callback pattern

### VLOOKUP Read-Back Timing
- After writing to col B, the VLOOKUP in col C may not instantly resolve in GAS (it's formula-based)
- Use `SpreadsheetApp.flush()` before reading back computed values to ensure the formula has recalculated
- Pattern: `sheet.getRange(row, 2).setValue(registration); SpreadsheetApp.flush(); const type = sheet.getRange(row, 3).getValue();`

### Dialog Size
- Max dialog size: `setWidth(1600).setHeight(900)` — tested on standard 1920×1080 monitor
- The HTML dialog has its own scrollbar; the apron canvas scales to fit

### Column Indexing
- GAS uses **1-based column indexing** (col A = 1, col B = 2, etc.)
- Column reference table for `getRange(row, col)`:
  - B=2 (registration), C=3 (type), D=4 (onBlock), F=6 (offBlock)
  - H=8 (stand), I=9 (from), J=10 (to)
  - K=11 (flightArr), L=12 (flightDep), M=13 (operator), N=14 (remarks), O=15 (status)

### Data Start Row
- Movement data always starts at **row 18** in all daily sheets
- Hardcode `const DATA_START_ROW = 18;`

---

## 15. Color Palette Reference

```css
/* AMC Color System */
--amc-blue: #3F72AF;
--amc-dark-blue: #112D4E;
--amc-light: #DBE2EF;
--amc-bg: #F9F7F7;

/* Stand Colors */
--stand-empty: linear-gradient(135deg, rgba(63,114,175,0.9), rgba(17,45,78,0.9));
--stand-ron: border: 3px solid #EAB308; background: linear-gradient(135deg, rgba(234,179,8,0.3), rgba(63,114,175,0.9));

/* Aircraft Icons */
--icon-planned: yellow  /* ✈ above stand */
--icon-current: red     /* ✈ below stand */
```

---

## 16. Open Questions / Future Decisions

| # | Question | Default |
|---|----------|---------|
| Q1 | What happens if a stand code in the sheet doesn't match any coordinate in the map? | Skip/ignore — show in a debug list |
| Q2 | Should the AERODROME and date header in the dialog be editable? | Read-only display only |
| Q3 | Should the staff roster (PETUGAS AMC) be visible/editable in the map dialog? | Out of V1 scope |
| Q4 | What if the user is on the 'DB' sheet when they open the map? | Default to today's day sheet |
| Q5 | Time format — does ON BLOCK accept "1430" (no colon) or "14:30"? | Accept both, store as-is |

