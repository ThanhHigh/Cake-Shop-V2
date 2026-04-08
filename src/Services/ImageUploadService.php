<?php
/**
 * Image/File Upload Service
 * Phase 3: Order System - File Upload Handling
 * VULNERABILITY A04: Insecure Design - File Upload
 */

namespace CakeShop\Services;

class ImageUploadService
{
    private $config;
    private $uploadDir;
    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    private $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    private $maxFileSize = 5242880; // 5MB

    public function __construct($config, $uploadDir = null)
    {
        $this->config = $config;
        
        if ($uploadDir === null) {
            $this->uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/';
        } else {
            $this->uploadDir = $uploadDir;
        }

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload and validate file
     * Handles both vulnerable and secure modes
     * 
     * @param array $file $_FILES element
     * @return array ['success' => bool, 'message' => string, 'filename' => string]
     */
    public function uploadFile($file)
    {
        // Validate file array
        if (!isset($file['error'])) {
            return ['success' => false, 'message' => 'Invalid file'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => $this->getUploadError($file['error'])];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'File too large (max 5MB)'];
        }

        // VULNERABILITY A04: File upload handling
        if (function_exists('isVulnerable') && isVulnerable('insecure_upload')) {
            // VULNERABLE: No validation, save with original filename
            return $this->uploadFileVulnerable($file);
        } else {
            // SECURE: Full validation and safe storage
            return $this->uploadFileSecure($file);
        }
    }

    /**
     * VULNERABLE: Upload file with minimal checks
     * Risks: 
     * - File type not validated (could upload executable)
     * - Original filename preserved (could overwrite files)
     * - No MIME type checking
     * 
     * @param array $file
     * @return array
     */
    private function uploadFileVulnerable($file)
    {
        try {
            $filename = $file['name'];
            $tmpPath = $file['tmp_name'];
            
            $uploadPath = $this->uploadDir . $filename;
            
            if (move_uploaded_file($tmpPath, $uploadPath)) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'filename' => $filename
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * SECURE: Upload file with full validation
     * Protections:
     * - Extension whitelist check
     * - MIME type validation
     * - File renamed with hash (prevents overwrite, obscures original)
     * - Path traversal prevention (realpath validation)
     * 
     * @param array $file
     * @return array
     */
    private function uploadFileSecure($file)
    {
        try {
            $filename = $file['name'];
            $tmpPath = $file['tmp_name'];
            
            // Validate extension
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, $this->allowedExtensions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file extension. Allowed: ' . implode(', ', $this->allowedExtensions)
                ];
            }

            // Validate MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedMimes)) {
                return [
                    'success' => false,
                    'message' => 'Invalid file type. MIME type detected: ' . $mimeType
                ];
            }

            // Generate safe filename with hash
            $hash = hash('sha256', uniqid() . time() . $filename);
            $safeFilename = $hash . '.' . $fileExt;
            $uploadPath = $this->uploadDir . $safeFilename;

            // Prevent path traversal: verify final path is still in upload dir
            $realPath = realpath($uploadPath) ?: $uploadPath;
            $realUploadDir = realpath($this->uploadDir);
            
            if (strpos($realPath, $realUploadDir) !== 0) {
                return ['success' => false, 'message' => 'Invalid file path'];
            }

            if (move_uploaded_file($tmpPath, $uploadPath)) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'filename' => $safeFilename,
                    'original_name' => $filename
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Download file with access control
     * SECURE: Validates file exists and MIME type before download
     * VULNERABLE: Serves any file without type checking
     * 
     * @param string $filename
     * @return void (outputs file or error)
     */
    public function downloadFile($filename)
    {
        try {
            if (function_exists('isVulnerable') && isVulnerable('insecure_upload')) {
                // VULNERABLE: Serve without validation
                $this->downloadFileVulnerable($filename);
            } else {
                // SECURE: Full validation
                $this->downloadFileSecure($filename);
            }
        } catch (\Exception $e) {
            http_response_code(404);
            die('File not found');
        }
    }

    /**
     * VULNERABLE: Download file without type validation
     * Risk: Could expose system files through path traversal
     * 
     * @param string $filename
     */
    private function downloadFileVulnerable($filename)
    {
        $filePath = $this->uploadDir . $filename;
        
        // Weak check - can be bypassed with ../ or other tricks
        if (file_exists($filePath) && is_file($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
        
        http_response_code(404);
        die('File not found');
    }

    /**
     * SECURE: Download file with validation
     * Protections:
     * - Path traversal prevention (realpath check)
     * - MIME type validation
     * - Proper headers
     * 
     * @param string $filename
     */
    private function downloadFileSecure($filename)
    {
        $filePath = realpath($this->uploadDir . $filename);
        $uploadDir = realpath($this->uploadDir);
        
        // Verify file is in upload directory (prevent path traversal)
        if (!$filePath || strpos($filePath, $uploadDir) !== 0) {
            http_response_code(404);
            die('File not found');
        }

        // Verify file exists and is readable
        if (!file_exists($filePath) || !is_file($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            die('File not found');
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimes)) {
            http_response_code(403);
            die('File type not allowed');
        }

        // Send file
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode
     * @return string
     */
    private function getUploadError($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];

        return $errors[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Validate file exists and is accessible
     * 
     * @param string $filename
     * @return bool
     */
    public function fileExists($filename)
    {
        if (function_exists('isVulnerable') && isVulnerable('insecure_upload')) {
            // Vulnerable: Simple check
            return file_exists($this->uploadDir . $filename);
        } else {
            // Secure: Verify path is valid
            $filePath = realpath($this->uploadDir . $filename);
            $uploadDir = realpath($this->uploadDir);
            
            return $filePath && strpos($filePath, $uploadDir) === 0 && file_exists($filePath);
        }
    }

    /**
     * Delete file (admin operation)
     * 
     * @param string $filename
     * @return bool
     */
    public function deleteFile($filename)
    {
        try {
            $filePath = realpath($this->uploadDir . $filename);
            $uploadDir = realpath($this->uploadDir);
            
            // Verify path is in upload directory
            if ($filePath && strpos($filePath, $uploadDir) === 0 && file_exists($filePath)) {
                return unlink($filePath);
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
