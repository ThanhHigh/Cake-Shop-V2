<?php

namespace CakeShop\Services;

class ProfileImageService
{
    private $uploadDir;
    private $config;
    private $mappingFile;

    public function __construct($config, $uploadDir = null, $mappingFile = null)
    {
        $baseDir = dirname(dirname(__DIR__)) . '/public/uploads/customers_profile_images';
        
        $this->config = $config;
        $this->uploadDir = $uploadDir ?? ($baseDir . '/');
        $this->mappingFile = $mappingFile ?? ($baseDir . '/user_to_image_link.txt');

        if (!is_dir($this->uploadDir)) {
            if (function_exists('isVulnerable') && isVulnerable('file_upload')) {
                mkdir($this->uploadDir, 0777, true);
            } else {
                mkdir($this->uploadDir, 0755, true);
            }
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

        if (function_exists('isVulnerable') && isVulnerable('file_upload')) {
            $filename = $file['name'];

            //Block with Content-type
            // $allowContentTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            // if (!isset($file['type']) || !in_array($file['type'], $allowContentTypes)) {
            //     return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'];
            // }

            //Block with file extension
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $blacklistExtensions = ['php', 'htaccess'];
            if (in_array(strtolower($extension), $blacklistExtensions)) {
                return [
                    'success' => false, 
                    'message' => 'Invalid file type. Files with .php and .htaccess are not allowed.'
                ];
            }

            //Block path traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
                return [
                    'success' => false, 
                    'message' => 'Invalid filename. Path traversal characters are not allowed.'
                ];
            }

            $targetPath = $this->uploadDir . $filename;

            // Try move_uploaded_file first (preferred). If it fails due to permission
            // or other runtime issues, attempt a fallback using copy() then unlink().
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                // fallback copy
                if (!@copy($file['tmp_name'], $targetPath)) {
                    return [
                        'success' => false, 
                        'message' => 'Failed to move uploaded file and copy fallback failed'
                    ];
                }

                // attempt to remove tmp file; ignore errors
                @unlink($file['tmp_name']);
            }

            // ensure permissions allow web server to serve/read file in container/host
            @chmod($targetPath, 0644);

            $this->upsertMapping((int)$userId, (string)$userEmail, 'customers_profile_images/' . $filename);

            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'image_path' => 'customers_profile_images/' . $filename,
            ];
        } else {
            // Validate the Content-Type header to ensure it's an image
            $allowContentTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!isset($file['type']) || !in_array($file['type'], $allowContentTypes)) {
                return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'];
            }

            //Validate file extension
            $extention = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($extention), $allowedExtensions)) {
                return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.'];
            }

            //Validate file content
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($mimeType, $allowedMimeTypes)) {
                return ['success' => false, 'message' => 'Invalid file content. The file does not appear to be a valid image.'];
            }

            // Unique safefilename
            $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;
            $targetPath = $this->uploadDir . $safeFilename;
            
            // // Not unique safefilename
            // $safeFilename = $file['name'];
            // $targetPath = $this->uploadDir . $safeFilename;

            // Try move_uploaded_file first (preferred). If it fails due to permission
            // or other runtime issues, attempt a fallback using copy() then unlink().
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                // fallback copy
                if (!@copy($file['tmp_name'], $targetPath)) {
                    return [
                        'success' => false, 
                        'message' => 'Failed to move uploaded file and copy fallback failed'
                    ];
                }

                // attempt to remove tmp file; ignore errors
                @unlink($file['tmp_name']);
            }

            // Use safer file permissions
            @chmod($targetPath, 0644);

            $this->upsertMapping((int)$userId, (string)$userEmail, 'customers_profile_images/' . $safeFilename);

            return [
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'image_path' => 'customers_profile_images/' . $safeFilename,
            ];
        }
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