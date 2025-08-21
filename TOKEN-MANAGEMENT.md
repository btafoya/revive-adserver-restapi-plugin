# API Token Management System

Complete token management system for Revive Adserver REST API Plugin with admin panel integration, database storage, and middleware validation.

## 🔑 Features

- **Secure Token Generation**: Cryptographically secure API tokens with SHA-256 hashing
- **Permission System**: Granular permissions for different API operations
- **Token Lifecycle**: Creation, validation, expiration, and deactivation
- **Admin Panel**: Web-based interface for token management
- **Usage Analytics**: Track token usage with detailed logging
- **Rate Limiting**: Configurable limits and security controls
- **Session + Token Auth**: Dual authentication methods

## 🏗️ Architecture

### Database Schema

#### `api_tokens` Table
```sql
- id (PRIMARY KEY)
- token_hash (UNIQUE, SHA-256 of actual token)
- name (Human-readable token name)
- user_id (Associated Revive user)
- permissions (JSON array of permissions)
- expires_at (Expiration timestamp)
- created_at, last_used_at, is_active
- created_by (Creator user ID)
```

#### `api_token_usage` Table
```sql
- id (PRIMARY KEY)
- token_id (Foreign key to api_tokens)
- endpoint, method, ip_address
- user_agent, response_status
- used_at (Usage timestamp)
```

#### `api_settings` Table
```sql
- setting_key, setting_value
- API configuration (auth required, rate limits, etc.)
```

### Components

```
src/
├── Middleware/
│   └── AuthenticationMiddleware.php    # Token validation & session auth
├── Services/
│   └── TokenService.php               # Token CRUD operations
├── Controllers/
│   └── ApiTokensController.php        # REST API endpoints
└── Support/
    └── ReviveConfig.php               # Database & settings management

www/admin/
└── index.php                          # Admin panel interface
```

## 🔐 Authentication Methods

### 1. Session-Based Authentication
For users logged into Revive admin panel:
```bash
# Automatically authenticated when logged into Revive admin
curl -X GET http://yoursite.com/api/v1/campaigns
```

### 2. Token-Based Authentication

#### Authorization Header (Recommended)
```bash
curl -X GET http://yoursite.com/api/v1/campaigns \
  -H "Authorization: Bearer rapi_YourTokenHere"
```

#### Custom Header
```bash
curl -X GET http://yoursite.com/api/v1/campaigns \
  -H "X-API-Token: rapi_YourTokenHere"
```

#### Query Parameter
```bash
curl -X GET "http://yoursite.com/api/v1/campaigns?api_token=rapi_YourTokenHere"
```

## 🎛️ Admin Panel

Access the admin panel at: `http://yoursite.com/plugins/reviveRestApi/www/admin/`

### Features
- **Token Management**: Create, list, and delete API tokens
- **Permission Control**: Assign granular permissions to tokens
- **Usage Analytics**: View token usage statistics
- **Settings Configuration**: Configure API settings and limits
- **Security Controls**: Rate limiting and authentication requirements

### Token Creation
1. Log into Revive admin panel
2. Navigate to API Token Management
3. Click "Create New Token"
4. Enter token name and select permissions
5. Save token securely (shown only once)

## 📡 API Endpoints

### Token Management
```bash
# List tokens (admin or own tokens)
GET /api/v1/tokens

# Get specific token details
GET /api/v1/tokens/{id}

# Create new token
POST /api/v1/tokens
{
  "name": "My API Token",
  "permissions": ["campaigns.read", "banners.write"]
}

# Update token
PUT /api/v1/tokens/{id}
{
  "name": "Updated Name",
  "permissions": ["all"]
}

# Delete token
DELETE /api/v1/tokens/{id}
```

### Admin Settings
```bash
# Get API settings (admin only)
GET /api/v1/admin/settings

# Update settings (admin only)
PUT /api/v1/admin/settings
{
  "api_enabled": "1",
  "require_authentication": "1",
  "rate_limit_per_minute": "100"
}

# Clean up expired tokens (admin only)
POST /api/v1/admin/cleanup
```

## 🔒 Permission System

### Available Permissions
- `campaigns.read` - Read campaign data
- `campaigns.write` - Create/update campaigns
- `campaigns.delete` - Delete campaigns
- `banners.read` - Read banner data
- `banners.write` - Create/update banners
- `banners.delete` - Delete banners
- `banners.upload` - Upload banner files
- `zones.read` - Read zone data
- `zones.write` - Create/update zones
- `zones.delete` - Delete zones
- `targeting.read` - Read targeting rules
- `targeting.write` - Modify targeting rules
- `rulesets.read` - Read rule sets
- `rulesets.write` - Create/update rule sets
- `rulesets.delete` - Delete rule sets
- `rulesets.apply` - Apply rule sets to banners
- `stats.read` - Access statistics
- `all` - Full API access

### Usage Example
```json
{
  "name": "Campaign Manager Token",
  "permissions": [
    "campaigns.read",
    "campaigns.write",
    "banners.read",
    "stats.read"
  ]
}
```

## ⚙️ Configuration

### API Settings
- **api_enabled**: Enable/disable REST API
- **require_authentication**: Force authentication for all endpoints
- **rate_limit_per_minute**: Requests per minute per IP
- **token_expiry_days**: Default token expiration (90 days)
- **max_tokens_per_user**: Maximum tokens per user (5)

### Security Features
- **Token Hashing**: Tokens stored as SHA-256 hashes
- **Secure Generation**: Cryptographically secure random tokens
- **Expiration**: Automatic token expiration
- **Usage Logging**: Complete audit trail
- **Rate Limiting**: IP-based request limits
- **Permission Validation**: Granular access control

## 🧪 Testing

Run authentication tests:
```bash
# Run token authentication tests
./vendor/bin/phpunit tests/Integration/Authentication/TokenAuthenticationTest.php

# Test specific functionality
./vendor/bin/phpunit --filter testTokenGeneration
./vendor/bin/phpunit --filter testAuthenticationMiddleware
```

## 🚀 Installation

1. **Database Setup**: Tables created automatically during plugin installation
2. **Admin Access**: Navigate to admin panel at `/plugins/reviveRestApi/www/admin/`
3. **Create Tokens**: Use admin panel to generate API tokens
4. **Test API**: Use tokens with Authorization header

## 💡 Usage Examples

### Create a Token via API
```bash
curl -X POST http://yoursite.com/api/v1/tokens \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Campaign Analytics",
    "permissions": ["campaigns.read", "stats.read"]
  }'
```

### Use Token for API Calls
```bash
# Get campaigns
curl -X GET http://yoursite.com/api/v1/campaigns \
  -H "Authorization: Bearer rapi_NewTokenHere"

# Upload banner
curl -X POST http://yoursite.com/api/v1/banners/1/upload \
  -H "Authorization: Bearer rapi_NewTokenHere" \
  -F "file=@banner.jpg"
```

### Check Token Usage
```bash
curl -X GET http://yoursite.com/api/v1/tokens/1 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

## 🔧 Troubleshooting

### Common Issues

1. **401 Unauthorized**
   - Check if authentication is required (`require_authentication` setting)
   - Verify token format and Authorization header
   - Ensure token is active and not expired

2. **403 Forbidden**
   - Check token permissions for the specific endpoint
   - Verify user has access to modify/view the token

3. **Token Not Working**
   - Verify token hasn't expired
   - Check if token is active (`is_active = 1`)
   - Ensure correct token format (`rapi_` prefix)

4. **Admin Panel Access**
   - Must be logged into Revive admin panel first
   - Check file permissions on admin directory
   - Verify database tables were created

### Debug Steps
1. Check API settings: `GET /api/v1/admin/settings`
2. Verify token in database: Check `api_tokens` table
3. Review usage logs: Check `api_token_usage` table
4. Test with session auth: Log into admin panel first

## 📊 Migration

For existing installations, the token system is automatically added during plugin upgrade. Existing API endpoints will continue to work with session authentication until tokens are configured.

## 🔄 Maintenance

### Regular Tasks
- **Cleanup Expired Tokens**: Use `/api/v1/admin/cleanup` endpoint
- **Review Usage**: Monitor `api_token_usage` table
- **Update Settings**: Adjust rate limits and expiration as needed
- **Security Audit**: Regular review of active tokens and permissions

### Best Practices
- **Rotate Tokens**: Regular token rotation for security
- **Minimal Permissions**: Grant only necessary permissions
- **Monitor Usage**: Track unusual usage patterns
- **Secure Storage**: Store tokens securely on client side
- **Name Tokens**: Use descriptive names for token identification