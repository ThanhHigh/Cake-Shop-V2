<?php
/**
 * Admin Review Moderation Page
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

use CakeShop\Services\ReviewService;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'customer';

if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // Vulnerable mode intentionally allows access.
} else {
    if (!$isLoggedIn || $userRole !== 'admin') {
        header('Location: /pages/login.php?redirect=/pages/admin/reviews.php&error=unauthorized');
        exit;
    }
}

$reviewService = new ReviewService($config);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $action = trim((string)($_POST['action'] ?? ''));

    if ($reviewId <= 0 || ($action !== 'approve' && $action !== 'reject')) {
        $error = 'Invalid review action.';
    } else {
        if ($action === 'approve') {
            $result = $reviewService->approveReview($reviewId);
        } else {
            $result = $reviewService->rejectReview($reviewId);
        }

        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$pendingReviews = $reviewService->getPendingReviews(200);
$allReviews = $reviewService->getAllReviewsForAdmin(300);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Reviews - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        h1 {
            color: #2d5016;
            font-size: 28px;
            font-weight: 300;
        }

        .links a {
            color: #2d5016;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-left: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 14px;
            font-size: 13px;
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

        .card {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background-color: #2d5016;
            color: #fff;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .table-wrap {
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background-color: #f4f7f2;
            color: #2d5016;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #e4eadf;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .stars {
            color: #d45113;
            font-size: 12px;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .comment {
            max-width: 340px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            font-size: 11px;
            padding: 3px 9px;
            font-weight: 600;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .action-form {
            display: inline;
        }

        .btn {
            border: none;
            border-radius: 3px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-approve {
            background-color: #2d5016;
            color: #fff;
            margin-right: 6px;
        }

        .btn-approve:hover {
            background-color: #3d6b1f;
        }

        .btn-reject {
            background-color: #b42318;
            color: #fff;
        }

        .btn-reject:hover {
            background-color: #8f1b13;
        }

        .empty {
            padding: 24px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-comments"></i> Admin Review Moderation</h1>
        <div class="links">
            <a href="/pages/admin/orders.php"><i class="fas fa-receipt"></i> Orders</a>
            <a href="/pages/admin/products.php"><i class="fas fa-boxes"></i> Products</a>
            <a href="/pages/admin/users.php"><i class="fas fa-users-cog"></i> Users</a>
            <a href="/catalog"><i class="fas fa-store"></i> Catalog</a>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Pending Reviews (Awaiting Approval)</div>
        <div class="table-wrap">
            <?php if (empty($pendingReviews)): ?>
            <div class="empty">No pending reviews right now.</div>
            <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Author</th>
                    <th>Rating</th>
                    <th>Title</th>
                    <th>Comment</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingReviews as $review): ?>
                    <tr>
                        <td>#<?php echo (int)$review['id']; ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($review['product_name']); ?></div>
                            <small>PID: <?php echo (int)$review['product_id']; ?></small>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($review['author_name'] ?? 'Unknown'); ?></div>
                            <small><?php echo htmlspecialchars($review['author_email'] ?? ''); ?></small>
                        </td>
                        <td class="stars"><?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', max(0, 5 - (int)$review['rating'])); ?></td>
                        <td><?php echo htmlspecialchars($review['title']); ?></td>
                        <td class="comment"><?php echo htmlspecialchars($review['comment']); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($review['created_at']))); ?></td>
                        <td>
                            <form class="action-form" method="POST" action="">
                                <input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn btn-approve" type="submit"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form class="action-form" method="POST" action="" onsubmit="return confirm('Reject and delete this review?');">
                                <input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-reject" type="submit"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent Reviews (All Statuses)</div>
        <div class="table-wrap">
            <?php if (empty($allReviews)): ?>
            <div class="empty">No reviews found.</div>
            <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Author</th>
                    <th>Rating</th>
                    <th>Title</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($allReviews as $review): ?>
                    <tr>
                        <td>#<?php echo (int)$review['id']; ?></td>
                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['author_name'] ?? 'Unknown'); ?></td>
                        <td class="stars"><?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', max(0, 5 - (int)$review['rating'])); ?></td>
                        <td><?php echo htmlspecialchars($review['title']); ?></td>
                        <td class="comment"><?php echo htmlspecialchars($review['comment']); ?></td>
                        <td>
                            <?php if ((int)$review['is_approved'] === 1): ?>
                                <span class="status status-approved">Approved</span>
                            <?php else: ?>
                                <span class="status status-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($review['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
