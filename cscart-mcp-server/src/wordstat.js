#!/usr/bin/env node

/**
 * Yandex Wordstat MCP Server
 * Uses Yandex Search API: https://searchapi.api.cloud.yandex.net/v2/wordstat/
 *
 * @file src/wordstat.js
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ErrorCode,
  ListToolsRequestSchema,
  McpError,
} from '@modelcontextprotocol/sdk/types.js';
import axios from 'axios';

const BASE_URL = 'https://searchapi.api.cloud.yandex.net/v2/wordstat';

// Default regions: Crimea (977) + Sevastopol (959)
const DEFAULT_REGIONS = [977, 959];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

class WordstatMCPServer {
  constructor() {
    this.server = new Server(
      { name: 'wordstat-mcp-server', version: '1.0.0' },
      { capabilities: { tools: {} } }
    );
    this.setupToolHandlers();
  }

  getApiKey() {
    const key = process.env.YANDEX_API_KEY;
    if (!key) throw new Error('YANDEX_API_KEY is not set in environment variables');
    return key;
  }

  async apiRequest(endpoint, body) {
    const response = await axios.post(`${BASE_URL}/${endpoint}`, body, {
      headers: {
        'Authorization': `Api-Key ${this.getApiKey()}`,
        'Content-Type': 'application/json',
      },
    });
    return response.data;
  }

  setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'wordstat_regions',
          description: 'Get regional frequency distribution for a search phrase. Returns count and affinity index per region. Useful for checking demand in specific regions (e.g. Crimea=977, Sevastopol=959).',
          inputSchema: {
            type: 'object',
            properties: {
              phrase: { type: 'string', description: 'Search phrase to check' },
              regions: {
                type: 'array',
                items: { type: 'number' },
                description: 'Filter to specific region IDs (optional). If omitted, returns all regions.',
              },
            },
            required: ['phrase'],
          },
        },
        {
          name: 'wordstat_top',
          description: 'Get total monthly frequency (totalCount) and top related queries for a phrase across all of Russia.',
          inputSchema: {
            type: 'object',
            properties: {
              phrase: { type: 'string', description: 'Search phrase to check' },
              num_phrases: {
                type: 'number',
                description: 'Number of related phrases to return (1-2000, default 10)',
              },
            },
            required: ['phrase'],
          },
        },
        {
          name: 'wordstat_bulk',
          description: 'Check Wordstat frequency for multiple phrases in target regions (default: Crimea+Sevastopol). Returns count per region and combined total for each phrase. Rate-limited to avoid API quota issues.',
          inputSchema: {
            type: 'object',
            properties: {
              phrases: {
                type: 'array',
                items: { type: 'string' },
                description: 'List of search phrases to check',
              },
              regions: {
                type: 'array',
                items: { type: 'number' },
                description: `Region IDs to sum up (default: [977, 959] = Crimea + Sevastopol)`,
              },
              delay_ms: {
                type: 'number',
                description: 'Delay between API requests in milliseconds (default: 300)',
              },
            },
            required: ['phrases'],
          },
        },
        {
          name: 'wordstat_dynamics',
          description: 'Get search frequency dynamics over time for a phrase. Useful for detecting seasonal trends.',
          inputSchema: {
            type: 'object',
            properties: {
              phrase: { type: 'string', description: 'Search phrase' },
              period: {
                type: 'string',
                enum: ['PERIOD_WEEKLY', 'PERIOD_MONTHLY'],
                description: 'Aggregation period (default: PERIOD_MONTHLY)',
              },
              from_date: {
                type: 'string',
                description: 'Start date in YYYY-MM-DD format (default: 12 months ago)',
              },
              to_date: {
                type: 'string',
                description: 'End date in YYYY-MM-DD format (default: today)',
              },
              regions: {
                type: 'array',
                items: { type: 'number' },
                description: 'Filter to specific region IDs (optional)',
              },
            },
            required: ['phrase'],
          },
        },
      ],
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;
      try {
        switch (name) {
          case 'wordstat_regions':   return await this.getRegions(args);
          case 'wordstat_top':       return await this.getTop(args);
          case 'wordstat_bulk':      return await this.getBulk(args);
          case 'wordstat_dynamics':  return await this.getDynamics(args);
          default:
            throw new McpError(ErrorCode.MethodNotFound, `Unknown tool: ${name}`);
        }
      } catch (error) {
        if (error instanceof McpError) throw error;
        throw new McpError(ErrorCode.InternalError, `Error executing ${name}: ${error.message}`);
      }
    });
  }

  async getRegions(args) {
    const data = await this.apiRequest('regions', { phrase: args.phrase });
    let results = data.results || [];

    if (args.regions && args.regions.length > 0) {
      const filterSet = new Set(args.regions.map(String));
      results = results.filter((r) => filterSet.has(String(r.region)));
    }

    results.sort((a, b) => parseInt(b.count) - parseInt(a.count));

    return {
      content: [{
        type: 'text',
        text: JSON.stringify({ phrase: args.phrase, regions: results }, null, 2),
      }],
    };
  }

  async getTop(args) {
    const data = await this.apiRequest('topRequests', {
      phrase: args.phrase,
      numPhrases: args.num_phrases || 10,
    });

    return {
      content: [{
        type: 'text',
        text: JSON.stringify(data, null, 2),
      }],
    };
  }

  async getBulk(args) {
    const phrases = args.phrases;
    const targetRegions = args.regions || DEFAULT_REGIONS;
    const delayMs = args.delay_ms ?? 300;
    const filterSet = new Set(targetRegions.map(String));

    const results = [];

    for (let i = 0; i < phrases.length; i++) {
      const phrase = phrases[i];
      try {
        const data = await this.apiRequest('regions', { phrase });
        const allRegions = data.results || [];
        const matched = allRegions.filter((r) => filterSet.has(String(r.region)));
        const totalCount = matched.reduce((sum, r) => sum + parseInt(r.count || 0), 0);

        results.push({
          phrase,
          total: totalCount,
          by_region: Object.fromEntries(
            matched.map((r) => [r.region, parseInt(r.count || 0)])
          ),
        });
      } catch (err) {
        results.push({ phrase, total: null, error: err.message });
      }

      if (i < phrases.length - 1 && delayMs > 0) {
        await sleep(delayMs);
      }
    }

    results.sort((a, b) => (a.total ?? -1) - (b.total ?? -1));

    return {
      content: [{
        type: 'text',
        text: JSON.stringify({
          regions_checked: targetRegions,
          count: results.length,
          results,
        }, null, 2),
      }],
    };
  }

  async getDynamics(args) {
    const today = new Date();
    const defaultFrom = new Date(today);
    defaultFrom.setMonth(defaultFrom.getMonth() - 12);

    const body = {
      phrase: args.phrase,
      period: args.period || 'PERIOD_MONTHLY',
      fromDate: args.from_date || defaultFrom.toISOString().split('T')[0],
      toDate: args.to_date || today.toISOString().split('T')[0],
    };

    if (args.regions && args.regions.length > 0) {
      body.regions = args.regions;
    }

    const data = await this.apiRequest('dynamics', body);

    return {
      content: [{
        type: 'text',
        text: JSON.stringify({ phrase: args.phrase, ...data }, null, 2),
      }],
    };
  }

  async run() {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('Wordstat MCP server running on stdio');
  }
}

const server = new WordstatMCPServer();
server.run().catch(console.error);
