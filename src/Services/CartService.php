<?php
/**
 * Cart Service
 * Handles shopping cart operations for customers
 */

namespace CakeShop\Services;

class CartService
{
    private $db;
    private $config;
    private $userId;

    public function __construct($config, $userId = null)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
        $this->userId = $userId;
    }

    /**
     * Add item to cart
     */
    public function addToCart($productId, $quantity = 1)
    {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        try {
            // Check if product exists and is in stock
            $sql = "SELECT id, stock_quantity FROM products WHERE id = ? AND is_active = TRUE";
            $product = $this->db->queryOne($sql, [$productId]);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            if ($product['stock_quantity'] < $quantity) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }

            // Insert or update cart item
            $sql = "INSERT INTO cart_items (user_id, product_id, quantity) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            
            $this->db->execute($sql, [$this->userId, $productId, $quantity]);
            
            return ['success' => true, 'message' => 'Product added to cart'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get cart items
     */
    public function getCartItems()
    {
        if (!$this->userId) {
            return [];
        }

        $sql = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image_url, p.stock_quantity
                FROM cart_items c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.added_at DESC";
        
        return $this->db->queryAll($sql, [$this->userId]);
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem($productId, $quantity)
    {
        if (!$this->userId) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }

        $sql = "UPDATE cart_items SET quantity = ? 
                WHERE user_id = ? AND product_id = ?";
        
        return $this->db->execute($sql, [$quantity, $this->userId, $productId]) > 0;
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($productId)
    {
        if (!$this->userId) {
            return false;
        }

        $sql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
        return $this->db->execute($sql, [$this->userId, $productId]) > 0;
    }

    /**
     * Get cart total
     */
    public function getCartTotal()
    {
        if (!$this->userId) {
            return 0;
        }

        $sql = "SELECT SUM(c.quantity * p.price) as total
                FROM cart_items c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?";
        
        $result = $this->db->queryOne($sql, [$this->userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get cart item count
     */
    public function getCartItemCount()
    {
        if (!$this->userId) {
            return 0;
        }

        $sql = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
        $result = $this->db->queryOne($sql, [$this->userId]);
        return $result['count'] ?? 0;
    }

    /**
     * Clear cart
     */
    public function clearCart()
    {
        if (!$this->userId) {
            return false;
        }

        $sql = "DELETE FROM cart_items WHERE user_id = ?";
        return $this->db->execute($sql, [$this->userId]) > 0;
    }
}
