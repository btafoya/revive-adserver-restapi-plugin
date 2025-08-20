<?php
$conf = $GLOBALS['_MAX']['CONF']['database'];
$pdo  = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $conf['host'], $conf['name']), $conf['username'], $conf['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

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
