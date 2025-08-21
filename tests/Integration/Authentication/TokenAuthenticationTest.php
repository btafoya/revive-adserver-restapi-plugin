<?php

namespace Tests\Integration\Authentication;

use PHPUnit\Framework\TestCase;
use App\Services\TokenService;
use App\Middleware\AuthenticationMiddleware;
use App\Support\ReviveConfig;
use Tests\Fixtures\TestDatabase;

class TokenAuthenticationTest extends TestCase
{
    private $tokenService;
    private $auth;
    private $config;

    public function setUp(): void
    {
        // Initialize test environment
        if (!defined('OA_ENVIRONMENT')) {
            define('OA_ENVIRONMENT', 'test');
        }

        $this->config = new ReviveConfig();
        $this->tokenService = new TokenService();
        $this->auth = new AuthenticationMiddleware();

        // Create test tables
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        $pdo = TestDatabase::getPdo();
        
        // Create API tokens table
        $pdo->exec("DROP TABLE IF EXISTS api_token_usage");
        $pdo->exec("DROP TABLE IF EXISTS api_tokens");
        $pdo->exec("DROP TABLE IF EXISTS api_settings");
        
        $pdo->exec("CREATE TABLE api_tokens (
            id int(11) NOT NULL AUTO_INCREMENT,
            token_hash varchar(255) NOT NULL,
            name varchar(100) NOT NULL,
            user_id int(11) DEFAULT NULL,
            permissions text DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used_at datetime DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash)
        )");

        $pdo->exec("CREATE TABLE api_token_usage (
            id int(11) NOT NULL AUTO_INCREMENT,
            token_id int(11) NOT NULL,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            response_status int(3) DEFAULT NULL,
            used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY token_id (token_id)
        )");

        $pdo->exec("CREATE TABLE api_settings (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value text DEFAULT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        )");

        // Insert default settings
        $pdo->exec("INSERT INTO api_settings (setting_key, setting_value) VALUES
            ('api_enabled', '1'),
            ('require_authentication', '1'),
            ('rate_limit_per_minute', '100'),
            ('token_expiry_days', '90'),
            ('max_tokens_per_user', '5')");
    }

    public function testTokenGeneration(): void
    {
        $tokenData = [
            'name' => 'Test Token',
            'user_id' => 1,
            'permissions' => ['campaigns.read', 'banners.read'],
            'created_by' => 1
        ];

        $result = $this->tokenService->generateToken($tokenData);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('Test Token', $result['name']);
        $this->assertStringStartsWith('rapi_', $result['token']);
        $this->assertIsArray($result['permissions']);
    }

    public function testTokenValidation(): void
    {
        // Create a token
        $tokenData = [
            'name' => 'Validation Test',
            'user_id' => 1,
            'permissions' => ['all'],
            'created_by' => 1
        ];

        $result = $this->tokenService->generateToken($tokenData);
        $token = $result['token'];

        // Validate the token
        $validation = $this->tokenService->validateToken($token);

        $this->assertNotNull($validation);
        $this->assertEquals('Validation Test', $validation['name']);
        $this->assertEquals(1, $validation['user_id']);
    }

    public function testInvalidTokenValidation(): void
    {
        $validation = $this->tokenService->validateToken('invalid_token');
        $this->assertNull($validation);
    }

    public function testAuthenticationMiddleware(): void
    {
        // Create a token
        $tokenData = [
            'name' => 'Middleware Test',
            'user_id' => 1,
            'permissions' => ['campaigns.read'],
            'created_by' => 1
        ];

        $result = $this->tokenService->generateToken($tokenData);
        $token = $result['token'];

        // Set up mock HTTP headers
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        $authResult = $this->auth->authenticate();

        $this->assertTrue($authResult['authenticated']);
        $this->assertEquals('token', $authResult['method']);
        $this->assertEquals(1, $authResult['user_id']);
        $this->assertEquals('Middleware Test', $authResult['token_name']);
    }

    public function testPermissionValidation(): void
    {
        // Test valid permissions
        $validPermissions = ['campaigns.read', 'banners.write', 'all'];
        $errors = $this->tokenService->validatePermissions($validPermissions);
        $this->assertEmpty($errors);

        // Test invalid permissions
        $invalidPermissions = ['invalid.permission', 'campaigns.read'];
        $errors = $this->tokenService->validatePermissions($invalidPermissions);
        $this->assertNotEmpty($errors);
        $this->assertStringContains('Invalid permission: invalid.permission', $errors[0]);
    }

    public function testTokenLimit(): void
    {
        $userId = 2;

        // Create max tokens (5)
        for ($i = 1; $i <= 5; $i++) {
            $tokenData = [
                'name' => "Token {$i}",
                'user_id' => $userId,
                'permissions' => ['campaigns.read'],
                'created_by' => 1
            ];
            $this->tokenService->generateToken($tokenData);
        }

        // Check if limit is reached
        $hasReachedLimit = $this->tokenService->hasReachedTokenLimit($userId);
        $this->assertTrue($hasReachedLimit);

        // Different user should not be affected
        $hasReachedLimit = $this->tokenService->hasReachedTokenLimit(999);
        $this->assertFalse($hasReachedLimit);
    }

    public function testTokenDeactivation(): void
    {
        // Create a token
        $tokenData = [
            'name' => 'Deactivation Test',
            'user_id' => 1,
            'permissions' => ['all'],
            'created_by' => 1
        ];

        $result = $this->tokenService->generateToken($tokenData);
        $tokenId = $result['id'];
        $token = $result['token'];

        // Verify token works
        $validation = $this->tokenService->validateToken($token);
        $this->assertNotNull($validation);

        // Deactivate token
        $this->tokenService->deleteToken($tokenId, false);

        // Verify token no longer works
        $validation = $this->tokenService->validateToken($token);
        $this->assertNull($validation);
    }

    public function testTokenExpiry(): void
    {
        // Create an expired token by manually setting expiry
        $tokenData = [
            'name' => 'Expired Test',
            'user_id' => 1,
            'permissions' => ['all'],
            'created_by' => 1
        ];

        $result = $this->tokenService->generateToken($tokenData);
        $tokenId = $result['id'];
        $token = $result['token'];

        // Manually expire the token
        $pdo = TestDatabase::getPdo();
        $stmt = $pdo->prepare("UPDATE api_tokens SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = ?");
        $stmt->execute([$tokenId]);

        // Verify expired token doesn't validate
        $validation = $this->tokenService->validateToken($token);
        $this->assertNull($validation);
    }

    public function testApiSettings(): void
    {
        // Test getting settings
        $settings = $this->tokenService->getApiSettings();
        $this->assertArrayHasKey('api_enabled', $settings);
        $this->assertEquals('1', $settings['api_enabled']);

        // Test updating settings
        $newSettings = [
            'rate_limit_per_minute' => '200',
            'token_expiry_days' => '30'
        ];

        $success = $this->tokenService->updateApiSettings($newSettings);
        $this->assertTrue($success);

        // Verify settings were updated
        $settings = $this->tokenService->getApiSettings();
        $this->assertEquals('200', $settings['rate_limit_per_minute']);
        $this->assertEquals('30', $settings['token_expiry_days']);
    }

    public function tearDown(): void
    {
        // Clean up test data
        $pdo = TestDatabase::getPdo();
        $pdo->exec("DROP TABLE IF EXISTS api_token_usage");
        $pdo->exec("DROP TABLE IF EXISTS api_tokens");
        $pdo->exec("DROP TABLE IF EXISTS api_settings");

        // Clear server variables
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['HTTP_X_API_TOKEN']);
    }
}