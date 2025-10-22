# Revision 8 - Multi-Task System Overhaul - 20250821-1205

## 1. PROBLEM ANALYSIS
- **Issue description:** This revision addresses a collection of 9 distinct tasks ranging from UI beautification and UX fixes to new feature implementation and standardization across the AMC Monitoring System. The core issues involve inconsistent UI, poor user experience in data entry fields, missing functionality, and visual clutter.
- **Root cause:** A combination of rapid initial development, lack of a standardized component library, and incomplete feature sets across different pages.
- **Affected files/systems:** `index.php`, `master-table.php`, `dashboard.php`, `styles.css`, and related JavaScript functions.
- **Risk assessment:** High - This is a major overhaul touching multiple critical components. A failure in one task could impact others. Execution requires careful, sequential implementation and rigorous testing at each stage.

## 2. IMPLEMENTATION PLAN FOR GEMINI CLI
### Pre-execution Checklist:
- [ ] Create backup branch: `revision-8-multi-task-overhaul-20250821`
- [ ] Verify current project state matches `context.md`
- [ ] Confirm all dependencies are installed and the application is in a runnable state.

---

### **Task 1: Fix Core Input Field Mechanics**
- **Objective:** Correct flawed JavaScript behavior for input fields in `index.php` and `master-table.php`.
#### Step-by-Step Execution:
1.  **Analyze Flawed Event Listeners:** Read the JavaScript in `index.php` and `master-table.php` to identify the `onkeyup` functions causing incorrect `Backspace`/`Delete` behavior.
2.  **Implement Corrected Key Handling:** Modify the identified functions to ensure `Backspace` and `Delete` only remove a single character.
3.  **Disable Browser Autocomplete & Implement Arrow Navigation:** Add `e.preventDefault()` to the arrow key handlers and implement logic to focus on the next/previous input field within the same row.
#### Testing Protocol:
- **Run:** Puppeteer test script that simulates typing, deleting, and navigating with arrow keys in the `index.php` modal and `master-table.php` cells.
- **Verify:** Confirm that text input is normal, deletion is character-by-character, and arrow keys move the cursor between fields without triggering browser UI.

---

### **Task 2: Add "Set RON" Button to Master Table**
- **Objective:** Implement the "Set RON" button in `master-table.php` with functionality identical to the one in `index.php`.
#### Step-by-Step Execution:
1.  **Copy Button HTML:** Replicate the "Set RON" button HTML from `index.php` and place it in the header of `master-table.php`.
2.  **Replicate Backend Logic:** Ensure the button in `master-table.php` calls the same backend PHP script/function that the `index.php` button uses for bulk updates.
#### Testing Protocol:
- **Run:** Manually click the "Set RON" button in `master-table.php`.
- **Verify:** Check the database to confirm that all eligible aircraft movements have been updated to "RON" status.

---

### **Task 3: Standardize Application Header**
- **Objective:** Create a consistent navigation header across `index.php`, `master-table.php`, and `dashboard.php`.
#### Step-by-Step Execution:
1.  **Create Master Header:** Design a single, standardized HTML/PHP include file for the header.
2.  **Implement Header:** Replace the existing header sections in all three files with the new standardized header.
3.  **Add Active State CSS:** Write CSS rules to apply a distinct style to the navigation button corresponding to the currently active page.
#### Testing Protocol:
- **Run:** Navigate between the "Apron Map", "Master Table", and "Dashboard" pages.
- **Verify:** Confirm the header is identical on all three pages and that the active page's button is correctly styled.

---

### **Task 4: Beautify UI Components**
- **Objective:** Redesign the filter section in `master-table.php` and the staff roster in `index.php`.
#### Step-by-Step Execution:
1.  **Redesign Filter Container:** Rewrite the CSS for the filter section in `master-table.php` to use a clean grid layout with proper alignment and spacing.
2.  **Redesign Staff Roster:** Rewrite the CSS for the staff roster table in `index.php` to make it more compact and visually consistent with the page theme.
#### Testing Protocol:
- **Run:** Visually inspect `master-table.php` and `index.php`.
- **Verify:** Confirm the filter section and staff roster are well-styled and visually appealing.

---

### **Task 5: Enhance Apron Map Visuals**
- **Objective:** Improve the readability and positioning of aircraft icons and labels on the `index.php` apron map.
#### Step-by-Step Execution:
1.  **Improve Label Readability:** Modify the CSS for `.plane-icon .label` to make the text bolder, uppercase, and add a `text-shadow` for contrast.
2.  **Adjust Icon Positioning:** Fine-tune the absolute positioning CSS for `.plane-icon` to ensure icons are tightly aligned with their stand boxes.
#### Testing Protocol:
- **Run:** Visually inspect the apron map on `index.php`.
- **Verify:** Confirm that icon labels are clear and that icons are positioned correctly against the stands.

---

### **Task 6: Add Live Apron Status to Index Page**
- **Objective:** Replicate the "Live Apron Status" component from `dashboard.php` onto `index.php`.
#### Step-by-Step Execution:
1.  **Copy Component HTML & JS:** Duplicate the HTML and JavaScript for the "Live Apron Status" from `dashboard.php` to `index.php`.
2.  **Position Component:** Place the component above the apron map container and style as needed.
3.  **Hook Up Backend Logic:** Ensure the JavaScript makes the necessary AJAX calls to the backend to fetch and display live data.
#### Testing Protocol:
- **Run:** Load `index.php` and observe the new component.
- **Verify:** Confirm the "Live Apron Status" displays correct, dynamic data.

---

### **Task 7: Refine Save Feedback in Master Table**
- **Objective:** Replace the intrusive `alert()` on successful save in `master-table.php` with a page refresh.
#### Step-by-Step Execution:
1.  **Modify `saveAllData()`:** Locate the `saveAllData()` JavaScript function in `master-table.php`.
2.  **Remove Success Alert:** Remove the `alert()` call from the success callback of the AJAX request.
3.  **Implement Refresh:** Add `location.reload();` to the success callback.
#### Testing Protocol:
- **Run:** Make a change in `master-table.php` and click the save button.
- **Verify:** Confirm the page refreshes on successful save and that no alert box appears. Error messages should still appear on failure.

---

### **Task 8: Implement Spreadsheet-like Copy Functionality**
- **Objective:** Allow users to copy a block of cells from `master-table.php` to the clipboard.
#### Step-by-Step Execution:
1.  **Implement Cell Selection:** Write JavaScript to handle mouse drag events to select a rectangular block of table cells.
2.  **Implement Copy Handler:** Add a `copy` event listener that captures the selected cells' content, formats it as tab-separated values, and places it on the clipboard.
#### Testing Protocol:
- **Run:** Select a block of cells in `master-table.php` and press `Ctrl+C`.
- **Verify:** Paste the content into a spreadsheet application (like Google Sheets or Excel) and confirm the data appears in the correct rows and columns.

---

### **Task 9: Correct Modal Positioning in Dashboard**
- **Objective:** Center the modals in `dashboard.php` in the user's viewport.
#### Step-by-Step Execution:
1.  **Analyze Modal CSS:** Inspect the CSS for the "Manage Aircraft Details" and "Manage Flight References" modals in `dashboard.php`.
2.  **Implement Centering:** Modify the CSS to use `position: fixed;`, `top: 50%;`, `left: 50%;`, and `transform: translate(-50%, -50%);` to center the modals.
#### Testing Protocol:
- **Run:** Open the modals on the `dashboard.php` page.
- **Verify:** Confirm that the modals appear centered in the current viewport, regardless of scroll position.

## 3. EXECUTION LOG (Updated by Gemini CLI)
- **Overall Status:** Partially Completed
- **Completed Tasks:**
  - Task 1: Fix Core Input Field Mechanics - **Completed**
  - Task 2: Add "Set RON" Button to Master Table - **Completed**
  - Task 5: Enhance Apron Map Visuals - **Completed**
  - Task 7: Refine Save Feedback in Master Table - **Completed**
  - Task 8: Implement Spreadsheet-like Copy Functionality - **Completed**
- **Issues Encountered:**
  - **Task 3 (Standardize Application Header):** Partially completed. The "Set RON" button was mistakenly removed from `index.php` during standardization. It needs to be added back.
  - **Task 4 (Beautify UI Components):** Partially completed. The daily roster table in `index.php` remains poorly styled and requires a complete redesign.
  - **Task 6 (Add Live Apron Status to Index Page):** Partially completed. The component is functional but lacks styling. It needs to be laid out horizontally and integrated visually with the apron map.
  - **Task 9 (Correct Modal Positioning in Dashboard):** Not completed. The modals in `dashboard.php` are still not centered in the viewport.
- **Next Actions Required:**
  - Create `revision9.md` to address all outstanding issues from this revision.
  - Update `context.md` to reflect the current state of the project.
