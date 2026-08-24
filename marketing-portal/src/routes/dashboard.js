import { Router } from 'express';
import { checkCredentials, requireSession } from '../auth.js';
import {
  listDrafts,
  setDraftStatus,
  listTopics,
  setTopicStatus,
  getDraftStatsBySource,
  getTopicStats,
  listTasks,
  createTask,
  setTaskStatus,
  deleteTask,
  listPlanItems,
  createPlanItem,
  setPlanItemStatus,
  deletePlanItem,
  getPlanItem,
  updatePlanItem,
  listPromoIdeas,
  setPromoIdeaStatus,
  listRepostSuggestions,
  setRepostSuggestionStatus,
  getNavCounts,
} from '../db.js';

const PLAN_TYPE_LABELS = {
  promo: 'Сезонная акция',
  campaign: 'Товарная кампания',
  news_hook: 'Инфоповод',
  event: 'Личное мероприятие',
};

const PLAN_STATUS_LABELS = {
  planned: 'Запланировано',
  in_progress: 'В работе',
  done: 'Готово',
  cancelled: 'Отменено',
};

function mskLocalToUtcSql(localValue) {
  if (!localValue) return null;
  const d = new Date(`${localValue}:00+03:00`);
  if (Number.isNaN(d.getTime())) return null;
  return d.toISOString().slice(0, 19).replace('T', ' ');
}

function utcSqlToMskDisplay(utcValue) {
  if (!utcValue) return null;
  const d = new Date(`${utcValue.replace(' ', 'T')}Z`);
  if (Number.isNaN(d.getTime())) return utcValue;
  const msk = new Date(d.getTime() + 3 * 60 * 60 * 1000);
  const pad = (n) => String(n).padStart(2, '0');
  return `${msk.getUTCFullYear()}-${pad(msk.getUTCMonth() + 1)}-${pad(msk.getUTCDate())} ${pad(msk.getUTCHours())}:${pad(msk.getUTCMinutes())}`;
}

const pad2 = (n) => String(n).padStart(2, '0');

function shiftMonth(monthStr, delta) {
  let [year, month] = monthStr.split('-').map(Number);
  month += delta;
  while (month > 12) { month -= 12; year += 1; }
  while (month < 1) { month += 12; year -= 1; }
  return `${year}-${pad2(month)}`;
}

const PLAN_COLOR_COUNT = 12;

function planColorClass(id) {
  return `plan-c${((Number(id) % PLAN_COLOR_COUNT) + PLAN_COLOR_COUNT) % PLAN_COLOR_COUNT}`;
}

function buildCalendarWeeks(monthStr, items) {
  const [year, month] = monthStr.split('-').map(Number);
  const firstOfMonth = new Date(Date.UTC(year, month - 1, 1));
  const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();
  const firstWeekday = (firstOfMonth.getUTCDay() + 6) % 7; // Monday = 0
  const cells = [];
  for (let i = 0; i < firstWeekday; i++) cells.push(null);
  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = `${year}-${pad2(month)}-${pad2(day)}`;
    const dayItems = items.filter(
      (it) => it.start_date && dateStr >= it.start_date && dateStr <= (it.end_date || it.start_date)
    );
    cells.push({ day, dateStr, items: dayItems });
  }
  while (cells.length % 7 !== 0) cells.push(null);
  const weeks = [];
  for (let i = 0; i < cells.length; i += 7) weeks.push(cells.slice(i, i + 7));
  return weeks;
}

export const dashboardRouter = Router();

dashboardRouter.use((req, res, next) => {
  res.locals.navCounts = getNavCounts();
  next();
});

const ROUTINES = [
  { name: 'Новинки', cadence: 'будни 09:33', url: 'https://claude.ai/code/routines/trig_01J15fDUaLRqpm14TxzLZs5d' },
  { name: 'Акции и уценка', cadence: 'будни 09:40', url: 'https://claude.ai/code/routines/trig_011Yk3zJWZKLz1ppg3iPDscE' },
  { name: 'Темы блога (предложить)', cadence: 'будни 07:00', url: 'https://claude.ai/code/routines/trig_013xSmLjQS2QE6yjb7gGs1Zp' },
  { name: 'Блог (генерация статей)', cadence: 'будни 19:00', url: 'https://claude.ai/code/routines/trig_01S3Goi79TaHVVkYgvYTU7CC' },
  { name: 'Пятничный пост', cadence: 'пятница 09:54', url: 'https://claude.ai/code/routines/trig_01VC6LnZDdtvLKEEYQAN1VBu' },
  { name: 'Мониторинг конкурентов', cadence: 'будни 08:30', url: 'https://claude.ai/code/routines/trig_0173oTYyg2LY1yaLJQGXfm2c' },
  { name: 'Идеи акций', cadence: 'понедельник 08:00', url: 'https://claude.ai/code/routines/trig_016TWCLzCrq4bA9QdUXY1xsB' },
  { name: 'Предложение постов', cadence: 'будни, каждый час 10:30–17:30', url: 'https://claude.ai/code/routines/trig_01A6iRVBPDPaH2Q1UEQ9R11P' },
];

const SOURCES = ['novinki', 'aktsii', 'ucenka', 'blog', 'friday', 'competitor'];

const SOURCE_LABELS = {
  novinki: 'Новинки',
  aktsii: 'Акции',
  ucenka: 'Уценка',
  blog: 'Блог',
  friday: 'Пятничный пост',
  competitor: 'Конкуренты',
};

dashboardRouter.get('/login', (req, res) => {
  res.render('login', { error: null });
});

dashboardRouter.post('/login', (req, res) => {
  const { username, password, remember } = req.body || {};
  if (checkCredentials(username || '', password || '')) {
    req.session.authenticated = true;
    if (remember) {
      req.session.cookie.maxAge = 90 * 24 * 60 * 60 * 1000;
    }
    return res.redirect('/');
  }
  res.render('login', { error: 'Неверный логин или пароль' });
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
  }).map((d) => ({ ...d, created_at: utcSqlToMskDisplay(d.created_at) }));
  const draftStats = getDraftStatsBySource();
  const sourceStats = SOURCES.map((key) => {
    const stats = draftStats[key] || { total: 0, new_count: 0, posted_count: 0, dismissed_count: 0, last_at: null };
    return {
      key,
      label: SOURCE_LABELS[key],
      ...stats,
      last_at: utcSqlToMskDisplay(stats.last_at),
    };
  });
  res.render('dashboard', {
    drafts,
    status,
    source,
    routines: ROUTINES,
    sourceLabels: SOURCE_LABELS,
    sourceStats,
    topicStats: getTopicStats(),
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

dashboardRouter.get('/topics', requireSession, (req, res) => {
  const status = req.query.status || 'pending';
  const topics = listTopics({ status: status === 'all' ? undefined : status })
    .map((t) => ({ ...t, created_at: utcSqlToMskDisplay(t.created_at) }));
  res.render('topics', { topics, status });
});

dashboardRouter.post('/topics/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!['approved', 'rejected', 'pending'].includes(status)) {
    return res.status(400).send('bad status');
  }
  setTopicStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/topics');
});

dashboardRouter.get('/tasks', requireSession, (req, res) => {
  const status = req.query.status || 'pending';
  const tasks = listTasks({ status: status === 'all' ? undefined : status }).map((t) => ({
    ...t,
    due_at_display: utcSqlToMskDisplay(t.due_at),
    remind_at_display: utcSqlToMskDisplay(t.remind_at),
  }));
  res.render('tasks', { tasks, status });
});

dashboardRouter.post('/tasks', requireSession, (req, res) => {
  const { title, description, due_at, remind_at } = req.body || {};
  if (!title) return res.status(400).send('title is required');
  createTask({
    title,
    description,
    due_at: mskLocalToUtcSql(due_at),
    remind_at: mskLocalToUtcSql(remind_at),
  });
  res.redirect('/tasks');
});

dashboardRouter.post('/tasks/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!['pending', 'done'].includes(status)) return res.status(400).send('bad status');
  setTaskStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/tasks');
});

dashboardRouter.post('/tasks/:id/delete', requireSession, (req, res) => {
  deleteTask(req.params.id);
  res.redirect(req.get('referer') || '/tasks');
});

dashboardRouter.get('/plan', requireSession, (req, res) => {
  const status = req.query.status || 'planned';
  const view = req.query.view === 'calendar' ? 'calendar' : 'list';
  const items = listPlanItems({ status: status === 'all' ? undefined : status })
    .map((i) => ({ ...i, colorClass: planColorClass(i.id) }));

  let month = null;
  let prevMonth = null;
  let nextMonth = null;
  let weeks = null;
  if (view === 'calendar') {
    const now = new Date();
    const defaultMonth = `${now.getUTCFullYear()}-${pad2(now.getUTCMonth() + 1)}`;
    month = /^\d{4}-\d{2}$/.test(req.query.month || '') ? req.query.month : defaultMonth;
    prevMonth = shiftMonth(month, -1);
    nextMonth = shiftMonth(month, 1);
    weeks = buildCalendarWeeks(month, items);
  }

  res.render('plan', {
    items,
    status,
    view,
    month,
    prevMonth,
    nextMonth,
    weeks,
    typeLabels: PLAN_TYPE_LABELS,
    statusLabels: PLAN_STATUS_LABELS,
  });
});

dashboardRouter.post('/plan', requireSession, (req, res) => {
  const { title, type, start_date, end_date, channels, description } = req.body || {};
  if (!title) return res.status(400).send('title is required');
  createPlanItem({ title, type, start_date, end_date, channels, description });
  res.redirect('/plan');
});

dashboardRouter.post('/plan/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!Object.keys(PLAN_STATUS_LABELS).includes(status)) return res.status(400).send('bad status');
  setPlanItemStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/plan');
});

dashboardRouter.post('/plan/:id/delete', requireSession, (req, res) => {
  deletePlanItem(req.params.id);
  res.redirect(req.get('referer') || '/plan');
});

dashboardRouter.get('/plan/:id/edit', requireSession, (req, res) => {
  const item = getPlanItem(req.params.id);
  if (!item) return res.status(404).send('not found');
  res.render('plan-edit', { item, typeLabels: PLAN_TYPE_LABELS });
});

dashboardRouter.post('/plan/:id/update', requireSession, (req, res) => {
  const { title, type, start_date, end_date, channels, description } = req.body || {};
  if (!title) return res.status(400).send('title is required');
  updatePlanItem(req.params.id, { title, type, start_date, end_date, channels, description });
  res.redirect('/plan');
});

dashboardRouter.get('/help', requireSession, (req, res) => {
  res.render('help');
});

dashboardRouter.get('/promo-ideas', requireSession, (req, res) => {
  const status = req.query.status || 'pending';
  const ideas = listPromoIdeas({ status: status === 'all' ? undefined : status })
    .map((idea) => ({ ...idea, created_at: utcSqlToMskDisplay(idea.created_at) }));
  res.render('promo-ideas', { ideas, status });
});

dashboardRouter.post('/promo-ideas/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!['approved', 'rejected', 'pending'].includes(status)) {
    return res.status(400).send('bad status');
  }
  setPromoIdeaStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/promo-ideas');
});

dashboardRouter.get('/repost-suggestions', requireSession, (req, res) => {
  const status = req.query.status || 'pending';
  const suggestions = listRepostSuggestions({ status: status === 'all' ? undefined : status })
    .map((s) => ({ ...s, created_at: utcSqlToMskDisplay(s.created_at) }));
  res.render('repost-suggestions', { suggestions, status });
});

dashboardRouter.post('/repost-suggestions/:id/status', requireSession, (req, res) => {
  const { status } = req.body || {};
  if (!['approved', 'rejected', 'pending'].includes(status)) {
    return res.status(400).send('bad status');
  }
  setRepostSuggestionStatus(req.params.id, status);
  res.redirect(req.get('referer') || '/repost-suggestions');
});
