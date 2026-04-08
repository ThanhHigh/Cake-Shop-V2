<?php
/**
 * Admin User Management Page
 * Comprehensive user management with CRUD operations, role assignment, and account control
 */

if (!function_exists('__autoload')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

if (!isset($config)) {
    $config = require_once dirname(__DIR__, 3) . '/config/config.php';
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use CakeShop\Services\AdminUserService;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'customer';

// Access control check
if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // Vulnerable mode: intentionally allows access
} else {
    if (!$isLoggedIn || $userRole !== 'admin') {
        header('Location: /pages/login.php?redirect=/pages/admin/users.php&error=unauthorized');
        exit;
    }
}

// Initialize service
$adminUserService = new AdminUserService($config, $_SESSION['user_id'] ?? null);
$message = '';
$error = '';
$tempPassword = '';
$resetEmail = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    switch ($action) {
        case 'update':
            $updateData = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'full_name' => trim($_POST['full_name'] ?? ''),
                'role' => $_POST['role'] ?? 'customer',
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ];
            $result = $adminUserService->updateUser($targetUserId, $updateData);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;

        case 'delete':
            $result = $adminUserService->deleteUser($targetUserId);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;

        case 'reset_password':
            $result = $adminUserService->resetPassword($targetUserId);
            if ($result['success']) {
                $message = $result['message'];
                $tempPassword = $result['temp_password'] ?? '';
                $resetEmail = $result['user_email'] ?? '';
            } else {
                $error = $result['message'];
            }
            break;

        case 'toggle_status':
            $result = $adminUserService->toggleUserStatus($targetUserId);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;

        case 'unlock':
            $result = $adminUserService->unlockUserAccount($targetUserId);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
            break;
    }
}

// Get pagination and filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;
$searchTerm = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build filters array
$filters = [];
if (!empty($searchTerm)) {
    $filters['search'] = $searchTerm;
}
if (!empty($roleFilter)) {
    $filters['role'] = $roleFilter;
}
if ($statusFilter !== '') {
    $filters['is_active'] = (int)$statusFilter;
}

// Fetch users list
try {
    $result = $adminUserService->getAllUsers($limit, $offset, $filters);
    $users = $result['users'] ?? [];
    $totalUsers = $adminUserService->getUserCount($filters);
    $totalPages = ceil($totalUsers / $limit);
} catch (\Exception $e) {
    $error = 'Failed to fetch users: ' . $e->getMessage();
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: User Management - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f2e8;
            color: #333;
            font-family: "UTMAvo", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        h1 {
            color: #2d5016;
            font-size: 28px;
            font-weight: 300;
        }

        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .link {
            color: #2d5016;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Filters section */
        .filters-section {
            background-color: #fff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filters-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #666;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 12px;
            font-family: inherit;
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #2d5016;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #3d6b1f;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
            font-size: 11px;
            padding: 6px 10px;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
            font-size: 11px;
            padding: 6px 10px;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-info {
            background-color: #17a2b8;
            color: #fff;
            font-size: 11px;
            padding: 6px 10px;
        }

        .btn-info:hover {
            background-color: #138496;
        }

        /* Table section */
        .table-wrap {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        thead th {
            background-color: #2d5016;
            color: #fff;
            text-align: left;
            padding: 12px;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            border-bottom: 2px solid #1a3009;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        td {
            padding: 10px 12px;
            vertical-align: middle;
            font-size: 13px;
        }

        .editable-cell {
            position: relative;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 2px;
            font-size: 12px;
            font-family: inherit;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-badge {
            display: inline-block;
            border-radius: 20px;
            font-size: 11px;
            padding: 4px 10px;
            font-weight: 600;
            text-align: center;
            min-width: 60px;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .locked-badge {
            background-color: #fff3cd;
            color: #856404;
        }

        .role-badge {
            display: inline-block;
            border-radius: 3px;
            font-size: 11px;
            padding: 3px 8px;
            font-weight: 600;
        }

        .role-admin {
            background-color: #dc3545;
            color: #fff;
        }

        .role-customer {
            background-color: #28a745;
            color: #fff;
        }

        .actions {
            white-space: nowrap;
            display: flex;
            gap: 5px;
        }

        .actions form {
            display: inline;
        }

        /* Pagination */
        .pagination-wrap {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination-wrap a,
        .pagination-wrap span {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            color: #2d5016;
        }

        .pagination-wrap a:hover {
            background-color: #f0f0f0;
        }

        .pagination-wrap .active {
            background-color: #2d5016;
            color: #fff;
            border-color: #2d5016;
        }

        .pagination-wrap .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .user-info-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
        }

        .no-users {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 1024px) {
            .filters-row {
                grid-template-columns: 1fr 1fr;
            }
            table {
                min-width: 900px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-users-cog"></i> User Management</h1>
        <div class="nav-links">
            <a class="link" href="/pages/admin/orders.php"><i class="fas fa-receipt"></i> Orders</a>
            <a class="link" href="/pages/admin/products.php"><i class="fas fa-boxes"></i> Products</a>
            <a class="link" href="/pages/admin/reviews.php"><i class="fas fa-comments"></i> Reviews</a>
            <a class="link" href="/catalog"><i class="fas fa-store"></i> Catalog</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php echo htmlspecialchars($message); ?>
                <?php if ($tempPassword): ?>
                    <div style="margin-top: 8px; font-weight: 600;">
                        Temporary Password: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 2px;"><?php echo htmlspecialchars($tempPassword); ?></code>
                        (Email: <?php echo htmlspecialchars($resetEmail); ?>)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filters Section -->
    <div class="filters-section">
        <form method="GET" action="">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="search">Search (Name/Email/Username)</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search users...">
                </div>
                <div class="filter-group">
                    <label for="role">Filter by Role</label>
                    <select id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status">Filter by Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                    <a href="?page=1" class="btn btn-secondary" style="text-decoration: none; text-align: center; flex: 1;">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <?php if (!empty($users)): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 120px;">Username</th>
                    <th style="width: 150px;">Email</th>
                    <th style="width: 150px;">Full Name</th>
                    <th style="width: 80px;">Role</th>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 100px;">Last Login</th>
                    <th style="width: 250px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): 
                    $isLocked = !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
                    $lastLogin = $user['last_login_at'] ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : 'Never';
                ?>
                <tr>
                    <td>#<?php echo (int)$user['id']; ?></td>
                    <td>
                        <form method="POST" action="" style="display: none;" id="form-<?php echo (int)$user['id']; ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                            <input type="hidden" name="is_active" value="<?php echo (int)$user['is_active']; ?>">
                        </form>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($isLocked): ?>
                            <span class="status-badge locked-badge">
                                <i class="fas fa-lock"></i> Locked
                            </span>
                        <?php else: ?>
                            <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $lastLogin; ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-info" onclick="editUser(<?php echo (int)$user['id']; ?>)" title="Edit user">
                                <i class="fas fa-edit"></i>
                            </button>

                            <?php if ($isLocked): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Unlock this account?');">
                                    <input type="hidden" name="action" value="unlock">
                                    <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>">
                                    <button type="submit" class="btn btn-warning" title="Unlock">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Reset password for this user?');">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" class="btn btn-warning" title="Reset password">
                                    <i class="fas fa-key"></i>
                                </button>
                            </form>

                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Toggle status for this user?');">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" class="btn btn-info" title="Toggle status">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>

                            <?php if ((int)$user['id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="target_user_id" value="<?php echo (int)$user['id']; ?>">
                                    <button type="submit" class="btn btn-danger" title="Delete user">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrap">
        <?php if ($page > 1): ?>
            <a href="?page=1<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?>">
                <i class="fas fa-chevron-left"></i> First
            </a>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?>">
                Prev
            </a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?>">
                Next
            </a>
            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : ''; ?>">
                Last <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #999;">
        Page <?php echo $page; ?> of <?php echo $totalPages; ?> | Total: <?php echo $totalUsers; ?> user(s)
    </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="no-users">
            <i class="fas fa-users" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
            <p>No users found matching your filters.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function editUser(userId) {
    alert('Edit modal would open here for user #' + userId + '. This is a placeholder for future enhancement.');
    // In a full implementation, this would open a modal with edit form
}
</script>
</body>
</html>
