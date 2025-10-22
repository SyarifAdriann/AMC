# Full System Testing Checklist

## Category 1: Security & Access Control

*   **Role Permissions (Admin):**
    *   [x ] Log in as an `admin`. Verify you can access the Dashboard, Master Table, and Apron pages.
    *   [x ] On the Dashboard, verify the "Manage Accounts" button is visible and opens the user management modal. 
    *   [ ] In the "Manage Accounts" modal, verify you can create, edit, suspend, and reset passwords for other users. result : cannot create new users and cannot fetch existing users
    *   [x ] In the Snapshot Archive, verify the "Delete" button is visible for each snapshot.

*   **Role Permissions (Operator):**
    *   [ ] Log in as an `operator`. Verify you can access the Dashboard, Master Table, and Apron pages.
    *   [ ] On the Dashboard, verify the "Manage Accounts" button is **not** visible.
    *   [ ] On the Dashboard, verify you **can** access modals for Aircraft Details, Flight References, and Snapshots.
    *   [ ] In the Snapshot Archive, verify the "Delete" button is **not** visible.
    *   [ ] On the Apron and Master Table, verify you can create and edit movements.

*   **Role Permissions (Viewer):**
    *   [ ] Log in as a `viewer`. Verify you are redirected from the Dashboard to the Apron (`index.php`) page.
    *   [ ] On the Apron page, verify that clicking a stand or aircraft does **not** open an editable modal.
    *   [ ] On the Master Table, verify all input fields and select boxes are `disabled` and you cannot save changes.

*   **Authentication & Session:**
    *   [ ] **Login Throttling:** Fail to log in 5+ times from the same IP address. On the next attempt, verify you see a "Too many failed attempts" lockout message.
    *   [ ] **Invalid Credentials:** Attempt to log in with a correct username but incorrect password. Verify an "Invalid username or password" error appears.
    *   [ ] **Suspended Account:** Attempt to log in with a `suspended` user's credentials. Verify login fails.
    *   [ ] **Direct Access:** While logged out, try to directly access `index.php` or `master-table.php`. Verify you are redirected to `login.php`.

*   **Form Security:**
    *   [ ] **CSRF Protection:** (Advanced) Use browser developer tools to remove the `csrf_token` hidden input from a form (e.g., the "Manage Aircraft Details" form) and submit it. Verify the request fails with a security error message.

---

## Category 2: Data Creation & Integrity (CRUD)

*   **Aircraft Movements (Apron & Master Table):**
    *   [ ] **Required Fields:** Try to save a new movement without a **Registration**. Verify the system shows a clear error message and does not save the record.
    *   [ ] **Data Consistency:** Create a new movement on the **Apron** page. Immediately navigate to the **Master Table** and **Dashboard**. Verify the new movement is reflected correctly in the tables and in the dashboard's analytics (e.g., "Movements Today" count increases).
    *   [ ] **Update Consistency:** Edit a movement's flight time on the **Master Table**. Navigate to the **Apron** page and verify the icon's details have been updated.
    *   [ ] **RON Logic:**
        *   Mark a flight as RON. Verify it appears correctly on the Apron map and in the Master Table's RON section for the next day.
        *   Use the `carryOverActiveRon()` feature (implicitly triggered on page load) by setting a flight to RON, changing your system date to the next day, and reloading the Master Table. Verify the RON flight is still present.

*   **User Management (Admin Only):**
    *   [ ] **Create User:** Create a new user with a specific role. Log out and log in as the new user to confirm their permissions are correct.
    *   [ ] **Duplicate Username:** Attempt to create a new user with a username that already exists. Verify the system returns an error.
    *   [ ] **Edit User:** Change a user's role from `viewer` to `operator`. Log in as that user and confirm they now have operator privileges.

---

## Category 3: UI/UX & Functionality

*   **General UI:**
    *   [ ] **Responsiveness:** Resize your browser window to simulate mobile, tablet, and desktop sizes on all three main pages. Verify the layout adapts correctly and no elements are broken or unusable.
    *   [ ] **Loading States:** On the Dashboard modals (Users, Snapshots), verify a "Loading..." message appears while data is being fetched.
    *   [ ] **Error Toasts:** When an action fails (e.g., saving a user with invalid data), verify a clear, non-blocking toast notification appears in the corner of the screen.
    *   **Modal Behavior:**
        *   [ ] Verify you can close any open modal by clicking the "X" button, the "Cancel" button, or by pressing the `Escape` key.
        *   [ ] Verify you can also close modals by clicking on the dark backdrop.

*   **Table Interactivity (Master Table):**
    *   [ ] **Keyboard Navigation:** In the Master Table, use the `Arrow Keys` (`Up`, `Down`, `Left`, `Right`) and `Enter` to navigate between cells like a spreadsheet.
    *   [ ] **Add New Rows:** Click the "Load More Empty Rows" button. Verify 25 new, blank, editable rows are added to the bottom of the table.
    *   [ ] **Copy/Paste:** Select a block of cells in the table, press `Ctrl+C`, select a different cell, and press `Ctrl+V`. Verify the data is pasted correctly.

*   **Autofill (Negative Cases):**
    *   [ ] Enter a registration or flight number that does **not** exist in the database. Verify that no data is autofilled and no errors break the form.
    *   [ ] Enter a valid registration to autofill the Type/Operator. Manually change the Type, then re-trigger the autofill for the same registration. Verify your manually entered data is **not** overwritten.

---

## Category 4: Reporting & System Actions

*   **Report Generation:**
    *   [ ] **Empty Date Range:** Generate a report with a date range where no movements occurred. Verify the report is generated correctly but shows "No data available" instead of breaking.
    *   [ ] **All Report Types:** Systematically generate one of every report type listed in the dropdown to ensure no specific report query fails.
*   **Snapshots:**
    *   [ ] **Create & View:** Create a snapshot. Immediately open the archive, find the new snapshot, and click "View". Verify the data in the snapshot accurately reflects the state of the apron and roster at the time of creation.
    *   [ ] **Print:** Click the "Print" button on a snapshot. Verify the browser's print preview opens with a clean, well-formatted layout designed for printing.
    *   [ ] **Delete (Admin):** As an admin, delete a snapshot. Verify it is removed from the list and cannot be accessed again.
