# AMC System: Technical Explanation

This document serves as a high-level technical summary of the Apron Movement Control (AMC) system, intended for developers or technically literate individuals seeking to quickly understand the project's architecture, data flow, and components.

## 1. What This System Does
**Purpose:** The AMC system is a web application designed to manage, track, and optimize aircraft parking stands across an airport's apron.
**Domain:** Airport Operations & Air Traffic Management.
**Problem it solves:** Historically, dispatchers manually logged aircraft movements (arrivals, departures, Remain Over Night (RON)) and guessed the best available parking stands. This system digitizes the logging via an administrative spreadsheet interface and layers an intelligent Machine Learning recommendation engine to suggest statistically optimal stands based on historical data and real-world physical constraints.

## 2. Full Tech Stack
**Backend Framework:** Bespoke Object-Oriented PHP Framework (No heavy frameworks like Laravel or Symfony).
**Languages:** 
- **PHP 8.2+:** Core routing, business logic, data formatting, and controller orchestration.
- **Python 3.11+:** Machine Learning model execution.
- **JavaScript:** Frontend interactivity, map updates, and AJAX requests.
- **SQL:** Database queries.
- **HTML/CSS:** User interface markup.
**Database:** MariaDB 10.4+ (or MySQL 8.0+)
**Machine Learning:** `scikit-learn` (Random Forest implementation), `pandas`, `numpy`, and `joblib` (for loading model `.pkl` files).
**Server / Infra:** Apache 2.4 (via XAMPP) with `mod_rewrite` enabled for routing. No Docker required.
**Frontend Styling:** Tailwind CSS.

**Why each is used:** 
- **PHP** provides simple, fast server-side processing capable of deep integration with standard Apache servers. Building a custom MVC framework keeps the codebase lean and free of external package vulnerabilities.
- **Python** is the industry standard for ML. Using `scikit-learn` allows reliable execution of the Random Forest model.
- **Vanilla JavaScript with AJAX** allows the interactive map and master spreadsheet to be updated dynamically without forcing full page refreshes, ensuring a seamless user experience.
- **MariaDB** provides a reliable, ACID-compliant relational data store capable of handling thousands of historical movement logs securely.

## 3. Main Components & Modules
- **Apron Map Module (`app/Controllers/ApronController.php`, `public/assets/js/apron.js`):** The live visual map interface showing active aircraft. It handles requesting and rendering AI stand recommendations.
- **Master Table Module (`app/Controllers/MasterTableController.php`, `public/assets/js/master-table.js`):** A collaborative, spreadsheet-like administrative view. Users log movements here. Features bulk-save functionality using secure database transactions (via `AircraftMovementRepository.php`) to prevent data corruption.
- **Dashboard & Reporting (`app/Controllers/DashboardController.php`, `app/Services/ReportService.php`):** Generates hourly/categorical (Cargo/Charter/Commercial) movement breakdowns, charts, and downloadable CSV/PDF reports.
- **Security & Auth (`app/Controllers/AuthController.php`, `app/Security/LoginThrottler.php`, `app/Security/CsrfManager.php`):** Manages secure user sessions, Argon2id password hashing, CSRF token validation, and lockout mechanisms after repeated failed login attempts.
- **Machine Learning Layer (`ml/predict.py`):** An independent Python script that loads the `.pkl` Random Forest model, calculates derived features, and returns top stand prediction probabilities as JSON.

## 4. Overall Data Flow (From Input to Output)
1. **User Action:** An operator inputs an incoming aircraft's `Registration number`, `Airline`, and `Category` into the web frontend.
2. **AJAX Request:** JavaScript sends this data asynchronously to the PHP endpoint `/api/recommend`.
3. **PHP Orchestration:** The `Router.php` maps the request to `ApronController`, which validates inputs and uses the OS-level `proc_open` command to spawn a Python process.
4. **Python ML Execution:** `predict.py` receives the input data (JSON via standard-input), processes it into derived features, queries the Random Forest model (`parking_stand_model_rf_redo.pkl`), and outputs the top 3 stand recommendations via standard-output (stdout).
5. **Business Rules Validation (PHP Safeguard):** The PHP backend parses the JSON returned from Python. It filters these statistical guesses against the `ApronStatusService.php` to ensure the AI's top recommendations aren't physically occupied or incompatible (e.g., trying to place a massive Boeing 737 on a small Cessna stand).
6. **Final Output:** The validated, perfected list of recommendations is sent back to the frontend and rendered in the UI.
7. **Storage Cycle:** The user accepts a recommendation and logs the finalized movement in the master table. The data is securely saved to MariaDB by `AircraftMovementRepository.php` and serves as ground-truth data for future ML training cycles.

## 5. Random Forest Model Integration
**Where it happens:** Python scripts located in the `ml/` folder, utilizing `parking_stand_model_rf_redo.pkl` and `encoders_redo.pkl`.
**What it predicts:** The statistically ideal parking stand for an incoming flight based on historical movement patterns.
**Raw Input Features (from user):** 
- `Registration number`
- `Airline` (e.g., "Garuda")
- `Category` (e.g., Commercial, Cargo, Charter)
**Derived Features (computed dynamically by `predict.py`):**
- `Zone` (Left Cargo, Middle Charter, Right Commercial)
- `Aircraft Size` (Small aircraft vs. Standard jet)
- `Airline Tier` (High, Medium, or Low frequency operator)
**Output:** The model uses its `predict_proba` function to calculate a percentage probability score for *every single stand* at the airport. `predict.py` then sorts and formats the Top 3 highest-probability stands into a JSON array (e.g., `[{"stand": "A1", "probability": 45%}]`) before passing it back to PHP.

## 6. System Structure (Folder Layout)
- **`app/`**: The core PHP MVC architecture.
  - **`Controllers/`**: The "traffic cops" that receive web requests, trigger services, and return views or JSON.
  - **`Services/`**: Contains complex business logic to avoid bloated controllers (e.g., `RonService.php` handles gracefully carrying overnight aircraft to the next day).
  - **`Repositories/`**: The only files permitted to write raw SQL. They act as data wrappers for database interaction (e.g., `AircraftMovementRepository.php`).
  - **`Core/` & `Security/`**: Application bootstrapping, Request/Response wrapping, router definitions, and CSRF protection modules.
- **`ml/`**: Holds the Python ML logic (`predict.py`), diagnostic tools (`health_check.py`), and the serialized model snapshots (`.pkl` files).
- **`public/`**: The web root document folder. Contains `index.php` (the single entry point) and static assets like CSS and JS.
- **`resources/`**: Contains the frontend visual template files (`.php` files containing HTML).
- **`reports/`**: Destination for generated logs and system metric documents.
- **`routes/`**: Contains URL definitions that map web endpoints to their respective controllers.
- **`storage/`**: Server-side directory for temporary cache files and system logs.

## 7. Notable Design Decisions & Dependencies
- **No Heavy Frameworks:** To reduce dependency bloat, a custom PHP framework (`Application.php`, `Router.php`) was built from scratch. This guarantees long-term stability without worrying about vendor updates breaking the app.
- **No Composer or Node.js required at Runtime:** The production server requires zero package managers. Tailwind CSS was pre-compiled, making the entire project a simple "plug-and-play" deployment on any standard Apache/PHP setup.
- **AI as an Advisor, Not a Dictator:** A critical design philosophy was layering human-driven "Business Rules" on top of the AI. The Random Forest makes a statistical prediction, but the PHP backend enforces reality (ensuring the stand is actually empty today) before showing it to the human operator.
- **PHP-Python Communication Bridge:** The languages communicate synchronously via `proc_open()`. The PHP execution thread pauses, pipes JSON into Python, waits for Python to calculate and output JSON back, and then resumes execution.
- **Bulk Save via Database Transactions:** To prevent race conditions and improve UX, the Master Table does not save individual cells. The JS aggregates all changes and sends them in one burst, which `MasterTableController` saves via a secure PDO SQL Transaction—ensuring all rows save perfectly, or if an error occurs, the entire batch is rolled back to prevent corruption.
