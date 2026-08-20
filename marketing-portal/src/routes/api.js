import { Router } from 'express';
import { requireApiToken } from '../auth.js';
import { getSeen, addSeen, createDraft } from '../db.js';

const ALLOWED_KEYS = new Set(['novinki', 'aktsii', 'ucenka']);

export const apiRouter = Router();
apiRouter.use(requireApiToken);

apiRouter.get('/state/:key', (req, res) => {
  const { key } = req.params;
  if (!ALLOWED_KEYS.has(key)) return res.status(400).json({ error: 'unknown key' });
  res.json({ seen: getSeen(key) });
});

apiRouter.post('/state/:key', (req, res) => {
  const { key } = req.params;
  if (!ALLOWED_KEYS.has(key)) return res.status(400).json({ error: 'unknown key' });
  const urls = Array.isArray(req.body?.urls) ? req.body.urls : [];
  addSeen(key, urls);
  res.json({ ok: true, added: urls.length });
});

apiRouter.post('/drafts', (req, res) => {
  const { source, title, text, source_url, category } = req.body || {};
  if (!source || !title || !text) {
    return res.status(400).json({ error: 'source, title and text are required' });
  }
  const id = createDraft({ source, title, text, source_url, category });
  res.json({ ok: true, id });
});
