<?php

namespace CakeShop\Services;

class ProfileImageService
{
    private $uploadDir;
    private $mappingFile;

    public function __construct($config, $uploadDir = null, $mappingFile = null)
    {
        $baseDir = dirname(dirname(__DIR__)) . '/public/uploads/customers_profile_images';
        $this->uploadDir = $uploadDir ?? ($baseDir . '/');
        $this->mappingFile = $mappingFile ?? ($baseDir . '/user_to_image_link.txt');

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $mappingDir = dirname($this->mappingFile);
        if (!is_dir($mappingDir)) {
            mkdir($mappingDir, 0777, true);
        }
    }

    public function uploadProfileImage($userId, $userEmail, array $file)
    {
        if (!isset($file['error'], $file['name'], $file['tmp_name'])) {
            return ['success' => false, 'message' => 'Invalid upload data'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed'];
        }

        if (!is_string($file['name']) || $file['name'] === '') {
            return ['success' => false, 'message' => 'Filename missing'];
        }

        $filename = $file['name'];
        $targetPath = $this->uploadDir . $filename;

        // Try move_uploaded_file first (preferred). If it fails due to permission
        // or other runtime issues, attempt a fallback using copy() then unlink().
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            // fallback copy
            if (!@copy($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Failed to move uploaded file and copy fallback failed'];
            }

            // attempt to remove tmp file; ignore errors
            @unlink($file['tmp_name']);
        }

        // ensure permissions allow web server to serve/read file in container/host
        @chmod($targetPath, 0666);

        $this->upsertMapping((int)$userId, (string)$userEmail, 'customers_profile_images/' . $filename);

        return [
            'success' => true,
            'message' => 'Profile image uploaded successfully',
            'image_path' => 'customers_profile_images/' . $filename,
        ];
    }

    public function getProfileImagePath($userId)
    {
        $defaultPath = 'customers_profile_images/default_image.png';

        if (!is_file($this->mappingFile)) {
            return $defaultPath;
        }

        $lines = file($this->mappingFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $defaultPath;
        }

        $targetId = (string)$userId;
        for ($index = count($lines) - 1; $index >= 0; $index--) {
            $parts = explode(':', $lines[$index], 3);
            if (count($parts) !== 3) {
                continue;
            }

            if ($parts[0] === $targetId) {
                return $this->normalizeImagePath($parts[2]);
            }
        }

        return $defaultPath;
    }

    private function normalizeImagePath($imagePath)
    {
        $cleanPath = ltrim((string)$imagePath, '/');

        if (strpos($cleanPath, 'customers_profile_images/') === 0) {
            return $cleanPath;
        }

        return 'customers_profile_images/' . $cleanPath;
    }

    private function upsertMapping($userId, $userEmail, $imagePath)
    {
        $lines = [];

        if (is_file($this->mappingFile)) {
            $existingLines = file($this->mappingFile, FILE_IGNORE_NEW_LINES);
            if ($existingLines !== false) {
                foreach ($existingLines as $line) {
                    if ($line === '') {
                        continue;
                    }

                    $parts = explode(':', $line, 3);
                    if (count($parts) === 3 && $parts[0] === (string)$userId && $parts[1] === (string)$userEmail) {
                        continue;
                    }

                    $lines[] = $line;
                }
            }
        }

        $lines[] = $userId . ':' . $userEmail . ':' . $imagePath;
        file_put_contents($this->mappingFile, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}