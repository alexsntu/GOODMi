// Выгрузка данных маркетинг-портала из SQLite в JSON для переноса в модуль gm_marketing (2026-10-21).
// Запуск в контейнере портала: docker exec goodmi-marketing-portal node -e "$(cat export-portal.js)" > portal-export.json
const Database = require('better-sqlite3');
const db = new Database('/app/data/portal.db', { readonly: true });
const out = {};
for (const t of ['drafts', 'seen_state', 'topics', 'tasks', 'plan_items', 'promo_ideas', 'repost_suggestions', 'bonus_batches', 'bonus_batch_items', 'bonus_batch_log']) {
  out[t] = db.prepare(`SELECT * FROM ${t}`).all();
}
process.stdout.write(JSON.stringify(out));
