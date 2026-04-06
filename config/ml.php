<?php

// =============================================================================
// ML Module Configuration
//
// PYTHON_PATH env var is set by docker-compose.yml to the venv binary:
//   /opt/venv/bin/python3
//
// In XAMPP / local dev, leave PYTHON_PATH unset and the controller's
// resolvePythonBinary() method will auto-detect python / python3 / py -3.
// =============================================================================

return [
    'python_path' => getenv('PYTHON_PATH') ?: '',
];
