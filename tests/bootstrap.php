<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment constants
if (!defined('OA_ENVIRONMENT')) {
    define('OA_ENVIRONMENT', 'test');
}

// Mock Revive constants if not defined
if (!defined('MAX_PATH')) {
    define('MAX_PATH', __DIR__ . '/../');
}

if (!defined('OA_UPGRADE_LOGIN')) {
    define('OA_UPGRADE_LOGIN', false);
}

// Initialize Mockery
\Mockery::globalHelpers();

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');