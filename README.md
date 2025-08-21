# Revive Adserver REST API Plugin

[![Release](https://img.shields.io/badge/release-v1.0.8-2ea44f.svg)](https://github.com/btafoya/revive-adserver-restapi-plugin/releases/latest)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](./LICENSE)
[![Revive](https://img.shields.io/badge/Revive-5.x-ff69b4.svg)](https://www.revive-adserver.com/)

A comprehensive REST API plugin for Revive Adserver that provides RESTful endpoints for managing campaigns, banners, zones, targeting, and statistics. Features a complete API token management system with admin panel interface for secure authentication and granular permissions. Fully compatible with the [revive-adserver-mcp](https://github.com/btafoya/revive-adserver-mcp) server.

## Features

### Core API Functionality
- ✅ **Campaign Management** - Complete CRUD operations for advertising campaigns
- ✅ **Banner Management** - Upload, manage, and configure banner advertisements  
- ✅ **Zone Configuration** - Configure and manage advertising zones and placements
- ✅ **Advanced Targeting** - Geographic, time-based, device, and URL targeting with rule sets
- ✅ **Statistics & Analytics** - Comprehensive performance reporting and metrics
- ✅ **File Upload System** - Secure banner file uploads with validation

### Security & Authentication
- ✅ **API Token Management** - Complete token-based authentication system
- ✅ **Admin Panel** - Web-based interface for token and settings management
- ✅ **Permission System** - Granular permissions (16 different access levels)
- ✅ **Session Authentication** - Integration with existing Revive admin sessions
- ✅ **Rate Limiting** - Configurable per-IP request limits and security controls
- ✅ **Usage Analytics** - Complete audit trail and token usage monitoring

### Integration & Compatibility
- ✅ **MCP Compatible** - Full integration with [revive-adserver-mcp](https://github.com/btafoya/revive-adserver-mcp)
- ✅ **CORS Support** - Cross-origin request handling for web applications
- ✅ **REST Standards** - RESTful API design with consistent JSON responses
- ✅ **Revive Integration** - Native integration with Revive Adserver 5.x

## Requirements

- Revive Adserver 5.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+

## Installation

### Recommended: Plugin Installer (Easy)

The easiest way to install this plugin is using the Revive Adserver built-in plugin installer:

1. **Download the Plugin Package**
   - Go to the [Releases page](https://github.com/btafoya/revive-adserver-restapi-plugin/releases)
   - Download the latest `reviveRestApi-X.X.X.zip` file

2. **Access Plugin Installer**
   - Log into your Revive Adserver admin panel
   - Navigate to **Plugins** → **Plugin Administration**

3. **Install Plugin**
   - Click the **"Install Plugin"** button
   - Choose **"Upload a plugin"**
   - Select the downloaded `reviveRestApi-X.X.X.zip` file
   - Click **"Upload"** to install

4. **Activate Plugin**
   - After upload, find "Revive REST API (MCP Compatible)" in the plugin list
   - Click **"Install"** to activate the plugin
   - The plugin status should change to "Enabled"

5. **Configure Settings**
   - Click **"Settings"** next to the plugin
   - Configure the **API Base Path** (default: `/api/v1`)
   - Save your settings

6. **Verify Installation**
   - Test the API health endpoint: `http://yoursite.com/api/v1/health`
   - You should receive a JSON response confirming the API is working

7. **Access Token Management**
   - Navigate to: `http://yoursite.com/plugins/reviveRestApi/www/admin/`
   - Create and manage API tokens through the web interface
   - Configure API settings and security controls

### Alternative: Manual Installation

If you prefer manual installation:

1. **Download Plugin Files**
   - Download the latest release ZIP file
   - Extract the contents

2. **Upload to Server**
   - Upload the `reviveRestApi` folder to: `/path/to/revive/plugins/`
   - Ensure proper file permissions (readable by web server)

3. **Install via Admin Panel**
   - Access your Revive admin panel
   - Navigate to **Plugins** → **Plugin Administration**
   - Find "Revive REST API (MCP Compatible)" and click **Install**
   - Configure plugin settings as needed

### Docker Installation

If using the Docker setup from this repository:

```bash
# Plugin is already included in the container
docker-compose up -d
```

### Troubleshooting Installation

**Plugin Not Showing Up:**
- Verify the ZIP file was uploaded correctly
- Check file permissions on the plugins directory
- Ensure Revive Adserver version compatibility (5.0+)

**Upload Fails:**
- Check PHP upload limits (`upload_max_filesize`, `post_max_size`)
- Verify disk space on server
- Ensure web server has write permissions to plugins directory

**API Not Working After Install:**
- Clear any caches (browser, CDN, server-side)
- Check web server URL rewriting is enabled
- Verify no conflicting plugins or .htaccess rules

## Admin Panel

### Access the Admin Interface
Navigate to: `http://yoursite.com/plugins/reviveRestApi/www/admin/`

**Requirements**: Must be logged into Revive Adserver admin panel first

### Token Management Features
- **Create API Tokens**: Generate secure tokens with custom permissions
- **Token Overview**: View all tokens with usage statistics and status
- **Permission Control**: Assign granular permissions (campaigns, banners, zones, etc.)
- **Usage Analytics**: Monitor token usage patterns and API activity
- **Token Lifecycle**: Activate, deactivate, and delete tokens as needed

### API Settings Configuration
- **Enable/Disable API**: Global API on/off control
- **Authentication Requirements**: Force authentication for all endpoints
- **Rate Limiting**: Configure requests per minute (default: 100)
- **Token Expiration**: Set default token expiry (default: 90 days)
- **User Token Limits**: Maximum tokens per user (default: 5)

### Security Controls
- **Token Monitoring**: Real-time usage tracking and alerts
- **IP Restrictions**: Monitor and control access by IP address
- **Audit Trail**: Complete log of all API token activities
- **Permission Management**: Fine-grained access control system

## Configuration

Plugin settings can be configured through:

1. **Admin Panel Interface** (Recommended): 
   - Navigate to `/plugins/reviveRestApi/www/admin/`
   - Use the Settings tab for configuration

2. **Revive Plugin Settings**:
   - **Plugins** → **Plugin Administration** → **Settings**
   - Configure the **API Base Path** (default: `/api/v1`)

## API Documentation

### Base URL
```
/api/v1
```

### Available Endpoints

#### Health Check
```http
GET /api/v1/health
```

#### Campaigns
```http
GET    /api/v1/campaigns           # List campaigns
POST   /api/v1/campaigns           # Create campaign
GET    /api/v1/campaigns/{id}      # Get campaign details
PUT    /api/v1/campaigns/{id}      # Update campaign
DELETE /api/v1/campaigns/{id}      # Delete campaign
```

#### Banners
```http
GET    /api/v1/banners             # List banners
POST   /api/v1/banners             # Create banner
GET    /api/v1/banners/{id}        # Get banner details
PUT    /api/v1/banners/{id}        # Update banner
DELETE /api/v1/banners/{id}        # Delete banner
POST   /api/v1/banners/{id}/upload # Upload banner file
```

#### Zones
```http
GET    /api/v1/zones               # List zones
POST   /api/v1/zones               # Create zone
GET    /api/v1/zones/{id}          # Get zone details
PUT    /api/v1/zones/{id}          # Update zone
DELETE /api/v1/zones/{id}          # Delete zone
```

#### Targeting
```http
GET /api/v1/campaigns/{id}/targeting   # Get campaign targeting
PUT /api/v1/campaigns/{id}/targeting   # Update campaign targeting
GET /api/v1/banners/{id}/targeting     # Get banner targeting
PUT /api/v1/banners/{id}/targeting     # Update banner targeting
```

#### Statistics
```http
GET /api/v1/stats/campaigns            # Campaign statistics
GET /api/v1/stats/campaigns/{id}       # Specific campaign stats
GET /api/v1/stats/banners              # Banner statistics  
GET /api/v1/stats/banners/{id}         # Specific banner stats
GET /api/v1/stats/zones                # Zone statistics
GET /api/v1/stats/zones/{id}           # Specific zone stats
```

## Authentication

The API supports two authentication methods with a complete token management system:

### Session-Based Authentication
For users logged into the Revive admin interface, API requests will automatically use the existing session.

### Token-Based Authentication
The plugin includes a complete API token management system with admin panel integration.

#### Creating API Tokens
1. Log into your Revive Adserver admin panel
2. Navigate to: `/plugins/reviveRestApi/www/admin/`
3. Click "Create New Token"
4. Enter token name and select permissions
5. Save the generated token securely (shown only once)

#### Using API Tokens
Include your API token in one of these ways:

**Authorization Header (Recommended):**
```bash
Authorization: Bearer rapi_YourTokenHere
```

**Custom Header:**
```bash
X-API-Token: rapi_YourTokenHere
```

**Query Parameter:**
```bash
/api/v1/campaigns?api_token=rapi_YourTokenHere
```

#### Token Management Features
- **Secure Generation**: Cryptographically secure tokens with SHA-256 hashing
- **Permission System**: Granular permissions (campaigns.read, banners.write, etc.)
- **Expiration Control**: Configurable token expiration (default: 90 days)
- **Usage Analytics**: Track token usage with detailed logging
- **Admin Panel**: Web-based interface for token management
- **Rate Limiting**: Configurable per-IP request limits

#### API Token Endpoints
```bash
# List your tokens
GET /api/v1/tokens

# Create new token
POST /api/v1/tokens
{
  "name": "My API Token",
  "permissions": ["campaigns.read", "stats.read"]
}

# Delete token
DELETE /api/v1/tokens/{id}
```

#### Available Permissions
- `campaigns.read/write/delete` - Campaign management
- `banners.read/write/delete/upload` - Banner management  
- `zones.read/write/delete` - Zone management
- `targeting.read/write` - Targeting rules
- `rulesets.read/write/delete/apply` - Rule set management
- `stats.read` - Statistics access
- `all` - Full API access

For complete token management documentation, see [TOKEN-MANAGEMENT.md](TOKEN-MANAGEMENT.md).

## Usage Examples

### Create a Campaign
```bash
curl -X POST http://yoursite.com/api/v1/campaigns \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer rapi_YourTokenHere" \
  -d '{
    "campaignname": "Summer Sale 2024",
    "clientid": 1,
    "views": 100000,
    "clicks": 5000,
    "revenue": 2500.00,
    "status": 0
  }'
```

### Upload a Banner
```bash
curl -X POST http://yoursite.com/api/v1/banners/1/upload \
  -H "Authorization: Bearer rapi_YourTokenHere" \
  -F "file=@banner-300x250.jpg"
```

### Set Geographic Targeting
```bash
curl -X PUT http://yoursite.com/api/v1/campaigns/1/targeting \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer rapi_YourTokenHere" \
  -d '{
    "geo": {
      "countries": ["US", "CA", "UK"],
      "regions": ["California", "New York", "London"]
    },
    "time": {
      "hour_from": 9,
      "hour_to": 17,
      "days": [1, 2, 3, 4, 5]
    }
  }'
```

### Create API Token
```bash
curl -X POST http://yoursite.com/api/v1/tokens \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer rapi_AdminTokenHere" \
  -d '{
    "name": "Campaign Manager",
    "permissions": ["campaigns.read", "campaigns.write", "stats.read"]
  }'
```

### Manage API Tokens
```bash
# List all tokens
curl -X GET http://yoursite.com/api/v1/tokens \
  -H "Authorization: Bearer rapi_YourTokenHere"

# Get token details with usage stats
curl -X GET http://yoursite.com/api/v1/tokens/1 \
  -H "Authorization: Bearer rapi_YourTokenHere"

# Delete a token
curl -X DELETE http://yoursite.com/api/v1/tokens/1 \
  -H "Authorization: Bearer rapi_YourTokenHere"
```

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 100,
    "limit": 20,
    "offset": 0,
    "page": 1,
    "pages": 5
  }
}
```

### Error Response
```json
{
  "error": "Error message",
  "code": 400,
  "timestamp": "2024-08-20T14:30:00+00:00"
}
```

## MCP Server Compatibility

This plugin is designed to work seamlessly with the [revive-adserver-mcp](https://github.com/btafoya/revive-adserver-mcp) Model Context Protocol server, enabling natural language interactions with your ad server through Claude Code.

## Security Features

### Authentication & Authorization
- **Dual Authentication**: Session-based and token-based authentication methods
- **API Token System**: Cryptographically secure tokens with SHA-256 hashing
- **Permission System**: 16 granular permissions (campaigns.read, banners.write, etc.)
- **Token Lifecycle**: Secure generation, validation, expiration, and revocation
- **Admin Panel Access**: Secure web interface with session-based authentication

### Security Controls
- **Rate Limiting**: Configurable per-IP request limits (default: 100/minute)
- **Token Expiration**: Automatic token expiration (configurable, default: 90 days)
- **Usage Monitoring**: Complete audit trail of all API token usage
- **Input Validation**: XSS and SQL injection protection across all endpoints
- **Secure Storage**: Tokens stored as SHA-256 hashes, never in plain text

### Advanced Security
- **CORS Management**: Configurable cross-origin resource sharing
- **IP Tracking**: Monitor and log all API requests by IP address
- **User Agent Logging**: Track client applications accessing the API
- **Security Event Logging**: Comprehensive security event monitoring
- **Failed Authentication Tracking**: Monitor and alert on authentication failures

## Troubleshooting

### Common API Issues

#### 401 Unauthorized Error
**Symptoms**: API returns 401 status code
**Causes & Solutions**:
- **Authentication disabled**: Check if `require_authentication` is set to `0` in API settings
- **Invalid token format**: Ensure token starts with `rapi_` and is properly formatted
- **Expired token**: Check token expiration date in admin panel
- **Inactive token**: Verify token is active (`is_active = 1`) in admin panel

#### 403 Forbidden Error
**Symptoms**: API returns 403 status code
**Causes & Solutions**:
- **Insufficient permissions**: Check token permissions match required endpoint access
- **Missing permission**: Add required permission (e.g., `campaigns.read`) to token
- **Token ownership**: Ensure user has access to view/modify the requested token

#### Token Not Working
**Symptoms**: Valid token rejected by API
**Diagnostic Steps**:
1. **Verify token format**: Should start with `rapi_` prefix
2. **Check expiration**: View token details in admin panel
3. **Confirm permissions**: Ensure token has required permissions for endpoint
4. **Test with admin session**: Try the same endpoint while logged into admin panel
5. **Check API settings**: Verify API is enabled and authentication configured correctly

#### Admin Panel Access Issues
**Symptoms**: Cannot access `/plugins/reviveRestApi/www/admin/`
**Solutions**:
- **Login required**: Must be logged into Revive admin panel first
- **File permissions**: Check web server read permissions on admin directory
- **URL path**: Verify correct plugin path based on your installation
- **Database tables**: Ensure token management tables were created during installation

### Debug Steps

1. **Test API Health**: `GET /api/v1/health` should return JSON status
2. **Check Database**: Verify `api_tokens`, `api_token_usage`, and `api_settings` tables exist
3. **Review Logs**: Check web server error logs for PHP errors
4. **Token Validation**: Use admin panel to view token status and usage
5. **Permission Test**: Try endpoint with session authentication (logged into admin)

### Getting Help

For token-related issues:
1. Check token status in admin panel (`/plugins/reviveRestApi/www/admin/`)
2. Review [TOKEN-MANAGEMENT.md](TOKEN-MANAGEMENT.md) for detailed documentation
3. Test with different permission combinations
4. Monitor usage logs for authentication patterns

## Support

For issues, questions, or contributions:

1. Check the documentation
2. Review existing issues
3. Create a new issue with detailed information

## License

This plugin is released under the GPL-2.0+ license, compatible with Revive Adserver's licensing.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
