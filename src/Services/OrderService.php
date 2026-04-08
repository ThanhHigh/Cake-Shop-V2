<?php
/**
 * Order Service
 * Handles order operations: creation from cart, retrieval, and status updates
 */

namespace CakeShop\Services;

class OrderService
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
     * Create order from cart items
     * Converts cart_items to order_items, generates order_number, clears cart
     * 
     * @param string $shippingAddress Shipping address text
     * @param string $customerNotes Optional customer notes
     * @return array ['success' => bool, 'message' => string, 'order_id' => int, 'order_number' => string]
     */
    public function createOrderFromCart($shippingAddress, $customerNotes = '')
    {
        if (!$this->userId) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        try {
            // Get cart items for user
            $cartItems = $this->getCartForConversion();
            
            if (empty($cartItems)) {
                return ['success' => false, 'message' => 'Cart is empty'];
            }

            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['price'] * $item['quantity'];
            }

            // Apply tax (10%)
            $tax = $total * 0.10;
            $total = $total + $tax;

            // Generate unique order number: YYYYMMDD-USERID-RANDOM6DIGITS
            $orderNumber = $this->generateOrderNumber();

            // Begin transaction
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();

            try {
                // Insert order record
                $sql = "INSERT INTO orders (user_id, order_number, status, total_amount, shipping_address, customer_notes)
                        VALUES (?, ?, 'pending', ?, ?, ?)";
                $this->db->execute($sql, [$this->userId, $orderNumber, $total, $shippingAddress, $customerNotes]);
                
                $orderId = $this->db->lastInsertId();

                // Insert order items from cart
                foreach ($cartItems as $item) {
                    $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity)
                            VALUES (?, ?, ?, ?, ?)";
                    $this->db->execute($sql, [
                        $orderId,
                        $item['product_id'],
                        $item['product_name'],
                        $item['price'],
                        $item['quantity']
                    ]);
                }

                // Clear user's cart
                $sql = "DELETE FROM cart_items WHERE user_id = ?";
                $this->db->execute($sql, [$this->userId]);

                $pdo->commit();

                return [
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'total' => $total
                ];
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Order creation failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get order by ID with items
     * 
     * @param int $orderId
     * @return array|null Order with items, or null if not found
     */
    public function getOrderById($orderId)
    {
        try {
            $sql = "SELECT id, user_id, order_number, status, total_amount, shipping_address, 
                           customer_notes, created_at, updated_at
                    FROM orders WHERE id = ?";
            
            $order = $this->db->queryOne($sql, [$orderId]);
            
            if (!$order) {
                return null;
            }

            // Get order items
            $order['items'] = $this->getOrderItems($orderId);
            
            return $order;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all orders for a user
     * 
     * @param int $userId
     * @return array List of orders (without items detail)
     */
    public function getOrdersByUser($userId)
    {
        try {
            $sql = "SELECT id, order_number, status, total_amount, created_at, updated_at
                    FROM orders 
                    WHERE user_id = ?
                    ORDER BY created_at DESC";
            
            return $this->db->queryAll($sql, [$userId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get current user's orders
     * 
     * @return array
     */
    public function getMyOrders()
    {
        if (!$this->userId) {
            return [];
        }

        return $this->getOrdersByUser($this->userId);
    }

    /**
     * Get items for a specific order
     * 
     * @param int $orderId
     * @return array Order items
     */
    public function getOrderItems($orderId)
    {
        try {
            $sql = "SELECT id, product_id, product_name, product_price, quantity
                    FROM order_items
                    WHERE order_id = ?
                    ORDER BY id ASC";
            
            return $this->db->queryAll($sql, [$orderId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Update order status
     * Allowed transitions: pending -> paid -> shipped -> delivered (or cancel)
     * 
     * @param int $orderId
     * @param string $newStatus
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateOrderStatus($orderId, $newStatus)
    {
        try {
            $allowedStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($newStatus, $allowedStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
            $rowsAffected = $this->db->execute($sql, [$newStatus, $orderId]);
            
            if ($rowsAffected > 0) {
                return ['success' => true, 'message' => 'Order status updated'];
            } else {
                return ['success' => false, 'message' => 'Order not found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Status update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get cart items for conversion to order
     * Internal method for createOrderFromCart
     * 
     * @return array
     */
    private function getCartForConversion()
    {
        try {
            $sql = "SELECT c.product_id, c.quantity, p.name as product_name, p.price
                    FROM cart_items c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ?";
            
            return $this->db->queryAll($sql, [$this->userId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate unique order number
     * Format: YYYYMMDD-USERID-RANDOM6DIGITS
     * 
     * @return string
     */
    private function generateOrderNumber()
    {
        $date = date('Ymd');
        $random = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        return "{$date}-{$this->userId}-{$random}";
    }

    /**
     * Get order total with tax
     * 
     * @param int $orderId
     * @return float|null
     */
    public function getOrderTotal($orderId)
    {
        try {
            $sql = "SELECT total_amount FROM orders WHERE id = ?";
            $result = $this->db->queryOne($sql, [$orderId]);
            return $result ? $result['total_amount'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
