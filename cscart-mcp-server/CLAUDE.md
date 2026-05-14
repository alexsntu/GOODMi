# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Core Development
```bash
npm start              # Run the MCP server in production mode
npm run dev            # Run the server with nodemon for development
npm test               # Run Jest tests
npm run setup          # Initial project setup (creates directories, copies .env)
```

### Docker Operations
```bash
npm run docker:build       # Build standard Docker image
npm run docker:build:prod  # Build optimized production image
npm run docker:run         # Start with docker-compose
npm run docker:stop        # Stop docker-compose services
npm run docker:logs        # View container logs
```

## Architecture

### Core Structure
This is a **Model Context Protocol (MCP) server** that provides tools for managing CS-Cart e-commerce stores. The server uses the `@modelcontextprotocol/sdk` to expose CS-Cart API functionality as MCP tools.

**Main entry point**: `src/index.js` - Contains the `CSCartMCPServer` class that implements the MCP protocol

### Key Components

1. **CSCartMCPServer Class** (`src/index.js:21-566`)
   - Implements MCP server using `@modelcontextprotocol/sdk`
   - Handles tool registration and execution
   - Makes authenticated requests to CS-Cart API using Basic Auth

2. **Tool Categories**:
   - **Product Management**: CRUD operations for products, stock management
   - **Order Management**: Retrieve orders, update order status
   - **Category Management**: Get product categories
   - **User Management**: Retrieve user/customer data
   - **Analytics**: Sales statistics
   - **PixelPlus SEO**: Manage semantic core in PixelPlus projects (queries, groups)

3. **Authentication**:
   - CS-Cart uses Basic Auth from environment variables:
     - `CSCART_API_URL` - CS-Cart store API endpoint
     - `CSCART_API_EMAIL` - Admin email for API access
     - `CSCART_API_KEY` - API key for authentication
   - PixelPlus uses token-based auth:
     - `PIXELPLUS_API_TOKEN` - API token from tools.pixelplus.ru account settings

### PixelPlus SEO Tools

Five MCP tools for managing semantic core in [tools.pixelplus.ru](https://tools.pixelplus.ru) SEO projects:

| Tool | Description |
|---|---|
| `pixelplus_get_project` | Get project info (domain, status) |
| `pixelplus_get_groups` | List query groups in a project |
| `pixelplus_get_queries` | Get full semantic core (all queries with IDs, groups, frequencies) |
| `pixelplus_add_queries` | Add queries to a project with group name and target URL |
| `pixelplus_delete_queries` | Delete queries by their IDs |

**Typical workflow:**
1. `pixelplus_get_project` — confirm project ID
2. `pixelplus_get_groups` — check available groups
3. `pixelplus_add_queries` — add new queries: `{ project_id, queries: ["query 1", "query 2"], group: "GroupName", url: "https://..." }`
4. `pixelplus_get_queries` — get all queries with their IDs
5. `pixelplus_delete_queries` — remove unwanted queries by ID: `{ project_id, query_ids: [123, 456] }`

**Base URL:** `https://tools.pixelplus.ru/projects/api/v1/{group}/{method}?token=TOKEN&{params}`

**Note on `delete`:** The `queries/delete` endpoint follows the standard PixelPlus pattern with `ids` param (pipe-separated). If the API returns an error, verify the endpoint with PixelPlus support.

### API Integration Pattern
- **CS-Cart** uses `makeRequest(method, endpoint, data)` — Basic Auth, supports GET/POST/PUT/DELETE
- **PixelPlus** uses `makePixelPlusRequest(group, method, params)` — token in query string, always GET

Both helpers format responses as MCP tool results: `{ content: [{ type: 'text', text: JSON.stringify(result) }] }`

### Configuration System
- `project.config.js` - Central configuration for project structure, Docker settings, and CS-Cart status codes
- Environment variables required for CS-Cart API connection
- `.env.example` template for environment setup

### Setup Automation
`scripts/setup.js` - Automated setup script that:
- Verifies Node.js version (requires >=18)
- Creates necessary directories (logs, tests, config)
- Copies `.env.example` to `.env`
- Validates required project files

### CS-Cart Status Codes
The system uses single-letter status codes throughout:
- **Products**: A=Active, D=Disabled, H=Hidden
- **Orders**: O=Open, P=Processed, C=Complete, F=Failed, D=Declined, B=Backordered, I=Incomplete
- **Users**: A=Active, D=Disabled
- **User Types**: A=Admin, V=Vendor, C=Customer

### Docker Support
Two Docker configurations:
- `Dockerfile` - Standard development image
- `Dockerfile.production` - Multi-stage optimized production build with security hardening
- Resource limits configured in `docker-compose.yml`

## Integration with AnythingLLM

For AnythingLLM integration, configuration is available in the README.md file at lines 220-268. Key points:

1. **Configuration Location**: AnythingLLM Settings → Integrations → MCP Servers
2. **Required Paths**: Use absolute paths for both `node` executable and `src/index.js`
3. **Environment Variables**: Set `CSCART_API_URL`, `CSCART_API_EMAIL`, and `CSCART_API_KEY`
4. **Docker Considerations**: Mount directories as volumes if AnythingLLM runs in Docker
5. **Testing**: Restart AnythingLLM after configuration and test CS-Cart tools

## Important Notes

- All API responses are returned as JSON-formatted text in MCP tool results
- The server runs as a stdio transport MCP server (not HTTP)
- Error handling wraps CS-Cart API errors in MCP error format
- No database - purely acts as an API proxy/adapter for CS-Cart