# Setup Walkthrough: Live Apron Map in Google Sheets

## What Was Built

Two files in `c:\xampp\htdocs\AMC\gas\`:

| File | Size | Purpose |
|------|------|---------|
| [Code.gs](file:///c:/xampp/htdocs/AMC/gas/Code.gs) | 13 KB | Server-side GAS: reads/writes sheet data, RON carry-over |
| [ApronMap.html](file:///c:/xampp/htdocs/AMC/gas/ApronMap.html) | 45 KB | Client-side HTML dialog: full apron map UI |

---

## How to Install (One-Time Setup)

### Step 1 — Open Apps Script

1. Open your **"APRON MOVEMENT SHEET JULI 2025"** Google Sheet
2. In the top menu: **Extensions → Apps Script**
3. The Apps Script editor opens in a new browser tab

### Step 2 — Paste Code.gs

1. In the Apps Script editor, click the file named `Code.gs` (left sidebar)
2. **Select all** existing content (Ctrl+A) and **delete it**
3. Open [Code.gs](file:///c:/xampp/htdocs/AMC/gas/Code.gs) and copy its entire contents
4. Paste into the Apps Script editor
5. Click **Save** (💾 icon or Ctrl+S)

### Step 3 — Create ApronMap.html

1. In the Apps Script editor left sidebar, click **+ (Add a file)**
2. Choose **HTML**
3. Name it exactly: `ApronMap` *(no extension — GAS adds .html automatically)*
4. A new file opens with default content — **select all and delete it**
5. Open [ApronMap.html](file:///c:/xampp/htdocs/AMC/gas/ApronMap.html) and copy its entire contents
6. Paste into the Apps Script editor
7. Click **Save**

### Step 4 — Authorize the Script

1. In the Apps Script editor, click **Run** (▶) on the `onOpen` function
2. A permissions dialog will appear — click **Review permissions**
3. Choose your Google account
4. Click **Advanced → Go to [Project name] (unsafe)** *(this is expected for personal scripts)*
5. Click **Allow**

### Step 5 — Reload Google Sheets

1. Go back to your Google Sheet tab
2. **Refresh the page** (F5)
3. After loading, you should see a new **"AMC Tools"** menu in the menu bar

### Step 6 — Open the Apron Map

1. Click **AMC Tools → 🛫 Open Apron Map**
2. The apron map dialog opens full-screen
3. It auto-detects which day sheet you're on and loads that day's data

---

## What Each Part Does

### Code.gs Functions

| Function | What it does |
|----------|-------------|
| `onOpen()` | Creates the "AMC Tools" menu when the sheet opens |
| `openApronMap()` | Triggers RON carry-over then opens the HTML dialog |
| `getActiveSheetName()` | Returns current sheet tab name ('01', '10', etc.) |
| `getAllStandData(sheet)` | Batch-reads all 80+ stand rows from a day sheet |
| `updateStandField(sheet, stand, data)` | Writes fields to a stand's row + reads VLOOKUP results back |
| `carryOverRON(prev, curr)` | Silently copies RON aircraft to the next day sheet |
| `getSheetDate(sheet)` | Returns the date label from row 8, col C |

### ApronMap.html Features

- **View A / View B toggle** — same coordinate sets as the AMC web app
- **Stand colors**: blue=available, yellow icon=planned, red icon=occupied, yellow border=RON
- **Click any stand** → floating edit panel slides in from right
- **All fields editable** — registration, on/off block, flight numbers, operator, remarks
- **Auto-save on blur** — saves each field as you move away from it
- **Autofill**: type registration → TYPE and OPERATOR auto-fill from DB sheet VLOOKUP
- **Autofill**: type arrival flight # → FROM auto-fills
- **15-second polling** — keeps map in sync with live sheet edits
- **Ctrl+Scroll** — zoom in/out on the map
- **Escape key** — closes the edit panel

---

## Testing Checklist

After installation, verify these work:

- [ ] "AMC Tools" menu appears in Google Sheets menu bar
- [ ] "Open Apron Map" opens a full-screen dialog
- [ ] Stands appear at correct positions on the map
- [ ] Aircraft from the sheet show up with correct icons (yellow/red)
- [ ] RON stands show yellow border
- [ ] Clicking a stand opens the edit panel
- [ ] Editing registration auto-fills TYPE and OPERATOR
- [ ] "Saved ✓" appears after each field change
- [ ] Synced indicator shows in the header
- [ ] View A / View B toggle repositions stands
- [ ] Ctrl+Scroll zooms the map

---

## Troubleshooting

| Problem | Solution |
|---------|---------|
| "AMC Tools" menu doesn't appear | Refresh the page, or re-run `onOpen()` manually from Apps Script editor |
| "Sheet not found" error | Make sure you're on a day sheet ('01'–'31') when opening the map |
| No stands appear on map | Check browser console for errors; verify Apps Script authorization was granted |
| Auto-fill doesn't work | The DB sheet VLOOKUP must reference the exact same registration format. Check DB!$D$4:$E$800 range |
| "Save failed" error | Check Apps Script execution log (Extensions → Apps Script → Executions) for details |
| Edit panel doesn't open | Try clicking the stand button text directly, not the colored background |

---

## Column Reference (for debugging)

If you need to verify the GAS is reading/writing the right columns:

```
Col A (1):  NO (row number)
Col B (2):  REGISTRATION  ← written by GAS
Col C (3):  TYPE          ← VLOOKUP, read-back by GAS
Col D (4):  ON BLOCK      ← written by GAS
Col E (5):  ON BLOCK CB   (checkbox, not touched)
Col F (6):  OFF BLOCK     ← written by GAS
Col G (7):  OFF BLOCK CB  (checkbox, not touched)
Col H (8):  PARKING STAND ← used for lookup (never written)
Col I (9):  FROM          ← VLOOKUP, read-back by GAS
Col J (10): TO            ← VLOOKUP, read-back by GAS
Col K (11): FLIGHT NO ARR ← written by GAS
Col L (12): FLIGHT NO DEP ← written by GAS
Col M (13): OPERATOR      ← VLOOKUP, read-back by GAS
Col N (14): REMARKS       ← written by GAS
Col O (15): STATUS (RON)  ← formula, read only
Data starts at: Row 18
```
