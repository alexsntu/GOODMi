import Database from 'better-sqlite3';
import fs from 'node:fs';
import path from 'node:path';

const dbPath = process.env.DB_PATH || './data/portal.db';
fs.mkdirSync(path.dirname(dbPath), { recursive: true });

export const db = new Database(dbPath);
db.pragma('journal_mode = WAL');

db.exec(`
  CREATE TABLE IF NOT EXISTS drafts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source TEXT NOT NULL,
    title TEXT NOT NULL,
    text TEXT NOT NULL,
    source_url TEXT,
    category TEXT,
    status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS seen_state (
    key TEXT NOT NULL,
    item_url TEXT NOT NULL,
    seen_at TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (key, item_url)
  );
`);

export function getSeen(key) {
  const rows = db.prepare('SELECT item_url FROM seen_state WHERE key = ?').all(key);
  return rows.map((r) => r.item_url);
}

export function addSeen(key, urls) {
  const insert = db.prepare(
    'INSERT OR IGNORE INTO seen_state (key, item_url) VALUES (?, ?)'
  );
  const insertMany = db.transaction((list) => {
    for (const url of list) insert.run(key, url);
  });
  insertMany(urls);
}

export function createDraft({ source, title, text, source_url, category }) {
  const stmt = db.prepare(
    `INSERT INTO drafts (source, title, text, source_url, category) VALUES (?, ?, ?, ?, ?)`
  );
  const info = stmt.run(source, title, text, source_url || null, category || null);
  return info.lastInsertRowid;
}

export function listDrafts({ status, source } = {}) {
  let query = 'SELECT * FROM drafts WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  if (source) {
    query += ' AND source = ?';
    params.push(source);
  }
  query += ' ORDER BY created_at DESC';
  return db.prepare(query).all(...params);
}

export function setDraftStatus(id, status) {
  db.prepare('UPDATE drafts SET status = ? WHERE id = ?').run(status, id);
}
