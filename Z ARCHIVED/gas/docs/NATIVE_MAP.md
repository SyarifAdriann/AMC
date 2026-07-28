# Native In-Sheet Apron Map (quota-free)

## Why this exists

The HTML apron map polls the server every 15s — ~5,760 Apps Script executions
per day *per open tab*, which busts the free 90-min/day quota (and pools all
users onto the deployer's quota in "Execute as: Me" mode). Not operationally
viable for a 24/7 unit.

The **MAP tab** solves this by being pure spreadsheet: after a one-time build,
**no script ever runs**. Live sync comes from Google Sheets' own realtime
collaboration — unlimited operators, unlimited hours, zero quota.

## Build (one time)

**AMC Tools → 🗺️ Build Native Map** — takes a few seconds, creates the `MAP`
tab as the first tab. Rebuild anytime (it asks before replacing).

## What you get

- **Coordinate-accurate layout**: every stand placed on a 20px cell grid at
  the same View-A positions as the web map (83 stands, collision-checked).
- **Live status colors** via 2 conditional-formatting rules keyed on marker
  emoji in the stand text:
  - blue = available / departed-free
  - 🟡 amber = planned (registration, no on-block)
  - 🔴 red = occupied or RON (aircraft on the stand)
  - RON gets no separate color (it was confusable) — the RON counter and
    the panel status line still identify RON aircraft
- Occupied/planned/RON stands show the registration under the stand code,
  and the arrival flight number on a third line when one is recorded.
- Block times display as `hh:mm` everywhere (panel included); times typed
  into the panel as `930` or `0930` are normalized to `09:30` on save.
- **Live counters** in the header: FREE / OCCUPIED / RON / PLANNED.
- **Day selector** (cell D2 dropdown, 01–31) + live date label from the day
  sheet's C8.
- **Click-to-edit**: click a stand → the fixed **side panel** (right of the
  map) shows its current movement instantly and lets you edit it in place.
  Each stand cell is also a hyperlink chip that jumps to the movement row in
  the day sheet (free stands jump to the first empty template row).

## How it works (for reference)

Hidden columns DA–DS on the MAP tab hold the formula engine:

| Col | Content |
|---|---|
| ED:ER | staging spill — ONE volatile `INDIRECT` pulls the selected day's A18:O367; everything below reads this local copy (this is what keeps recalc fast) |
| DA | stand codes (static) |
| DB | latest movement row per stand — `MAP(codes, XMATCH(code, spill-H, 0, -1))` (bottom-up exact match) |
| DC/DD/DE | registration / on-block / off-block at that row |
| DF | status (`av/pl/oc/ro/de`) — same logic as the web map |
| DG | display text incl. the emoji marker that drives conditional formatting |
| DI:DJ | day-sheet name → GID table (for the hyperlinks) |
| DK | first empty template row (link target for free stands) |
| DL | active day's GID |
| DM–DP | type / from / to / operator at the latest row |
| DQ–DS | flight arr / flight dep / remarks at the latest row |
| DT | hidden row anchor for follow-up panel saves |

Each stand cell is one `LET/XLOOKUP/HYPERLINK` formula reading DG/DB.
~6 array formulas + 83 stand formulas total; recalc is instant.

## Edit panel — fixed side panel, instant loading

The MAP tab has a built-in **side panel** to the right of the map:

1. **Click a stand block** (or pick it from the panel's stand dropdown) →
   every panel field updates **instantly**, because the fields are live
   formulas over the engine — no script runs to display data, and the
   fields keep tracking the day sheet as colleagues edit it.
2. **Type over a yellow input field** (REG, ON/OFF BLOCK, FLT ARR/DEP,
   REMARKS) → `onEdit` commits the entry straight to the day sheet using
   the same row-anchored/append logic as the HTML map (checkbox E/G
   handling included), then restores the live formula so the cell goes
   back to tracking the sheet. A toast confirms the saved row. Times
   keep their leading zero (`0930` stays `0930`).
3. TYPE / FROM / TO / OPERATOR / status are **live read-only fields**
   (the sheet's VLOOKUPs still do the autofill after REG / flight numbers
   are saved).
4. **CLEAR checkbox** = the Clear Stand button (resets the movement
   row, unticks itself).

### Why this doesn't hit quota

These are **simple event triggers** — they run only when someone actually
clicks or types, for a fraction of a second, as the acting user. There is
no polling and no background execution: a map left open 24/7 on a wall
display consumes **zero** script time. A busy day of a few hundred edits
costs a couple of minutes of runtime, spread across the operators doing
the editing. (The engine formulas driving colors/labels use no script
time at all.)

## Limitations vs the HTML map

- View A only (View B can be added as a second map area if needed).
- The edit panel is **shared state** — two operators editing *via the
  panel* at the same moment would fight over it. In practice: one
  controller uses the map panel, others edit the day sheet directly
  (both portals stay in sync live).
- Panel fields are live formulas; typing into a **display** (blue) field
  would overwrite its formula — rebuild the map to restore it. Only the
  yellow fields are meant for typing (those restore themselves).
- `onSelectionChange` can lag ~a second on slow connections — that only
  delays click-to-select, not the data display; the stand dropdown in
  the panel is the always-reliable fallback.

## Other AMC Tools tabs

All operator-facing notices, toasts and alerts are in **Indonesian**;
table/column headers stay in English.

- **SEARCH** (`Build Search Tab`) — type a date (e.g. `18`), registration,
  stand, flight number or operator in B2; results appear instantly. The
  match haystack is built per day sheet from real-range concatenation
  (the only array pattern proven reliable in this workbook — LAMBDA and
  INDEX-slicing versions all broke). The DATE column = which day sheet
  (tanggal 01-31) the movement came from, and the date is searchable.
  An onEdit hook borders + auto-fits the results. If results ever show a
  real error code (#…), that's a formula fault to report — IFNA only
  masks genuine "no matches".
- **DASH** (`Build Dashboard`) — the PHP dashboard's metrics: KPI rows
  (movements/arrivals/departures/on-ground + live stands
  free/occupied/RON/planned), arrivals/departures by category
  (operator→category map seeded into DB!J:K, editable), movements by hour
  with a chart, busiest stands top-10, and a **stand-usage Gantt chart**
  (stacked-bar trick: invisible lead segment + red occupied segment; ALL
  83 stands listed in map order, unused ones show an empty row; earliest
  arrival → latest departure per stand). Day dropdown in D2.
- **Daily RON carry-over** (`Enable Daily RON Carry-Over`) — installs a
  time trigger that runs between 00:00-01:00 and copies RON aircraft into
  the new day's sheet. **Triggers do not copy with the workbook** — re-run
  the menu item in each new month's file.

## Coexistence

The HTML map (ApronMap.html) is retired from the menu — the MAP tab is the
portal. The web-app code remains in the project but nothing points to it.
