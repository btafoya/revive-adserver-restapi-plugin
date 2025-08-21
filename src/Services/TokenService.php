<?php

namespace App\Services;

use App\Support\ReviveConfig;
use PDO;

class TokenService
{
    private $config;
    private $pdo;

    public function __construct()
    {
        $this->config = new ReviveConfig();
        $this->pdo = $this->config->getPdo();
    }

    /**
     * Generate a new API token
     */
    public function generateToken(array $data): array
    {
        $token = $this->createSecureToken();
        $tokenHash = hash('sha256', $token);
        
        $expiryDays = $this->config->getSetting('token_expiry_days', 90);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO api_tokens 
            (token_hash, name, user_id, permissions, expires_at, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $permissions = is_array($data['permissions']) ? json_encode($data['permissions']) : $data['permissions'];
        
        $stmt->execute([
            $tokenHash,
            $data['name'],
            $data['user_id'] ?? null,
            $permissions,
            $expiresAt,
            $data['created_by'] ?? null
        ]);
        
        $tokenId = $this->pdo->lastInsertId();
        
        return [
            'id' => $tokenId,
            'token' => $token, // Only returned on creation
            'name' => $data['name'],
            'expires_at' => $expiresAt,
            'permissions' => json_decode($permissions, true)
        ];
    }

    /**
     * Validate a token and return token data
     */
    public function validateToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->pdo->prepare("
            SELECT id, name, user_id, permissions, expires_at, last_used_at
            FROM api_tokens 
            WHERE token_hash = ? 
            AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        $stmt->execute([$tokenHash]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $tokenData ?: null;
    }

    /**
     * List all tokens for a user
     */
    public function listTokens(?int $userId = null, bool $includeInactive = false): array
    {
        $sql = "
            SELECT id, name, user_id, permissions, expires_at, created_at, last_used_at, is_active
            FROM api_tokens 
            WHERE 1=1
        ";
        $params = [];
        
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse permissions JSON
        foreach ($tokens as &$token) {
            $token['permissions'] = json_decode($token['permissions'] ?? '[]', true);
            $token['is_expired'] = $token['expires_at'] && strtotime($token['expires_at']) < time();
        }
        
        return $tokens;
    }

    /**
     * Get token details by ID
     */
    public function getToken(int $tokenId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name, user_id, permissions, expires_at, created_at, last_used_at, is_active, created_by
            FROM api_tokens 
            WHERE id = ?
        ");
        
        $stmt->execute([$tokenId]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token) {
            $token['permissions'] = json_decode($token['permissions'] ?? '[]', true);
            $token['is_expired'] = $token['expires_at'] && strtotime($token['expires_at']) < time();
        }
        
        return $token ?: null;
    }

    /**
     * Update token details
     */
    public function updateToken(int $tokenId, array $data): bool
    {
        $allowedFields = ['name', 'permissions', 'expires_at', 'is_active'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                if ($field === 'permissions' && is_array($data[$field])) {
                    $params[] = json_encode($data[$field]);
                } else {
                    $params[] = $data[$field];
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $tokenId;
        
        $stmt = $this->pdo->prepare("
            UPDATE api_tokens 
            SET " . implode(', ', $updates) . " 
            WHERE id = ?
        ");
        
        return $stmt->execute($params);
    }

    /**
     * Delete/deactivate a token
     */
    public function deleteToken(int $tokenId, bool $hardDelete = false): bool
    {
        if ($hardDelete) {
            $stmt = $this->pdo->prepare("DELETE FROM api_tokens WHERE id = ?");
        } else {
            $stmt = $this->pdo->prepare("UPDATE api_tokens SET is_active = 0 WHERE id = ?");
        }
        
        return $stmt->execute([$tokenId]);
    }

    /**
     * Get token usage statistics
     */
    public function getTokenUsage(int $tokenId, int $days = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE(used_at) as date,
                COUNT(*) as requests,
                COUNT(DISTINCT endpoint) as unique_endpoints
            FROM api_token_usage 
            WHERE token_id = ? 
            AND used_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(used_at)
            ORDER BY date DESC
        ");
        
        $stmt->execute([$tokenId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE api_tokens 
            SET is_active = 0 
            WHERE expires_at < NOW() AND is_active = 1
        ");
        
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Validate token permissions
     */
    public function validatePermissions(array $permissions): array
    {
        $validPermissions = [
            'campaigns.read', 'campaigns.write', 'campaigns.delete',
            'banners.read', 'banners.write', 'banners.delete', 'banners.upload',
            'zones.read', 'zones.write', 'zones.delete',
            'targeting.read', 'targeting.write',
            'rulesets.read', 'rulesets.write', 'rulesets.delete', 'rulesets.apply',
            'stats.read',
            'all' // Full access
        ];
        
        $errors = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $validPermissions)) {
                $errors[] = "Invalid permission: {$permission}";
            }
        }
        
        return $errors;
    }

    /**
     * Check if user has reached token limit
     */
    public function hasReachedTokenLimit(int $userId): bool
    {
        $maxTokens = (int) $this->config->getSetting('max_tokens_per_user', 5);
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM api_tokens 
            WHERE user_id = ? AND is_active = 1
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] >= $maxTokens;
    }

    /**
     * Generate a cryptographically secure token
     */
    private function createSecureToken(): string
    {
        // Generate 32 random bytes and encode as base64
        $randomBytes = random_bytes(32);
        $token = base64_encode($randomBytes);
        
        // Make URL-safe
        $token = str_replace(['+', '/', '='], ['-', '_', ''], $token);
        
        // Add prefix for identification
        return 'rapi_' . $token;
    }

    /**
     * Get API settings
     */
    public function getApiSettings(): array
    {
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM api_settings");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    /**
     * Update API settings
     */
    public function updateApiSettings(array $settings): bool
    {
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO api_settings (setting_key, setting_value, updated_by) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_by = VALUES(updated_by),
                updated_at = NOW()
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value, $_SESSION['user']['user_id'] ?? null]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}