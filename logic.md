# Project Logic and Requirements Analysis

## 1. Complete Business Rules

### Field Validations (from `amc.sql` and PHP logic)

*   **`aircraft_details` table**:
    *   `registration`: `varchar(10) NOT NULL` (Primary Key). Max length 10 characters. Required.
    *   `aircraft_type`: `varchar(30) DEFAULT NULL`. Max length 30 characters. Optional.
    *   `operator_airline`: `varchar(100) DEFAULT NULL`. Max length 100 characters. Optional.
    *   `category`: `varchar(20) NOT NULL COMMENT 'Commercial, Cargo, Charter'`. Max length 20 characters. Required. Values are 'Commercial', 'Cargo', 'Charter'.
    *   `notes`: `text DEFAULT NULL`. Optional.
*   **`aircraft_movements` table**:
    *   `id`: `bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT` (Primary Key). Auto-incremented.
    *   `registration`: `varchar(10) NOT NULL`. Max length 10 characters. Required. (Foreign key to `aircraft_details.registration`).
    *   `aircraft_type`: `varchar(30) DEFAULT NULL`. Max length 30 characters. Optional.
    *   `on_block_time`: `varchar(50) DEFAULT NULL`. Max length 50 characters. Optional. Stores user input like "1430" or "EX RON". Can include date in `(DD/MM/YYYY)` format.
    *   `off_block_time`: `varchar(50) DEFAULT NULL`. Max length 50 characters. Optional. Stores user input like "1500". Can include date in `(DD/MM/YYYY)` format.
    *   `parking_stand`: `varchar(20) NOT NULL`. Max length 20 characters. Required.
    *   `from_location`: `varchar(50) DEFAULT NULL`. Max length 50 characters. Optional.
    *   `to_location`: `varchar(50) DEFAULT NULL`. Max length 50 characters. Optional.
    *   `flight_no_arr`: `varchar(20) DEFAULT NULL`. Max length 20 characters. Optional.
    *   `flight_no_dep`: `varchar(20) DEFAULT NULL`. Max length 20 characters. Optional.
    *   `operator_airline`: `varchar(100) DEFAULT NULL`. Max length 100 characters. Optional.
    *   `remarks`: `text DEFAULT NULL`. Optional.
    *   `is_ron`: `tinyint(1) NOT NULL DEFAULT 0`. Boolean (0 or 1). Defaults to 0.
    *   `ron_complete`: `tinyint(1) NOT NULL DEFAULT 0`. Boolean (0 or 1). Defaults to 0.
    *   `movement_date`: `date NOT NULL`. Required.
    *   `user_id_created`: `bigint(20) UNSIGNED NOT NULL`. Required. (Foreign key to `users.id`).
    *   `user_id_updated`: `bigint(20) UNSIGNED NOT NULL`. Required. (Foreign key to `users.id`).
    *   `created_at`: `timestamp NULL DEFAULT current_timestamp()`. Auto-set on creation.
    *   `updated_at`: `timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()`. Auto-updated on modification.
    *   `on_block_date`: `date DEFAULT NULL`. Set to current date if `on_block_time` is provided.
    *   `off_block_date`: `date DEFAULT NULL`. Set to current date if `off_block_time` is provided.
*   **`daily_staff_roster` table**:
    *   `roster_date`: `date NOT NULL`. Required.
    *   `shift`: `varchar(50) NOT NULL`. Required.
    *   `updated_by_user_id`: `int(11) DEFAULT 1`. Defaults to 1.
    *   `aerodrome_code`: `varchar(10) DEFAULT NULL`. Defaults to 'WIHH' in `index.php` save logic.
    *   `day_shift_staff_1`, `day_shift_staff_2`, `day_shift_staff_3`: `varchar(100) DEFAULT NULL`. Optional.
    *   `night_shift_staff_1`, `night_shift_staff_2`, `night_shift_staff_3`: `varchar(100) DEFAULT NULL`. Optional.
*   **`users` table**:
    *   `username`: `varchar(50) NOT NULL` (Unique). Required.
    *   `password_hash`: `varchar(255) NOT NULL`. Required. Stores hashed password.
    *   `role`: `varchar(20) NOT NULL COMMENT 'e.g., admin, operator, viewer'`. Required.
    *   `status`: `enum('active','suspended') DEFAULT 'active'`. Defaults to 'active'.
    *   `email`: `varchar(100) DEFAULT NULL` (Unique). Optional.
    *   `full_name`: `varchar(100) DEFAULT NULL`. Optional.

### Business Process Flows and Logic

*   **RON (Remain Overnight) Status Calculation and Update**:
    *   **Definition**: An aircraft is considered RON if it has an `on_block_time` but no `off_block_time`, and its `movement_date` is prior to the current date.
    *   **Automatic Carry-over (`carryOverActiveRON` function in `index.php` and `master-table.php`):**
        *   Executed on page load of `index.php` and `master-table.php`.
        *   Updates `is_ron` to `1` for `aircraft_movements` records where:
            *   `off_block_time` is `NULL` or empty.
            *   `on_block_time` is not `NULL` or empty.
            *   `movement_date` is less than the current date.
            *   `is_ron` is `0` or `NULL`.
        *   Appends `(DD/MM/YYYY)` (movement date) to `on_block_time` if not already present, for newly identified RON movements.
    *   **Manual Set RON (`setRON` action in `index.php` and `master-table.php`):**
        *   Triggered by a button click.
        *   Identifies movements with `on_block_time` but no `off_block_time` and `is_ron = 0`.
        *   Sets `is_ron = 1` for these movements.
        *   Appends `(DD/MM/YYYY)` (current date) to `on_block_time` if not already present.
    *   **RON Completion**:
        *   When `off_block_time` is entered for a movement that is `is_ron = 1`, `ron_complete` is set to `1`.
        *   If `off_block_time` is cleared, `ron_complete` is reset to `0`.
*   **Duplicate Flight Number Handling Rules**:
    *   In `master-table.php`, duplicate `flight_no_arr` and `flight_no_dep` for the *current day* are identified.
    *   These duplicate flight numbers are collected into `$duplicate_flights` array.
    *   Rows in the Master Table (`master-movements-table`) that contain these duplicate flight numbers are visually highlighted (e.g., `bg-orange-100` class).
    *   There is no explicit business rule to prevent or warn against entering duplicate flight numbers; it's a visual indicator only.
*   **Aircraft Movement Creation/Update**:
    *   `registration` is a mandatory field for saving a movement.
    *   When `registration` is entered, `aircraft_type` and `operator_airline` can be autofilled from `aircraft_details` table if a matching registration exists.
    *   When `flight_no_arr` or `flight_no_dep` is entered, `from_location` or `to_location` can be autofilled from `flight_references` table if a matching flight number exists.
    *   `movement_date` is automatically set to the current date upon creation.
    *   `on_block_date` is set to the current date if `on_block_time` is provided.
    *   `off_block_date` is set to the current date if `off_block_time` is provided.
    *   `user_id_created` and `user_id_updated` are set to the current logged-in user's ID.
*   **Staff Roster Saving**:
    *   A roster can be saved for a specific `roster_date` and `aerodrome_code`.
    *   If a roster already exists for that date and aerodrome, it is updated; otherwise, a new one is inserted.
    *   `updated_by_user_id` is set to the current logged-in user's ID.

### Complete User Role Definitions and Permissions

*   **Roles**: Defined in `users.role` as `admin`, `operator`, `viewer`.
*   **Permissions (from `auth_check.php` and various PHP files)**:
    *   **`viewer`**:
        *   Can view `index.php` (Apron Map) and `master-table.php` (Master Table).
        *   **Cannot** access `dashboard.php`.
        *   **Cannot** save roster (`saveRoster` action).
        *   **Cannot** set RON status (`setRON` action).
        *   **Cannot** save/create aircraft movements (`saveMovement` action).
        *   **Cannot** save changes in Master Table (`save_all_changes` action).
        *   **Cannot** create new movements in Master Table (`create_new_movement` action).
        *   **Cannot** manage aircraft details, flight references, or monthly charter reports from Dashboard.
        *   **Cannot** access `api/admin/users` or `api/snapshots`.
        *   Input fields in `index.php` and `master-table.php` are read-only for viewers.
    *   **`operator`**:
        *   Can view `index.php`, `master-table.php`, and `dashboard.php`.
        *   Can save roster (`saveRoster`).
        *   Can set RON status (`setRON`).
        *   Can save/create aircraft movements (`saveMovement`).
        *   Can save changes in Master Table (`save_all_changes`).
        *   Can create new movements in Master Table (`create_new_movement`).
        *   Can manage aircraft details, flight references, and monthly charter reports from Dashboard.
        *   Can access `api/snapshots` (create, list, view, print snapshots).
        *   **Cannot** manage user accounts (`api/admin/users`).
        *   **Cannot** delete snapshots (`api/snapshots`).
    *   **`admin`**:
        *   Full access to all features and pages.
        *   Can view `index.php`, `master-table.php`, and `dashboard.php`.
        *   Can save roster (`saveRoster`).
        *   Can set RON status (`setRON`).
        *   Can save/create aircraft movements (`saveMovement`).
        *   Can save changes in Master Table (`save_all_changes`).
        *   Can create new movements in Master Table (`create_new_movement`).
        *   Can manage aircraft details, flight references, and monthly charter reports from Dashboard.
        *   Can access `api/snapshots` (create, list, view, print, delete snapshots).
        *   Can manage user accounts (`api/admin/users` - create, update, set status, reset password).

### All Error Handling Specifications

*   **Database Errors (PDO Exceptions)**:
    *   Caught in `dbconnection.php` (logs to `php_errors.log`).
    *   Caught in `index.php` and `master-table.php` for AJAX requests, returning JSON with `success: false` and `message`.
    *   Caught in `login.php` (displays generic "Login system temporarily unavailable" message).
    *   Caught in `dashboard.php` for report generation (displays error message).
    *   Caught in `api/snapshots` (logs to `php_errors.log` and returns JSON error).
*   **Login Errors (`login.php`)**:
    *   "Please enter both username and password." (empty fields)
    *   "Too many failed attempts. Please try again in 15 minutes." (rate limiting)
    *   "Invalid username or password." (incorrect credentials)
    *   "Your session has expired. Please log in again." (session timeout)
    *   Failed login attempts are logged in `login_attempts` table and `audit_log`.
*   **Authorization Errors (`auth_check.php`)**:
    *   If `requireRole` fails for a normal page request, an `alert` is shown and `history.back()` is called.
    *   If `requireRole` fails for an AJAX POST request, JSON `{'success': false, 'message': 'Unauthorized access'}` is returned with HTTP 403 status.
*   **Input Validation Errors**:
    *   "Registration is required." (for saving movements in `index.php` and `master-table.php`).
    *   "Date is required for roster." (for saving roster in `index.php`).
    *   "Invalid CSRF token" (for POST requests requiring CSRF in `dashboard.php`, `api/snapshots`).
    *   "No changes to save" (for `save_all_changes` in `master-table.php`).
*   **Logging Levels**:
    *   `error_log` is used for PHP errors and PDO exceptions, writing to `logs/php_errors.log`.
    *   `audit_log` table records user actions (LOGIN_SUCCESS, LOGIN_FAIL, CREATE_USER, UPDATE_USER, SET_STATUS, RESET_PASSWORD, UPSERT_SNAPSHOT, DELETE_SNAPSHOT).

### Security Requirements

*   **Authentication**:
    *   Username/Email and Password.
    *   Passwords are hashed using `password_hash()` (likely bcrypt or Argon2id based on `amc.sql` dumps).
    *   Session-based authentication (`session_start()`, `$_SESSION['user_id']`, `$_SESSION['role']`).
*   **Authorization**:
    *   Role-based access control (`hasRole`, `requireRole` functions).
    *   Permissions are enforced on page access (`dashboard.php` for viewers) and specific actions (saving data, managing users, snapshots).
*   **Session Management**:
    *   `session_start()` at the beginning of most PHP files.
    *   `session_regenerate_id(true)` on successful login to prevent session fixation.
    *   `$_SESSION['last_activity']` tracks user activity.
    *   **Session Timeout**: `SESSION_TIMEOUT` (1800 seconds / 30 minutes) defined in `config.php`. If inactivity exceeds this, session is unset/destroyed, and user is redirected to `login.php?timeout=1`.
*   **Rate Limiting on Login Attempts**:
    *   Implemented in `login.php`.
    *   Tracks failed login attempts by `ip_address` in the `login_attempts` table.
    *   `LOGIN_ATTEMPTS_LIMIT` (5 attempts) and `LOGIN_ATTEMPTS_WINDOW` (900 seconds / 15 minutes) defined in `config.php`.
    *   If 5 failed attempts occur within 15 minutes from the same IP, further logins from that IP are blocked for the remainder of the 15-minute window.
    *   Failed login attempts are logged in the `audit_log` table, including the IP address.

*   **CSRF Protection**:
    *   `generateCSRFToken()` and `validateCSRFToken()` functions are used.
    *   CSRF tokens are stored in `$_SESSION['csrf_token']`.
    *   Checked for POST requests in `dashboard.php` (manage aircraft, flight reference, monthly charter report) and `api/snapshots` (create, delete snapshots).
*   **Input Validation**:
    *   Basic server-side validation for required fields (e.g., `registration`).
    *   Client-side validation (e.g., `required` attribute on HTML inputs) is present but not exhaustive.
*   **Audit Logging**:
    *   The `audit_log` table records significant security-related events and administrative actions.
    *   **Logged Events**:
        *   `LOGIN_SUCCESS`: Successful user login.
        *   `LOGIN_FAIL`: Failed user login attempts (includes IP address).
        *   `LOGOUT`: User logout.
        *   `CREATE_USER`: New user creation.
        *   `UPDATE_USER`: User profile updates.
        *   `SET_STATUS`: User status changes (active/suspended).
        *   `RESET_PASSWORD`: Password reset for a user.
        *   `UPSERT_SNAPSHOT`: Creation or update of daily snapshots.
        *   `DELETE_SNAPSHOT`: Deletion of daily snapshots.

## 2. Complete UI/UX Requirements

### HTML Structure Requirements

*   **Overall Page Structure**:
    *   All main pages (`index.php`, `master-table.php`, `dashboard.php`, `login.php`) use a common `gradient-bg` body with a `min-h-screen` and `font-sans`.
    *   A main content `div` with `max-w-7xl mx-auto container-bg rounded-xl p-4 lg:p-8 shadow-2xl` wraps the page content.
*   **Header (Common to `index.php`, `master-table.php`, `dashboard.php`)**:
    *   `flex flex-col lg:flex-row justify-between items-center mb-6 lg:mb-8 pb-4 border-b-2 border-amc-light gap-4`.
    *   **Logo/Title**: `flex items-center cursor-pointer transition-transform duration-300 hover:scale-105` with an SVG airplane icon and `AMC MONITORING` text (`text-lg lg:text-xl font-bold text-amc-dark-blue`).
    *   **Navigation Buttons**: `flex flex-wrap justify-center lg:justify-end gap-2 lg:gap-4`.
        *   Buttons for "Apron Map", "Master Table", "Dashboard" (conditional for viewer role), and "Logout".
        *   Buttons have `nav-btn-gradient text-white border-none rounded-md text-sm font-semibold px-3 py-2 lg:px-4 lg:py-2 transition-all duration-300 hover:-translate-y-1`.
        *   Active button has `shadow-inner transform translate-y-px`.
*   **Login Page (`login.php`)**:
    *   Centered `div` (`w-full max-w-md mx-auto`) containing a `container-bg rounded-xl p-6 lg:p-8 shadow-2xl`.
    *   Title (`h1`) and subtitle (`p`).
    *   Error message `div` (conditional).
    *   **Login form** (`form method="POST" class="space-y-4"`):
        *   Username/Email input (`input type="text" id="username" name="username" required`).
        *   Password input (`input type="password" id="password" name="password" required`).
        *   Login button (`button type="submit" name="login"`).
        *   All inputs have `w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:border-amc-blue focus:shadow-sm transition-all duration-300`.
*   **Apron Map Page (`index.php`)**:
    *   Welcome message (`div text-center mb-6 lg:mb-8 text-base lg:text-lg text-gray-700`).
    *   **Staff Roster section** (`div mb-6 lg:mb-8 bg-amc-bg rounded-xl p-4 lg:p-6 border border-amc-light shadow-lg`):
        *   Table (`table id="roster-table"`) with specific `th` and `td` structures for aerodrome, date, and day/night shift staff inputs.
        *   Save Roster button.
    *   **Live Apron Status section** (`div flex flex-wrap justify-center lg:justify-around items-center bg-amc-bg rounded-xl p-4 lg:p-6 mb-6 lg:mb-8 border border-amc-light shadow-lg gap-4`):
        *   KPIs for Total Stands, Available, Occupied, Live RON.
        *   "Set RON" and "Refresh" buttons.
    *   **Apron Map** (`div w-full mx-auto mb-8 lg:mb-10 relative overflow-hidden rounded-xl shadow-lg border-2 border-amc-light apron-checkerboard id="apron-wrapper"`):
        *   Inner `div id="apron-container"` with fixed `width: 1920px; height: 1080px;`.
        *   Stand elements (`div class="stand-gradient absolute..." data-stand="..."`) positioned with `left` and `top` styles.
        *   Dynamically added `plane-icon` divs for aircraft.
    *   **Modals**:
        *   `standModalBg` (for individual stand details/movement input): `modal-backdrop` with `bg-white rounded-lg p-4 lg:p-6 w-full max-w-4xl mx-4 my-4 lg:my-0 max-h-screen overflow-y-auto relative shadow-xl`. Contains a form-like table for movement details.
        *   `hgrModalBg` (for Hangar details): Similar modal structure, contains a table for HGR records.
*   **Master Table Page (`master-table.php`)**:
    *   **Filter Container** (`div bg-amc-bg rounded-xl p-4 lg:p-6 mb-6 border border-amc-light shadow-lg`):
        *   Form (`form action="master-table.php" method="GET" id="filter-form"`) with date, category, airline, flight number inputs.
        *   "Reset Filters" and "Apply Filters" buttons.
    *   **Master Movements Table** (`div bg-white rounded-lg shadow-lg overflow-hidden mb-6`):
        *   Header (`div table-header-gradient`).
        *   Mobile Card View (`div mobile-cards lg:hidden`).
        *   Desktop Table View (`div desktop-table hidden lg:block overflow-x-auto`):
            *   Table (`table id="master-movements-table"`) with specific `th` for columns (NO, REGISTRATION, TYPE, ON BLOCK, OFF BLOCK, etc.).
            *   Input fields within `td` for editing.
        *   Pagination (`div flex justify-center py-4 border-t border-gray-200`).
        *   "Load More Empty Rows" button.
    *   **RON Data Table** (`div bg-white rounded-lg shadow-lg overflow-hidden mb-6`):
        *   Header (`div bg-gradient-to-r from-red-500 to-red-600`).
        *   Mobile RON Cards (`div mobile-cards lg:hidden`).
        *   Desktop RON Table (`div desktop-table hidden lg:block overflow-x-auto`):
            *   Table (`table id="ron-data-table"`).
        *   RON Pagination.
*   **Dashboard Page (`dashboard.php`)**:
    *   Dashboard Grid (`div space-y-6`).
    *   **KPI Cards Row** (`div grid grid-cols-1 lg:grid-cols-2 gap-6`):
        *   Live Apron Status Card (`div bg-amc-bg rounded-xl shadow-lg border border-amc-light flex flex-col overflow-hidden`): Displays Total, Available, Occupied, Live RON.
        *   Movements Today Card: Displays Arrivals/Departures by Commercial, Cargo, Charter.
    *   **Apron Movement by Hour Table** (`div bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden`): Table showing hourly movements.
    *   **Peak Hour Analysis** (`div bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden`):
        *   Custom Bar Chart (`div id="customPeakChart"`).
        *   Peak Hours Summary (`div id="peakHoursSummary"`).
    *   **Automated Reporting Suite** (`div bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden`):
        *   Form for report type, date range, with "Generate Report" and "Export to CSV" buttons.
    *   **Administrative Controls** (`div bg-amc-bg rounded-xl shadow-lg border border-amc-light overflow-hidden`):
        *   Buttons for "Manage Accounts", "Manage Aircraft Details", "Manage Flight References", "Monthly Charter Report", "Daily Snapshot Archive" (conditional for admin/operator roles).
    *   Modals (similar to `index.php` for admin functions like user management, aircraft details, etc.).

### Styling Specifications

*   **Color Palette (from `tailwind.config.js` and `styles.css`)**:
    *   `amc-blue`: `#3F72AF` (Primary blue)
    *   `amc-dark-blue`: `#112D4E` (Darker blue, text color)
    *   `amc-light`: `#DBE2EF` (Light blue, borders, backgrounds)
    *   `amc-bg`: `#F9F7F7` (Light background)
    *   `red-500`, `red-600`, `red-700`, `red-800` (for RON, errors)
    *   `green-500`, `green-600`, `green-700`, `green-800` (for available, success)
    *   `yellow-500`, `yellow-800` (for RON, warnings)
    *   `gray-50`, `gray-100`, `gray-200`, `gray-300`, `gray-400`, `gray-500`, `gray-600`, `gray-700`, `gray-800` (various shades of gray for text, borders, backgrounds).
*   **Fonts**:
    *   `font-sans`: `Segoe UI`, `Tahoma`, `Geneva`, `Verdana`, `Arial`, `sans-serif`.
*   **Responsive Breakpoints (from `tailwind.config.js`)**:
    *   `xs`: `475px`
    *   `sm`: (default Tailwind) `640px`
    *   `md`: (default Tailwind) `768px`
    *   `lg`: (default Tailwind) `1024px`
    *   `xl`: (default Tailwind) `1280px`
    *   `2xl`: (default Tailwind) `1536px`
*   **Gradients**:
    *   `gradient-bg`: `linear-gradient(135deg, #3F72AF 0%, #112D4E 100%)` (body background).
    *   `nav-btn-gradient`: `linear-gradient(135deg, #3F72AF, #112D4E)`.
    *   `stand-gradient`: `linear-gradient(135deg, rgba(63, 114, 175, 0.9), rgba(17, 45, 78, 0.9))`.
    *   `table-header-gradient`: `linear-gradient(135deg, #3F72AF, #112D4E)`.
    *   `apron-checkerboard`: `linear-gradient(45deg, #DBE2EF 25%, #F9F7F7 25%, #F9F7F7 50%, #DBE2EF 50%, #DBE2EF 75%, #F9F7F7 75%)`.
*   **Shadows**: `shadow-lg`, `shadow-2xl`, `shadow-inner`.
*   **Borders**: `border`, `border-2`, `border-b-2`, `border-l-4`.
*   **Table Styling**:
    *   Compact inputs within table cells.
    *   Zebra striping (`tr:nth-child(even) td`).
    *   Hover effects (`tr:hover td`).
    *   Specific column widths for master and RON tables.
    *   Highlighting for RON rows (`bg-yellow-50`, `border-l-4 border-l-yellow-400`).
    *   Highlighting for duplicate flight numbers (`bg-orange-100`).
*   **Mobile Adaptations (`mobile-adaptations.js`, `styles.css`, `tailwind-custom.css`)**:
    *   `mobile-cards` vs `desktop-table` display switching based on screen width (`@media (max-width: 1024px)`).
    *   Apron map scaling and overflow for mobile (`apron-wrapper`, `apron-container`).
    *   Modal positioning and sizing for mobile (full-height, `overflow-y: auto`).
    *   Input font size adjustment to prevent iOS zoom (`font-size: 16px !important`).
    *   Improved touch target sizes for stands.

### Interactive Behavior Requirements

*   **Navigation**:
    *   Clicking the logo/title redirects to `index.php`.
    *   Clicking navigation buttons changes page.
    *   Hover effects on navigation buttons (`hover:-translate-y-1`).
*   **Apron Map (`index.php`)**:
    *   Clicking a stand opens `standModalBg` for movement input/editing.
    *   Clicking "HGR" stand opens `hgrModalBg` to view hangar records.
    *   **Autofill**:
        *   Typing in `registration` field in stand modal triggers AJAX to `index.php?action=getAircraftDetails` to autofill `aircraft_type` and `operator_airline`.
        *   Typing in `flight_no_arr` or `flight_no_dep` triggers AJAX to `index.php?action=getFlightRoute` to autofill `from_location` or `to_location`.
    *   "Set RON" button: Triggers POST request to `index.php?action=setRON` to mark movements as RON.
    *   "Refresh" button: Reloads the page.
    *   Live Apron Status: Auto-refreshes every 5 seconds via AJAX to `dashboard.php?action=refresh_apron`.
    *   Airplane icons: Dynamically rendered on the apron map based on `standData`. Clickable to open the stand modal for editing.
*   **Modals**:
    *   Opened by clicking stands or admin buttons.
    *   Closed by clicking "x" button, "Cancel" button, clicking outside the modal (`modal-backdrop`), or pressing `Escape` key.
    *   `f-reg` input in stand modal automatically focused on open.
*   **Table Navigation (Master Table, Roster, HGR)**:
    *   Google Sheets-like keyboard navigation (`ArrowUp`, `ArrowDown`, `ArrowLeft`, `ArrowRight`, `Tab`, `Enter`) for input fields within tables.
    *   `setupSheetBehavior` function enables selection, copy (`Ctrl+C`), cut (`Ctrl+X`), and paste (`Ctrl+V`) functionality across table cells.
*   **Master Table Page (`master-table.php`)**:
    *   "Save" button: Collects all changed fields (`data-field` where `value !== data-original`) and new rows (`data-id="new"`) and sends them via AJAX POST to `master-table.php?action=save_all_changes` or `master-table.php?action=create_new_movement`.
    *   "Set RON" button: Same functionality as on `index.php`.
    *   "Refresh" button: Reloads the page.
    *   "Load More Empty Rows" button: Dynamically adds 25 empty rows to the master movements table for new entries.
    *   Filter form: Submits GET request to `master-table.php` to filter data. "Reset Filters" clears the form and reloads the page.
*   **Dashboard Page (`dashboard.php`)**:
    *   Live Apron Status: Auto-refreshes every 5 seconds via AJAX to `dashboard.php?action=refresh_apron`.
    *   "Generate Report" button: Submits form to generate report output on the same page.
    *   "Export to CSV" button: Submits form to trigger CSV download.
    *   Admin buttons (e.g., "Manage Accounts"): Open corresponding modals.
    *   Toast notifications (`showToast` function): Provide feedback for success/error messages (e.g., "Password copied to clipboard", "Error saving roster").
*   **User Management (via Dashboard modals)**:
    *   User search and filter inputs trigger `loadUsers` function via AJAX to `api/admin/users?action=list`.
    *   "Edit" button: Populates user form with existing data.
    *   "Reset PW" button: Opens password reset modal.
    *   "Suspend"/"Activate" button: Triggers AJAX to `api/admin/users?action=set_status`.
    *   "Create User" button: Clears form and sets title to "Create User".
    *   User form submission: Triggers AJAX to `api/admin/users?action=update` or `api/admin/users?action=create`.
    *   Password reset form submission: Triggers AJAX to `api/admin/users?action=reset_password`.
*   **Snapshot Management (via Dashboard modals)**:
    *   "Create Snapshot" form submission: Triggers AJAX to `api/snapshots?action=create`.
    *   "View" button: Triggers AJAX to `api/snapshots?action=view` and renders snapshot data in a modal.
    *   "Print" button: Triggers AJAX to `api/snapshots?action=view`, renders data, and then calls `window.print()`.
    *   "Delete" button: Triggers AJAX to `api/snapshots?action=delete`.

## 4. Complete Integration Points

*   **AJAX Endpoints and Data Flows**:

    *   **Endpoint**: `index.php`
        *   **Action**: `saveRoster` (Save Staff Roster)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "saveRoster",
                    "date": "YYYY-MM-DD",
                    "aerodrome": "WIHH",
                    "day_staff_1": "Staff Name 1",
                    "day_staff_2": "Staff Name 2",
                    "day_staff_3": "Staff Name 3",
                    "night_staff_1": "Staff Name 4",
                    "night_staff_2": "Staff Name 5",
                    "night_staff_3": "Staff Name 6"
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Roster saved successfully."
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `setRON` (Set Remain Overnight Status)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "setRON"
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "RON status set for X movements"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `saveMovement` (Save/Update Aircraft Movement)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "saveMovement",
                    "id": "movement_id" (optional, for update),
                    "registration": "PK-ABC",
                    "aircraft_type": "A320",
                    "on_block_time": "14:30",
                    "off_block_time": "15:00",
                    "parking_stand": "A1",
                    "from_location": "WIII",
                    "to_location": "WARR",
                    "flight_no_arr": "GA123",
                    "flight_no_dep": "GA456",
                    "operator_airline": "Garuda",
                    "remarks": "Test remarks",
                    "is_ron": true/false
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Movement saved successfully.",
                    "id": "new_movement_id",
                    "is_new": true/false
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message.",
                    "sqlstate": "SQLSTATE_CODE" (if database error)
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `getAircraftDetails` (Autofill Aircraft Details)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "getAircraftDetails",
                    "registration": "PK-ABC"
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "aircraft_type": "A320",
                    "operator_airline": "Garuda"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Aircraft not found."
                }
                ```
            *   **Permissions**: All authenticated users.
        *   **Action**: `getFlightRoute` (Autofill Flight Route)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "getFlightRoute",
                    "flight_no": "GA123"
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "default_route": "WIII"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Flight route not found."
                }
                ```
            *   **Permissions**: All authenticated users.

    *   **Endpoint**: `master-table.php`
        *   **Action**: `save_all_changes` (Save Multiple Changes from Master Table)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=save_all_changes&changes=[
                    {"id": "movement_id_1", "field": "field_name_1", "value": "new_value_1"},
                    {"id": "movement_id_2", "field": "field_name_2", "value": "new_value_2"},
                    ...
                ]
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "All changes saved successfully"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `create_new_movement` (Create New Movement from Master Table)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=create_new_movement&registration=PK-XYZ&aircraft_type=B737&on_block_time=10:00&parking_stand=B5&...
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Movement created successfully.",
                    "id": "new_movement_id"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `setRON` (Set Remain Overnight Status - same as `index.php`)
            *   **Method**: `POST`
            *   **Request Format (JSON)**:
                ```json
                {
                    "action": "setRON"
                }
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "RON status set for X movements"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`

    *   **Endpoint**: `dashboard.php`
        *   **Action**: `refresh_apron` (Get Live Apron Status)
            *   **Method**: `GET`
            *   **Request Format**: `dashboard.php?action=refresh_apron`
            *   **Response Format (JSON)**:
                ```json
                {
                    "total": 83,
                    "available": 50,
                    "occupied": 33,
                    "ron": 15
                }
                ```
            *   **Permissions**: All authenticated users.
        *   **Action**: `generate` (Generate Report)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=generate&report_type=charter_log&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
                ```
            *   **Response**: HTML table embedded in the page.
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `export_csv` (Export Report to CSV)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=export_csv&report_type=charter_log&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD
                ```
            *   **Response**: CSV file download.
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `manage_aircraft` (Add/Update Aircraft Details)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=manage_aircraft&registration=PK-ABC&aircraft_type=A320&operator_airline=Garuda&category=Commercial&notes=Some notes&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response**: HTML message (`<p style='color: green;'>Aircraft details saved successfully.</p>`) embedded in the page.
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `manage_flight_reference` (Add/Update Flight Reference)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=manage_flight_reference&flight_no=GA123&default_route=WIII&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response**: HTML message (`<p style='color: green;'>Flight reference saved successfully.</p>`) embedded in the page.
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `monthly_charter_report` (Generate Monthly Charter Report)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=monthly_charter_report&month=MM&year=YYYY&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response**: HTML table embedded in the page.
            *   **Permissions**: `admin`, `operator`

    *   **Endpoint**: `api/admin/users` (accessed via `api/admin/users`)
        *   **Action**: `list` (List Users)
            *   **Method**: `GET`
            *   **Request Format**: `api/admin/users?action=list&query=search_term&role=admin&status=active&page=1&per_page=25`
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "data": [
                        {"id": 1, "username": "admin", "full_name": "Admin User", "email": "admin@example.com", "role": "admin", "status": "active", "last_login_at": "2025-09-18 10:00:00"},
                        ...
                    ],
                    "total": 100,
                    "page": 1,
                    "per_page": 25
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`
        *   **Action**: `create` (Create User)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=create&full_name=New User&username=newuser&email=new@example.com&role=operator&status=active&password=securepassword&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "User created successfully.",
                    "temp_password": "generated_password" (if password not provided)
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`
        *   **Action**: `update` (Update User)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=update&id=1&full_name=Updated Name&username=updateduser&email=updated@example.com&role=admin&status=active&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "User updated successfully."
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`
        *   **Action**: `set_status` (Set User Status)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=set_status&id=1&status=suspended&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "User status updated successfully."
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`
        *   **Action**: `reset_password` (Reset User Password)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=reset_password&id=1&password=new_secure_password&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Password reset successfully."
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`

    *   **Endpoint**: `api/snapshots`
        *   **Action**: `create` (Create Daily Snapshot)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=create&snapshot_date=YYYY-MM-DD&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Daily snapshot saved successfully"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `list` (List Snapshots)
            *   **Method**: `GET`
            *   **Request Format**: `api/snapshots?action=list&page=1&per_page=20`
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "data": [
                        {"id": 1, "snapshot_date": "2025-09-17", "created_by_user_id": 1, "created_by_username": "admin", "created_at": "2025-09-17 10:00:00", "snapshot_data": "{...}"},
                        ...
                    ],
                    "total": 50,
                    "page": 1,
                    "per_page": 20
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `view` (View Snapshot Details)
            *   **Method**: `GET`
            *   **Request Format**: `api/snapshots?action=view&id=1`
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "data": {
                        "id": 1,
                        "snapshot_date": "2025-09-17",
                        "created_by_user_id": 1,
                        "created_by_username": "admin",
                        "created_at": "2025-09-17 10:00:00",
                        "snapshot_data": {
                            "staff_roster": [...],
                            "movements": [...],
                            "ron_data": [...],
                            "daily_metrics": {...}
                        }
                    }
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`, `operator`
        *   **Action**: `delete` (Delete Snapshot)
            *   **Method**: `POST` (`application/x-www-form-urlencoded`)
            *   **Request Format**:
                ```
                action=delete&id=1&csrf_token=YOUR_CSRF_TOKEN
                ```
            *   **Response Format (JSON)**:
                ```json
                {
                    "success": true,
                    "message": "Snapshot deleted successfully"
                }
                ```
                or
                ```json
                {
                    "success": false,
                    "message": "Error message."
                }
                ```
            *   **Permissions**: `admin`

## 5. Complete List of All Features and Functionality

*   **User Authentication and Authorization**:
    *   Secure user login with username/email and password.
    *   Role-based access control (`admin`, `operator`, `viewer`) for different functionalities and pages.
    *   Session management with inactivity timeout.
    *   Brute-force protection for login attempts (rate limiting by IP address).
    *   CSRF protection for sensitive POST requests.
    *   Audit logging of user actions (login, logout, user management, snapshot management).

*   **Aircraft Movement Tracking (Apron Map - `index.php`)**:
    *   Visual representation of aircraft on an apron map with stand labels.
    *   Ability to input and edit aircraft movement details (registration, type, on/off block times, parking stand, flight numbers, operator, remarks).
    *   Autofill of aircraft type and operator based on registration from a pre-defined database.
    *   Autofill of origin/destination based on flight number from a pre-defined database.
    *   Identification and management of Remain Overnight (RON) aircraft.
    *   Automatic carry-over of active RON aircraft to the current day's movements.
    *   Manual "Set RON" function to mark current movements as RON.
    *   Live display of apron status (total, available, occupied, live RON stands).
    *   Hangar (HGR) stand functionality to view aircraft currently in the hangar.

*   **Master Movement Data Management (`master-table.php`)**:
    *   Tabular display of all aircraft movements.
    *   Editable fields within the table for quick updates.
    *   Ability to save multiple changes to existing movements in a single operation.
    *   Ability to add new aircraft movements directly from the table.
    *   Pagination for large datasets.
    *   Filtering capabilities by date range, aircraft category, airline/operator, and flight number.
    *   Visual highlighting of duplicate flight numbers for the current day.
    *   "Load More Empty Rows" feature to facilitate bulk data entry.

*   **Daily Staff Roster Management (`index.php`)**:
    *   Input fields for recording day and night shift staff for a specific date and aerodrome.
    *   Ability to save and update daily staff rosters.

*   **Dashboard and Reporting (`dashboard.php`)**:
    *   Key Performance Indicators (KPIs) for daily operations:
        *   Live Apron Status (total, available, occupied, live RON).
        *   Daily movements by category (Commercial, Cargo, Charter) for arrivals and departures.
    *   Hourly movement analysis:
        *   Table showing arrivals and departures per 2-hour time range.
        *   Visual bar chart for peak hour analysis.
        *   Summary of peak/quiet periods and daily totals.
    *   Automated Reporting Suite:
        *   Generate various reports based on date range and type:
            *   Daily Log (AM Shift)
            *   Daily Log (PM Shift)
            *   Charter/VVIP Flight Log
            *   Daily RON Report
            *   Monthly Movement Summary
            *   Logbook AMC Narrative (mentioned in UI, but not explicitly implemented in provided code)
        *   Export generated reports to CSV format.

*   **Administrative Functions (via Dashboard modals)**:
    *   **User Management (`api/admin/users` / `api/admin/users`)**:
        *   List all system users with search and filter options.
        *   Create new user accounts.
        *   Update existing user details (full name, username, email, role, status).
        *   Reset user passwords.
        *   Suspend/activate user accounts.
    *   **Aircraft Details Management (`dashboard.php` POST action)**:
        *   Add and update static aircraft details (registration, type, operator, category, notes).
    *   **Flight References Management (`dashboard.php` POST action)**:
        *   Add and update flight numbers with default routes.
    *   **Daily Snapshot Archive (`api/snapshots`)**:
        *   Manually create daily snapshots of staff rosters, aircraft movements, RON data, and daily metrics.
        *   View historical snapshots.
        *   Print historical snapshots.
        *   Delete historical snapshots (admin only).
        *   Automated daily snapshot creation (via `php tools/console.php snapshot:generate` intended for cron job).

*   **Database Management**:
    *   `dbconnection.php` for PDO database connection.
    *   `amc.sql` defines the complete database schema (tables: `aircraft_details`, `aircraft_movements`, `audit_log`, `daily_snapshots`, `daily_staff_roster`, `flight_references`, `login_attempts`, `narrative_logbook_amc`, `stands`, `users`).
