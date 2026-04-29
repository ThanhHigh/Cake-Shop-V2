<?php

use CakeShop\Services\ReviewService;
use PHPUnit\Framework\TestCase;

class FakeReviewDb
{
    public $lastSql;
    public $lastParams;
    private $queryAllResult = [];

    public function execute($sql, $params = [])
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;

        return 1;
    }

    public function queryAll($sql, $params = [])
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;

        return $this->queryAllResult;
    }

    public function setQueryAllResult($result)
    {
        $this->queryAllResult = $result;
    }
}

class ReviewServiceSsrfTest extends TestCase
{
    private $originalConfig;

    protected function setUp(): void
    {
        $this->originalConfig = $GLOBALS['config'];
    }

    protected function tearDown(): void
    {
        $GLOBALS['config'] = $this->originalConfig;
    }

    public function test_add_review_without_proof_url_marks_not_provided(): void
    {
        $db = new FakeReviewDb();
        $service = $this->buildService($db, [
            'app_mode' => 'vulnerable',
            'vulnerabilities' => ['ssrf' => true],
        ]);

        $result = $service->addReview(1, 2, 5, 'Good Cake', 'Nice texture', '');

        $this->assertTrue($result['success']);
        $this->assertSame('not_provided', $result['proof_fetch_status']);
        $this->assertSame('INSERT INTO reviews (product_id, user_id, rating, title, content, proof_url, proof_fetch_status, proof_fetch_message, proof_preview, is_approved)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)', $db->lastSql);
        $this->assertNull($db->lastParams[5]);
        $this->assertSame('not_provided', $db->lastParams[6]);
        $this->assertNull($db->lastParams[7]);
        $this->assertNull($db->lastParams[8]);
    }

    public function test_add_review_with_internal_proof_url_blocked_in_secure_mode(): void
    {
        $db = new FakeReviewDb();
        $service = $this->buildService($db, [
            'app_mode' => 'secure',
            'vulnerabilities' => ['ssrf' => false],
        ]);

        $result = $service->addReview(1, 2, 4, 'Arrived Fast', 'Delivery was quick', 'http://127.0.0.1/admin');

        $this->assertTrue($result['success']);
        $this->assertSame('blocked', $result['proof_fetch_status']);
        $this->assertStringContainsString('blocked', strtolower((string)$result['proof_fetch_message']));
        $this->assertSame('http://127.0.0.1/admin', $db->lastParams[5]);
        $this->assertSame('blocked', $db->lastParams[6]);
    }

    public function test_get_product_reviews_selects_proof_columns(): void
    {
        $db = new FakeReviewDb();
        $db->setQueryAllResult([
            [
                'id' => 1,
                'rating' => 5,
                'title' => 'Great',
                'comment' => 'Loved it',
                'proof_url' => 'http://example.com',
                'proof_fetch_status' => 'fetched',
                'proof_fetch_message' => 'Fetched successfully',
                'proof_preview' => 'sample',
                'author_name' => 'Customer',
            ],
        ]);

        $service = $this->buildService($db, [
            'app_mode' => 'vulnerable',
            'vulnerabilities' => ['ssrf' => true],
        ]);

        $reviews = $service->getProductReviews(10, 5);

        $this->assertCount(1, $reviews);
        $this->assertStringContainsString('r.proof_url', $db->lastSql);
        $this->assertStringContainsString('r.proof_preview', $db->lastSql);
    }

    private function buildService(FakeReviewDb $db, array $config): ReviewService
    {
        $defaults = [
            'app_mode' => 'vulnerable',
            'vulnerabilities' => [
                'ssrf' => true,
            ],
        ];

        $mergedConfig = array_replace_recursive($defaults, $config);
        $GLOBALS['config'] = $mergedConfig;

        $reflection = new ReflectionClass(ReviewService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $dbProp = $reflection->getProperty('db');
        $dbProp->setAccessible(true);
        $dbProp->setValue($service, $db);

        $configProp = $reflection->getProperty('config');
        $configProp->setAccessible(true);
        $configProp->setValue($service, $mergedConfig);

        return $service;
    }
}
