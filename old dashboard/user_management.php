<?php
session_start();
require_once 'auth_check.php';
require_once 'dbconnection.php';

// Only admins can access this
requireRole('admin', 'Unauthorized access to user management');

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list_users':
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT * FROM users WHERE 1=1";
            $countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
            $params = [];

            if (!empty($_GET['search'])) {
                $sql .= " AND (username LIKE :search OR full_name LIKE :search OR email LIKE :search)";
                $countSql .= " AND (username LIKE :search OR full_name LIKE :search OR email LIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            if (!empty($_GET['role'])) {
                $sql .= " AND role = :role";
                $countSql .= " AND role = :role";
                $params[':role'] = $_GET['role'];
            }

            if (!empty($_GET['status'])) {
                $sql .= " AND status = :status";
                $countSql .= " AND status = :status";
                $params[':status'] = $_GET['status'];
            }

            $totalStmt = $pdo->prepare($countSql);
            $totalStmt->execute($params);
            $total = $totalStmt->fetchColumn();

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => &$val) {
                $stmt->bindParam($key, $val);
            }
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $users, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()]);
        }
        break;

    case 'get_user':
        try {
            $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, status FROM users WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $user]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching user: ' . $e->getMessage()]);
        }
        break;

    case 'update_user':
        try {
            $userId = $_POST['id'];
            $fullName = $_POST['full_name'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $status = $_POST['status'];

            if (empty($userId)) {
                // Create new user
                $password = !empty($_POST['password']) ? $_POST['password'] : bin2hex(random_bytes(8));
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (full_name, username, email, role, status, password) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fullName, $username, $email, $role, $status, $passwordHash]);
                $message = 'User created successfully.';
            } else {
                // Update existing user
                $sql = "UPDATE users SET full_name = ?, username = ?, email = ?, role = ?, status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fullName, $username, $email, $role, $status, $userId]);
                $message = 'User updated successfully.';
            }

            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()]);
        }
        break;

    case 'update_status':
        try {
            $userId = $_POST['id'];
            $status = $_POST['status'];

            $sql = "UPDATE users SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $userId]);

            echo json_encode(['success' => true, 'message' => 'User status updated successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating user status: ' . $e->getMessage()]);
        }
        break;

    case 'reset_password':
        try {
            $userId = $_POST['id'];
            $password = $_POST['password'];
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$passwordHash, $userId]);

            echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error resetting password: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>