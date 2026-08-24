import { Router } from 'express';
import { requireApiToken } from '../auth.js';
import {
  getSeen,
  addSeen,
  createDraft,
  listDrafts,
  createTopic,
  listTopics,
  setTopicStatus,
  getDueTasks,
  markTaskReminded,
  listPlanItems,
  createPromoIdea,
  listPromoIdeas,
  createRepostSuggestion,
  listRepostSuggestions,
} from '../db.js';

const ALLOWED_KEYS = new Set(['novinki', 'aktsii', 'ucenka', 'blog', 'friday', 'competitor', 'repost']);

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

apiRouter.get('/drafts', (req, res) => {
  const { source, category, status, limit } = req.query;
  res.json({ drafts: listDrafts({ source, category, status, limit: limit ? Number(limit) : 20 }) });
});

const TOPIC_STATUSES = new Set(['pending', 'approved', 'rejected', 'generated']);

apiRouter.post('/topics', (req, res) => {
  const { title, pitch, source_url, category } = req.body || {};
  if (!title || !pitch) {
    return res.status(400).json({ error: 'title and pitch are required' });
  }
  const id = createTopic({ title, pitch, source_url, category });
  res.json({ ok: true, id });
});

apiRouter.get('/topics', (req, res) => {
  const { status } = req.query;
  if (status && !TOPIC_STATUSES.has(status)) return res.status(400).json({ error: 'unknown status' });
  res.json({ topics: listTopics({ status }) });
});

apiRouter.post('/topics/:id/generated', (req, res) => {
  setTopicStatus(req.params.id, 'generated');
  res.json({ ok: true });
});

apiRouter.get('/tasks/due', (req, res) => {
  res.json({ tasks: getDueTasks() });
});

apiRouter.post('/tasks/:id/reminded', (req, res) => {
  markTaskReminded(req.params.id);
  res.json({ ok: true });
});

apiRouter.get('/plan', (req, res) => {
  const { status } = req.query;
  res.json({ items: listPlanItems({ status }) });
});

apiRouter.post('/promo-ideas', (req, res) => {
  const { title, rationale, mechanic, category } = req.body || {};
  if (!title || !rationale) {
    return res.status(400).json({ error: 'title and rationale are required' });
  }
  const id = createPromoIdea({ title, rationale, mechanic, category });
  res.json({ ok: true, id });
});

apiRouter.get('/promo-ideas', (req, res) => {
  const { status, limit } = req.query;
  res.json({ ideas: listPromoIdeas({ status, limit: limit ? Number(limit) : 20 }) });
});

apiRouter.post('/repost-suggestions', (req, res) => {
  const { title, summary, source_channel, source_url } = req.body || {};
  if (!title || !summary) {
    return res.status(400).json({ error: 'title and summary are required' });
  }
  const id = createRepostSuggestion({ title, summary, source_channel, source_url });
  res.json({ ok: true, id });
});

apiRouter.get('/repost-suggestions', (req, res) => {
  const { status, limit } = req.query;
  res.json({ suggestions: listRepostSuggestions({ status, limit: limit ? Number(limit) : 20 }) });
});
