import 'dotenv/config';
import express from 'express';
import session from 'express-session';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { apiRouter } from './routes/api.js';
import { dashboardRouter } from './routes/dashboard.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

for (const key of ['INGEST_API_TOKEN', 'ADMIN_PASSWORD_HASH', 'SESSION_SECRET']) {
  if (!process.env[key]) {
    console.error(`Отсутствует обязательная переменная окружения ${key} — заполните .env по образцу .env.example`);
    process.exit(1);
  }
}

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.static(path.join(__dirname, '..', 'public')));

app.use(express.json());
app.use(express.urlencoded({ extended: false }));

app.use(
  session({
    secret: process.env.SESSION_SECRET,
    resave: false,
    saveUninitialized: false,
    cookie: { maxAge: 30 * 24 * 60 * 60 * 1000 },
  })
);

app.use('/api', apiRouter);
app.use('/', dashboardRouter);

app.get('/healthz', (req, res) => res.json({ ok: true }));

const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`GOODMi marketing portal listening on :${port}`);
});
