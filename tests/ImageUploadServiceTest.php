<?php

use CakeShop\Services\ImageUploadService;
use PHPUnit\Framework\TestCase;

class TestableImageUploadService extends ImageUploadService
{
    public $downloadFilePath;
    public $downloadMimeType;
    public $responseCode;
    public $responseMessage;

    protected function storeUploadedFile($tmpPath, $uploadPath)
    {
        return copy($tmpPath, $uploadPath);
    }

    protected function sendDownloadResponse($filePath, $mimeType)
    {
        $this->downloadFilePath = $filePath;
        $this->downloadMimeType = $mimeType;
    }

    protected function sendNotFoundResponse($message)
    {
        $this->responseCode = 404;
        $this->responseMessage = $message;
    }

    protected function sendForbiddenResponse($message)
    {
        $this->responseCode = 403;
        $this->responseMessage = $message;
    }
}

class ImageUploadServiceTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/cake_shop_upload_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_it_can_be_constructed_with_custom_upload_dir(): void
    {
        $service = new TestableImageUploadService($GLOBALS['config'], $this->tempDir . '/');

        $this->assertInstanceOf(ImageUploadService::class, $service);
    }

    public function test_vulnerable_upload_accepts_and_preserves_original_filename(): void
    {
        $config = $GLOBALS['config'];
        $config['app_mode'] = 'vulnerable';
        $config['vulnerabilities']['insecure_upload'] = true;

        $service = new TestableImageUploadService($config, $this->tempDir . '/');
        $sourceFile = tempnam($this->tempDir, 'upload_');
        file_put_contents($sourceFile, '<?php echo "test"; ?>');

        $result = $service->uploadFile([
            'name' => 'shell.php',
            'tmp_name' => $sourceFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($sourceFile),
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('shell.php', $result['filename']);
        $this->assertFileExists($this->tempDir . '/shell.php');
    }

    public function test_secure_upload_rejects_disallowed_extension(): void
    {
        $config = $GLOBALS['config'];
        $config['app_mode'] = 'secure';
        $config['vulnerabilities']['insecure_upload'] = false;

        $service = new TestableImageUploadService($config, $this->tempDir . '/');
        $sourceFile = tempnam($this->tempDir, 'upload_');
        file_put_contents($sourceFile, 'plain text');

        $result = $service->uploadFile([
            'name' => 'shell.php',
            'tmp_name' => $sourceFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($sourceFile),
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid file extension', $result['message']);
    }

    public function test_secure_upload_renames_file_with_hash(): void
    {
        $config = $GLOBALS['config'];
        $config['app_mode'] = 'secure';
        $config['vulnerabilities']['insecure_upload'] = false;

        $service = new TestableImageUploadService($config, $this->tempDir . '/');
        $sourceFile = tempnam($this->tempDir, 'upload_');
        file_put_contents($sourceFile, '%PDF-1.4');

        $result = $service->uploadFile([
            'name' => 'invoice.pdf',
            'tmp_name' => $sourceFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($sourceFile),
        ]);

        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}\.pdf$/', $result['filename']);
        $this->assertFileExists($this->tempDir . '/' . $result['filename']);
    }

    public function test_download_file_uses_uploaded_filename_in_vulnerable_mode(): void
    {
        $config = $GLOBALS['config'];
        $config['app_mode'] = 'vulnerable';
        $config['vulnerabilities']['insecure_upload'] = true;

        $service = new TestableImageUploadService($config, $this->tempDir . '/');
        file_put_contents($this->tempDir . '/report.pdf', 'pdf-data');

        $service->downloadFile('report.pdf');

        $this->assertSame($this->tempDir . '/report.pdf', $service->downloadFilePath);
        $this->assertSame('application/octet-stream', $service->downloadMimeType);
    }

    public function test_download_file_blocks_traversal_in_secure_mode(): void
    {
        $config = $GLOBALS['config'];
        $config['app_mode'] = 'secure';
        $config['vulnerabilities']['insecure_upload'] = false;

        $service = new TestableImageUploadService($config, $this->tempDir . '/');
        $service->downloadFile('../../config.php');

        $this->assertSame(404, $service->responseCode);
        $this->assertSame('File not found', $service->responseMessage);
        $this->assertNull($service->downloadFilePath);
    }

    public function test_file_exists_returns_false_for_missing_file(): void
    {
        $service = new TestableImageUploadService($GLOBALS['config'], $this->tempDir . '/');

        $this->assertFalse($service->fileExists('missing.pdf'));
    }

    public function test_delete_file_returns_false_for_missing_file(): void
    {
        $service = new TestableImageUploadService($GLOBALS['config'], $this->tempDir . '/');

        $this->assertFalse($service->deleteFile('missing.pdf'));
    }
}
