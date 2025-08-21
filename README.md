# Revive Adserver REST API Plugin

[![Release](https://img.shields.io/badge/release-v1.0.6-2ea44f.svg)](https://github.com/btafoya/revive-adserver-restapi-plugin/releases/latest)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](./LICENSE)
[![Revive](https://img.shields.io/badge/Revive-5.x-ff69b4.svg)](https://www.revive-adserver.com/)

A comprehensive REST API plugin for Revive Adserver that provides RESTful endpoints for managing campaigns, banners, zones, targeting, and statistics. Fully compatible with the [revive-adserver-mcp](https://github.com/btafoya/revive-adserver-mcp) server.

## Features

- ✅ **Campaign Management** - Create, read, update, delete campaigns
- ✅ **Banner Management** - Upload and manage banner advertisements  
- ✅ **Zone Configuration** - Configure and manage advertising zones
- ✅ **Advanced Targeting** - Geographic, time-based, device, and URL targeting
- ✅ **Statistics & Analytics** - Comprehensive performance reporting
- ✅ **Security** - Authentication, rate limiting, and input validation
- ✅ **CORS Support** - Cross-origin request handling for web applications

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

## Configuration

After installation, configure the plugin through the admin interface:

- **Enable REST API**: Turn the API on/off
- **Require Authentication**: Force authentication for all endpoints
- **Rate Limit**: Set requests per minute limit (default: 100)
- **API Tokens**: Configure valid authentication tokens

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

The API supports two authentication methods:

### Session-Based Authentication
For users logged into the Revive admin interface, API requests will automatically use the existing session.

### Token-Based Authentication
Include your API token in one of these ways:

**Authorization Header:**
```bash
Authorization: Bearer YOUR_API_TOKEN
```

**Custom Header:**
```bash
X-API-Token: YOUR_API_TOKEN
```

**Query Parameter:**
```bash
/api/v1/campaigns?api_token=YOUR_API_TOKEN
```

## Usage Examples

### Create a Campaign
```bash
curl -X POST http://yoursite.com/api/v1/campaigns   -H "Content-Type: application/json"   -H "Authorization: Bearer YOUR_TOKEN"   -d '{
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
curl -X POST http://yoursite.com/api/v1/banners/1/upload   -H "Authorization: Bearer YOUR_TOKEN"   -F "file=@banner-300x250.jpg"
```

### Set Geographic Targeting
```bash
curl -X PUT http://yoursite.com/api/v1/campaigns/1/targeting   -H "Content-Type: application/json"   -H "Authorization: Bearer YOUR_TOKEN"   -d '{
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

- **Authentication**: Session and token-based authentication
- **Rate Limiting**: Configurable per-IP rate limiting
- **Input Validation**: XSS and SQL injection protection
- **CORS**: Configurable cross-origin resource sharing
- **Logging**: Security event logging and monitoring

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
