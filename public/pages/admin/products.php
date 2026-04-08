<?php
/**
 * Admin Product Management Page
 * Full CRUD with soft delete (is_active = 0).
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

use CakeShop\Services\ProductService;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'customer';

if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // Vulnerable mode intentionally allows access.
} else {
    if (!$isLoggedIn || $userRole !== 'admin') {
        header('Location: /pages/login.php?redirect=/pages/admin/products.php&error=unauthorized');
        exit;
    }
}

$productService = new ProductService($config);
$categories = $productService->getAllCategories();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'create') {
        $result = $productService->adminCreateProduct($_POST);
    } elseif ($action === 'update') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $result = $productService->adminUpdateProduct($productId, $_POST);
    } elseif ($action === 'delete') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $result = $productService->adminSoftDeleteProduct($productId);
    } else {
        $result = ['success' => false, 'message' => 'Invalid action.'];
    }

    if (!empty($result['success'])) {
        $message = $result['message'] ?? 'Done.';
    } else {
        $error = $result['message'] ?? 'Operation failed.';
    }
}

$products = $productService->getAllProductsForAdmin(500, 0, true);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Products - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            margin-left: 12px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 13px;
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

        .card-body {
            padding: 15px;
        }

        .create-grid {
            display: grid;
            grid-template-columns: 1fr 140px 140px 140px 1fr auto;
            gap: 10px;
            align-items: end;
        }

        label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 13px;
        }

        textarea {
            resize: vertical;
            min-height: 68px;
        }

        .table-wrap {
            overflow: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
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

        .btn {
            border: none;
            border-radius: 3px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-create,
        .btn-update {
            background-color: #2d5016;
            color: #fff;
        }

        .btn-create:hover,
        .btn-update:hover {
            background-color: #3d6b1f;
        }

        .btn-delete {
            background-color: #b42318;
            color: #fff;
            margin-top: 6px;
        }

        .btn-delete:hover {
            background-color: #8f1b13;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            font-size: 11px;
            padding: 3px 9px;
            font-weight: 600;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .row-inactive {
            opacity: 0.75;
            background-color: #fff7f7;
        }

        .small {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            display: block;
        }

        .check-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #444;
        }

        @media (max-width: 920px) {
            .create-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-boxes"></i> Admin Product Management</h1>
        <div class="links">
            <a href="/pages/admin/orders.php"><i class="fas fa-receipt"></i> Orders</a>
            <a href="/pages/admin/users.php"><i class="fas fa-users-cog"></i> Users</a>
            <a href="/pages/admin/reviews.php"><i class="fas fa-comments"></i> Reviews</a>
            <a href="/catalog"><i class="fas fa-store"></i> Catalog</a>
        </div>
    </div>

    <?php if (function_exists('isVulnerable') && isVulnerable('access_control')): ?>
    <div class="notice">
        <strong>VULNERABILITY A01 ACTIVE:</strong> access control checks are intentionally relaxed in vulnerable mode.
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Create Product</div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="create-grid">
                    <div>
                        <label for="create_name">Name</label>
                        <input id="create_name" type="text" name="name" required maxlength="255">
                    </div>

                    <div>
                        <label for="create_category">Category</label>
                        <select id="create_category" name="category_id" required>
                            <option value="">Select</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="create_price">Price ($)</label>
                        <input id="create_price" type="number" step="0.01" min="0" name="price" required>
                    </div>

                    <div>
                        <label for="create_stock">Stock</label>
                        <input id="create_stock" type="number" min="0" name="stock_quantity" value="0" required>
                    </div>

                    <div>
                        <label for="create_image_url">Image URL</label>
                        <input id="create_image_url" type="text" name="image_url" maxlength="255" placeholder="https://... or /uploads/...">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-create"><i class="fas fa-plus"></i> Create</button>
                        <span class="small">Soft delete available below.</span>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <label for="create_description">Description</label>
                    <textarea id="create_description" name="description" placeholder="Product description"></textarea>
                </div>

                <div style="margin-top: 10px;">
                    <label class="check-inline">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active
                    </label>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Products (Active + Inactive)</div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Image URL</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr class="<?php echo (int)$product['is_active'] === 1 ? '' : 'row-inactive'; ?>">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <td>#<?php echo (int)$product['id']; ?></td>
                            <td>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" maxlength="255" required>
                            </td>
                            <td>
                                <select name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>" <?php echo (int)$category['id'] === (int)$product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars((string)$product['price']); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="stock_quantity" min="0" value="<?php echo (int)$product['stock_quantity']; ?>" required>
                            </td>
                            <td>
                                <input type="text" name="image_url" maxlength="255" value="<?php echo htmlspecialchars((string)($product['image_url'] ?? '')); ?>">
                            </td>
                            <td>
                                <textarea name="description"><?php echo htmlspecialchars((string)($product['description'] ?? '')); ?></textarea>
                            </td>
                            <td>
                                <label class="check-inline">
                                    <input type="checkbox" name="is_active" value="1" <?php echo (int)$product['is_active'] === 1 ? 'checked' : ''; ?>>
                                    Active
                                </label>
                                <div style="margin-top: 6px;">
                                    <?php if ((int)$product['is_active'] === 1): ?>
                                        <span class="status status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status status-inactive">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button type="submit" class="btn btn-update"><i class="fas fa-save"></i> Update</button>
                        </form>
                        <?php if ((int)$product['is_active'] === 1): ?>
                            <form method="POST" action="" onsubmit="return confirm('Soft delete this product?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                <button type="submit" class="btn btn-delete"><i class="fas fa-trash"></i> Soft Delete</button>
                            </form>
                        <?php endif; ?>
                            </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
