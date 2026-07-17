# Monthly Charter Report (PERGERAKAN format) — How It Works

## What it is

**AMC Tools → 📊 Generate Charter Report** rebuilds a `CHARTER FLIGHT` tab (last
tab of the month file) in the exact layout of the unit's manual *PERGERAKAN
PESAWAT* report: one row per aircraft **visit**, with merged MASUK / KELUAR
sections:

`NO | OPERATOR | REG | TYPE AC | TANGGAL MASUK | TIME | ARRIVAL (EX + code) | TANGGAL KELUAR | TIME | DEPARTURE (TO + code)`

Run it anytime — it deletes and regenerates the whole tab from the day sheets
(01–31). It is a snapshot, not live formulas: regenerate after entering new
movements (takes a few seconds).

## The rules (reverse-engineered from the unit's own manual report)

Validated against May 2025: the generator produced 180 visits vs 174 manual
rows, with 158/172 exact (reg, masuk, keluar) matches — the residual
differences were manual-entry errors in the old report (split rows, unclosed
departures, one date typo), which the generator handles correctly.

1. **Foreign-registered aircraft only** — registrations starting `PK` are
   never listed (the manual contains zero PK- rows). This is what separates
   charter/CIP *visits* from the Halim-based fleets of the same operators.
2. **Operator not on the excluded list** (`DB!H`, editable in-sheet):
   commercial (GARUDA, BATIK AIR, CITILINK, PELITA, SUSI AIR), cargo
   (AIRNESIA, JAYAWIJAYA, TRI MG, TRIGANA), and TNI AU (military → VIP/VVIP
   report, not charter).
3. **Not a VVIP/state flight** — visits whose REMARKS contain a
   head-of-state marker (`RI 1`–`RI 4`, `VVIP`, `PM`, `PRESIDENT`,
   `PRESIDEN`, `RAJA`, `KING`, `QUEEN`, `SULTAN` — whole-word match,
   keyword list editable in `DB!I`) go to the VIP/VVIP report instead,
   even when the operator is charter-eligible (e.g. GAPURA-handled state
   guests like `PM CHINA`, `PRESIDENT SENAT OF CAMBODIA`). Minister-level
   remarks (MENPORA, MENT ESDM, MENTAN, ...) do NOT exclude — those are
   charter/CIP in the manual report.
4. **MASUK** = the movement row where the aircraft arrived from an *airport*
   (FROM is a 4-letter ICAO code, e.g. WSSL). **KELUAR** = the row where it
   departed to an airport (TO is an ICAO code).
5. **Tows and repositions don't split a visit** — rows whose FROM/TO is a
   stand, `HGR`, etc. keep the visit open, so an aircraft that arrives, gets
   towed to the hangar, returns to a stand, and departs a week later is
   still ONE row.
6. **`-` placeholders** exactly like the manual: masuk `-` when the aircraft
   was already on the ground at the start of the month; keluar `-` when it is
   still on the ground at month end.

## Formatting

- Title rows: `CHARTER FLIGHT / CIP FLIGHT` + `BULAN <month> <year>`
  (month taken from the day sheets' own date cells).
- Two-tier header with merged MASUK/KELUAR groups, grey header background.
- Everything center-aligned (horizontal + vertical), full borders,
  columns auto-fitted with padding.
- Dates formatted `dd-mm-yyyy`; times as `HH:MM` text.

## Troubleshooting

- **A visit is missing** — check the movement rows on the day sheets: the
  registration must be non-PK, the operator must not match `DB!H`, and the
  arrival/departure row needs a valid 4-letter ICAO code in FROM/TO plus a
  time in ON/OFF BLOCK.
- **A visit shows masuk or keluar `-` unexpectedly** — the corresponding
  movement row's FROM/TO isn't an ICAO code (typo, or entered as a stand).
- **An operator should/shouldn't be excluded** — edit the list in `DB!H4:H`
  and regenerate.
