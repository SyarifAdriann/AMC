# Troubleshooting Guide

This document provides solutions to common issues that may arise with the ML recommendation system.

## Frontend Issues

### Problem: The recommendation button does nothing, and there are no errors in the console.

*   **Cause:** This is likely a caching issue where your browser is using an old version of the JavaScript files.
*   **Solution:** Perform a hard refresh (`Ctrl+F5` or `Cmd+Shift+R`) to force the browser to download the latest scripts.

### Problem: The dashboard widgets are not loading, and you see 404 errors in the console for API requests.

*   **Cause:** The API endpoint paths in the JavaScript configuration might be incorrect.
*   **Solution:**
    1.  Open `resources/views/dashboard/index.php`.
    2.  Find the `window.dashboardConfig` object.
    3.  Ensure that the `endpoints` do not have a leading slash (e.g., it should be `api/ml/metrics`, not `/api/ml/metrics`).

### Problem: You see a `TypeError: Cannot set properties of null` error in the console.

*   **Cause:** An HTML element that the JavaScript expects to find is missing from the page.
*   **Solution:** Check the error message to see which element is missing, and then add it to the appropriate view file (e.g., `resources/views/dashboard/index.php`).

## Backend Issues

### Problem: Prediction logs are not being saved to the database.

*   **Cause:** There might be a silent error in the backend PHP code.
*   **Solution:**
    1.  Check the PHP error log for any errors. The log is usually located at `C:\xampp\php\logs\php_error_log`.
    2.  If the log file does not exist, you may need to enable error logging in your `php.ini` file.
    3.  Add `error_log()` statements to the `ApronController.php` methods (`recordPredictionLog` and `markPredictionOutcome`) to trace the execution and see where it is failing.

### Problem: The Python prediction script is not working.

*   **Cause:** The PHP process may not be able to execute the Python script due to incorrect paths or permissions.
*   **Solution:**
    1.  Verify that the path to the Python executable is correct in the `ml.python_path` configuration.
    2.  Ensure that the `ml/predict.py` script is readable and executable by the web server user.
    3.  Test the script from the command line to make sure it runs without errors: `python ml/predict.py "{\"aircraft_type\": \"A320\", \"operator_airline\": \"BATIK AIR\", \"category\": \"Komersial\"}"`

## General Debugging

*   **Check the browser's developer console (`F12`)** for any errors.
*   **Check the Network tab** in the developer tools to inspect the status and response of all API requests.
*   **Check the PHP error log** for any backend errors.
