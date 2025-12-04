# AMC Parking Stand Prediction System

## Project Overview

The **AMC (Aircraft Movement Control) Parking Stand Prediction System** is an intelligent web application that manages aircraft parking operations and provides ML-powered stand recommendations for incoming aircraft. The system combines real-time operational data management with machine learning predictions to optimize parking stand allocation at an airport.

## Purpose

This system solves the critical problem of **efficient parking stand allocation** by:
- Tracking real-time aircraft movements and parking assignments
- Predicting optimal parking stands based on historical data and aircraft characteristics
- Managing daily operational snapshots and staff rosters
- Generating comprehensive reports on aircraft operations
- Maintaining audit trails for all system operations

## Tech Stack

### Backend
- **PHP 8.3.25** - Custom MVC framework (no Laravel/Symfony)
- **MariaDB 10.4.32** - Primary database
- **Python 3.13.5** - Machine learning module
- **scikit-learn** - ML model training and predictions

### Frontend
- **HTML/CSS/JavaScript** - Vanilla JS, no frameworks
- **Tailwind CSS** - Utility-first CSS framework
- **AJAX** - Asynchronous data operations

### Infrastructure
- **XAMPP** - Development environment (Apache, MySQL/MariaDB)
- **Windows-based** - Designed for Windows Server deployment

## System Capabilities

### Core Features
1. **Real-time Apron View** - Live view of current aircraft positions and parking stand occupancy
2. **ML-Powered Stand Recommendations** - Top-3 parking stand predictions with confidence scores (80.15% Top-3 accuracy)
3. **Movement Tracking** - Complete aircraft movement history with RON (Remain Overnight) status
4. **Dashboard Analytics** - Movement metrics, category breakdowns, and hourly analytics
5. **Master Data Management** - Aircraft details, flight references, airline preferences
6. **Daily Snapshots** - Automated daily operational snapshots
7. **Staff Roster Management** - Daily staff assignments and on-duty tracking
8. **Report Generation** - Multiple report types with CSV export
9. **User Management** - Role-based access control (Admin, Operator, Viewer)
10. **Audit Logging** - Comprehensive activity tracking

### ML Prediction Features
- **Input**: Aircraft type, operator airline, category
- **Output**: Top-3 recommended stands with probability scores
- **Model**: Random Forest Classifier
- **Performance**: ~4 seconds prediction time, 80.15% Top-3 accuracy
- **Caching**: File-based cache (5-minute TTL) and Python model cache (1-hour TTL)

## Quick Start Guide

### Prerequisites
- XAMPP with PHP 8.3.25 and MariaDB 10.4.32
- Python 3.13.5 with pip
- Windows OS (designed for Windows paths)

### Installation (Quick)
```bash
# 1. Clone/extract to XAMPP htdocs
cd C:\xampp\htdocs\amc

# 2. Install Python dependencies
pip install numpy pandas scikit-learn

# 3. Import database
mysql -u root -p < amc.sql

# 4. Configure database (if needed)
# Edit config/database.php for DB credentials

# 5. Start XAMPP Apache and MySQL

# 6. Access application
http://localhost/amc
```

### Default Login
- **Username**: admin
- **Password**: (Check database users table)

## Folder Structure Overview

```
C:\xampp\htdocs\amc\
├── app/                          # Application core
│   ├── Controllers/              # MVC Controllers (Apron, Dashboard, etc.)
│   ├── Models/                   # Database models (User, AircraftMovement, etc.)
│   ├── Repositories/             # Data access layer
│   ├── Services/                 # Business logic (ApronStatus, Ron, Report, etc.)
│   ├── Core/                     # Framework core (Router, Controller, DB, Auth, etc.)
│   ├── Security/                 # Security components (CSRF, LoginThrottler)
│   └── Middleware/               # Request middleware (AuthMiddleware)
│
├── ml/                           # Machine Learning module
│   ├── predict.py                # Main prediction script (entry point)
│   ├── train_model.py            # Model training script
│   ├── model_cache.py            # Python model caching
│   ├── parking_stand_model_rf_redo.pkl  # Trained Random Forest model
│   └── encoders_redo.pkl         # Label encoders for features
│
├── resources/                    # Frontend resources
│   └── views/                    # PHP view templates
│       ├── layouts/              # Layout templates (app.php)
│       ├── partials/             # Reusable view components (nav, modals)
│       ├── apron/                # Apron view templates
│       ├── dashboard/            # Dashboard view templates
│       ├── master-table/         # Master table view templates
│       └── auth/                 # Authentication view templates
│
├── public/                       # Public assets
│   ├── css/                      # Stylesheets (including Tailwind output)
│   └── js/                       # JavaScript files
│
├── config/                       # Configuration files
│   ├── database.php              # Database configuration
│   ├── app.php                   # Application settings
│   ├── session.php               # Session configuration
│   └── logging.php               # Logging configuration
│
├── bootstrap/                    # Application bootstrap
│   ├── app.php                   # Application initialization
│   ├── autoload.php              # PSR-4 autoloader
│   ├── legacy.php                # Legacy helper functions
│   └── providers.php             # Service providers
│
├── routes/                       # Route definitions
│   ├── web.php                   # Web routes
│   └── api.php                   # API routes
│
├── database/                     # Database migrations
│   └── migrations/               # SQL migration files
│
├── tools/                        # Utility scripts
│   ├── console.php               # CLI command runner
│   ├── cleanup_cache.php         # Cache cleanup utility
│   ├── precompute_preferences.php # Airline preference precomputation
│   └── [various test/analysis scripts]
│
├── reports/                      # Generated reports (ML metrics, etc.)
├── cache/                        # File cache storage
├── storage/                      # Application storage
├── tests/                        # Test files
├── docs/                         # Documentation (this folder)
│
├── index.php                     # Main application entry point
├── login.php                     # Login page
├── logout.php                    # Logout handler
├── dashboard.php                 # Dashboard page
├── master-table.php              # Master table page
└── amc.sql                       # Database schema and seed data
```

## Key Features List

### Operational Features
- Real-time parking stand occupancy visualization
- Aircraft movement tracking (arrival, departure, RON)
- Hangar management and tracking
- Stand availability calculation
- Movement categorization (Commercial, Cargo, Charter)

### Machine Learning Features
- Intelligent stand recommendation (Top-3 predictions)
- Historical pattern analysis
- Airline preference learning
- Aircraft size compatibility detection (A0-compatible vs Standard)
- Airline tier classification (High/Medium/Low frequency)

### Reporting Features
- Movement summary reports
- Monthly charter reports
- Category-based analytics
- Hourly breakdown charts
- CSV export functionality

### Security Features
- Role-based access control (RBAC)
- CSRF protection on all forms
- Login throttling (5 attempts, 15-minute lockout)
- Session timeout (30 minutes)
- Audit logging for all critical operations

### Data Management Features
- Aircraft details management
- Flight reference database
- Airline preferences
- Daily snapshots (automated at 23:59)
- Staff roster management

## Documentation Structure

This documentation suite consists of:

1. **README.md** (this file) - Project overview and quick start
2. **ARCHITECTURE.md** - Deep dive into system design, components, and integration flows
3. **DATABASE.md** - Complete database schema, relationships, and queries
4. **ML_MODEL.md** - Machine learning implementation details
5. **API_ENDPOINTS.md** - All routes, endpoints, and API documentation
6. **SETUP.md** - Detailed installation and configuration guide
7. **TESTING.md** - Testing procedures and validation
8. **ERRORS_AND_FIXES.md** - Common issues and troubleshooting
9. **DEPENDENCIES.md** - Complete dependency list with versions
10. **CODING_STANDARDS.md** - Code style guide and conventions

## Project Status

- **Version**: 1.0 (Production-ready)
- **Last Updated**: November 2025
- **ML Model Version**: RF_REDO (Random Forest with engineered features)
- **Database Version**: 12 tables, fully normalized
- **Performance**: Prediction time ~4 seconds, 80.15% Top-3 accuracy

## Support and Maintenance

For system modifications or troubleshooting:
1. Read **ERRORS_AND_FIXES.md** for common issues
2. Consult **ARCHITECTURE.md** for system design understanding
3. Review **DATABASE.md** before modifying database schema
4. Check **ML_MODEL.md** before retraining the model

## License

Internal project - proprietary system for airport operations management.
