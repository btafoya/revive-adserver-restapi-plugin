<?php

namespace App\Controllers;

use App\Services\TokenService;
use App\Middleware\AuthenticationMiddleware;

class ApiTokensController
{
    private $tokenService;
    private $auth;

    public function __construct()
    {
        $this->tokenService = new TokenService();
        $this->auth = new AuthenticationMiddleware();
    }

    /**
     * List all tokens for the authenticated user
     */
    public function index(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated']) {
            $this->auth->sendAuthError();
        }

        // Only admin users can see all tokens, regular users see only their tokens
        $userId = $this->isAdmin($authData) ? null : $authData['user_id'];
        $includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === 'true';

        $tokens = $this->tokenService->listTokens($userId, $includeInactive);

        $this->json([
            'success' => true,
            'data' => $tokens,
            'meta' => [
                'total' => count($tokens),
                'user_id' => $userId
            ]
        ]);
    }

    /**
     * Get specific token details
     */
    public function show(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated']) {
            $this->auth->sendAuthError();
        }

        $tokenId = (int) ($_GET['id'] ?? 0);
        if (!$tokenId) {
            $this->jsonError('Token ID required', 400);
        }

        $token = $this->tokenService->getToken($tokenId);
        if (!$token) {
            $this->jsonError('Token not found', 404);
        }

        // Check if user can access this token
        if (!$this->canAccessToken($authData, $token)) {
            $this->auth->sendPermissionError('Cannot access this token');
        }

        // Include usage statistics
        $usage = $this->tokenService->getTokenUsage($tokenId);

        $this->json([
            'success' => true,
            'data' => array_merge($token, ['usage' => $usage])
        ]);
    }

    /**
     * Create a new API token
     */
    public function create(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated']) {
            $this->auth->sendAuthError();
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonError('Invalid JSON input', 400);
        }

        // Validate required fields
        $errors = $this->validateTokenInput($input);
        if ($errors) {
            $this->jsonError('Validation failed', 400, ['errors' => $errors]);
        }

        // Check token limit for non-admin users
        $userId = $input['user_id'] ?? $authData['user_id'];
        if (!$this->isAdmin($authData) && $this->tokenService->hasReachedTokenLimit($userId)) {
            $this->jsonError('Token limit reached', 429);
        }

        // Validate permissions
        $permissions = $input['permissions'] ?? [];
        $permissionErrors = $this->tokenService->validatePermissions($permissions);
        if ($permissionErrors) {
            $this->jsonError('Invalid permissions', 400, ['errors' => $permissionErrors]);
        }

        $tokenData = [
            'name' => $input['name'],
            'user_id' => $userId,
            'permissions' => $permissions,
            'created_by' => $authData['user_id']
        ];

        $result = $this->tokenService->generateToken($tokenData);

        $this->json([
            'success' => true,
            'data' => $result,
            'message' => 'Token created successfully. Save the token value - it will not be shown again.'
        ], 201);
    }

    /**
     * Update token details
     */
    public function update(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated']) {
            $this->auth->sendAuthError();
        }

        $tokenId = (int) ($_GET['id'] ?? 0);
        if (!$tokenId) {
            $this->jsonError('Token ID required', 400);
        }

        $token = $this->tokenService->getToken($tokenId);
        if (!$token) {
            $this->jsonError('Token not found', 404);
        }

        // Check if user can modify this token
        if (!$this->canModifyToken($authData, $token)) {
            $this->auth->sendPermissionError('Cannot modify this token');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonError('Invalid JSON input', 400);
        }

        // Validate permissions if provided
        if (isset($input['permissions'])) {
            $permissionErrors = $this->tokenService->validatePermissions($input['permissions']);
            if ($permissionErrors) {
                $this->jsonError('Invalid permissions', 400, ['errors' => $permissionErrors]);
            }
        }

        $success = $this->tokenService->updateToken($tokenId, $input);
        if (!$success) {
            $this->jsonError('Failed to update token', 500);
        }

        $updatedToken = $this->tokenService->getToken($tokenId);

        $this->json([
            'success' => true,
            'data' => $updatedToken,
            'message' => 'Token updated successfully'
        ]);
    }

    /**
     * Delete/deactivate a token
     */
    public function delete(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated']) {
            $this->auth->sendAuthError();
        }

        $tokenId = (int) ($_GET['id'] ?? 0);
        if (!$tokenId) {
            $this->jsonError('Token ID required', 400);
        }

        $token = $this->tokenService->getToken($tokenId);
        if (!$token) {
            $this->jsonError('Token not found', 404);
        }

        // Check if user can delete this token
        if (!$this->canModifyToken($authData, $token)) {
            $this->auth->sendPermissionError('Cannot delete this token');
        }

        $hardDelete = isset($_GET['hard']) && $_GET['hard'] === 'true';
        $success = $this->tokenService->deleteToken($tokenId, $hardDelete);

        if (!$success) {
            $this->jsonError('Failed to delete token', 500);
        }

        $this->json([
            'success' => true,
            'message' => $hardDelete ? 'Token permanently deleted' : 'Token deactivated'
        ]);
    }

    /**
     * Get API settings
     */
    public function settings(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated'] || !$this->isAdmin($authData)) {
            $this->auth->sendPermissionError('Admin access required');
        }

        $settings = $this->tokenService->getApiSettings();

        $this->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update API settings
     */
    public function updateSettings(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated'] || !$this->isAdmin($authData)) {
            $this->auth->sendPermissionError('Admin access required');
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonError('Invalid JSON input', 400);
        }

        $success = $this->tokenService->updateApiSettings($input);
        if (!$success) {
            $this->jsonError('Failed to update settings', 500);
        }

        $this->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Clean up expired tokens
     */
    public function cleanup(): void
    {
        $authData = $this->auth->authenticate();
        if (!$authData['authenticated'] || !$this->isAdmin($authData)) {
            $this->auth->sendPermissionError('Admin access required');
        }

        $count = $this->tokenService->cleanupExpiredTokens();

        $this->json([
            'success' => true,
            'data' => ['cleaned_tokens' => $count],
            'message' => "Cleaned up {$count} expired tokens"
        ]);
    }

    /**
     * Validate token input data
     */
    private function validateTokenInput(array $input): array
    {
        $errors = [];

        if (empty($input['name'])) {
            $errors[] = 'Token name is required';
        } elseif (strlen($input['name']) > 100) {
            $errors[] = 'Token name must be 100 characters or less';
        }

        if (isset($input['permissions']) && !is_array($input['permissions'])) {
            $errors[] = 'Permissions must be an array';
        }

        return $errors;
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(array $authData): bool
    {
        // For now, consider session users as admins
        // This can be expanded with proper role checking
        return $authData['method'] === 'session';
    }

    /**
     * Check if user can access token
     */
    private function canAccessToken(array $authData, array $token): bool
    {
        // Admins can access all tokens
        if ($this->isAdmin($authData)) {
            return true;
        }

        // Users can only access their own tokens
        return $authData['user_id'] == $token['user_id'];
    }

    /**
     * Check if user can modify token
     */
    private function canModifyToken(array $authData, array $token): bool
    {
        // Admins can modify all tokens
        if ($this->isAdmin($authData)) {
            return true;
        }

        // Users can only modify their own tokens
        return $authData['user_id'] == $token['user_id'];
    }

    /**
     * Send JSON response
     */
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send JSON error response
     */
    private function jsonError(string $message, int $status = 400, array $extra = []): void
    {
        $response = array_merge([
            'error' => $message,
            'code' => $status,
            'timestamp' => date('c')
        ], $extra);

        $this->json($response, $status);
    }
}