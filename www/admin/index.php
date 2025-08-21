<?php
/**
 * Admin Panel for API Token Management
 * Revive Adserver REST API Plugin
 */

// Basic security check
if (!isset($_SESSION) || empty($_SESSION['user'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in to Revive admin
if (empty($_SESSION['user']) && empty($GLOBALS['session']['user'])) {
    http_response_code(401);
    echo '<h1>Access Denied</h1><p>Please log in to Revive Adserver admin panel first.</p>';
    exit;
}

$root = realpath(__DIR__ . '/../..');
require_once $root . '/src/Services/TokenService.php';
require_once $root . '/src/Support/ReviveConfig.php';

use App\Services\TokenService;
use App\Support\ReviveConfig;

$tokenService = new TokenService();
$config = new ReviveConfig();

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'list_tokens':
            $tokens = $tokenService->listTokens();
            echo json_encode(['success' => true, 'data' => $tokens]);
            exit;
            
        case 'create_token':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $input['created_by'] = $_SESSION['user']['user_id'] ?? null;
                $result = $tokenService->generateToken($input);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid input']);
            }
            exit;
            
        case 'delete_token':
            $tokenId = (int) ($_GET['token_id'] ?? 0);
            if ($tokenId) {
                $success = $tokenService->deleteToken($tokenId);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Token ID required']);
            }
            exit;
            
        case 'get_settings':
            $settings = $tokenService->getApiSettings();
            echo json_encode(['success' => true, 'data' => $settings]);
            exit;
            
        case 'update_settings':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $success = $tokenService->updateApiSettings($input);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid input']);
            }
            exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Token Management - Revive Adserver</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #2c5aa0;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            color: #495057;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            color: #2c5aa0;
            border-bottom-color: #2c5aa0;
            background: white;
        }
        .tab-content {
            display: none;
            padding: 20px;
        }
        .tab-content.active {
            display: block;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
        }
        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }
        .permission-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .permission-item {
            display: flex;
            align-items: center;
        }
        .permission-item input {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>API Token Management</h1>
            <p>Manage API tokens for Revive Adserver REST API</p>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('tokens')">API Tokens</button>
            <button class="tab" onclick="showTab('settings')">Settings</button>
        </div>
        
        <!-- Tokens Tab -->
        <div id="tokens-tab" class="tab-content active">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                <h2>API Tokens</h2>
                <button class="btn btn-primary" onclick="showCreateTokenModal()">Create New Token</button>
            </div>
            
            <div id="tokens-alert"></div>
            
            <table class="table" id="tokens-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tokens-tbody">
                    <tr>
                        <td colspan="6" style="text-align: center;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Settings Tab -->
        <div id="settings-tab" class="tab-content">
            <h2>API Settings</h2>
            <div id="settings-alert"></div>
            
            <form id="settings-form">
                <div class="form-group">
                    <label for="api_enabled">
                        <input type="checkbox" id="api_enabled" name="api_enabled"> Enable REST API
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="require_authentication">
                        <input type="checkbox" id="require_authentication" name="require_authentication"> Require Authentication
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="rate_limit_per_minute">Rate Limit (requests per minute)</label>
                    <input type="number" id="rate_limit_per_minute" name="rate_limit_per_minute" class="form-control" min="1" max="1000">
                </div>
                
                <div class="form-group">
                    <label for="token_expiry_days">Token Expiry (days)</label>
                    <input type="number" id="token_expiry_days" name="token_expiry_days" class="form-control" min="1" max="365">
                </div>
                
                <div class="form-group">
                    <label for="max_tokens_per_user">Max Tokens Per User</label>
                    <input type="number" id="max_tokens_per_user" name="max_tokens_per_user" class="form-control" min="1" max="50">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
    
    <!-- Create Token Modal -->
    <div id="create-token-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideCreateTokenModal()">&times;</span>
            <h3>Create New API Token</h3>
            <div id="create-token-alert"></div>
            
            <form id="create-token-form">
                <div class="form-group">
                    <label for="token_name">Token Name *</label>
                    <input type="text" id="token_name" name="name" class="form-control" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label>Permissions</label>
                    <div class="permission-list">
                        <div class="permission-item">
                            <input type="checkbox" id="perm_all" name="permissions[]" value="all">
                            <label for="perm_all">All Permissions</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_campaigns_read" name="permissions[]" value="campaigns.read">
                            <label for="perm_campaigns_read">Campaigns: Read</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_campaigns_write" name="permissions[]" value="campaigns.write">
                            <label for="perm_campaigns_write">Campaigns: Write</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_banners_read" name="permissions[]" value="banners.read">
                            <label for="perm_banners_read">Banners: Read</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_banners_write" name="permissions[]" value="banners.write">
                            <label for="perm_banners_write">Banners: Write</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="perm_stats_read" name="permissions[]" value="stats.read">
                            <label for="perm_stats_read">Statistics: Read</label>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Create Token</button>
                    <button type="button" class="btn btn-secondary" onclick="hideCreateTokenModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
            
            if (tabName === 'tokens') {
                loadTokens();
            } else if (tabName === 'settings') {
                loadSettings();
            }
        }
        
        // Load tokens
        function loadTokens() {
            fetch('?action=list_tokens')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('tokens-tbody');
                    if (data.success) {
                        tbody.innerHTML = data.data.map(token => `
                            <tr>
                                <td>${token.name}</td>
                                <td>${new Date(token.created_at).toLocaleDateString()}</td>
                                <td>${token.last_used_at ? new Date(token.last_used_at).toLocaleDateString() : 'Never'}</td>
                                <td>${token.expires_at ? new Date(token.expires_at).toLocaleDateString() : 'Never'}</td>
                                <td>
                                    ${token.is_active == 1 && !token.is_expired ? 
                                        '<span class="badge badge-success">Active</span>' : 
                                        '<span class="badge badge-danger">Inactive</span>'}
                                </td>
                                <td>
                                    <button class="btn btn-danger" onclick="deleteToken(${token.id})">Delete</button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6">Failed to load tokens</td></tr>';
                    }
                })
                .catch(error => {
                    document.getElementById('tokens-tbody').innerHTML = '<tr><td colspan="6">Error loading tokens</td></tr>';
                });
        }
        
        // Load settings
        function loadSettings() {
            fetch('?action=get_settings')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const settings = data.data;
                        document.getElementById('api_enabled').checked = settings.api_enabled === '1';
                        document.getElementById('require_authentication').checked = settings.require_authentication === '1';
                        document.getElementById('rate_limit_per_minute').value = settings.rate_limit_per_minute || 100;
                        document.getElementById('token_expiry_days').value = settings.token_expiry_days || 90;
                        document.getElementById('max_tokens_per_user').value = settings.max_tokens_per_user || 5;
                    }
                });
        }
        
        // Show create token modal
        function showCreateTokenModal() {
            document.getElementById('create-token-modal').style.display = 'block';
        }
        
        // Hide create token modal
        function hideCreateTokenModal() {
            document.getElementById('create-token-modal').style.display = 'none';
            document.getElementById('create-token-form').reset();
            document.getElementById('create-token-alert').innerHTML = '';
        }
        
        // Create token form submission
        document.getElementById('create-token-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const permissions = Array.from(formData.getAll('permissions[]'));
            
            const tokenData = {
                name: formData.get('name'),
                permissions: permissions
            };
            
            fetch('?action=create_token', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(tokenData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('create-token-alert').innerHTML = `
                        <div class="alert alert-success">
                            <strong>Token Created Successfully!</strong><br>
                            <strong>Token:</strong> <code>${data.data.token}</code><br>
                            <small>Save this token securely - it will not be shown again.</small>
                        </div>
                    `;
                    setTimeout(() => {
                        hideCreateTokenModal();
                        loadTokens();
                    }, 5000);
                } else {
                    document.getElementById('create-token-alert').innerHTML = `
                        <div class="alert alert-danger">Failed to create token: ${data.error}</div>
                    `;
                }
            });
        });
        
        // Settings form submission
        document.getElementById('settings-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const settings = {
                api_enabled: document.getElementById('api_enabled').checked ? '1' : '0',
                require_authentication: document.getElementById('require_authentication').checked ? '1' : '0',
                rate_limit_per_minute: formData.get('rate_limit_per_minute'),
                token_expiry_days: formData.get('token_expiry_days'),
                max_tokens_per_user: formData.get('max_tokens_per_user')
            };
            
            fetch('?action=update_settings', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(settings)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('settings-alert').innerHTML = `
                        <div class="alert alert-success">Settings updated successfully!</div>
                    `;
                } else {
                    document.getElementById('settings-alert').innerHTML = `
                        <div class="alert alert-danger">Failed to update settings</div>
                    `;
                }
            });
        });
        
        // Delete token
        function deleteToken(tokenId) {
            if (confirm('Are you sure you want to delete this token? This action cannot be undone.')) {
                fetch(`?action=delete_token&token_id=${tokenId}`, {method: 'POST'})
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadTokens();
                        } else {
                            alert('Failed to delete token');
                        }
                    });
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadTokens();
        });
    </script>
</body>
</html>