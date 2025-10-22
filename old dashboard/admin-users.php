<?php
require_once 'dbconnection.php'; // This loads config.php, so it MUST be first.
session_start();
require_once 'auth_check.php';

// Only admins can access this
requireRole('admin', 'Unauthorized access to user management');

// Set a global error handler to catch anything that slips by
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json');

try {
    // CSRF token generation and validation
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Password hashing helper
    function hash_password_or_fail(string $plain): string {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $hash = password_hash($plain, $algo);
        if ($hash === false) {
            throw new RuntimeException('Password hashing failed');
        }
        return $hash;
    }

    // Generate strong temporary password
    function generateTempPassword($length = 16): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&';
        return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            $query = $_GET['query'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $per_page = max(1, min(100, intval($_GET['per_page'] ?? 25)));
            $offset = ($page - 1) * $per_page;

            $where_conditions = [];
            $params = [];

            if (!empty($query)) {
                $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                $search_term = "%$query%";
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            if (!empty($role)) {
                $where_conditions[] = "role = ?";
                $params[] = $role;
            }

            if (!empty($status)) {
                $where_conditions[] = "status = ?";
                $params[] = $status;
            }

            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

            // Get total count
            $count_sql = "SELECT COUNT(*) FROM users $where_clause";
            $stmt = $pdo->prepare($count_sql);
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Get paginated results
            $sql = "SELECT id, username, email, full_name, role, status, last_login_at, created_at 
                    FROM users $where_clause 
                    ORDER BY created_at DESC 
                    LIMIT $per_page OFFSET $offset";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => $users,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page
            ]);
            break;

        case 'create':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }

            $username = trim($_POST['username']);
            $email = strtolower(trim($_POST['email']));
            $full_name = trim($_POST['full_name']);
            $role = $_POST['role'];
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? generateTempPassword();

            // Validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
                throw new Exception('Username must be 3-32 characters and contain only letters, numbers, dots, hyphens, and underscores');
            }

            if (!in_array($role, ['admin', 'operator', 'viewer'])) {
                throw new Exception('Invalid role specified');
            }

            if (!in_array($status, ['active', 'suspended'])) {
                throw new Exception('Invalid status specified');
            }

            // Check uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username or email already exists');
            }

            // Hash password
            $password_hash = hash_password_or_fail($password);

            // Insert user
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, full_name, role, status, password_hash, must_change_password) 
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([$username, $email, $full_name, $role, $status, $password_hash]);

            $new_user_id = $pdo->lastInsertId();

            // Log creation
            $pdo->prepare(
                "INSERT INTO audit_log (user_id, action_type, target_table, target_id, new_values) 
                 VALUES (?, 'CREATE_USER', 'users', ?, ?)"
            )->execute([$current_user_id, $new_user_id, json_encode([
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'status' => $status
            ])]);

            echo json_encode([
                'success' => true,
                'message' => 'User created successfully',
                'temp_password' => $password
            ]);
            break;

        case 'update':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }

            $user_id = $_POST['id'];
            $email = !empty($_POST['email']) ? strtolower(trim($_POST['email'])) : null;
            $full_name = !empty($_POST['full_name']) ? trim($_POST['full_name']) : null;
            $role = $_POST['role'] ?? null;
            $status = $_POST['status'] ?? null;

            // Get current user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_user = $stmt->fetch();

            if (!$current_user) {
                throw new Exception('User not found');
            }

            // Business rules - ensure at least 1 active admin
            if ($role === 'admin' || $status === 'suspended') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id != ?");
                $stmt->execute([$user_id]);
                $other_active_admins = $stmt->fetchColumn();

                if ($other_active_admins < 1 && ($current_user['role'] === 'admin')) {
                    if ($role !== 'admin' || $status === 'suspended') {
                        throw new Exception('Cannot modify the last active admin account');
                    }
                }
            }

            // Self-protection rules
            if ($user_id == $current_user_id) {
                if ($role && $role !== 'admin') {
                    throw new Exception('You cannot downgrade your own admin role');
                }
                if ($status === 'suspended') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id != ?");
                    $stmt->execute([$user_id]);
                    if ($stmt->fetchColumn() < 1) {
                        throw new Exception('You cannot suspend yourself as the last active admin');
                    }
                }
            }

            // Build update query dynamically
            $updates = [];
            $params = [];

            if ($email !== null) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address');
                }
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Email already exists');
                }
                $updates[] = "email = ?";
                $params[] = $email;
            }

            if ($full_name !== null) {
                $updates[] = "full_name = ?";
                $params[] = $full_name;
            }

            if ($role !== null) {
                if (!in_array($role, ['admin', 'operator', 'viewer'])) {
                    throw new Exception('Invalid role specified');
                }
                $updates[] = "role = ?";
                $params[] = $role;
            }

            if ($status !== null) {
                if (!in_array($status, ['active', 'suspended'])) {
                    throw new Exception('Invalid status specified');
                }
                $updates[] = "status = ?";
                $params[] = $status;
            }

            if (!empty($updates)) {
                $params[] = $user_id;
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Log the update
                $pdo->prepare(
                    "INSERT INTO audit_log (user_id, action_type, target_table, target_id, old_values, new_values) 
                     VALUES (?, 'UPDATE_USER', 'users', ?, ?, ?)"
                )->execute([
                    $current_user_id, $user_id,
                    json_encode($current_user),
                    json_encode($_POST)
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'reset_password':
        try {
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }

            $user_id = $_POST['id'];
            $new_password = $_POST['password'];

            if (empty($new_password)) {
                throw new Exception('Password cannot be empty.');
            }

            $password_hash = hash_password_or_fail($new_password);

            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);

            // Log the reset
            $pdo->prepare(
                "INSERT INTO audit_log (user_id, action_type, target_table, target_id, new_values) 
                 VALUES (?, 'RESET_PASSWORD', 'users', ?, ?)"
            )->execute([$current_user_id, $user_id, json_encode(['reset_by' => $current_user_id])]);

            echo json_encode([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

        case 'set_status':
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }

            $user_id = $_POST['id'];
            $status = $_POST['status'];

            if (!in_array($status, ['active', 'suspended'])) {
                throw new Exception('Invalid status specified');
            }

            // Business rules
            if ($status === 'suspended') {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_role = $stmt->fetchColumn();

                if ($user_role === 'admin') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id != ?");
                    $stmt->execute([$user_id]);
                    if ($stmt->fetchColumn() < 1) {
                        throw new Exception('Cannot suspend the last active admin');
                    }
                }

                // Self-protection
                if ($user_id == $current_user_id) {
                    throw new Exception('You cannot suspend your own account');
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);

            // Log the change
            $pdo->prepare(
                "INSERT INTO audit_log (user_id, action_type, target_table, target_id, new_values) 
                 VALUES (?, 'SET_STATUS', 'users', ?, ?)"
            )->execute([$current_user_id, $user_id, json_encode(['status' => $status])]);

            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    // Log the error to the PHP error log for good measure
    error_log("Fatal error in admin-users.php: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Send a valid JSON response with the error details
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}