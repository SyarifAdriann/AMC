# Project Explanation: Aircraft Movement Control (AMC)

## 1. Overview

This document provides a detailed explanation of the Aircraft Movement Control (AMC) project. The project is a web-based application designed to track and manage aircraft movements. It is built using PHP, MySQL, and vanilla CSS for styling.

The core functionality of the application is to display a master table of aircraft movements, allowing users to view and manage flight data. The application also includes a dashboard to provide a summary of key metrics.

## 2. Detailed Q&A

### Q1: What does your application do?

*   **Main Purpose/Functionality:** The application's primary function is to serve as a centralized system for tracking and displaying aircraft movements for an airport. It provides a real-time, comprehensive view of all flight activities, including arrivals, departures, and on-ground movements.
*   **Users:** The application is intended for internal company use by various operational units at the airport, including AMC (Aircraft Movement Control), AirNav (Air Navigation), Firefighters, and Safety personnel. Future plans include role-based access for admins, operators, and viewers.

### Q2: Current features:

*   **What users can do:** Currently, users can view a master table of all aircraft movements and a dashboard that shows summary metrics. The application is primarily for data display and monitoring.
*   **Real-time features:** The application does not have real-time features like chat, notifications, or live updates. The data is refreshed on page load.
*   **Other features:** There are no features for file uploads, payments, or email notifications.

### Q3: Database & Data:

*   **Database Tables:** There are 8 tables in the database (`aircraft_details`, `aircraft_movements`, `audit_log`, `daily_staff_roster`, `flight_references`, `narrative_logbook_amc`, `stands`, `users`).
*   **Data Entry vs. Display:** The application is heavily focused on data display. There are currently no forms for data entry in the user interface.
*   **Data Relationships:** Yes, there are complex relationships between the data. The `aircraft_movements` table is the central table and has foreign key relationships to other tables like `aircraft_details`, `users`, and `stands`.

### Q4: User Interface:

*   **UI Style:** The user interface consists of simple pages with tables and forms for displaying data. The main focus is on presenting the aircraft movement data in a clear and concise manner.
*   **Interactive Elements:** The application does not have complex interactive elements like drag-and-drop, modals, or dynamic content loading.
*   **Charts & Graphs:** Yes, the dashboard includes charts, graphs, and other data visualizations to provide a high-level overview of the aircraft movement data.

### Q5: Current Structure:

*   **Project Organization:** The project is organized as a monolithic application with all files in a single directory. It does not follow a formal design pattern like MVC.
*   **PHP Files:** There are roughly 6 PHP files that handle the application's logic.
*   **Admin Area:** There is no separate admin area in the current version of the application.

### Q6: Traffic & Users:

*   **Expected Users:** The application is expected to have 4-5 concurrent users, with a maximum of 10.
*   **User Base:** The application will be used internally by various operational units at the airport, including AMC, AirNav, Firefighters, and Safety.

### Q7: Future Plans:

*   **Mobile App:** There are no plans to build a separate mobile app, but the layout will be optimized to be responsive and work on mobile devices.
*   **Developer Collaboration:** 1 or 2 other developers may work on this project in the future.
*   **New Features:** Future plans include adding user authentication with login functionality and role-based access control (admin, operator, viewer).

### Q8: Technical Preferences:

*   **Codebase:** A single, unified codebase is the ideal preference, but separate frontend/backend codebases are also acceptable.
*   **Learning:** Open to learning new concepts and technologies.