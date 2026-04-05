# AMC System: Academic Codebase Documentation

This document serves as the comprehensive, academic reference for the AMC (Apron Movement Control) system. It is designed to bridge the technical implementation with plain-English explanations, allowing for a confident presentation to a mixed panel of technical and non-technical academics.

---

## 1. Project Overview

The AMC system is a comprehensive web application designed to monitor, track, and optimize aircraft parking stands across an airport's apron. The application elegantly bridges two core components:

1. **The Administrative Entry & Tracking System**: A robust, secure database interface where operators view live apron statuses, log aircraft movements (arrivals, departures, Remain Over Night/RON carryovers), toggle between physical apron layouts, and generate analytical reports (e.g., hourly breakdowns, category metrics).
2. **The Machine Learning Recommendation System**: An advanced Python-powered predictor integrated seamlessly into the PHP web interface. When an operator needs to assign a stand for an incoming flight, they input the aircraft's details. The system queries a trained **Random Forest** model using historical movement data to rank the most statistically appropriate parking stands. Human-defined "business rules" (like whether the stand is currently physically empty, or if small aircraft constraints apply) are then layered on top of the AI's prediction to yield a perfect recommendation.

These two systems act in concert: the ML system advises the human operator, and the administrative system records the human's final decision, feeding future data back into the cycle.

---

## 2. Directory & File Tree

This is the high-level architecture of the application's source code.

```text
AMC/
├── app/                  # Core backend PHP framework (Models, Views, Controllers, Services, Repositories)
├── ml/                   # Machine learning .pkl model files and Python execution scripts
├── public/               # Public web root containing the entry point and static assets (CSS, JS)
├── reports/              # Important operational logs, metric tracking, and documentation
├── resources/            # Frontend HTML/PHP view templates (the visual UI)
├── routes/               # URL routing definitions (maps web addresses to code)
├── storage/cache/        # Temporary file storage for quick aircraft lookups
├── tests/                # Automated testing scripts
├── .htaccess             # Apache web server configuration (handles URL rewriting and security)
├── amc.sql               # Database backup containing the table structures and data
├── CLAUDE.md / GEMINI.MD # Developer AI instructions and constraints (safeguards)
├── DATASET AMC .csv      # Raw historical aircraft movement dataset used to train the ML model
├── DEPLOY.md             # Deployment instructions for production or Docker environments
├── index.php             # The singular application bootstrap and entry point
├── package-lock.json     # Node.js locked dependency tree for precise builds
├── package.json          # Node.js configuration for building Tailwind CSS
└── tailwind.config.js    # Tailwind CSS configuration and visual theme settings
```

---

## 3. File-by-File Breakdown

Every code file in the `app/`, `ml/`, `public/`, `routes/`, and `resources/` directories is detailed below.

### Controllers
*The "traffic cops" of the application. They receive user requests, orchestrate the logic, and return a response.*

*   **`app/Controllers/ApronController.php`**
    *   **What it does:** Manages the live visual Apron Map, plotting active aircraft and handling the ML stand recommendation requests.
    *   **Key Functions:**
        *   `show()`: Renders the visual map and live aircraft data.
        *   `recommend()`: Accepts aircraft details, connects to Python, and returns the top 3 intelligent stand recommendations.
        *   `applyBusinessRules()`: Filters the ML's raw mathematical guess against real-world constraints (e.g., ensuring the stand isn't currently occupied by another plane).
*   **`app/Controllers/AuthController.php`**
    *   **What it does:** Securely securely handles users logging in and out of the system.
    *   **Key Functions:**
        *   `login()`: Verifies credentials, checking against brute-force attacks via a throttler, and starts a session.
*   **`app/Controllers/DashboardController.php`**
    *   **What it does:** Powers the analytical dashboard, rendering charts, metrics, and generating exportable reports.
    *   **Key Functions:**
        *   `movementMetrics()`: Calculates and returns hourly and categorical (Cargo/Charter/Commercial) breakdowns of the day's traffic.
        *   `handleReport()`: Generates performance and historical logs, outputting either HTML tables or a downloadable CSV file.
*   **`app/Controllers/MasterTableController.php`**
    *   **What it does:** Controls the spreadsheet-like administrative view where operators log every single aircraft movement manually.
    *   **Key Functions:**
        *   `show()`: Generates the paginated list of movements and highlights duplicates or scheduling conflicts.
        *   `saveAllChanges()`: Processes bulk edits from the frontend, securely saving them all to the database at once.
*   **`app/Controllers/Admin/UserController.php`**
    *   **What it does:** An admin-only API that allows creation, deletion, and modification of staff user accounts.
    *   **Key Functions:**
        *   `create()`, `update()`, `resetPassword()`: CRUD (Create, Read, Update, Delete) operations with enforced security roles.
*   **`app/Controllers/Api/SnapshotController.php`**
    *   **What it does:** Manages 'snapshots' — frozen, daily backups of the entire day's operations saved into a massive JSON string for historical auditing.

### Services
*The "business logic" layer. Complex operations are extracted here so controllers don't become bloated.*

*   **`app/Services/ApronStatusService.php`**
    *   **What it does:** Rapidly counts exactly how many stands are occupied, available, or containing RON (overnight) aircraft.
*   **`app/Services/AuditLogger.php`**
    *   **What it does:** Silently tracks every critical action (logins, deletions, edits) made by users into an unalterable database table for security compliance.
*   **`app/Services/ReportService.php`**
    *   **What it does:** Performs heavy data aggregation to build the HTML and CSV strings required for PDF or Excel exports.
*   **`app/Services/RonService.php`**
    *   **What it does:** Handles the complex "Remain Over Night" logic, automatically gracefully carrying an aircraft parked at 11:59 PM onto the next day's active sheet.
*   **`app/Services/SnapshotService.php`**
    *   **What it does:** Collects a massive array of movements, roster staff, and metrics at the end of the day, compressing them into a single string for storage.
*   **`app/Services/UserAdminService.php`**
    *   **What it does:** Enforces strict security rules on user management, such as preventing the system from ever deleting the final remaining Administrator.

### Repositories
*The "database communicators". Only these files are allowed to write raw SQL, preventing SQL injection and centralizing data access.*

*   **`app/Repositories/AircraftMovementRepository.php`**
    *   **What it does:** The largest repository. Performs all bulk updates, reads, and pagination for aircraft arrivals and departures.
*   **`app/Repositories/AircraftDetailRepository.php`**
    *   **What it does:** Queries aircraft specifications (Type, Operator) by their Tail Registration, utilizing file-caching to speed up recurrent lookups.
*   **`app/Repositories/UserRepository.php`**
    *   **What it does:** Safely queries user credentials and handles encrypted passwords using modern Argon2id hashing algorithms.
*   **(Remaining Repositories)**: `DailySnapshotRepository.php`, `DailyStaffRosterRepository.php`, `FlightReferenceRepository.php`, `StandRepository.php` perform identical, specialized SQL mapping for their respective tables.

### Core Framework & Security
*The "engine room". The bespoke framework the application runs on.*

*   **`app/Core/Application.php` & `Router.php`**
    *   **What it does:** Reads the incoming URL, finds the correct Controller to handle it, and passes the user's data along securely.
*   **`app/Core/Http/Request.php` & `Response.php`**
    *   **What it does:** Wraps messy PHP superglobals (`$_POST`, `$_GET`) into clean, object-oriented data structures.
*   **`app/Security/CsrfManager.php`**
    *   **What it does:** Generates unique, hidden cryptographic tokens injected into web forms, ensuring malicious websites cannot forge requests against the AMC system.
*   **`app/Security/LoginThrottler.php`**
    *   **What it does:** Prevents brute-force hacker attacks by locking out an IP address for 15 minutes if they guess a password incorrectly 5 times.

### Machine Learning
*The Python intelligence layer.*

*   **`ml/predict.py`**
    *   **What it does:** The brain of the recommendation engine. Reads JSON input via standard-input, feeds it to the loaded Random Forest model, and spits out an array of suggested parking stands.
    *   **Key Functions:**
        *   `build_feature_vector()`: Computes "derived features" dynamically (like identifying if the airline is 'High Frequency') to give the AI context it wasn't explicitly provided.
        *   `main()`: the entry point that handles the JSON conversation with the PHP system.
*   **`ml/health_check.py`**
    *   **What it does:** A diagnostic script that verifies the model `.pkl` files are not corrupted and can successfully load into memory.

### Frontend (Public Assets & Views)
*The visual interface.*

*   **`public/assets/js/apron.js` / `dashboard.js` / `master-table.js`**
    *   **What they do:** These files use JavaScript's `fetch()` API to make asynchronous (AJAX) requests to the PHP backend. This allows the map to redraw planes, or the spreadsheet to save rows, without ever requiring the user to refresh their browser tab.
*   **`resources/views/apron/index.php`**
    *   **What it does:** The main visual map screen. It contains the logic controlling "View A" (stylized) vs "View B" (real coordinates) by simply toggling CSS `display:none` on different arrays of stands.
*   **`public/index.php`**
    *   **What it does:** The absolute first file loaded by the server. It bootstraps the application by loading dependencies and handing control to the Router.

---

## 4. The Random Forest Integration

This section explains precisely how the AI was integrated into the web infrastructure.

### The Loading Mechanism
The trained Machine Learning models are not written in PHP; they are Python objects saved to disc as `.pkl` (pickle) files. These files contain the "frozen memory" of the trained Random Forest and the Label Encoders.
When a recommendation is requested, the PHP `ApronController` uses a command called `proc_open` to invisibly spawn a new Python process on the server. Python loads `parking_stand_model_rf_redo.pkl` into memory via the `joblib` library.

### The Input Features
The user on the frontend inputs three things: `Registration number`, `Airline`, and `Category`.
Before querying the model, `predict.py` takes these inputs and infers **Derived Features**:
1.  **Zone:** Categorizes the plane into Left Cargo, Middle Charter, or Right Commercial based on existing airport logic.
2.  **Aircraft Size:** Identifies if a plane is a small general aviation craft (like a Cessna) or a standard commercial jet.
3.  **Airline Tier:** Classifies airlines as High, Medium, or Low frequency.
These features are combined into a numeric matrix and fed into the AI.

### Model Output & Usage
The Random Forest model doesn't just guess one stand. It utilizes a `predict_proba` function, which outputs a percentage probability score for *every single stand on the airport*.
The Python script returns the Top 3 highest-percentage stands as JSON.

### The Full Request Lifecycle
1.  **Input:** The operator fills out the "Get Recommendation" sidebar on the web UI and clicks submit.
2.  **AJAX Request:** JS silently sends the data to `/api/recommend`.
3.  **PHP Validation:** `ApronController` ensures fields aren't blank.
4.  **Python Execution:** PHP sends the data pipeline to `predict.py`.
5.  **Prediction:** Python calculates probabilities and returns JSON (e.g. `[{"stand": "A1", "probability": 45%}]`).
6.  **Business Rules (The Safeguard):** The PHP controller receives the AI's top guesses. It then verifies reality: *Is stand A1 actually physically empty right now? Are we trying to put a giant 737 on a small Cessna A0 stand?* If the AI made an illegal guess based on current reality, PHP eliminates it.
7.  **Output:** The final, perfected, and sorted list is returned to the user interface.

---

## 5. The Admin System

The Administrative system handles the "Master Table" — a massive, collaborative, live spreadsheet of airport operations.

### How Entries are Created & Stored
1.  **Frontend Generation:** A user clicks "Add Row", generating a blank DOM template on their screen. They type in the plane's timings and stand.
2.  **Bulk Transmission:** Instead of saving one cell at a time (which is slow), the frontend JavaScript (`master-table.js`) tracks all modified rows in memory. When the user hits "Save All", it pushes an array of changes to the backend.
3.  **Robust Saving:** The `MasterTableController` receives the JSON. It passes it to the `AircraftMovementRepository.php`. The repository uses a **Database Transaction**, meaning all 50 rows save perfectly together, or if an error occurs, none of it saves. This prevents data corruption.

### Data Flow from Database to Display
When the user requests the Master Table page:
1.  The Controller asks the Repository for paginated data (e.g. "Get me Page 1, 75 items").
2.  The Repository writes a secure SQL `SELECT` statement, fetching the rows and counting total items for the pagination buttons.
3.  Simultaneously, `MasterTableController` executes a check for "Duplicate Flight Numbers" to warn the operator of scheduling conflicts.
4.  The data is injected into `resources/views/master-table/index.php`, where a standard HTML `foreach` loop spits out the spreadsheet rows.

---

## 6. Data Flow Diagram

```ascii
+-----------------------+     AJAX / HTTP (JSON)      +-------------------------+
|     User / Browser    | <=========================> |  index.php (Entry)      |
|  (Frontend UI, Maps)  |                             |  & Router (web.php)     |
+-----------------------+                             +------------+------------+
                                                                   |
                                                      +------------v------------+
                                                      |       Controllers       |
                                                      | (Traffic routing logic) |
                                                      +-----+-------------+-----+
                                                            |             |
           +------------------------------------------------+             +---------------------------------+
           |                                                                                                |
+----------v----------+                           +-----------------------+                        +--------v--------+
|      Services       |                           |      Repositories     |                        |  ML Integration |
|  (Complex business  |                           |     (SQL / Database)  |                        |  (predict.py)   |
|   logic, RON rules) |                           +-----------+-----------+                        +--------+--------+
+----------+----------+                                       |                                             |
           |                                                  |                                             |
           +--------------------------+-----------------------+                                             |
                                      |                                                                     |
                           +----------v-----------+                                                +--------v--------+
                           |    MySQL Database    |                                                | Random Forest   |
                           |   (Tables & Data)    |                                                |   (.pkl files)  |
                           +----------------------+                                                +-----------------+
```

---

## 7. Key Concepts Glossary

If technical terminology arises during the defence, rely on these simplified definitions:

1.  **Random Forest:** A machine learning model that acts like a "committee of experts". It creates hundreds of small "decision trees" (like flowcharts). Each tree makes a guess, and the "forest" votes on the best final answer, making it highly accurate and resistant to anomalous data.
2.  **Controller:** The "traffic cop" of the application. It receives an event from a user (like "load page"), asks the system for data, and returns the finished response.
3.  **Repository:** A specific code file completely dedicated to talking to the database. It stops us from writing raw, messy SQL queries everywhere else in our code.
4.  **Model / Value Object:** A standardized, lightweight container for data in our code. Think of it like a digital ID card; it guarantees that everywhere we pass "User" data, it always has an "Email" and "Role".
5.  **.pkl (Pickle) File:** A snapshot of a trained machine learning model. Freezing the model means Python can load it in milliseconds upon request, rather than spending hours relearning historical data every time an operator asks for a recommendation.
6.  **proc_open:** A server architecture mechanism that allows our PHP web server to completely step out of its own environment and safely run an external Python script in real-time.
7.  **CSRF Token:** A unique, hidden password automatically generated and inserted into forms by our system. It ensures that when someone submits data, the request came legitimately from *our* website, stopping malicious forgery cross-site.
8.  **RON (Remain Over Night):** A core business logic challenge. Aircraft that do not depart on their arrival day must be accurately carried over via software to the next day's live sheet.
9.  **AJAX (Asynchronous JavaScript and XML):** Web technology that lets the browser talk to the backend database behind the scenes. This is why our Apron visual map can update and draw planes without forcing the user to violently refresh the web page.
10. **Label Encoder:** Machine learning models only understand numbers. An encoder is a translation dictionary created alongside the model that converts text (e.g. Airline "Garuda") into an integer (e.g. "4") before making a prediction.
11. **PDO (PHP Data Objects):** The secure pathway our backend uses to talk to MySQL. It automatically neutralizes user inputs to prevent "SQL Injection" hacking attempts.
12. **Routing:** The mapping system that tells the server which code to run when a user types a specific URL (like mapping `/dashboard` to `DashboardController`).
