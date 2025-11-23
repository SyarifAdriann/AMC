# Phase 8 – Post-Prediction Filtering

## Logic Updates Implemented
- **Availability baseline (`app/Controllers/ApronController.php:1004`)** now falls back to the full apron stand catalog whenever the `stands` table returns no active rows, ensuring we always have a universe of stands to compare against and subtract the occupied set derived from live movements.
- **Preference sourcing (`app/Controllers/ApronController.php:1087`)** accepts airline, category, aircraft type, and the current availability list. It queries `airline_preferences` (matching aliases like `GAR` → `GARUDA`), normalizes categories (Commercial/Cargo/Charter, plus Komersial/Domestik/VIP etc.), and falls back in this order:
  1. Airline-specific rows (type-specific first, then general).
  2. Historical stand frequencies for the supplied category via `aircraft_movements` + `aircraft_details`.
  3. Charter historical distribution if the category itself is unknown.
  4. Ranked list of currently available stands when no stored knowledge exists (gives operators something deterministic even on true cold starts).
- **Combined scoring (`app/Controllers/ApronController.php:871`)** now follows the handbook formula `score = (0.6 × probability) + (0.4 × normalizedPreference)` with `normalizedPreference = preference_score / 100`. This keeps the ML model in the driver seat while letting airline prefs sway the ordering.
- **Fallback resilience (`app/Controllers/ApronController.php:1187`)** builds deterministic stand suggestions by walking available stands, then raw predictions, then recently occupied stands—ensuring three candidates are always returned even if the model provides unusable stands.

## Scenario Tests
| Scenario | Payload | Highlight | Evidence |
| --- | --- | --- | --- |
| Occupied filter | `{"aircraft_type":"B 738","operator_airline":"GARUDA","category":"Komersial"}` | Raw predictions were `B2/B4/B5`, but B4 & B5 were in the occupied list so only B2 survived. Shows availability guard removing blocked stands. | `reports/phase8_case_occupied.json` |
| Cold start airline | `{"aircraft_type":"ATR 72","operator_airline":"SPACEJET","category":"Cargo"}` | Airline not in `airline_preferences`. Historical cargo usage re-ranked the top-3 (`B11` bubbled above `B10`), demonstrating the category-frequency fallback and composite scoring. | `reports/phase8_case_cold_start.json` |
| Missing category | `{"aircraft_type":"GLEX","operator_airline":"ROYAL WINGS","category":"VIP"}` | Unknown category auto-mapped to Charter distribution, producing a preference map seeded by charter history (see `preferences` block) even though the airline has no configuration. | `reports/phase8_case_missing_category.json` |

Each evidence file captures:
1. The **raw ML output** (`raw_predictions`).
2. The **availability snapshot** (lists of `available` and `occupied` stands at request time).
3. The final **recommendations array** including probability, preference score, and composite score for auditing.

## Before/After Snapshot
- **Before filtering:** For GARUDA the model suggested `B2/B4/B5` purely by probability.
- **After filtering:** File `phase8_case_occupied.json` shows `B4/B5` removed because they appear in `availability.occupied`, leaving only `B2` (with a boosted composite score thanks to a 100-point airline preference). Notes now explicitly state “Recommendations filtered by availability and airline preferences.”

## How to Replay Tests
```powershell
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
Invoke-WebRequest 'http://localhost/amc/public/login.php' -Method Post `
    -Body @{username='amc';password='mikecharlie';login='Login'} -WebSession $session | Out-Null
$payload = @{ aircraft_type = 'ATR 72'; operator_airline = 'SPACEJET'; category = 'Cargo' } | ConvertTo-Json
$raw = Invoke-RestMethod 'http://localhost/amc/public/api/apron/recommend' `
    -Method Post -Body $payload -ContentType 'application/json' -WebSession $session
([regex]::Match($raw,'{.*',[System.Text.RegularExpressions.RegexOptions]::Singleline).Value `
    | ConvertFrom-Json) | ConvertTo-Json -Depth 8
```
The regex strip is necessary because Apache prepends a BOM; once parsed, the JSON matches the artifacts listed above.
