import { Router } from 'express';
import { checkPassword, requireSession } from '../auth.js';
import { listDrafts, setDraftStatus } from '../db.js';

export const dashboardRouter = Router();

const ROUTINES = [
  { name: 'Новинки', url: 'https://claude.ai/code/routines/trig_01J15fDUaLRqpm14TxzLZs5d' },
  { name: 'Акции и уценка', url: 'https://claude.ai/code/routines/trig_011Yk3zJWZKLz1ppg3iPDscE' },
];

const SOURCE_LABELS = {
  novinki: 'Новинки',
  aktsii: 'Акции',
  ucenka: 'Уценка',
};

dashboardRouter.get('/login', (req, res) => {
  res.render('login', { error: null });
});

dashboardRouter.post('/login', (req, res) => {
  const { password } = req.body || {};
  if (checkPassword(password || '')) {
    req.session.authenticated = true;
    return res.redirect('/');
  }
  res.render('login', { error: 'Неверный пароль' });
});

dashboardRouter.post('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});

dashboardRouter.get('/', requireSession, (req, res) => {
  const status = req.query.status || 'new';
  const source = req.query.source || null;
  const drafts = listDrafts({
    status: status === 'all' ? undefined : status,
    source: source || undefined,
  });
  res.render('dashboard', {
    drafts,
    status,
    source,
    routines: ROUTINES,
    sourceLabels: SOURCE_LABELS,
  });
});

dashboardRouter.post('/drafts/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!['posted', 'dismissed', 'new'].includes(status)) {
    return res.status(400).send('bad status');
  }
  setDraftStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/');
});
