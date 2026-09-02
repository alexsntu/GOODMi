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

  CREATE TABLE IF NOT EXISTS topics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    pitch TEXT NOT NULL,
    source_url TEXT,
    category TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    due_at TEXT,
    remind_at TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    reminded INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS plan_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    type TEXT,
    start_date TEXT,
    end_date TEXT,
    channels TEXT,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'planned',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS promo_ideas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    rationale TEXT NOT NULL,
    mechanic TEXT,
    category TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS repost_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    summary TEXT NOT NULL,
    source_channel TEXT,
    source_url TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS bonus_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    status TEXT NOT NULL DEFAULT 'preview',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  );

  CREATE TABLE IF NOT EXISTS bonus_batch_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL REFERENCES bonus_batches(id),
    phone TEXT NOT NULL,
    name TEXT,
    dup_count INTEGER NOT NULL DEFAULT 1,
    requested_deduct REAL NOT NULL,
    balance_at_preview REAL,
    pending_at_preview REAL,
    deducted_step1 REAL,
    deducted_step2 REAL NOT NULL DEFAULT 0,
    step2_needed INTEGER NOT NULL DEFAULT 0,
    step2_done INTEGER NOT NULL DEFAULT 0,
    error TEXT
  );

  CREATE TABLE IF NOT EXISTS bonus_batch_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL REFERENCES bonus_batches(id),
    phone TEXT,
    action TEXT NOT NULL,
    amount REAL,
    details TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
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

export function listDrafts({ status, source, category, limit } = {}) {
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
  if (category) {
    query += ' AND category = ?';
    params.push(category);
  }
  query += ' ORDER BY created_at DESC';
  if (limit) {
    query += ' LIMIT ?';
    params.push(limit);
  }
  return db.prepare(query).all(...params);
}

export function setDraftStatus(id, status) {
  db.prepare('UPDATE drafts SET status = ? WHERE id = ?').run(status, id);
}

export function createTopic({ title, pitch, source_url, category }) {
  const stmt = db.prepare(
    `INSERT INTO topics (title, pitch, source_url, category) VALUES (?, ?, ?, ?)`
  );
  const info = stmt.run(title, pitch, source_url || null, category || null);
  return info.lastInsertRowid;
}

export function listTopics({ status } = {}) {
  let query = 'SELECT * FROM topics WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  query += ' ORDER BY created_at DESC';
  return db.prepare(query).all(...params);
}

export function setTopicStatus(id, status) {
  db.prepare('UPDATE topics SET status = ? WHERE id = ?').run(status, id);
}

export function getDraftStatsBySource() {
  const rows = db
    .prepare(
      `SELECT source,
              COUNT(*) AS total,
              SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
              SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) AS posted_count,
              SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed_count,
              MAX(created_at) AS last_at
         FROM drafts
        GROUP BY source`
    )
    .all();
  const bySource = {};
  for (const row of rows) bySource[row.source] = row;
  return bySource;
}

export function getTopicStats() {
  const rows = db.prepare(`SELECT status, COUNT(*) AS cnt FROM topics GROUP BY status`).all();
  const stats = { pending: 0, approved: 0, rejected: 0, generated: 0 };
  for (const row of rows) stats[row.status] = row.cnt;
  return stats;
}

export function createTask({ title, description, due_at, remind_at }) {
  const stmt = db.prepare(
    `INSERT INTO tasks (title, description, due_at, remind_at) VALUES (?, ?, ?, ?)`
  );
  const info = stmt.run(title, description || null, due_at || null, remind_at || null);
  return info.lastInsertRowid;
}

export function listTasks({ status } = {}) {
  let query = 'SELECT * FROM tasks WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  query += ' ORDER BY (due_at IS NULL), due_at ASC, created_at DESC';
  return db.prepare(query).all(...params);
}

export function setTaskStatus(id, status) {
  db.prepare('UPDATE tasks SET status = ? WHERE id = ?').run(status, id);
}

export function deleteTask(id) {
  db.prepare('DELETE FROM tasks WHERE id = ?').run(id);
}

export function getDueTasks() {
  return db
    .prepare(
      `SELECT * FROM tasks
        WHERE status = 'pending' AND reminded = 0
          AND remind_at IS NOT NULL AND remind_at <= datetime('now')
        ORDER BY remind_at ASC`
    )
    .all();
}

export function markTaskReminded(id) {
  db.prepare('UPDATE tasks SET reminded = 1 WHERE id = ?').run(id);
}

export function getNavCounts() {
  const drafts = db.prepare(`SELECT COUNT(*) AS c FROM drafts WHERE status = 'new'`).get().c;
  const topics = db.prepare(`SELECT COUNT(*) AS c FROM topics WHERE status = 'pending'`).get().c;
  const promoIdeas = db.prepare(`SELECT COUNT(*) AS c FROM promo_ideas WHERE status = 'pending'`).get().c;
  const repostSuggestions = db
    .prepare(`SELECT COUNT(*) AS c FROM repost_suggestions WHERE status = 'pending'`)
    .get().c;
  const bonusBatchesAwaiting = db
    .prepare(`SELECT COUNT(*) AS c FROM bonus_batches WHERE status = 'executed'`)
    .get().c;
  return { drafts, topics, promoIdeas, repostSuggestions, bonusBatchesAwaiting };
}

export function createPlanItem({ title, type, start_date, end_date, channels, description }) {
  const stmt = db.prepare(
    `INSERT INTO plan_items (title, type, start_date, end_date, channels, description)
     VALUES (?, ?, ?, ?, ?, ?)`
  );
  const info = stmt.run(
    title,
    type || null,
    start_date || null,
    end_date || null,
    channels || null,
    description || null
  );
  return info.lastInsertRowid;
}

export function listPlanItems({ status } = {}) {
  let query = 'SELECT * FROM plan_items WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  query += ' ORDER BY (start_date IS NULL), start_date ASC, created_at DESC';
  return db.prepare(query).all(...params);
}

export function setPlanItemStatus(id, status) {
  db.prepare('UPDATE plan_items SET status = ? WHERE id = ?').run(status, id);
}

export function getPlanItem(id) {
  return db.prepare('SELECT * FROM plan_items WHERE id = ?').get(id);
}

export function updatePlanItem(id, { title, type, start_date, end_date, channels, description }) {
  db.prepare(
    `UPDATE plan_items SET title = ?, type = ?, start_date = ?, end_date = ?, channels = ?, description = ?
     WHERE id = ?`
  ).run(
    title,
    type || null,
    start_date || null,
    end_date || null,
    channels || null,
    description || null,
    id
  );
}

export function deletePlanItem(id) {
  db.prepare('DELETE FROM plan_items WHERE id = ?').run(id);
}

export function createPromoIdea({ title, rationale, mechanic, category }) {
  const stmt = db.prepare(
    `INSERT INTO promo_ideas (title, rationale, mechanic, category) VALUES (?, ?, ?, ?)`
  );
  const info = stmt.run(title, rationale, mechanic || null, category || null);
  return info.lastInsertRowid;
}

export function listPromoIdeas({ status, limit } = {}) {
  let query = 'SELECT * FROM promo_ideas WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  query += ' ORDER BY created_at DESC';
  if (limit) {
    query += ' LIMIT ?';
    params.push(limit);
  }
  return db.prepare(query).all(...params);
}

export function setPromoIdeaStatus(id, status) {
  db.prepare('UPDATE promo_ideas SET status = ? WHERE id = ?').run(status, id);
}

export function createRepostSuggestion({ title, summary, source_channel, source_url }) {
  const stmt = db.prepare(
    `INSERT INTO repost_suggestions (title, summary, source_channel, source_url) VALUES (?, ?, ?, ?)`
  );
  const info = stmt.run(title, summary, source_channel || null, source_url || null);
  return info.lastInsertRowid;
}

export function listRepostSuggestions({ status, limit } = {}) {
  let query = 'SELECT * FROM repost_suggestions WHERE 1=1';
  const params = [];
  if (status) {
    query += ' AND status = ?';
    params.push(status);
  }
  query += ' ORDER BY created_at DESC';
  if (limit) {
    query += ' LIMIT ?';
    params.push(limit);
  }
  return db.prepare(query).all(...params);
}

export function setRepostSuggestionStatus(id, status) {
  db.prepare('UPDATE repost_suggestions SET status = ? WHERE id = ?').run(status, id);
}

// --- Бонусы (БонусПлюс) ---

export function createBonusBatch() {
  const info = db.prepare(`INSERT INTO bonus_batches (status) VALUES ('preview')`).run();
  return info.lastInsertRowid;
}

export function addBonusBatchItem(batchId, item) {
  const stmt = db.prepare(
    `INSERT INTO bonus_batch_items
       (batch_id, phone, name, dup_count, requested_deduct, balance_at_preview, pending_at_preview)
     VALUES (?, ?, ?, ?, ?, ?, ?)`
  );
  const info = stmt.run(
    batchId,
    item.phone,
    item.name || null,
    item.dup_count || 1,
    item.requested_deduct,
    item.balance_at_preview,
    item.pending_at_preview
  );
  return info.lastInsertRowid;
}

export function listBonusBatches() {
  return db
    .prepare(
      `SELECT b.*,
              (SELECT COUNT(*) FROM bonus_batch_items i WHERE i.batch_id = b.id) AS items_count,
              (SELECT COALESCE(SUM(requested_deduct), 0) FROM bonus_batch_items i WHERE i.batch_id = b.id) AS total_requested,
              (SELECT COALESCE(SUM(deducted_step1), 0) + COALESCE(SUM(deducted_step2), 0)
                 FROM bonus_batch_items i WHERE i.batch_id = b.id) AS total_deducted
         FROM bonus_batches b
        ORDER BY b.created_at DESC`
    )
    .all();
}

export function getBonusBatch(id) {
  return db.prepare(`SELECT * FROM bonus_batches WHERE id = ?`).get(id);
}

export function setBonusBatchStatus(id, status) {
  db.prepare(`UPDATE bonus_batches SET status = ? WHERE id = ?`).run(status, id);
}

export function listBonusBatchItems(batchId) {
  return db.prepare(`SELECT * FROM bonus_batch_items WHERE batch_id = ? ORDER BY id`).all(batchId);
}

export function updateBonusBatchItemStep1(id, { deducted_step1, balance_at_preview, pending_at_preview, step2_needed, error }) {
  db.prepare(
    `UPDATE bonus_batch_items
        SET deducted_step1 = ?, balance_at_preview = ?, pending_at_preview = ?, step2_needed = ?, error = ?
      WHERE id = ?`
  ).run(deducted_step1, balance_at_preview, pending_at_preview, step2_needed ? 1 : 0, error || null, id);
}

export function updateBonusBatchItemStep2(id, { deducted_step2, step2_needed, pending_at_preview, error }) {
  db.prepare(
    `UPDATE bonus_batch_items
        SET deducted_step2 = deducted_step2 + ?, step2_needed = ?, pending_at_preview = ?, step2_done = ?, error = ?
      WHERE id = ?`
  ).run(deducted_step2, step2_needed ? 1 : 0, pending_at_preview, step2_needed ? 0 : 1, error || null, id);
}

export function logBonusEvent(batchId, { phone, action, amount, details }) {
  db.prepare(
    `INSERT INTO bonus_batch_log (batch_id, phone, action, amount, details) VALUES (?, ?, ?, ?, ?)`
  ).run(batchId, phone || null, action, amount ?? null, details || null);
}

export function listBonusLog({ batchId, phone, limit } = {}) {
  let query = `SELECT l.*, b.created_at AS batch_created_at
                 FROM bonus_batch_log l
                 JOIN bonus_batches b ON b.id = l.batch_id
                WHERE 1=1`;
  const params = [];
  if (batchId) {
    query += ' AND l.batch_id = ?';
    params.push(batchId);
  }
  if (phone) {
    query += ' AND l.phone LIKE ?';
    params.push(`%${phone}%`);
  }
  query += ' ORDER BY l.created_at DESC, l.id DESC';
  if (limit) {
    query += ' LIMIT ?';
    params.push(limit);
  }
  return db.prepare(query).all(...params);
}
