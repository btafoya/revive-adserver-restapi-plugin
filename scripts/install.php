<?php
$conf = $GLOBALS['_MAX']['CONF']['database'];
$pdo  = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $conf['host'], $conf['name']), $conf['username'], $conf['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

// Create existing tables
$pdo->exec("CREATE TABLE IF NOT EXISTS mcp_rule_sets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(190) NOT NULL,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mcp_rule_sets_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS mcp_rule_set_rules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rule_set_id BIGINT UNSIGNED NOT NULL,
  `order` INT NOT NULL DEFAULT 1,
  json_rule JSON NOT NULL,
  PRIMARY KEY (id),
  KEY fk_mcp_rule_set_rules_set (rule_set_id),
  CONSTRAINT fk_mcp_rule_set_rules_set FOREIGN KEY (rule_set_id) REFERENCES mcp_rule_sets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create API token management tables
$pdo->exec("CREATE TABLE IF NOT EXISTS api_tokens (
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
    UNIQUE KEY token_hash (token_hash),
    KEY user_id (user_id),
    KEY active_tokens (is_active, expires_at),
    KEY created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$pdo->exec("CREATE TABLE IF NOT EXISTS api_token_usage (
    id int(11) NOT NULL AUTO_INCREMENT,
    token_id int(11) NOT NULL,
    endpoint varchar(255) NOT NULL,
    method varchar(10) NOT NULL,
    ip_address varchar(45) NOT NULL,
    user_agent text DEFAULT NULL,
    response_status int(3) DEFAULT NULL,
    used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY token_id (token_id),
    KEY endpoint_method (endpoint, method),
    KEY used_at (used_at),
    FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$pdo->exec("CREATE TABLE IF NOT EXISTS api_settings (
    id int(11) NOT NULL AUTO_INCREMENT,
    setting_key varchar(100) NOT NULL,
    setting_value text DEFAULT NULL,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by int(11) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Insert default API settings
$pdo->exec("INSERT IGNORE INTO api_settings (setting_key, setting_value) VALUES
('api_enabled', '1'),
('require_authentication', '1'),
('rate_limit_per_minute', '100'),
('token_expiry_days', '90'),
('max_tokens_per_user', '5');");
