<?php
/**
 * Review Service
 * Handles product reviews and ratings
 */

namespace CakeShop\Services;

class ReviewService
{
    private $db;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->db = Database::getInstance($config);
    }

    /**
     * Add a product review
     */
    public function addReview($productId, $userId, $rating, $title, $comment, $proofUrl = '')
    {
        try {
            $proofUrl = trim((string)$proofUrl);
            $proofStatus = 'not_provided';
            $proofMessage = null;
            $proofPreview = null;

            if ($proofUrl !== '') {
                $integrationService = new IntegrationService($this->config);

                try {
                    $fetchResult = $integrationService->fetchUrl($proofUrl);

                    $proofStatus = !empty($fetchResult['success']) ? 'fetched' : 'blocked';
                    $proofMessage = (string)($fetchResult['message'] ?? 'Proof URL request failed');
                    $proofPreview = isset($fetchResult['preview'])
                        ? substr((string)$fetchResult['preview'], 0, 500)
                        : null;
                } catch (\Throwable $fetchException) {
                    $proofStatus = 'failed';
                    $proofMessage = 'Proof URL request failed: ' . $fetchException->getMessage();
                }
            }

            $sql = "INSERT INTO reviews (product_id, user_id, rating, title, content, proof_url, proof_fetch_status, proof_fetch_message, proof_preview, is_approved)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";

            $this->db->execute($sql, [
                $productId,
                $userId,
                $rating,
                $title,
                $comment,
                $proofUrl !== '' ? $proofUrl : null,
                $proofStatus,
                $proofMessage,
                $proofPreview,
            ]);

            return [
                'success' => true,
                'message' => 'Review submitted successfully',
                'proof_url' => $proofUrl,
                'proof_fetch_status' => $proofStatus,
                'proof_fetch_message' => $proofMessage,
                'proof_preview' => $proofPreview,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check whether user exceeded review submission limit in a time window.
     */
    public function isReviewRateLimited($userId, $maxReviews = 3, $windowSeconds = 300)
    {
        $windowSeconds = max(60, (int)$windowSeconds);
        $maxReviews = max(1, (int)$maxReviews);
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);

        $sql = "SELECT COUNT(*) AS review_count
                FROM reviews
                WHERE user_id = ? AND created_at >= ?";

        $result = $this->db->queryOne($sql, [(int)$userId, $cutoff]);
        $count = (int)($result['review_count'] ?? 0);

        return [
            'limited' => $count >= $maxReviews,
            'current_count' => $count,
            'max_reviews' => $maxReviews,
            'window_seconds' => $windowSeconds,
        ];
    }

    /**
     * Get pending reviews for admin moderation.
     */
    public function getPendingReviews($limit = 100)
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT r.id, r.product_id, r.user_id, r.rating, r.title, r.content AS comment, r.created_at,
                       p.name AS product_name,
                       u.full_name AS author_name, u.email AS author_email
                FROM reviews r
                JOIN products p ON p.id = r.product_id
                JOIN users u ON u.id = r.user_id
                WHERE r.is_approved = FALSE
                ORDER BY r.created_at DESC
                LIMIT $limit";

        return $this->db->queryAll($sql);
    }

    /**
     * Get all reviews for admin, newest first.
     */
    public function getAllReviewsForAdmin($limit = 200)
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT r.id, r.product_id, r.user_id, r.rating, r.title, r.content AS comment,
                       r.is_approved, r.created_at,
                       p.name AS product_name,
                       u.full_name AS author_name, u.email AS author_email
                FROM reviews r
                JOIN products p ON p.id = r.product_id
                JOIN users u ON u.id = r.user_id
                ORDER BY r.created_at DESC
                LIMIT $limit";

        return $this->db->queryAll($sql);
    }

    /**
     * Approve a review by id.
     */
    public function approveReview($reviewId)
    {
        try {
            $sql = "UPDATE reviews SET is_approved = TRUE WHERE id = ?";
            $affected = $this->db->execute($sql, [(int)$reviewId]);

            if ($affected < 1) {
                return ['success' => false, 'message' => 'Review not found'];
            }

            return ['success' => true, 'message' => 'Review approved'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reject a review by deleting it.
     */
    public function rejectReview($reviewId)
    {
        try {
            $sql = "DELETE FROM reviews WHERE id = ?";
            $affected = $this->db->execute($sql, [(int)$reviewId]);

            if ($affected < 1) {
                return ['success' => false, 'message' => 'Review not found'];
            }

            return ['success' => true, 'message' => 'Review rejected and removed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get product reviews
     */
    public function getProductReviews($productId, $limit = 10)
    {
        $limit = (int)$limit; // Ensure limit is an integer
        $sql = "SELECT r.id, r.rating, r.title, r.content AS comment, r.proof_url, r.proof_fetch_status,
                   r.proof_fetch_message, r.proof_preview, r.created_at, u.full_name as author_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ? AND r.is_approved = TRUE
                ORDER BY r.created_at DESC
                LIMIT $limit";
        
        return $this->db->queryAll($sql, [$productId]);
    }

    /**
     * Get average rating for product
     */
    public function getAverageRating($productId)
    {
        $sql = "SELECT AVG(rating) as avg_rating FROM reviews 
                WHERE product_id = ? AND is_approved = TRUE";
        
        $result = $this->db->queryOne($sql, [$productId]);
        return $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
    }

    /**
     * Get rating count
     */
    public function getRatingCount($productId)
    {
        $sql = "SELECT COUNT(*) as count FROM reviews 
                WHERE product_id = ? AND is_approved = TRUE";
        
        $result = $this->db->queryOne($sql, [$productId]);
        return $result['count'] ?? 0;
    }

    /**
     * Get rating distribution
     */
    public function getRatingDistribution($productId)
    {
        $sql = "SELECT rating, COUNT(*) as count FROM reviews 
                WHERE product_id = ? AND is_approved = TRUE
                GROUP BY rating
                ORDER BY rating DESC";
        
        return $this->db->queryAll($sql, [$productId]);
    }
}
