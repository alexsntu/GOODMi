import 'dotenv/config';
import express from 'express';
import session from 'express-session';
import BetterSqlite3Store from 'better-sqlite3-session-store';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { db } from './db.js';
import { apiRouter } from './routes/api.js';
import { dashboardRouter } from './routes/dashboard.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

for (const key of ['INGEST_API_TOKEN', 'ADMIN_USERNAME', 'ADMIN_PASSWORD_HASH', 'SESSION_SECRET']) {
  if (!process.env[key]) {
    console.error(`Отсутствует обязательная переменная окружения ${key} — заполните .env по образцу .env.example`);
    process.exit(1);
  }
}

const SqliteStore = BetterSqlite3Store(session);

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.static(path.join(__dirname, '..', 'public')));

app.use(express.json());
app.use(express.urlencoded({ extended: false }));

app.use(
  session({
    store: new SqliteStore({ client: db, expired: { clear: true, intervalMs: 15 * 60 * 1000 } }),
    secret: process.env.SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: {},
  })
);

app.use('/api', apiRouter);
app.use('/', dashboardRouter);

app.get('/healthz', (req, res) => res.json({ ok: true }));

const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`Marketing portal listening on :${port}`);
});
