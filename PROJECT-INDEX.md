# 📋 Revive Adserver REST API Plugin - Project Index

## 🔍 Project Overview

**Purpose**: REST API plugin for Revive Adserver providing RESTful endpoints for campaigns, banners, zones, targeting, and statistics  
**Version**: 1.0.0  
**Compatibility**: Revive Adserver 5.x, PHP 7.4+  
**License**: GPL-2.0+  
**MCP Compatible**: ✅ Works with [revive-adserver-mcp](https://github.com/btafoya/revive-adserver-mcp)

---

## 📁 Project Structure

```
revive-adserver-restapi-plugin/
├── 📄 Core Configuration
│   ├── plugin.xml              # Plugin definition & lifecycle
│   ├── routes.addendum.php     # API route definitions
│   └── www/index.php          # Plugin entry point
├── 📝 Documentation
│   ├── README.md              # Primary documentation
│   ├── CHANGELOG.md           # Version history
│   └── PROJECT-INDEX.md       # This file
├── ⚙️ Installation Scripts
│   ├── scripts/install.php    # Plugin installation
│   ├── scripts/upgrade.php    # Version upgrades
│   └── scripts/uninstall.php  # Clean removal
└── 🏗️ Source Code
    ├── Controllers/           # API endpoint handlers
    ├── Services/             # Business logic
    └── Support/              # Infrastructure utilities
```

---

## 🎯 Core Features

| Feature | Status | Description |
|---------|--------|-------------|
| **Campaign Management** | ✅ | CRUD operations for advertising campaigns |
| **Banner Management** | ✅ | File uploads and banner configuration |
| **Zone Configuration** | ✅ | Ad placement zone management |
| **Advanced Targeting** | ✅ | Geographic, temporal, device, URL targeting |
| **Rule Sets** | ✅ | Reusable targeting rule collections |
| **Statistics & Analytics** | ✅ | Performance reporting and metrics |
| **Security** | ✅ | Authentication, rate limiting, validation |
| **CORS Support** | ✅ | Cross-origin request handling |

---

## 🛠️ Components

### Controllers (`src/Controllers/`)

| Controller | Purpose | Key Methods |
|------------|---------|-------------|
| **RuleSetsController** | Targeting rule management | `index()`, `create()`, `apply()`, `preview()` |
| **BannersApplyController** | Bulk banner operations | `apply()` - multi-banner targeting |
| **UploadsController** | File upload handling | `attachToBanner()` - banner file uploads |
| **TargetingSchemaController** | Schema introspection | `schema()` - UI generation support |
| **VariablesController** | Site variable formatting | `formatSite()` - variable helpers |

### Services (`src/Services/`)

| Service | Purpose | Key Features |
|---------|---------|--------------|
| **TargetingCompiler** | Rule compilation | Converts targeting rules to executable expressions |
| **TargetingValidator** | Rule validation | Dry-run validation, normalization, warnings |

### Support (`src/Support/`)

| Component | Purpose | Features |
|-----------|---------|----------|
| **ReviveConfig** | Configuration management | Database connections, plugin settings |

---

## 🔌 API Architecture

### Base URL
```
/api/v1
```

### Authentication Methods
- **Session-based**: Existing Revive admin sessions
- **Token-based**: Bearer tokens, custom headers, query parameters

### Core Endpoints

#### 🏥 Health & Status
```http
GET /api/v1/health           # System health check
```

#### 📊 Campaigns
```http
GET    /api/v1/campaigns     # List campaigns
POST   /api/v1/campaigns     # Create campaign  
GET    /api/v1/campaigns/{id} # Get campaign details
PUT    /api/v1/campaigns/{id} # Update campaign
DELETE /api/v1/campaigns/{id} # Delete campaign
```

#### 🖼️ Banners
```http
GET    /api/v1/banners       # List banners
POST   /api/v1/banners       # Create banner
GET    /api/v1/banners/{id}  # Get banner details
PUT    /api/v1/banners/{id}  # Update banner
DELETE /api/v1/banners/{id}  # Delete banner
POST   /api/v1/banners/{id}/upload # Upload banner file
POST   /api/v1/banners/apply # Bulk apply targeting
```

#### 🎯 Zones
```http
GET    /api/v1/zones         # List zones
POST   /api/v1/zones         # Create zone
GET    /api/v1/zones/{id}    # Get zone details
PUT    /api/v1/zones/{id}    # Update zone
DELETE /api/v1/zones/{id}    # Delete zone
```

#### 🎯 Targeting & Rules
```http
GET /api/v1/campaigns/{id}/targeting # Get campaign targeting
PUT /api/v1/campaigns/{id}/targeting # Update campaign targeting
GET /api/v1/banners/{id}/targeting   # Get banner targeting
PUT /api/v1/banners/{id}/targeting   # Update banner targeting

# Rule Sets
GET    /api/v1/rule-sets              # List rule sets
POST   /api/v1/rule-sets              # Create rule set
GET    /api/v1/rule-sets/{id}         # Get rule set
PUT    /api/v1/rule-sets/{id}         # Update rule set
DELETE /api/v1/rule-sets/{id}         # Delete rule set
GET    /api/v1/rule-sets/{id}/preview # Preview compiled rules
POST   /api/v1/rule-sets/{id}/apply   # Apply to banners
POST   /api/v1/rule-sets/import       # Import rule set
GET    /api/v1/rule-sets/{id}/export  # Export rule set

# Validation & Schema
POST /api/v1/targeting/validate       # Validate targeting rules
GET  /api/v1/targeting/schema         # Get targeting schema
POST /api/v1/variables/site/format    # Format site variables
```

#### 📈 Statistics
```http
GET /api/v1/stats/campaigns          # Campaign statistics
GET /api/v1/stats/campaigns/{id}     # Specific campaign stats
GET /api/v1/stats/banners            # Banner statistics
GET /api/v1/stats/banners/{id}       # Specific banner stats
GET /api/v1/stats/zones              # Zone statistics
GET /api/v1/stats/zones/{id}         # Specific zone stats
```

---

## 🎯 Targeting System

### Supported Targeting Types

| Type | Function | Range Support | Description |
|------|----------|---------------|-------------|
| **Site:Variable** | `MAX_checkSite_Variable` | ❌ | Custom site variables |
| **Site:Domain** | `MAX_checkSite_Domain` | ❌ | Domain targeting |
| **Source:Source** | `MAX_checkSource` | ❌ | Traffic source targeting |
| **Geo:Country** | `MAX_checkGeo_Country` | ❌ | Country-based targeting |
| **Client:Browser** | `MAX_checkClient_Browser` | ❌ | Browser targeting |
| **Client:Language** | `MAX_checkClient_Language` | ❌ | Language targeting |
| **Time:HourOfDay** | `MAX_checkTime_HourOfDay` | ✅ | Hour-based scheduling |
| **Time:DayOfWeek** | `MAX_checkTime_DayOfWeek` | ❌ | Day-based scheduling |

### Logical Operators
- **AND**: Default conjunction
- **OR**: Alternative matching
- **NOT**: Negation (wrapped in parentheses)

### Rule Set Features
- **Reusable Rules**: Store and reuse targeting configurations
- **Bulk Application**: Apply rule sets to multiple banners
- **Import/Export**: Transfer rules between environments
- **Preview Mode**: Test rule compilation without applying
- **Validation**: Dry-run validation with warnings

---

## 🔒 Security Features

### Authentication
- **Session Integration**: Automatic session validation
- **Token Authentication**: Bearer tokens, custom headers, query params
- **Configurable**: Enable/disable authentication per endpoint

### Security Controls
- **Rate Limiting**: Configurable per-IP request limits
- **Input Validation**: XSS and SQL injection protection
- **CORS Management**: Configurable cross-origin policies
- **Security Logging**: Event logging and monitoring
- **Transaction Safety**: Database transactions for consistency

### Data Protection
- **Sanitization**: Input cleaning and validation
- **Safe Encoding**: JSON encoding/decoding with error handling
- **SQL Protection**: Prepared statements and parameterized queries

---

## 🔧 Installation & Configuration

### Requirements
- **Revive Adserver**: 5.0+
- **PHP**: 7.4+
- **Database**: MySQL 5.7+ or MariaDB 10.3+

### Installation Methods

#### Manual Installation
1. Extract to `/path/to/revive/plugins/`
2. Access Revive admin panel
3. Navigate to **Plugins** → **Plugin Administration**
4. Install "REST API Plugin"
5. Configure settings

#### Docker Installation
```bash
# Plugin included in container
docker-compose up -d
```

### Configuration Options
- **API Base Path**: Default `/api/v1`
- **Authentication**: Required/optional toggle
- **Rate Limits**: Requests per minute (default: 100)
- **CORS Settings**: Cross-origin configuration
- **Token Management**: API token configuration

---

## 🧪 Testing & Validation

### Validation Pipeline
1. **Input Validation**: Type checking, sanitization
2. **Business Logic**: Rule compilation, constraint validation
3. **Security Checks**: Authentication, authorization
4. **Database Validation**: Transaction integrity
5. **Output Formatting**: Response standardization

### Testing Endpoints
- **Health Check**: System status validation
- **Rule Validation**: Dry-run targeting compilation
- **Schema Introspection**: UI generation support
- **Preview Mode**: Non-destructive rule testing

---

## 📊 Response Formats

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

### Validation Response
```json
{
  "valid": true,
  "compiled": "MAX_checkGeo_Country(...)",
  "normalized": { ... },
  "warnings": [],
  "preview": "Country IN [US, CA, UK]"
}
```

---

## 🔗 MCP Integration

### revive-adserver-mcp Compatibility
- **Natural Language Interface**: Claude Code integration
- **Command Support**: AI-driven ad server management
- **Context Awareness**: Project understanding and automation
- **Workflow Integration**: Seamless development experience

### MCP Features
- **Campaign Management**: AI-assisted campaign creation
- **Targeting Configuration**: Natural language targeting rules
- **Performance Analysis**: Automated reporting and insights
- **Troubleshooting**: Intelligent problem diagnosis

---

## 📚 Development Workflow

### File Organization
```
Controllers/    # HTTP request handlers
├── Business logic coordination
├── Input validation and sanitization
├── Response formatting
└── Error handling

Services/       # Core business logic
├── Targeting compilation and validation
├── Rule processing algorithms
└── Data transformation utilities

Support/        # Infrastructure utilities
├── Configuration management
├── Database connectivity
└── Common utilities
```

### Code Standards
- **PSR Compliance**: PHP Standards Recommendations
- **Type Safety**: Strict typing where possible
- **Error Handling**: Comprehensive exception management
- **Documentation**: Inline code documentation
- **Security**: Input validation and output sanitization

### Extension Points
- **Custom Targeting**: Add new targeting types via MAP constant
- **Middleware**: Custom authentication and validation
- **Response Formatting**: Custom response transformers
- **Validation Rules**: Extended rule validation logic

---

## 🚀 Deployment

### Production Considerations
- **Performance**: Database query optimization
- **Security**: Production security hardening
- **Monitoring**: Application performance monitoring
- **Backup**: Configuration and rule set backups
- **Scaling**: Load balancing and caching strategies

### Configuration Management
- **Environment Variables**: Sensitive configuration
- **Plugin Settings**: Revive admin interface configuration
- **Database Schema**: Custom table management
- **File Permissions**: Upload directory security

---

## 📞 Support & Resources

### Documentation
- **README.md**: Primary usage documentation
- **CHANGELOG.md**: Version history and changes
- **API Examples**: cURL and programming examples
- **MCP Integration**: revive-adserver-mcp documentation

### Community & Support
- **GitHub Issues**: Bug reports and feature requests
- **Documentation**: Comprehensive usage guides
- **Examples**: Real-world implementation patterns
- **MCP Server**: Natural language interface support

---

## 🔄 Version History

### Current: v1.0.0 (2024-08-20)
- Initial public release
- Complete REST API implementation
- MCP server compatibility
- Advanced targeting system
- Security and validation features

### Future Roadmap
- Enhanced analytics and reporting
- Additional targeting types
- Performance optimizations
- Extended MCP integration
- Advanced rule management

---

*Generated: 2024-08-20 | Plugin Version: 1.0.0 | Compatible with Revive 5.x*