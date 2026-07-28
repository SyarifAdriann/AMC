# Live Apron Map — How It Works
### Simple Explainer Guide
**For:** AMC Operators & Project Reference  
**Version:** 1.0

---

## What Is This?

The **Live Apron Map** is a new interactive panel that opens inside your existing "APRON MOVEMENT SHEET JULI 2025" Google Sheets document. Instead of scanning the data table row by row, you'll see a **visual map of all parking stands** at Halim Perdanakusuma — just like the AMC web program — and you can click any stand to edit it directly.

---

## What It Looks Like

When you open it, you see:

```
+------------------------------------------------------------------+
| 🛫 AMC Live Apron Map   |  01 July 2025  | [View A] [View B]    |
| 🟢 79 Available  🔴 3 Occupied  🟡 1 RON  |  Last synced: 8s ago |
+------------------------------------------------------------------+
|                                                                  |
|   [  All parking stands shown as clickable buttons              ]|
|   [  Color-coded: blue=empty, yellow plane=planned,             ]|
|   [  red plane=occupied, yellow stand=RON                       ]|
|                                                                  |
+------------------------------------------------------------------+
```

When you click a stand, a floating panel appears on the right showing all the details for that stand — fully editable.

---

## How It Connects to the Existing Sheets

### The Connection Flow

```
Your existing sheets           ←→         The Apron Map Panel
─────────────────────────────────────────────────────────────────
Daily sheets ('01', '02'...)  ←READ─  Loads all movement data
Col B: REGISTRATION            ←WRITE─ When you type in the panel
Col C: TYPE (VLOOKUP)          ←READ─  Shows auto-filled value
Col D: ON BLOCK time           ←WRITE─ When you type in the panel
Col F: OFF BLOCK time          ←WRITE─ When you type in the panel
Col H: PARKING STAND           (never changes — used for lookup)
Col I: FROM (VLOOKUP)          ←READ─  Shows auto-filled value
Col J: TO (VLOOKUP)            ←READ─  Shows auto-filled value
Col K: FLIGHT NO ARR           ←WRITE─ When you type in the panel
Col L: FLIGHT NO DEP           ←WRITE─ When you type in the panel
Col M: OPERATOR (VLOOKUP)      ←READ─  Shows auto-filled value
Col N: REMARKS                 ←WRITE─ When you type in the panel
Col O: STATUS (formula)        ←READ─  Shows RON/empty status
DB sheet                       ←READ─  Used by VLOOKUP (no changes)
```

**Key point:** The apron map does NOT replace or duplicate your existing sheet formulas. The VLOOKUP formulas you already have (for auto-filling TYPE, OPERATOR, FROM, TO) continue to work exactly as before. The map just writes to the raw input cells and reads back whatever the formula computed.

---

## How Linking Works (Technical Setup)

### One-Time Setup Steps

1. **Open the Google Sheet**
2. In the menu bar: **Extensions → Apps Script**
3. This opens the Google Apps Script editor for this document
4. Paste the provided `Code.gs` content into the script editor
5. Click **+ New file → HTML** and name it `ApronMap`
6. Paste the provided `ApronMap.html` content
7. Click **Save** (disk icon)
8. Go back to your Google Sheet and **refresh the page**
9. A new menu item **"AMC Tools"** will appear in the menu bar
10. Done — the apron map is now linked to your sheet

That's it. The script lives inside the document. No external servers, no internet dependency, no extra accounts needed. It's part of the file.

---

## Day-by-Day Operation

### Normal Day (e.g., opening Day 10)

1. You're on the "10" sheet tab (or any tab)
2. Click **AMC Tools → 🛫 Open Apron Map**
3. The map opens, reads all data from the Day 10 sheet
4. All aircraft show up on their stands, color-coded
5. You click a stand, edit details, it saves automatically
6. The map refreshes every 15 seconds, so if a colleague edits the sheet directly, you'll see their changes on the map within 15 seconds

### RON Carry-Over (next morning)

When you open the map on Day 11:
- The system automatically checks Day 10 for any aircraft that stayed overnight (RON = no off_block time)
- Those aircraft are silently copied to Day 11's rows for their same stands
- Their ON BLOCK time is set to "EX RON"
- You see them already on the map when it opens
- No manual copy-pasting needed

### Editing a Stand

1. Click the stand → floating panel appears
2. Type the registration (e.g., `PK-VCD`)
   - TYPE and OPERATOR auto-fill from the DB sheet
3. Type the arrival flight number → FROM auto-fills
4. Fill ON BLOCK time (e.g., `0800`)
5. Move to the next field → it saves automatically
6. The stand's color updates on the map to reflect occupancy
7. Close the panel by clicking the X or clicking empty space

---

## Color Code (Same as AMC Web App)

| What You See | What It Means |
|---|---|
| Blue stand (no icon) | Stand is empty / available |
| Blue stand + **yellow ✈ above** | Aircraft assigned, not yet arrived (planned) |
| Blue stand + **red ✈ below** | Aircraft is on block (currently parked) |
| **Yellow-bordered stand** + red ✈ | RON — aircraft stayed overnight |
| Stand returns to plain blue | Aircraft departed (off block time filled in) |

---

## View A vs View B

Just like the AMC web program, you can toggle between two map layouts:

- **View A** — Stylized layout (organized for easy reading, matches what operators already know)
- **View B** — Real physical layout (reflects actual Halim apron geography)

Both views show the same data — just different visual positions.

---

## What Stays the Same (Nothing Breaks)

- All your existing VLOOKUP formulas continue to work
- All daily sheets remain unchanged in structure
- The DB sheet is only read, never written to
- You can still edit cells directly in the sheet the normal way
- The map and the sheet stay in sync — they're the same data

---

## What the Map Does NOT Do

- It does not modify the DB sheet
- It does not change any existing formulas
- It does not create new sheets
- It does not connect to any external system
- It does not require internet beyond normal Google Sheets access
- It cannot edit data from the RON carry-over if that stand already has a registration in the new day's sheet (safety guard)

---

## V2 Idea (Future)

A future version could eliminate the dialog panel entirely by using the sheet cells themselves as the visual apron map — coloring individual cells in the exact positions of the stands using conditional formatting and emoji/text. This would make the map visible directly on the sheet without needing to open any popup. This is kept for later iteration.

