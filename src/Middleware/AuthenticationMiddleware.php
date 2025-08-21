<?php

namespace App\Middleware;

use App\Services\TokenService;
use App\Support\ReviveConfig;

class AuthenticationMiddleware
{
    private $tokenService;
    private $config;

    public function __construct()
    {
        $this->tokenService = new TokenService();
        $this->config = new ReviveConfig();
    }

    /**
     * Authenticate the current request
     */
    public function authenticate(): array
    {
        // Check if authentication is required
        if (!$this->isAuthenticationRequired()) {
            return ['authenticated' => true, 'method' => 'disabled'];
        }

        // Try session-based authentication first
        $sessionAuth = $this->validateSession();
        if ($sessionAuth['authenticated']) {
            return $sessionAuth;
        }

        // Try token-based authentication
        $tokenAuth = $this->validateToken();
        if ($tokenAuth['authenticated']) {
            return $tokenAuth;
        }

        // Authentication failed
        return ['authenticated' => false, 'error' => 'Authentication required'];
    }

    /**
     * Validate session-based authentication
     */
    private function validateSession(): array
    {
        // Check if user is logged into Revive admin
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            return [
                'authenticated' => true,
                'method' => 'session',
                'user_id' => $_SESSION['user']['user_id'] ?? null,
                'username' => $_SESSION['user']['username'] ?? null
            ];
        }

        // Check for OA session (Revive's session format)
        if (isset($GLOBALS['session']) && !empty($GLOBALS['session']['user'])) {
            return [
                'authenticated' => true,
                'method' => 'session',
                'user_id' => $GLOBALS['session']['user']['user_id'] ?? null,
                'username' => $GLOBALS['session']['user']['username'] ?? null
            ];
        }

        return ['authenticated' => false];
    }

    /**
     * Validate token-based authentication
     */
    private function validateToken(): array
    {
        $token = $this->extractToken();
        
        if (!$token) {
            return ['authenticated' => false, 'error' => 'No token provided'];
        }

        $tokenData = $this->tokenService->validateToken($token);
        
        if (!$tokenData) {
            return ['authenticated' => false, 'error' => 'Invalid token'];
        }

        // Log token usage
        $this->logTokenUsage($tokenData['id']);

        return [
            'authenticated' => true,
            'method' => 'token',
            'token_id' => $tokenData['id'],
            'user_id' => $tokenData['user_id'],
            'token_name' => $tokenData['name'],
            'permissions' => json_decode($tokenData['permissions'] ?? '[]', true)
        ];
    }

    /**
     * Extract token from request headers or query parameters
     */
    private function extractToken(): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Check custom X-API-Token header
        $apiTokenHeader = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
        if (!empty($apiTokenHeader)) {
            return trim($apiTokenHeader);
        }

        // Check query parameter
        $queryToken = $_GET['api_token'] ?? '';
        if (!empty($queryToken)) {
            return trim($queryToken);
        }

        return null;
    }

    /**
     * Check if authentication is required
     */
    private function isAuthenticationRequired(): bool
    {
        $setting = $this->config->getSetting('require_authentication', '1');
        return $setting === '1' || $setting === 'true';
    }

    /**
     * Log token usage for analytics
     */
    private function logTokenUsage(int $tokenId): void
    {
        try {
            $pdo = $this->config->getPdo();
            
            $stmt = $pdo->prepare("
                INSERT INTO api_token_usage 
                (token_id, endpoint, method, ip_address, user_agent, used_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $tokenId,
                $_SERVER['REQUEST_URI'] ?? '',
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

            // Update last_used_at for the token
            $updateStmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
            $updateStmt->execute([$tokenId]);

        } catch (\Exception $e) {
            // Log error but don't fail authentication
            error_log("Failed to log token usage: " . $e->getMessage());
        }
    }

    /**
     * Check if user has permission for specific action
     */
    public function hasPermission(array $authData, string $permission): bool
    {
        // Session users have all permissions
        if ($authData['method'] === 'session') {
            return true;
        }

        // Check token permissions
        $permissions = $authData['permissions'] ?? [];
        
        // If no permissions set, allow all (backwards compatibility)
        if (empty($permissions)) {
            return true;
        }

        // Check for specific permission or 'all' permission
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }

    /**
     * Send authentication error response
     */
    public function sendAuthError(string $message = 'Authentication required'): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'code' => 401,
            'timestamp' => date('c')
        ]);
        exit;
    }

    /**
     * Send permission error response
     */
    public function sendPermissionError(string $message = 'Insufficient permissions'): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'code' => 403,
            'timestamp' => date('c')
        ]);
        exit;
    }
}