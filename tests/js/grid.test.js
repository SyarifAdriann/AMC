/**
 * Behavior tests for the Excel-like grid in public/assets/js/master-table.js.
 *
 * Simulates the master table DOM in jsdom and exercises selection, keyboard
 * navigation, clipboard (copy/cut/paste as TSV), undo/redo, fills, and the
 * two-mode (navigation/edit) model.
 *
 * Run: npm test
 */
const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const assetsDir = path.join(__dirname, '..', '..', 'public', 'assets', 'js');
const httpSource = fs.readFileSync(path.join(assetsDir, 'amc-http.js'), 'utf8');
const scriptSource = fs.readFileSync(path.join(assetsDir, 'master-table.js'), 'utf8');

function makeRow(id, values) {
    const fields = ['registration', 'aircraft_type', 'on_block_time', 'off_block_time', 'parking_stand',
        'from_location', 'to_location', 'flight_no_arr', 'flight_no_dep', 'operator_airline', 'remarks'];
    let tds = `<td><input readonly value="${id}"></td>`;
    fields.forEach((f, i) => {
        const v = values[i] || '';
        tds += `<td><input data-field="${f}" data-original="${v}" value="${v}"></td>`;
    });
    tds += `<td><select data-field="is_ron" data-original="0"><option value="0">No</option><option value="1">Yes</option></select></td>`;
    return `<tr data-id="${id}">${tds}</tr>`;
}

const html = `<!DOCTYPE html><html><body>
<table id="master-movements-table"><thead><tr>${'<th></th>'.repeat(13)}</tr></thead><tbody>
${makeRow(1, ['PK-AAA', 'B738', '08:00'])}
${makeRow(2, ['PK-BBB', 'A320', '09:00'])}
${makeRow(3, ['PK-CCC', 'C208', '10:00'])}
${makeRow(4, [])}
</tbody></table>
<table id="ron-data-table"><thead><tr>${'<th></th>'.repeat(6)}</tr></thead><tbody></tbody></table>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/AMC/master-table.php' });
const { window } = dom;
const { document } = window;

// jsdom shims
window.Element.prototype.scrollIntoView = function () {};
window.alert = msg => { window.__lastAlert = msg; };
window.masterTableConfig = { userRole: 'operator', csrfToken: 'test', endpoints: {} };
window.BroadcastChannel = class { postMessage() {} addEventListener() {} };
window.fetch = () => Promise.resolve({ ok: true, text: () => Promise.resolve('{}') });

window.eval(httpSource);
window.eval(scriptSource);
document.dispatchEvent(new window.Event('DOMContentLoaded', { bubbles: true }));

const table = document.getElementById('master-movements-table');
const tbody = table.tBodies[0];
const cell = (r, c) => tbody.rows[r].cells[c];
const ctl = (r, c) => cell(r, c).querySelector('input, select');

let pass = 0, fail = 0;
function check(name, cond) {
    if (cond) { pass++; console.log('  PASS ' + name); }
    else { fail++; console.log('  FAIL ' + name); }
}

function mousedown(r, c, opts = {}) {
    ctl(r, c).dispatchEvent(new window.MouseEvent('mousedown', { bubbles: true, cancelable: true, ...opts }));
}
function mouseover(r, c) {
    ctl(r, c).dispatchEvent(new window.MouseEvent('mouseover', { bubbles: true }));
}
function key(k, opts = {}) {
    const target = document.activeElement && table.contains(document.activeElement) ? document.activeElement : table;
    target.dispatchEvent(new window.KeyboardEvent('keydown', { key: k, bubbles: true, cancelable: true, ...opts }));
}

console.log('1. sheet-grid class + nav lock');
check('table got sheet-grid class', table.classList.contains('sheet-grid'));
check('inputs locked readOnly for nav mode', ctl(0, 1).readOnly === true);

console.log('2. click + drag selection');
mousedown(0, 1);
check('single cell selected', cell(0, 1).classList.contains('selected'));
check('active cell marked', cell(0, 1).classList.contains('active-cell'));
mouseover(1, 2);
window.dispatchEvent(new window.MouseEvent('mouseup'));
check('drag extended selection 2x2', cell(1, 2).classList.contains('selected') && cell(0, 2).classList.contains('selected') && cell(1, 1).classList.contains('selected'));

console.log('3. keyboard navigation + shift selection');
mousedown(0, 1);
key('ArrowDown');
check('ArrowDown moved active cell', cell(1, 1).classList.contains('active-cell'));
key('ArrowRight', { shiftKey: true });
check('Shift+ArrowRight extended selection', cell(1, 1).classList.contains('selected') && cell(1, 2).classList.contains('selected'));
key('Escape');
check('Escape collapsed selection', !cell(1, 1).classList.contains('selected') && cell(1, 2).classList.contains('active-cell'));

console.log('4. copy produces TSV');
mousedown(0, 1);
mouseover(1, 2);
window.dispatchEvent(new window.MouseEvent('mouseup'));
let copied = null;
const copyEvent = new window.Event('copy', { bubbles: true, cancelable: true });
copyEvent.clipboardData = { setData: (t, v) => { copied = v; } };
document.dispatchEvent(copyEvent);
check('copied TSV matrix', copied === 'PK-AAA\tB738\nPK-BBB\tA320');

console.log('5. paste block from active cell');
mousedown(2, 1); // row 3, registration
const pasteEvent = new window.Event('paste', { bubbles: true, cancelable: true });
pasteEvent.clipboardData = { getData: () => 'PK-XXX\tB744\nPK-YYY\tAT76\n' };
document.dispatchEvent(pasteEvent);
check('paste row1', ctl(2, 1).value === 'PK-XXX' && ctl(2, 2).value === 'B744');
check('paste row2', ctl(3, 1).value === 'PK-YYY' && ctl(3, 2).value === 'AT76');
check('paste selected pasted rect', cell(3, 2).classList.contains('selected'));

console.log('6. undo/redo of paste');
key('z', { ctrlKey: true });
check('undo restored old values', ctl(2, 1).value === 'PK-CCC' && ctl(3, 1).value === '');
key('y', { ctrlKey: true });
check('redo re-applied paste', ctl(2, 1).value === 'PK-XXX');
key('z', { ctrlKey: true }); // undo again to leave clean

console.log('7. type-to-replace and Escape revert');
mousedown(0, 2); // aircraft_type = B738
key('X');
// simulate the browser inserting the character after keydown made it editable
if (!ctl(0, 2).readOnly) { ctl(0, 2).value += 'X'; }
check('typing entered edit mode and cleared', ctl(0, 2).classList.contains('grid-editing') && ctl(0, 2).value === 'X');
key('Escape');
check('Escape reverted value', ctl(0, 2).value === 'B738' && ctl(0, 2).readOnly === true);

console.log('8. edit + Enter commits and moves down');
mousedown(0, 2);
key('F2');
check('F2 entered edit preserving value', ctl(0, 2).value === 'B738' && !ctl(0, 2).readOnly);
ctl(0, 2).value = 'B739';
key('Enter');
check('Enter committed and moved down', ctl(0, 2).value === 'B739' && cell(1, 2).classList.contains('active-cell'));
check('commit recorded for save (differs from data-original)', ctl(0, 2).getAttribute('data-original') === 'B738');

console.log('9. Delete clears range');
mousedown(1, 1);
mouseover(1, 2);
window.dispatchEvent(new window.MouseEvent('mouseup'));
key('Delete');
check('Delete cleared cells', ctl(1, 1).value === '' && ctl(1, 2).value === '');
key('z', { ctrlKey: true });
check('undo restored cleared cells', ctl(1, 1).value === 'PK-BBB' && ctl(1, 2).value === 'A320');

console.log('10. cut = copy + clear');
mousedown(0, 1);
let cutData = null;
const cutEvent = new window.Event('cut', { bubbles: true, cancelable: true });
cutEvent.clipboardData = { setData: (t, v) => { cutData = v; } };
document.dispatchEvent(cutEvent);
check('cut copied value', cutData === 'PK-AAA');
check('cut cleared cell', ctl(0, 1).value === '');

console.log('11. Ctrl+A and row select');
key('a', { ctrlKey: true });
check('Ctrl+A selected all', cell(0, 1).classList.contains('selected') && cell(3, 12).classList.contains('selected'));
mousedown(1, 0); // row number column
check('row-number click selected whole row', cell(1, 1).classList.contains('selected') && cell(1, 12).classList.contains('selected') && !cell(0, 1).classList.contains('selected'));

console.log('12. fill down (Ctrl+D)');
mousedown(2, 5); // parking_stand col row3
ctl(2, 5).value = 'B1';
ctl(2, 5).dispatchEvent(new window.Event('change', { bubbles: true }));
mousedown(2, 5);
mouseover(3, 5);
window.dispatchEvent(new window.MouseEvent('mouseup'));
key('d', { ctrlKey: true });
check('Ctrl+D filled down', ctl(3, 5).value === 'B1');

console.log('13. select paste mapping');
mousedown(0, 12); // is_ron select
const pasteYes = new window.Event('paste', { bubbles: true, cancelable: true });
pasteYes.clipboardData = { getData: () => 'Yes' };
document.dispatchEvent(pasteYes);
check('pasting "Yes" into RON select maps to 1', ctl(0, 12).value === '1');

console.log('14. arrow while typing commits and navigates (always navigable)');
mousedown(2, 3); // on_block_time row 3
key('1');
if (!ctl(2, 3).readOnly) { ctl(2, 3).value += '1'; }
check('typing entered edit mode', ctl(2, 3).classList.contains('grid-editing'));
key('ArrowRight');
check('ArrowRight committed the edit', ctl(2, 3).value === '1' && ctl(2, 3).readOnly === true);
check('ArrowRight moved to next cell', cell(2, 4).classList.contains('active-cell'));
key('ArrowDown');
check('ArrowDown keeps navigating', cell(3, 4).classList.contains('active-cell'));

console.log('15. dirty-cell highlighting');
mousedown(1, 6); // from_location row 2
key('W');
if (!ctl(1, 6).readOnly) { ctl(1, 6).value += 'W'; }
ctl(1, 6).dispatchEvent(new window.Event('input', { bubbles: true }));
check('edited cell marked dirty', ctl(1, 6).classList.contains('cell-dirty'));
key('Escape');
ctl(1, 6).dispatchEvent(new window.Event('input', { bubbles: true }));
check('reverted cell no longer dirty', !ctl(1, 6).classList.contains('cell-dirty'));

console.log(`\nRESULT: ${pass} passed, ${fail} failed`);
process.exit(fail ? 1 : 0);
