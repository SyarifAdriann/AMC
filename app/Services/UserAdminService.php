<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use InvalidArgumentException; // ADD THIS
use RuntimeException; // ADD THIS
use Exception;

class UserAdminService
{
    protected UserRepository $userRepository;
    protected AuditLogger $auditLogger;

    public function __construct(UserRepository $userRepository, AuditLogger $auditLogger)
    {
        $this->userRepository = $userRepository;
        $this->auditLogger = $auditLogger;
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $users = $this->userRepository->search($filters, $perPage, $offset);
        $totalUsers = $this->userRepository->countByFilters($filters);

        return [
            'data' => $users,
            'total' => $totalUsers,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function create(array $payload, int $adminId): array
    {
        $username = $this->validateUsername($payload['username'] ?? '');
        $email = $this->validateEmail($payload['email'] ?? '');
        $fullName = trim($payload['full_name'] ?? '');
        $role = $this->validateRole($payload['role'] ?? '');
        $status = $this->validateStatus($payload['status'] ?? 'active');
        $password = $payload['password'] ?? '';

        if (empty($username) || empty($fullName) || empty($role)) {
            throw new InvalidArgumentException('Username, full name, and role are required.');
        }

        if ($this->userRepository->usernameExists($username)) {
            throw new InvalidArgumentException('Username already exists.');
        }

        if (!empty($email) && $this->userRepository->emailExists($email)) {
            throw new InvalidArgumentException('Email already exists.');
        }

        $tempPassword = '';
        if (empty($password)) {
            $tempPassword = $this->generateTempPassword();
            $passwordHash = $this->hashPassword($tempPassword);
            $mustChangePassword = 1;
        } else {
            $passwordHash = $this->hashPassword($password);
            $mustChangePassword = 0;
        }

        $userId = $this->userRepository->create([
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'role' => $role,
            'status' => $status,
            'password_hash' => $passwordHash,
            'must_change_password' => $mustChangePassword,
        ]);

        $this->auditLogger->log('User Created', "User {$username} (ID: {$userId}) created by admin (ID: {$adminId}).", $adminId);

        return [
            'id' => $userId,
            'temp_password' => $tempPassword,
        ];
    }

    public function update(int $id, array $payload, int $adminId): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('User not found.');
        }

        $attributesToUpdate = [];
        $oldValues = $user->toArray(); // Capture old values for audit log

        // Validate and set email
        if (isset($payload['email'])) {
            $email = $this->validateEmail($payload['email']);
            if ($email !== $user->email() && $this->userRepository->emailExists($email, $id)) {
                throw new InvalidArgumentException('Email already in use by another user.');
            }
            $attributesToUpdate['email'] = $email;
        }

        // Validate and set full_name
        if (isset($payload['full_name'])) {
            $attributesToUpdate['full_name'] = trim($payload['full_name']);
        }

        // FIX: Only validate username if it's being changed
        if (isset($payload['username'])) {
            $newUsername = $this->validateUsername($payload['username']);
            if ($newUsername !== $user->username() && $this->userRepository->usernameExists($newUsername, $id)) {
                throw new InvalidArgumentException('Username already exists.');
            }
            $attributesToUpdate['username'] = $newUsername;
        }

        // Validate and set role
        if (isset($payload['role'])) {
            $newRole = $this->validateRole($payload['role']);
            $this->guardLastAdmin($id, $user->role(), $newRole, $user->status(), $adminId);
            $attributesToUpdate['role'] = $newRole;
        }

        // Validate and set status
        if (isset($payload['status'])) {
            $newStatus = $this->validateStatus($payload['status']);
            $this->guardStatusChange($id, $newStatus, $adminId, $user->role());
            $attributesToUpdate['status'] = $newStatus;
        }

        if (!empty($attributesToUpdate)) {
            $this->userRepository->update($id, $attributesToUpdate);
            $newValues = array_merge($oldValues, $attributesToUpdate); // Merge updated values
            $this->auditLogger->log(
                'User Updated',
                "User {$user->username} (ID: {$id}) updated by admin (ID: {$adminId}).",
                $adminId,
                json_encode($oldValues), // Old values
                json_encode($newValues)  // New values
            );
        }
    }

    public function resetPassword(int $id, string $password, int $adminId): string
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('User not found.');
        }

        $tempPassword = '';
        if (empty($password)) {
            $tempPassword = $this->generateTempPassword();
            $passwordHash = $this->hashPassword($tempPassword);
        } else {
            $passwordHash = $this->hashPassword($password);
        }

        $this->userRepository->updatePassword($id, $passwordHash, true);

        $this->auditLogger->log('User Password Reset', "Password for user {$user->username} (ID: {$id}) reset by admin (ID: {$adminId}).", $adminId);

        return $tempPassword;
    }

    public function setStatus(int $id, string $status, int $adminId): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('User not found.');
        }

        $status = $this->validateStatus($status);

        $this->guardStatusChange($id, $status, $adminId, $user->role());

        $this->userRepository->updateStatus($id, $status);

        $this->auditLogger->log('User Status Updated', "Status for user {$user->username} (ID: {$id}) changed to {$status} by admin (ID: {$adminId}).", $adminId);
    }

    public function delete(int $id, int $adminId): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('User not found.');
        }

        $this->guardDelete($id, $adminId, $user->role);

        $this->userRepository->delete($id);

        $this->auditLogger->log('User Deleted', "User {$user->username} (ID: {$id}) deleted by admin (ID: {$adminId}).", $adminId);
    }

    protected function guardLastAdmin(int $id, string $currentRole, string $newRole, string $currentStatus, int $adminId): void
    {
        // Only apply this guard if the user being updated is an admin
        if ($currentRole !== 'admin') {
            return;
        }

        $roleChanging = ($currentRole !== $newRole);
        $statusChanging = ($currentStatus !== 'suspended' && $newRole === 'admin'); // Only consider status change if role remains admin

        // If neither role nor status is changing in a way that affects admin count, no need to check
        if (!$roleChanging && !$statusChanging) {
            return;
        }

        // If the role is changing from admin to something else, or status is changing to suspended
        if ($newRole !== 'admin' || $newStatus === 'suspended') {
            $activeAdmins = $this->userRepository->countActiveAdminsExcluding($id);
            if ($activeAdmins < 1) {
                throw new InvalidArgumentException('Cannot modify the last active administrator account.');
            }
        }

        // Prevent an admin from downgrading their own role
        if ($id === $adminId && $newRole !== 'admin') {
            throw new InvalidArgumentException('You cannot downgrade your own admin role.');
        }
    }

    protected function guardStatusChange(int $id, string $status, int $actorId, string $userRole): void
    {
        if ($status === 'active') {
            return;
        }

        // If suspending an admin, ensure there's at least one other active admin
        if ($userRole === 'admin') {
            $activeAdmins = $this->userRepository->countActiveAdminsExcluding($id);
            if ($activeAdmins < 1) {
                throw new InvalidArgumentException('Cannot suspend the last active administrator.');
            }
        }

        // Prevent a user from suspending their own account
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot suspend your own account.');
        }
    }

    protected function guardDelete(int $id, int $actorId, string $userRole): void
    {
        // Prevent a user from deleting their own account
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        // If deleting an admin, ensure there's at least one other active admin
        if ($userRole === 'admin') {
            $activeAdmins = $this->userRepository->countActiveAdminsExcluding($id);
            if ($activeAdmins < 1) {
                throw new InvalidArgumentException('Cannot delete the last active administrator.');
            }
        }
    }

    protected function validateUsername(string $username): string
    {
        $username = trim($username);
        if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3-32 characters and contain only letters, numbers, dots, hyphens, and underscores.');
        }
        return $username;
    }

    protected function validateEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
        return $email;
    }

    protected function validateRole(string $role): string
    {
        if (!in_array($role, ['admin', 'operator', 'viewer'], true)) {
            throw new InvalidArgumentException('Invalid role specified.');
        }
        return $role;
    }

    protected function validateStatus(string $status): string
    {
        if (!in_array($status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Invalid status specified.');
        }
        return $status;
    }

    protected function generateTempPassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&';
        $password = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }

    protected function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $hash = password_hash($password, $algo);

        if ($hash === false) {
            throw new RuntimeException('Password hashing failed.');
        }

        return $hash;
    }
}