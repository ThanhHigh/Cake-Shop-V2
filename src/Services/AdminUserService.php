<?php
/**
 * Admin User Management Service
 * Handles administrative operations on user accounts: view, edit, delete, role management
 * Includes access control vulnerabilities for OWASP A01 training
 */

namespace CakeShop\Services;

class AdminUserService
{
    private $db;
    private $config;
    private $adminId;

    public function __construct($config, $adminId = null)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
        $this->adminId = $adminId; // Current admin user ID for audit logging
    }

    /**
     * Validate admin access
     * VULNERABLE: Bypass admin check when access_control vulnerability is enabled
     * SECURE: Always verify admin role
     */
    private function validateAdminAccess()
    {
        if ($this->config['app_mode'] === 'vulnerable' && ($this->config['vulnerabilities']['access_control'] ?? false)) {
            // VULNERABLE: Skip admin check entirely
            return true;
        }

        // SECURE: Verify admin in session
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new \Exception('Unauthorized: Admin access required');
        }

        return true;
    }

    /**
     * Get all users with optional filtering and pagination
     */
    public function getAllUsers($limit = 200, $offset = 0, $filters = [])
    {
        try {
            $this->validateAdminAccess();

            $sql = "SELECT id, username, email, full_name, role, is_active, 
                           created_at, updated_at, last_login_at, failed_login_attempts, locked_until
                    FROM users
                    WHERE 1=1";
            $params = [];

            // Apply filters
            if (!empty($filters['role'])) {
                $sql .= " AND role = ?";
                $params[] = $filters['role'];
            }

            if (isset($filters['is_active'])) {
                $sql .= " AND is_active = ?";
                $params[] = (int)$filters['is_active'];
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $sql .= " AND (email LIKE ? OR full_name LIKE ? OR username LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Order and limit
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = max(1, (int)$limit);
            $params[] = max(0, (int)$offset);

            $users = $this->db->queryAll($sql, $params);

            // Format response
            return [
                'success' => true,
                'users' => $users,
                'count' => count($users)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user count for pagination
     */
    public function getUserCount($filters = [])
    {
        try {
            $this->validateAdminAccess();

            $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
            $params = [];

            if (!empty($filters['role'])) {
                $sql .= " AND role = ?";
                $params[] = $filters['role'];
            }

            if (isset($filters['is_active'])) {
                $sql .= " AND is_active = ?";
                $params[] = (int)$filters['is_active'];
            }

            if (!empty($filters['search'])) {
                $searchTerm = '%' . $filters['search'] . '%';
                $sql .= " AND (email LIKE ? OR full_name LIKE ? OR username LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $result = $this->db->queryOne($sql, $params);
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get single user by ID
     */
    public function getUserById($userId)
    {
        try {
            $this->validateAdminAccess();

            $sql = "SELECT id, username, email, full_name, role, is_active, 
                           created_at, updated_at, last_login_at, failed_login_attempts, locked_until
                    FROM users WHERE id = ?";
            $user = $this->db->queryOne($sql, [$userId]);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            return [
                'success' => true,
                'user' => $user
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update user details
     * VULNERABLE: Allow role escalation and bypass validation
     * SECURE: Strict validation and authorization
     */
    public function updateUser($targetUserId, $data)
    {
        try {
            $this->validateAdminAccess();

            // Prevent self-deletion
            if ($targetUserId === $this->adminId && isset($data['is_active']) && !$data['is_active']) {
                return ['success' => false, 'message' => 'Cannot deactivate your own account'];
            }

            $updates = [];
            $params = [];

            // Update email
            if (isset($data['email'])) {
                $email = trim((string)$data['email']);
                
                // VULNERABLE: Skip email validation
                if (!($this->config['app_mode'] === 'vulnerable' && ($this->config['vulnerabilities']['input_validation'] ?? false))) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return ['success' => false, 'message' => 'Invalid email format'];
                    }
                }

                // Check email uniqueness
                $existing = $this->db->queryOne("SELECT id FROM users WHERE email = ? AND id <> ?", [$email, $targetUserId]);
                if ($existing) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }

                $updates[] = 'email = ?';
                $params[] = $email;
            }

            // Update full name
            if (isset($data['full_name'])) {
                $fullName = trim((string)$data['full_name']);
                if ($fullName === '') {
                    return ['success' => false, 'message' => 'Full name cannot be empty'];
                }
                $updates[] = 'full_name = ?';
                $params[] = $fullName;
            }

            // Update username
            if (isset($data['username'])) {
                $username = trim((string)$data['username']);
                if ($username === '') {
                    return ['success' => false, 'message' => 'Username cannot be empty'];
                }

                $existing = $this->db->queryOne("SELECT id FROM users WHERE username = ? AND id <> ?", [$username, $targetUserId]);
                if ($existing) {
                    return ['success' => false, 'message' => 'Username already taken'];
                }

                $updates[] = 'username = ?';
                $params[] = $username;
            }

            // Update role
            if (isset($data['role'])) {
                $role = $data['role'];
                
                // VULNERABLE: Allow any role change without verification
                if (!($this->config['app_mode'] === 'vulnerable' && ($this->config['vulnerabilities']['access_control'] ?? false))) {
                    // SECURE: Only allow valid roles
                    if (!in_array($role, ['customer', 'admin'])) {
                        return ['success' => false, 'message' => 'Invalid role'];
                    }
                }

                $updates[] = 'role = ?';
                $params[] = $role;
            }

            // Update active status
            if (isset($data['is_active'])) {
                $updates[] = 'is_active = ?';
                $params[] = (int)(bool)$data['is_active'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No updates provided'];
            }

            // Perform update
            $params[] = $targetUserId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->execute($sql, $params);

            // Log admin action
            $this->logAdminAction('user_update', $targetUserId, "Updated user ID: $targetUserId");

            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Delete user (soft delete - set inactive)
     */
    public function deleteUser($targetUserId)
    {
        try {
            $this->validateAdminAccess();

            // Prevent self-deletion
            if ($targetUserId === $this->adminId) {
                return ['success' => false, 'message' => 'Cannot delete your own account'];
            }

            // Check user exists
            $user = $this->db->queryOne("SELECT id FROM users WHERE id = ?", [$targetUserId]);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Soft delete via deactivation
            $sql = "UPDATE users SET is_active = FALSE, updated_at = NOW() WHERE id = ?";
            $this->db->execute($sql, [$targetUserId]);

            // Log admin action
            $this->logAdminAction('user_delete', $targetUserId, "Deleted user ID: $targetUserId");

            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()];
        }
    }

    /**
     * Reset user password
     * Returns a temporary password or reset token
     */
    public function resetPassword($targetUserId)
    {
        try {
            $this->validateAdminAccess();

            // Check user exists
            $user = $this->db->queryOne("SELECT id, email FROM users WHERE id = ?", [$targetUserId]);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Generate temporary password
            $tempPassword = $this->generateTemporaryPassword();
            $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update user password
            $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $this->db->execute($sql, [$passwordHash, $targetUserId]);

            // Log admin action
            $this->logAdminAction('password_reset', $targetUserId, "Reset password for user ID: $targetUserId");

            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'temp_password' => $tempPassword,
                'user_email' => $user['email']
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle user account status (active/inactive)
     */
    public function toggleUserStatus($targetUserId)
    {
        try {
            $this->validateAdminAccess();

            // Prevent self-deactivation
            if ($targetUserId === $this->adminId) {
                return ['success' => false, 'message' => 'Cannot deactivate your own account'];
            }

            // Get current status
            $user = $this->db->queryOne("SELECT id, is_active FROM users WHERE id = ?", [$targetUserId]);
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            // Toggle status
            $newStatus = $user['is_active'] ? 0 : 1;
            $sql = "UPDATE users SET is_active = ? WHERE id = ?";
            $this->db->execute($sql, [$newStatus, $targetUserId]);

            // Log admin action
            $this->logAdminAction('status_toggle', $targetUserId, "Toggled user status for ID: $targetUserId to $newStatus");

            $statusText = $newStatus ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => "User $statusText successfully"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Status toggle failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get user login history
     */
    public function getUserLoginHistory($targetUserId, $limit = 50)
    {
        try {
            $this->validateAdminAccess();

            // For now, use the last_login_at field
            $sql = "SELECT id, username, email, last_login_at, locked_until FROM users WHERE id = ?";
            $user = $this->db->queryOne($sql, [$targetUserId]);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            return [
                'success' => true,
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'last_login_at' => $user['last_login_at'],
                'locked_until' => $user['locked_until']
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unlock user account (clear failed login attempts)
     */
    public function unlockUserAccount($targetUserId)
    {
        try {
            $this->validateAdminAccess();

            $sql = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?";
            $this->db->execute($sql, [$targetUserId]);

            // Log admin action
            $this->logAdminAction('account_unlock', $targetUserId, "Unlocked user account ID: $targetUserId");

            return ['success' => true, 'message' => 'User account unlocked successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Unlock failed: ' . $e->getMessage()];
        }
    }

    /**
     * Generate a secure temporary password
     */
    private function generateTemporaryPassword($length = 12)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * Log admin action for audit trail
     */
    private function logAdminAction($eventType, $targetUserId, $description)
    {
        try {
            $sql = "INSERT INTO audit_logs (event_type, user_id, action_description, affected_resource, affected_resource_id, ip_address, success)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            $this->db->execute($sql, [
                'admin_' . $eventType,
                $this->adminId,
                $description,
                'users',
                $targetUserId,
                $ipAddress,
                true
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't break functionality if audit logging fails
        }
    }
}
