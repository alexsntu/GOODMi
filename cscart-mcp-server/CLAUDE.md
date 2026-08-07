# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

This is a **Model Context Protocol (MCP) server** that provides tools for managing CS-Cart e-commerce stores, using `@modelcontextprotocol/sdk`.

### Key Components

1. **Tool Categories**:
   - **Product Management**: CRUD operations for products, stock management
   - **Order Management**: Retrieve orders, update order status
   - **Category Management**: Get product categories
   - **User Management**: Retrieve user/customer data
   - **Analytics**: Sales statistics
   - **PixelPlus SEO**: Manage semantic core in PixelPlus projects (queries, groups)

2. **Authentication**:
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

### CS-Cart Status Codes
The system uses single-letter status codes throughout:
- **Products**: A=Active, D=Disabled, H=Hidden
- **Orders**: O=Open, P=Processed, C=Complete, F=Failed, D=Declined, B=Backordered, I=Incomplete
- **Users**: A=Active, D=Disabled
- **User Types**: A=Admin, V=Vendor, C=Customer

## Integration with AnythingLLM

Configuration steps are in `README.md` lines 220-268.

## Important Notes

- All API responses are returned as JSON-formatted text in MCP tool results
- The server runs as a stdio transport MCP server (not HTTP)
- Error handling wraps CS-Cart API errors in MCP error format
- No database - purely acts as an API proxy/adapter for CS-Cart