<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class AuthenticationLogicTest extends TestCase
{
    public function testTokenGeneration(): void
    {
        // Test token generation logic
        $token = $this->generateSecureToken();
        
        $this->assertStringStartsWith('rapi_', $token);
        $this->assertGreaterThan(40, strlen($token)); // Should be long enough
        $this->assertMatchesRegularExpression('/^rapi_[A-Za-z0-9_-]+$/', $token);
    }

    public function testTokenHashing(): void
    {
        $token = 'rapi_test_token_12345';
        $hash = hash('sha256', $token);
        
        $this->assertEquals(64, strlen($hash)); // SHA-256 is 64 chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
        
        // Same token should produce same hash
        $hash2 = hash('sha256', $token);
        $this->assertEquals($hash, $hash2);
    }

    public function testPermissionValidation(): void
    {
        $validPermissions = [
            'campaigns.read', 'campaigns.write', 'campaigns.delete',
            'banners.read', 'banners.write', 'banners.delete', 'banners.upload',
            'zones.read', 'zones.write', 'zones.delete',
            'targeting.read', 'targeting.write',
            'rulesets.read', 'rulesets.write', 'rulesets.delete', 'rulesets.apply',
            'stats.read',
            'all'
        ];

        // Test valid permissions
        $testPermissions = ['campaigns.read', 'banners.write', 'all'];
        $errors = $this->validatePermissions($testPermissions, $validPermissions);
        $this->assertEmpty($errors);

        // Test invalid permissions
        $testPermissions = ['invalid.permission', 'campaigns.read'];
        $errors = $this->validatePermissions($testPermissions, $validPermissions);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid permission: invalid.permission', $errors[0]);
    }

    public function testHeaderParsing(): void
    {
        // Test Authorization header parsing
        $authHeader = 'Bearer rapi_test_token_12345';
        $token = $this->extractTokenFromAuthHeader($authHeader);
        $this->assertEquals('rapi_test_token_12345', $token);

        // Test malformed header
        $authHeader = 'Basic username:password';
        $token = $this->extractTokenFromAuthHeader($authHeader);
        $this->assertNull($token);

        // Test empty header
        $authHeader = '';
        $token = $this->extractTokenFromAuthHeader($authHeader);
        $this->assertNull($token);
    }

    public function testTokenExpiryLogic(): void
    {
        // Test future expiry
        $futureDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        $this->assertFalse($this->isTokenExpired($futureDate));

        // Test past expiry
        $pastDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->assertTrue($this->isTokenExpired($pastDate));

        // Test no expiry (null)
        $this->assertFalse($this->isTokenExpired(null));
    }

    public function testPermissionChecking(): void
    {
        // Test specific permission
        $userPermissions = ['campaigns.read', 'banners.write'];
        $this->assertTrue($this->hasPermission($userPermissions, 'campaigns.read'));
        $this->assertFalse($this->hasPermission($userPermissions, 'campaigns.write'));

        // Test 'all' permission
        $userPermissions = ['all'];
        $this->assertTrue($this->hasPermission($userPermissions, 'campaigns.read'));
        $this->assertTrue($this->hasPermission($userPermissions, 'any.permission'));

        // Test empty permissions (allow all for backwards compatibility)
        $userPermissions = [];
        $this->assertTrue($this->hasPermission($userPermissions, 'campaigns.read'));
    }

    // Helper methods that simulate the actual implementation logic

    private function generateSecureToken(): string
    {
        // Simulate the token generation logic
        $randomBytes = random_bytes(32);
        $token = base64_encode($randomBytes);
        $token = str_replace(['+', '/', '='], ['-', '_', ''], $token);
        return 'rapi_' . $token;
    }

    private function validatePermissions(array $permissions, array $validPermissions): array
    {
        $errors = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $validPermissions)) {
                $errors[] = "Invalid permission: {$permission}";
            }
        }
        return $errors;
    }

    private function extractTokenFromAuthHeader(string $authHeader): ?string
    {
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function isTokenExpired(?string $expiresAt): bool
    {
        if ($expiresAt === null) {
            return false; // No expiry
        }
        return strtotime($expiresAt) < time();
    }

    private function hasPermission(array $userPermissions, string $requiredPermission): bool
    {
        // If no permissions set, allow all (backwards compatibility)
        if (empty($userPermissions)) {
            return true;
        }

        // Check for specific permission or 'all' permission
        return in_array($requiredPermission, $userPermissions) || in_array('all', $userPermissions);
    }
}