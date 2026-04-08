<?php
/**
 * Authentication Service
 * Handles user login, registration, and session management
 */

namespace CakeShop\Services;

class AuthService
{
    private $db;
    private $config;
    private $supportsUsername = null;

    public function __construct($config)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
    }

    private function ensureSessionStarted()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function supportsUsernameColumn()
    {
        if ($this->supportsUsername !== null) {
            return $this->supportsUsername;
        }

        try {
            $sql = "SELECT COUNT(*) as count
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = ?
                      AND TABLE_NAME = 'users'
                      AND COLUMN_NAME = 'username'";
            $result = $this->db->queryOne($sql, [$this->config['db']['name']]);
            $this->supportsUsername = ((int)($result['count'] ?? 0)) > 0;
        } catch (\Exception $e) {
            $this->supportsUsername = false;
        }

        return $this->supportsUsername;
    }

    /**
     * Register a new user
     * VULNERABLE: Weak password hashing
     * SECURE: Strong password hashing with bcrypt
     */
    public function register($username, $email, $password, $role = 'customer')
    {
        try {
            $username = trim((string)$username);
            $email = trim((string)$email);

            if ($username === '' || $email === '' || $password === '') {
                return ['success' => false, 'message' => 'Username, email and password are required'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Check if username or email already exists
            if ($this->supportsUsernameColumn()) {
                $sql = "SELECT id, username, email FROM users WHERE email = ? OR username = ?";
                $existingUser = $this->db->queryOne($sql, [$email, $username]);
            } else {
                $sql = "SELECT id, full_name AS username, email FROM users WHERE email = ?";
                $existingUser = $this->db->queryOne($sql, [$email]);
            }
            
            if ($existingUser) {
                if ($existingUser['email'] === $email) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }

                return ['success' => false, 'message' => 'Username already taken'];
            }

            // Hash password based on mode
            if ($this->config['app_mode'] === 'vulnerable') {
                // VULNERABLE: Weak hashing
                $passwordHash = md5($password);
            } else {
                // SECURE: Strong bcrypt hashing
                $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            // Insert new user
                if ($this->supportsUsernameColumn()) {
                $sql = "INSERT INTO users (username, email, password_hash, full_name, role)
                    VALUES (?, ?, ?, ?, ?)";
                $this->db->execute($sql, [$username, $email, $passwordHash, $username, $role]);
                } else {
                $sql = "INSERT INTO users (email, password_hash, full_name, role)
                    VALUES (?, ?, ?, ?)";
                $this->db->execute($sql, [$email, $passwordHash, $username, $role]);
                }
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Authenticate user login
     * VULNERABLE: No rate limiting, timing attack vulnerable
     * SECURE: Rate limiting and constant-time comparison
     */
    public function login($email, $password)
    {
        try {
            $this->ensureSessionStarted();

            $email = trim((string)$email);

                if ($this->supportsUsernameColumn()) {
                $sql = "SELECT id, username, email, password_hash, role, failed_login_attempts, locked_until
                    FROM users WHERE email = ? AND is_active = TRUE";
                } else {
                $sql = "SELECT id, full_name AS username, email, password_hash, role, failed_login_attempts, locked_until
                    FROM users WHERE email = ? AND is_active = TRUE";
                }
            $user = $this->db->queryOne($sql, [$email]);

            if (!$user) {
                // VULNERABLE: Generic error message leak
                if ($this->config['app_mode'] === 'vulnerable') {
                    return ['success' => false, 'message' => 'Invalid email or password'];
                }
                // SECURE: Generic error
                return ['success' => false, 'message' => 'Invalid credentials'];

            }

            // Check account lockout
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return ['success' => false, 'message' => 'Account locked. Please try again later.'];
            }

            // Verify password
            if ($this->config['app_mode'] === 'vulnerable') {
                // VULNERABLE: Simple string comparison (timing attack)
                $passwordMatch = md5($password) === $user['password_hash'] || $password === $user['password_hash'];
            } else {
                // SECURE: Constant-time comparison
                $passwordMatch = password_verify($password, $user['password_hash']);
            }

            if (!$passwordMatch) {
                // Increment failed login attempts
                $failedAttempts = $user['failed_login_attempts'] + 1;
                $maxAttempts = (int)($this->config['security']['max_login_attempts'] ?? 5);
                $lockDuration = (int)($this->config['security']['lockout_duration'] ?? 900);
                $lockUntil = $failedAttempts >= $maxAttempts ? date('Y-m-d H:i:s', time() + $lockDuration) : null;
                
                $updateSql = "UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?";
                $this->db->execute($updateSql, [$failedAttempts, $lockUntil, $user['id']]);
                
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Reset failed attempts
            $updateSql = "UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?";
            $this->db->execute($updateSql, [$user['id']]);

            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['username'];

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->ensureSessionStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        return true;
    }

    /**
     * Get current logged-in user
     */
    public function getCurrentUser()
    {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        if ($this->supportsUsernameColumn()) {
            $sql = "SELECT id, username, email, full_name, role FROM users WHERE id = ? AND is_active = TRUE";
        } else {
            $sql = "SELECT id, full_name AS username, email, full_name, role FROM users WHERE id = ? AND is_active = TRUE";
        }
        return $this->db->queryOne($sql, [$_SESSION['user_id']]);
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        $this->ensureSessionStarted();
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        $this->ensureSessionStarted();
        return isset($_SESSION['user_id']);
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data)
    {
        try {
            $allowedFields = ['full_name', 'email'];
            if ($this->supportsUsernameColumn()) {
                $allowedFields[] = 'username';
            }
            $updates = [];
            $params = [];

            if (isset($data['email'])) {
                $data['email'] = trim((string)$data['email']);

                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'message' => 'Invalid email format'];
                }

                $existingByEmail = $this->db->queryOne("SELECT id FROM users WHERE email = ? AND id <> ?", [$data['email'], $userId]);
                if ($existingByEmail) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }
            }

            if ($this->supportsUsernameColumn() && isset($data['username'])) {
                $data['username'] = trim((string)$data['username']);

                if ($data['username'] === '') {
                    return ['success' => false, 'message' => 'Username is required'];
                }

                $existingByUsername = $this->db->queryOne("SELECT id FROM users WHERE username = ? AND id <> ?", [$data['username'], $userId]);
                if ($existingByUsername) {
                    return ['success' => false, 'message' => 'Username already taken'];
                }
            }

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $this->db->execute($sql, $params);

            $this->ensureSessionStarted();
            if (isset($data['email'])) {
                $_SESSION['user_email'] = $data['email'];
            }
            if (isset($data['username']) && $this->supportsUsernameColumn()) {
                $_SESSION['user_name'] = $data['username'];
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updatePassword($userId, $currentPassword, $newPassword)
    {
        try {
            if ($newPassword === '') {
                return ['success' => false, 'message' => 'New password is required'];
            }

            $sql = "SELECT password_hash FROM users WHERE id = ?";
            $user = $this->db->queryOne($sql, [$userId]);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if ($this->config['app_mode'] === 'vulnerable') {
                $currentPasswordValid = md5($currentPassword) === $user['password_hash'] || $currentPassword === $user['password_hash'];
                $newPasswordHash = md5($newPassword);
            } else {
                $currentPasswordValid = password_verify($currentPassword, $user['password_hash']);
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            if (!$currentPasswordValid) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            $updateSql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $this->db->execute($updateSql, [$newPasswordHash, $userId]);

            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAllUsers($limit = 200)
    {
        if ($this->supportsUsernameColumn()) {
            $sql = "SELECT id, username, email, full_name, role, is_active, created_at, updated_at
                FROM users
                ORDER BY created_at DESC
                LIMIT ?";
        } else {
            $sql = "SELECT id, full_name AS username, email, full_name, role, is_active, created_at, updated_at
                FROM users
                ORDER BY created_at DESC
                LIMIT ?";
        }

        return $this->db->queryAll($sql, [max(1, (int)$limit)]);
    }

    public function adminUpdateUser($targetUserId, $data)
    {
        try {
            $updates = [];
            $params = [];

            if (isset($data['username']) && $this->supportsUsernameColumn()) {
                $username = trim((string)$data['username']);
                if ($username === '') {
                    return ['success' => false, 'message' => 'Username is required'];
                }

                $existingByUsername = $this->db->queryOne("SELECT id FROM users WHERE username = ? AND id <> ?", [$username, $targetUserId]);
                if ($existingByUsername) {
                    return ['success' => false, 'message' => 'Username already taken'];
                }

                $updates[] = 'username = ?';
                $params[] = $username;
            }

            if (isset($data['email'])) {
                $email = trim((string)$data['email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'message' => 'Invalid email format'];
                }

                $existingByEmail = $this->db->queryOne("SELECT id FROM users WHERE email = ? AND id <> ?", [$email, $targetUserId]);
                if ($existingByEmail) {
                    return ['success' => false, 'message' => 'Email already registered'];
                }

                $updates[] = 'email = ?';
                $params[] = $email;
            }

            if (isset($data['full_name'])) {
                $updates[] = 'full_name = ?';
                $params[] = trim((string)$data['full_name']);
            }

            if (isset($data['role'])) {
                if (!in_array($data['role'], ['customer', 'admin'], true)) {
                    return ['success' => false, 'message' => 'Invalid role'];
                }

                $updates[] = 'role = ?';
                $params[] = $data['role'];
            }

            if (isset($data['is_active'])) {
                $updates[] = 'is_active = ?';
                $params[] = (int)(bool)$data['is_active'];
            }

            if (empty($updates)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $params[] = $targetUserId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->execute($sql, $params);

            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
