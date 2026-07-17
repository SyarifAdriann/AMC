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
- **Live status colors** via 3 conditional-formatting rules keyed on marker
  emoji in the stand text:
  - blue = available / departed-free
  - 🟡 amber = planned (registration, no on-block)
  - 🔴 red = occupied (on-block, no off-block)
  - 🌙 gold = RON carry-over (on-block `-` / `EX RON` / date suffix)
- Occupied/planned/RON stands show the registration under the stand code.
- **Live counters** in the header: FREE / OCCUPIED / RON / PLANNED.
- **Day selector** (cell D2 dropdown, 01–31) + live date label from the day
  sheet's C8.
- **Click-to-edit**: click a stand → click its link chip → jumps straight to
  that stand's current movement row in the day sheet (registration column).
  Free stands jump to the **first empty template row** so you can start a new
  movement (type registration + stand; the sheet's VLOOKUPs autofill
  type/operator/route as always).

## How it works (for reference)

Hidden columns DA–DL on the MAP tab hold the formula engine:

| Col | Content |
|---|---|
| DA | stand codes (static) |
| DB | latest movement row per stand — `MAP(codes, MAX(IF(H=code, SEQUENCE…)))` over `INDIRECT` of the selected day |
| DC/DD/DE | registration / on-block / off-block at that row |
| DF | status (`av/pl/oc/ro/de`) — same logic as the web map |
| DG | display text incl. the emoji marker that drives conditional formatting |
| DI:DJ | day-sheet name → GID table (for the hyperlinks) |
| DK | first empty template row (link target for free stands) |
| DL | active day's GID |

Each stand cell is one `LET/XLOOKUP/HYPERLINK` formula reading DG/DB.
~6 array formulas + 83 stand formulas total; recalc is instant.

## Edit panel — click-and-edit ON the map

The MAP tab has a built-in **EDIT PANEL** card (center of the map area):

1. **Click a stand block** → `onSelectionChange` loads it into the panel
   (or pick it from the panel's stand dropdown).
2. **Type into the yellow input fields** (REG, ON/OFF BLOCK, FLT ARR/DEP,
   REMARKS) → `onEdit` commits each entry straight to the day sheet using
   the same row-anchored/append logic as the HTML map (checkbox E/G
   handling included). A toast confirms the saved row.
3. TYPE / FROM / TO / OPERATOR / status are **live read-only fields**
   (the sheet's VLOOKUPs still do the autofill after REG / flight numbers
   are saved).
4. **CLEAR ROW checkbox** = the Clear Stand button (resets the movement
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

- RON is a gold **fill**, not a border (conditional formatting can't do
  borders).
- View A only (View B can be added as a second map area if needed).
- The edit panel is **shared state** — two operators editing *via the
  panel* at the same moment would fight over it. In practice: one
  controller uses the map panel, others edit the day sheet directly
  (both portals stay in sync live).
- Input fields are snapshots loaded on click; if someone else changes that
  stand from the day sheet, re-click the stand to reload (the read-only
  fields and colors update live regardless).
- `onSelectionChange` can lag ~a second on slow connections — the stand
  dropdown in the panel is the always-reliable fallback.

## Coexistence

The HTML map (dialog + web app) still exists and is fine for **occasional**
interactive use. The MAP tab is the one to leave open 24/7 on ops displays.
