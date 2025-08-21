<?php
// Add to routes.php as needed:
$router->post('/api/v1/banners/{bannerId:\d+}/file', [\App\Controllers\UploadsController::class, 'attachToBanner']);
$router->post('/api/v1/targeting/validate', [\App\Controllers\TargetingController::class, 'validate']);

// Rule sets (CRUD/preview/export/import/apply)
$router->get ('/api/v1/rule-sets', [\App\Controllers\RuleSetsController::class, 'index']);
$router->get ('/api/v1/rule-sets/{id:\d+}', [\App\Controllers\RuleSetsController::class, 'show']);
$router->post('/api/v1/rule-sets', [\App\Controllers\RuleSetsController::class, 'create']);
$router->put ('/api/v1/rule-sets/{id:\d+}', [\App\Controllers\RuleSetsController::class, 'update']);
$router->delete('/api/v1/rule-sets/{id:\d+}', [\App\Controllers\RuleSetsController::class, 'delete']);
$router->get ('/api/v1/rule-sets/{id:\d+}/preview', [\App\Controllers\RuleSetsController::class, 'preview'] ?? null);
$router->get ('/api/v1/rule-sets/{id:\d+}/export',  [\App\Controllers\RuleSetsController::class, 'export'] ?? null);
$router->post('/api/v1/rule-sets/import',           [\App\Controllers\RuleSetsController::class, 'import'] ?? null);
$router->post('/api/v1/rule-sets/{id:\d+}/apply',  [\App\Controllers\RuleSetsController::class, 'apply'] ?? null);

// Ad-hoc multi-banner apply
$router->post('/api/v1/banners/apply', [\App\Controllers\BannersApplyController::class, 'apply']);

// Schema + Variables helper
$router->get ('/api/v1/targeting/schema', [\App\Controllers\TargetingSchemaController::class, 'schema']);
$router->post('/api/v1/variables/site/format', [\App\Controllers\VariablesController::class, 'formatSite']);

// API Token Management
$router->get   ('/api/v1/tokens',           [\App\Controllers\ApiTokensController::class, 'index']);
$router->get   ('/api/v1/tokens/{id:\d+}', [\App\Controllers\ApiTokensController::class, 'show']);
$router->post  ('/api/v1/tokens',           [\App\Controllers\ApiTokensController::class, 'create']);
$router->put   ('/api/v1/tokens/{id:\d+}', [\App\Controllers\ApiTokensController::class, 'update']);
$router->delete('/api/v1/tokens/{id:\d+}', [\App\Controllers\ApiTokensController::class, 'delete']);

// API Settings & Administration
$router->get   ('/api/v1/admin/settings',  [\App\Controllers\ApiTokensController::class, 'settings']);
$router->put   ('/api/v1/admin/settings',  [\App\Controllers\ApiTokensController::class, 'updateSettings']);
$router->post  ('/api/v1/admin/cleanup',   [\App\Controllers\ApiTokensController::class, 'cleanup']);
