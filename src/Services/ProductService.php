<?php
/**
 * Product Service
 * Handles product and category operations with support for vulnerable and secure modes
 */

namespace CakeShop\Services;

class ProductService
{
    private $db;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
    }

    /**
     * Get all active categories
     * VULNERABLE: No input validation on filtering
     * SECURE: Proper prepared statements and validation
     */
    public function getAllCategories()
    {
        $sql = "SELECT id, name, description, image_url FROM categories 
                WHERE is_active = TRUE 
                ORDER BY display_order ASC, name ASC";
        
        return $this->db->queryAll($sql);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($categoryId)
    {
        $sql = "SELECT id, name, description, image_url FROM categories 
                WHERE id = ? AND is_active = TRUE";
        
        return $this->db->queryOne($sql, [$categoryId]);
    }

    /**
     * Get all active products with optional filtering
     * VULNERABLE: Susceptible to SQL injection via search parameter
     * SECURE: Uses prepared statements and input validation
     */
    public function getAllProducts($limit = 50, $offset = 0)
    {
        // Ensure limit and offset are integers (required for LIMIT/OFFSET)
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                       p.stock_quantity, c.id as category_id, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = TRUE
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
        
        return $this->db->queryAll($sql, []);
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId, $limit = 50, $offset = 0)
    {
        // Ensure parameters are integers
        $categoryId = (int)$categoryId;
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                       p.stock_quantity, c.id as category_id, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = ? AND p.is_active = TRUE
                ORDER BY p.name ASC
                LIMIT $limit OFFSET $offset";
        
        return $this->db->queryAll($sql, [$categoryId]);
    }

    /**
     * Search products by name or description
     * VULNERABLE MODE: Raw SQL concatenation allows SQLi
     * SECURE MODE: Prepared statement with validation
     */
    public function searchProducts($searchTerm, $limit = 50, $offset = 0)
    {
        if ($this->config['app_mode'] === 'vulnerable') {
            // VULNERABLE: Concatenation allows SQL injection
            $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                           p.stock_quantity, c.id as category_id, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_active = TRUE 
                    AND (p.name LIKE '%{$searchTerm}%' OR p.description LIKE '%{$searchTerm}%') ORDER BY p.name ASC LIMIT {$limit} OFFSET {$offset}";
            
            return $this->db->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            // SECURE: Prepared statement with bound parameters
            $searchPatern = "%{$searchTerm}%";
            $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                           p.stock_quantity, c.id as category_id, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.is_active = TRUE 
                    AND (p.name LIKE ? OR p.description LIKE ?)
                    ORDER BY p.name ASC
                    LIMIT ? OFFSET ?";
            
            return $this->db->queryAll($sql, [$searchPatern, $searchPatern, $limit, $offset]);
        }
    }

    /**
     * Get product by ID
     */
    public function getProductById($productId)
    {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                       p.stock_quantity, c.id as category_id, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.is_active = TRUE";
        
        return $this->db->queryOne($sql, [$productId]);
    }

    /**
     * Get featured products (for homepage banner)
     */
    public function getFeaturedProducts($limit = 8)
    {
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, 
                       p.stock_quantity, c.id as category_id, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = TRUE
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->queryAll($sql, [$limit]);
    }

    /**
     * Get product count
     */
    public function getProductCount($categoryId = null)
    {
        if ($categoryId) {
            $sql = "SELECT COUNT(*) as count FROM products 
                    WHERE category_id = ? AND is_active = TRUE";
            $result = $this->db->queryOne($sql, [$categoryId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM products WHERE is_active = TRUE";
            $result = $this->db->queryOne($sql);
        }
        
        return $result['count'] ?? 0;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock($productId)
    {
        $sql = "SELECT stock_quantity FROM products WHERE id = ?";
        $result = $this->db->queryOne($sql, [$productId]);
        
        return $result && $result['stock_quantity'] > 0;
    }

    /**
     * Admin: Get products including inactive items for management.
     */
    public function getAllProductsForAdmin($limit = 300, $offset = 0, $includeInactive = true)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        $sql = "SELECT p.id, p.category_id, p.name, p.description, p.price, p.image_url,
                       p.stock_quantity, p.is_active, p.created_at, p.updated_at,
                       c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id";

        if (!$includeInactive) {
            $sql .= " WHERE p.is_active = TRUE";
        }

        $sql .= " ORDER BY p.updated_at DESC, p.id DESC LIMIT $limit OFFSET $offset";

        return $this->db->queryAll($sql);
    }

    /**
     * Admin: Create a product.
     */
    public function adminCreateProduct($data)
    {
        $validation = $this->validateAdminProductPayload($data, false);
        if (!$validation['success']) {
            return $validation;
        }

        $clean = $validation['data'];

        try {
            $sql = "INSERT INTO products (category_id, name, description, price, image_url, stock_quantity, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $clean['category_id'],
                $clean['name'],
                $clean['description'],
                $clean['price'],
                $clean['image_url'],
                $clean['stock_quantity'],
                $clean['is_active']
            ]);

            return [
                'success' => true,
                'message' => 'Product created successfully.',
                'product_id' => (int)$this->db->lastInsertId(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Admin: Update a product.
     */
    public function adminUpdateProduct($productId, $data)
    {
        $productId = (int)$productId;
        if ($productId <= 0 || !$this->adminProductExists($productId)) {
            return [
                'success' => false,
                'message' => 'Product not found.',
            ];
        }

        $validation = $this->validateAdminProductPayload($data, true);
        if (!$validation['success']) {
            return $validation;
        }

        $clean = $validation['data'];

        try {
            $sql = "UPDATE products
                    SET category_id = ?,
                        name = ?,
                        description = ?,
                        price = ?,
                        image_url = ?,
                        stock_quantity = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?";

            $this->db->execute($sql, [
                $clean['category_id'],
                $clean['name'],
                $clean['description'],
                $clean['price'],
                $clean['image_url'],
                $clean['stock_quantity'],
                $clean['is_active'],
                $productId,
            ]);

            return [
                'success' => true,
                'message' => 'Product updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Admin: Soft delete product by deactivating it.
     */
    public function adminSoftDeleteProduct($productId)
    {
        $productId = (int)$productId;
        if ($productId <= 0 || !$this->adminProductExists($productId)) {
            return [
                'success' => false,
                'message' => 'Product not found.',
            ];
        }

        try {
            $sql = "UPDATE products SET is_active = FALSE, updated_at = NOW() WHERE id = ?";
            $this->db->execute($sql, [$productId]);

            return [
                'success' => true,
                'message' => 'Product deleted (deactivated) successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage(),
            ];
        }
    }

    private function validateAdminProductPayload($data, $isUpdate)
    {
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $imageUrl = trim((string)($data['image_url'] ?? ''));
        $categoryId = (int)($data['category_id'] ?? 0);
        $price = $data['price'] ?? null;
        $stockQuantity = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
        $isActive = isset($data['is_active']) && (int)$data['is_active'] === 0 ? 0 : 1;

        if ($categoryId <= 0 || !$this->adminCategoryExists($categoryId)) {
            return [
                'success' => false,
                'message' => 'Invalid category selected.',
            ];
        }

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Product name is required.',
            ];
        }

        if (mb_strlen($name) > 255) {
            return [
                'success' => false,
                'message' => 'Product name is too long.',
            ];
        }

        if (!is_numeric($price) || (float)$price < 0) {
            return [
                'success' => false,
                'message' => 'Price must be a valid non-negative number.',
            ];
        }

        if ($stockQuantity < 0) {
            return [
                'success' => false,
                'message' => 'Stock quantity must be a non-negative integer.',
            ];
        }

        if ($imageUrl !== '' && mb_strlen($imageUrl) > 255) {
            return [
                'success' => false,
                'message' => 'Image URL is too long.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'price' => (float)$price,
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'stock_quantity' => $stockQuantity,
                'is_active' => $isActive,
            ],
            'mode' => $isUpdate ? 'update' : 'create',
        ];
    }

    private function adminCategoryExists($categoryId)
    {
        $sql = "SELECT id FROM categories WHERE id = ? LIMIT 1";
        $result = $this->db->queryOne($sql, [(int)$categoryId]);
        return !empty($result);
    }

    private function adminProductExists($productId)
    {
        $sql = "SELECT id FROM products WHERE id = ? LIMIT 1";
        $result = $this->db->queryOne($sql, [(int)$productId]);
        return !empty($result);
    }
}
