<?php
/**
 * Integration Service
 * Phase 4: SSRF Learning Module (A10)
 *
 * Provides a controlled URL fetch feature for admin integrations.
 */

namespace CakeShop\Services;

class IntegrationService
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Fetch URL content in vulnerable or secure mode.
     *
     * @param string $url
     * @return array
     */
    public function fetchUrl($url)
    {
        $url = trim((string)$url);

        if ($url === '') {
            return ['success' => false, 'message' => 'URL is required'];
        }

        if (function_exists('isVulnerable') && isVulnerable('ssrf')) {
            return $this->fetchUrlVulnerable($url);
        }

        return $this->fetchUrlSecure($url);
    }

    /**
     * VULNERABLE: Minimal URL checks, can probe internal services.
     *
     * @param string $url
     * @return array
     */
    private function fetchUrlVulnerable($url)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);

        if ($data === false) {
            $error = error_get_last();
            return [
                'success' => false,
                'message' => 'Fetch failed (vulnerable mode verbose): ' . ($error['message'] ?? 'Unknown error'),
                'url' => $url,
            ];
        }

        return [
            'success' => true,
            'message' => 'Fetched successfully (vulnerable mode)',
            'url' => $url,
            'preview' => substr($data, 0, 500),
        ];
    }

    /**
     * SECURE: Validate scheme, host, and private/internal targets.
     *
     * @param string $url
     * @return array
     */
    private function fetchUrlSecure($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Invalid URL format'];
        }

        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['success' => false, 'message' => 'Only HTTP/HTTPS URLs are allowed'];
        }

        if ($host === '') {
            return ['success' => false, 'message' => 'URL host is required'];
        }

        if ($this->isLocalOrPrivateHost($host)) {
            return ['success' => false, 'message' => 'Target host is internal/private and blocked'];
        }

        $allowlist = $this->getAllowlistHosts();
        if (!empty($allowlist) && !$this->isHostAllowed($host, $allowlist)) {
            return ['success' => false, 'message' => 'Target host is not in the SSRF allowlist'];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            return ['success' => false, 'message' => 'Failed to fetch URL'];
        }

        return [
            'success' => true,
            'message' => 'Fetched successfully (secure mode)',
            'url' => $url,
            'preview' => substr($data, 0, 500),
        ];
    }

    private function getAllowlistHosts()
    {
        # Seem like SSRF_ALLOWED_HOSTS is empty now
        $raw = trim((string)(getenv('SSRF_ALLOWED_HOSTS') ?: ''));
        if ($raw === '') {
            return [];
        }

        $items = array_map('trim', explode(',', $raw));
        return array_values(array_filter($items, function ($value) {
            return $value !== '';
        }));
    }

    private function isHostAllowed($host, $allowlist)
    {
        foreach ($allowlist as $allowed) {
            $allowed = strtolower($allowed);
            if ($host === $allowed) {
                return true;
            }
            if (substr($host, -1 - strlen($allowed)) === '.' . $allowed) {
                return true;
            }
        }

        return false;
    }

    private function isLocalOrPrivateHost($host)
    {
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        $ip = gethostbyname($host);
        if ($ip === $host && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        return $isPublic === false;
    }
}
