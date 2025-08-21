<?php
namespace App\Support;

use PDO;
use PDOException;

if (!class_exists('App\Support\ReviveConfig')) {
    final class ReviveConfig
{
    private static $pdo = null;

    public static function conf(): array { return $GLOBALS['_MAX']['CONF'] ?? []; }

    public static function table(string $name): string
    {
        $c = self::conf();
        $prefix = $c['table']['prefix'] ?? 'rv_';
        $map    = $c['table'] ?? [];
        $base   = $map[$name] ?? $name;
        return $prefix . $base;
    }

    public static function imagesDir(): string
    {
        $c = self::conf();
        return rtrim($c['store']['webDir'] ?? (__DIR__ . '/../../www/images'), '/');
    }

    public static function imagesUrlBase(): string
    {
        $c = self::conf();
        return rtrim($c['webpath']['imagesSSL'] ?? ($c['webpath']['images'] ?? '/www/images'), '/');
    }

    /**
     * Get database connection
     */
    public function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = $this->createConnection();
        }
        return self::$pdo;
    }

    /**
     * Create database connection
     */
    private function createConnection(): PDO
    {
        // Use test database in test environment
        if (defined('OA_ENVIRONMENT') && OA_ENVIRONMENT === 'test') {
            return \Tests\Fixtures\TestDatabase::getPdo();
        }

        $c = self::conf()['database'] ?? [];
        
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $c['host'] ?? 'localhost',
            $c['name'] ?? 'revive'
        );

        try {
            $pdo = new PDO($dsn, $c['username'] ?? '', $c['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return $pdo;
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get API setting value
     */
    public function getSetting(string $key, $default = null)
    {
        try {
            $stmt = $this->getPdo()->prepare("SELECT setting_value FROM api_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            return $result ? $result['setting_value'] : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set API setting value
     */
    public function setSetting(string $key, $value): bool
    {
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT INTO api_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            return $stmt->execute([$key, $value]);
        } catch (\Exception $e) {
            return false;
        }
    }
}
}
