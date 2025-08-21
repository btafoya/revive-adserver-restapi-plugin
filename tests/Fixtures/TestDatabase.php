<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use PDO;
use PDOException;

class TestDatabase
{
    private static ?PDO $pdo = null;

    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            self::createTables();
            self::seedData();
        }

        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    private static function createTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS mcp_rule_sets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS mcp_rule_set_rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rule_set_id INTEGER NOT NULL,
                `order` INTEGER NOT NULL DEFAULT 1,
                json_rule TEXT NOT NULL,
                FOREIGN KEY (rule_set_id) REFERENCES mcp_rule_sets(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS banners (
                bannerid INTEGER PRIMARY KEY AUTOINCREMENT,
                campaignid INTEGER NOT NULL,
                description VARCHAR(255),
                imageurl VARCHAR(255),
                htmltemplate TEXT,
                width INTEGER,
                height INTEGER,
                weight INTEGER DEFAULT 1,
                seq INTEGER DEFAULT 0,
                target VARCHAR(255),
                url VARCHAR(255),
                alt VARCHAR(255),
                status INTEGER DEFAULT 0,
                keyword VARCHAR(255),
                transparent INTEGER DEFAULT 0,
                compiledlimitation TEXT,
                append TEXT,
                bannertext TEXT,
                ct0 INTEGER DEFAULT 0,
                ct1 INTEGER DEFAULT 0,
                ct2 INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS campaigns (
                campaignid INTEGER PRIMARY KEY AUTOINCREMENT,
                campaignname VARCHAR(255) NOT NULL,
                clientid INTEGER NOT NULL,
                views INTEGER DEFAULT 0,
                clicks INTEGER DEFAULT 0,
                conversions INTEGER DEFAULT 0,
                revenue DECIMAL(10,4) DEFAULT 0,
                status INTEGER DEFAULT 0,
                weight INTEGER DEFAULT 1,
                target_impression INTEGER DEFAULT 0,
                target_click INTEGER DEFAULT 0,
                target_conversion INTEGER DEFAULT 0,
                anonymous INTEGER DEFAULT 0,
                comments TEXT,
                updated DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS zones (
                zoneid INTEGER PRIMARY KEY AUTOINCREMENT,
                zonename VARCHAR(255) NOT NULL,
                description TEXT,
                width INTEGER,
                height INTEGER,
                zonetype INTEGER DEFAULT 0,
                category TEXT,
                ad_selection TEXT,
                inventory_forecast_type INTEGER DEFAULT 0,
                block INTEGER DEFAULT 0,
                capping INTEGER DEFAULT 0,
                session_capping INTEGER DEFAULT 0,
                what TEXT,
                as_zone_ad TEXT,
                prepend TEXT,
                append TEXT,
                updated DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS acls (
                bannerid INTEGER NOT NULL,
                logical VARCHAR(3) NOT NULL DEFAULT 'and',
                type VARCHAR(255) NOT NULL,
                comparison VARCHAR(2) NOT NULL DEFAULT '==',
                data TEXT,
                executionorder INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (bannerid) REFERENCES banners(bannerid) ON DELETE CASCADE
            );
        ";

        self::$pdo->exec($sql);
    }

    private static function seedData(): void
    {
        // Insert test campaigns
        $campaigns = [
            [1, 'Summer Sale Campaign', 1, 10000, 500, 25, 1250.00, 0],
            [2, 'Winter Holiday Campaign', 1, 5000, 250, 10, 625.00, 1],
            [3, 'Spring Launch Campaign', 2, 15000, 750, 35, 1875.00, 0],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO campaigns (campaignid, campaignname, clientid, views, clicks, conversions, revenue, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($campaigns as $campaign) {
            $stmt->execute($campaign);
        }

        // Insert test banners
        $banners = [
            [1, 1, 'Summer Sale Banner 1', '/images/summer1.jpg', null, 300, 250, 1, 0, '_blank', 'https://example.com/summer', 'Summer Sale', 0],
            [2, 1, 'Summer Sale Banner 2', '/images/summer2.jpg', null, 728, 90, 1, 0, '_blank', 'https://example.com/summer', 'Summer Sale', 0],
            [3, 2, 'Winter Holiday Banner', '/images/winter.jpg', null, 300, 250, 1, 0, '_blank', 'https://example.com/winter', 'Winter Sale', 1],
            [4, 3, 'Spring Launch Banner', null, '<div>Spring is here!</div>', 300, 250, 1, 0, '_blank', 'https://example.com/spring', 'Spring Launch', 0],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO banners (bannerid, campaignid, description, imageurl, htmltemplate, width, height, weight, seq, target, url, alt, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($banners as $banner) {
            $stmt->execute($banner);
        }

        // Insert test zones
        $zones = [
            [1, 'Header Zone', 'Main header advertising zone', 728, 90, 0],
            [2, 'Sidebar Zone', 'Right sidebar zone', 300, 250, 0],
            [3, 'Footer Zone', 'Footer advertising zone', 728, 90, 0],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO zones (zoneid, zonename, description, width, height, zonetype)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($zones as $zone) {
            $stmt->execute($zone);
        }

        // Insert test rule sets
        $ruleSets = [
            [1, 'US Desktop Users', 'Targeting US users on desktop devices'],
            [2, 'Mobile Weekend', 'Mobile users during weekends'],
            [3, 'Business Hours', 'Business hours targeting'],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO mcp_rule_sets (id, name, description)
            VALUES (?, ?, ?)
        ");

        foreach ($ruleSets as $ruleSet) {
            $stmt->execute($ruleSet);
        }

        // Insert test rule set rules
        $rules = [
            [1, 1, 1, json_encode(['type' => 'Geo:Country', 'comparison' => '==', 'data' => 'US'])],
            [2, 1, 2, json_encode(['logical' => 'and', 'type' => 'Client:Browser', 'comparison' => '==', 'data' => ['Chrome', 'Firefox']])],
            [3, 2, 1, json_encode(['type' => 'Time:DayOfWeek', 'comparison' => '==', 'data' => [0, 6]])],
            [4, 3, 1, json_encode(['type' => 'Time:HourRange', 'data' => ['from' => '9', 'to' => '17']])],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO mcp_rule_set_rules (id, rule_set_id, `order`, json_rule)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rules as $rule) {
            $stmt->execute($rule);
        }

        // Insert test ACLs
        $acls = [
            [1, 'and', 'Geo:Country', '==', 'US', 0],
            [1, 'and', 'Time:HourOfDay', '>=', '9', 1],
            [1, 'and', 'Time:HourOfDay', '<=', '17', 2],
            [2, 'and', 'Client:Browser', '==', 'Chrome', 0],
        ];

        $stmt = self::$pdo->prepare("
            INSERT INTO acls (bannerid, logical, type, comparison, data, executionorder)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($acls as $acl) {
            $stmt->execute($acl);
        }
    }

    public static function truncateAll(): void
    {
        $pdo = self::getPdo();
        
        $tables = ['acls', 'mcp_rule_set_rules', 'mcp_rule_sets', 'banners', 'campaigns', 'zones'];
        
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM {$table}");
            $pdo->exec("DELETE FROM sqlite_sequence WHERE name='{$table}'");
        }
        
        self::seedData();
    }
}