<?php
/**
 * Order Management Service
 * Handles admin order operations with access control vulnerability teaching
 * 
 * VULNERABILITY A01: Broken Access Control
 * This service demonstrates both vulnerable and secure access control patterns
 */

namespace CakeShop\Services;

class OrderManagementService
{
    private $db;
    private $config;
    private $userId;
    private $userRole;

    public function __construct($config, $userId = null, $userRole = null)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
        $this->userId = $userId;
        $this->userRole = $userRole;
    }

    /**
     * Get all orders (admin dashboard)
     * Demonstrates VULNERABLE path: No user_id filtering
     * 
     * @return array All orders in system
     */
    public function getAllOrders()
    {
        try {
            // VULNERABLE: Return all orders regardless of user role
            if (function_exists('isVulnerable') && isVulnerable('access_control')) {
                // Vulnerable path: Admin endpoint accessible to anyone, shows all orders
                $sql = "SELECT id, user_id, order_number, status, total_amount, created_at, updated_at
                        FROM orders
                        ORDER BY created_at DESC";
                
                return $this->db->queryAll($sql, []);
            } else {
                // SECURE: Only admin can retrieve all orders
                if ($this->userRole !== 'admin') {
                    return ['error' => true, 'message' => 'Unauthorized access'];
                }

                $sql = "SELECT id, user_id, order_number, status, total_amount, created_at, updated_at
                        FROM orders
                        ORDER BY created_at DESC";
                
                return $this->db->queryAll($sql, []);
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get single order with access control check
     * Demonstrates VULNERABLE vs SECURE access patterns
     * 
     * @param int $orderId
     * @return array|null Order data with items, or null if denied/not found
     */
    public function getOrderWithAccessCheck($orderId)
    {
        try {
            // First: retrieve the order
            $sql = "SELECT id, user_id, order_number, status, total_amount, shipping_address, 
                           customer_notes, created_at, updated_at
                    FROM orders WHERE id = ?";
            
            $order = $this->db->queryOne($sql, [$orderId]);
            
            if (!$order) {
                return null;
            }

            // VULNERABLE vs SECURE: Access control check
            if (function_exists('isVulnerable') && isVulnerable('access_control')) {
                // VULNERABLE: No access check - anyone can view any order
                // This demonstrates why authorization should ALWAYS be verified server-side
                // User can simply change URL from /order-detail.php?id=1 to /order-detail.php?id=999
                
                // Get items and return
                $order['items'] = $this->getOrderItems($orderId);
                return $order;
            } else {
                // SECURE: Verify user has permission
                // User can only view their own orders; admin can view any
                if ($this->userRole === 'admin' || $order['user_id'] == $this->userId) {
                    $order['items'] = $this->getOrderItems($orderId);
                    return $order;
                } else {
                    // Access denied - return null
                    return null;
                }
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get orders for a specific user
     * SECURE: Always checks user_id
     * 
     * @param int $userId
     * @return array
     */
    public function getOrdersByUserId($userId)
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
     * Update order status (admin operation)
     * SECURE: Admin role required
     * 
     * @param int $orderId
     * @param string $newStatus
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateOrderStatus($orderId, $newStatus)
    {
        try {
            // VULNERABLE: Skip admin check if vulnerability enabled
            if (function_exists('isVulnerable') && isVulnerable('access_control')) {
                // Vulnerable: Anyone can change order status
                // Real-world impact: Customer changes their order from pending to shipped
            } else {
                // SECURE: Verify admin role
                if ($this->userRole !== 'admin') {
                    return ['success' => false, 'message' => 'Unauthorized: Admin access required'];
                }
            }

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
     * Get order items
     * 
     * @param int $orderId
     * @return array
     */
    public function getOrderItems($orderId)
    {
        try {
            $sql = "SELECT id, product_id, product_name, product_price, quantity
                    FROM order_items
                    WHERE order_id = ?";
            
            return $this->db->queryAll($sql, [$orderId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate if current user can access order management
     * Used for admin routes
     * 
     * @return bool
     */
    public function canAccessOrderManagement()
    {
        // VULNERABLE: Skip role check
        if (function_exists('isVulnerable') && isVulnerable('access_control')) {
            return true;  // Anyone can access admin features
        }

        // SECURE: Require admin role
        return $this->userRole === 'admin';
    }

    /**
     * Get order statistics (admin dashboard)
     * SECURE: Admin only
     * 
     * @return array Stats or empty if unauthorized
     */
    public function getOrderStatistics()
    {
        // VULNERABLE: Return stats to anyone
        if (function_exists('isVulnerable') && isVulnerable('access_control')) {
            try {
                $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as revenue,
                        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
                        FROM orders";
                
                $stats = $this->db->queryOne($sql, []);
                return $stats ?: [];
            } catch (\Exception $e) {
                return [];
            }
        } else {
            // SECURE: Admin only
            if ($this->userRole !== 'admin') {
                return [];
            }

            try {
                $sql = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(total_amount) as revenue,
                        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
                        FROM orders";
                
                $stats = $this->db->queryOne($sql, []);
                return $stats ?: [];
            } catch (\Exception $e) {
                return [];
            }
        }
    }

    /**
     * Search orders (admin feature)
     * Filters: by status, customer, date range
     * 
     * @param array $filters ['status' => string, 'customer_id' => int, etc]
     * @return array
     */
    public function searchOrders($filters = [])
    {
        // VULNERABLE: Skip auth check
        if (function_exists('isVulnerable') && isVulnerable('access_control')) {
            // Anyone can search all orders
        } else {
            // SECURE: Admin only
            if ($this->userRole !== 'admin') {
                return [];
            }
        }

        try {
            $sql = "SELECT id, user_id, order_number, status, total_amount, created_at
                    FROM orders WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT 100";

            return $this->db->queryAll($sql, $params);
        } catch (\Exception $e) {
            return [];
        }
    }
}
