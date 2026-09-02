import { Router } from 'express';
import { requireSession } from '../auth.js';
import {
  isConfigured,
  getCustomer,
  patchBalance,
  parsePhoneAmountList,
} from '../bonusplus.js';
import {
  createBonusBatch,
  addBonusBatchItem,
  listBonusBatches,
  getBonusBatch,
  setBonusBatchStatus,
  listBonusBatchItems,
  updateBonusBatchItemStep1,
  updateBonusBatchItemStep2,
  logBonusEvent,
  listBonusLog,
} from '../db.js';

export const bonusesRouter = Router();
bonusesRouter.use(requireSession);

function utcSqlToMskDisplay(utcValue) {
  if (!utcValue) return null;
  const d = new Date(`${utcValue.replace(' ', 'T')}Z`);
  if (Number.isNaN(d.getTime())) return utcValue;
  const msk = new Date(d.getTime() + 3 * 60 * 60 * 1000);
  const pad = (n) => String(n).padStart(2, '0');
  return `${msk.getUTCFullYear()}-${pad(msk.getUTCMonth() + 1)}-${pad(msk.getUTCDate())} ${pad(msk.getUTCHours())}:${pad(msk.getUTCMinutes())}`;
}

function decorateItems(items) {
  return items.map((it) => {
    const deducted = (it.deducted_step1 || 0) + (it.deducted_step2 || 0);
    const shortfall = it.requested_deduct - deducted;
    return {
      ...it,
      total_deducted: deducted,
      shortfall,
      still_pending: it.step2_needed === 1 && it.step2_done === 0,
    };
  });
}

bonusesRouter.get('/', (req, res) => {
  const batches = listBonusBatches().map((b) => ({
    ...b,
    created_at_display: utcSqlToMskDisplay(b.created_at),
  }));
  res.render('bonuses', { batches, configured: isConfigured() });
});

bonusesRouter.get('/log', (req, res) => {
  const phone = req.query.phone || '';
  const batchId = req.query.batch || '';
  const entries = listBonusLog({
    phone: phone || undefined,
    batchId: batchId || undefined,
    limit: 500,
  }).map((e) => ({ ...e, created_at_display: utcSqlToMskDisplay(e.created_at) }));
  res.render('bonuses-log', { entries, phone, batchId });
});

bonusesRouter.post('/preview', async (req, res) => {
  if (!isConfigured()) return res.status(400).send('BONUSPLUS_API_KEY не настроен в .env портала');

  const { list } = req.body || {};
  const { totals, dupCounts } = parsePhoneAmountList(list || '');
  if (totals.size === 0) {
    return res.status(400).send('Список пуст или не распознан — формат: телефон и сумма через пробел, по одному на строку');
  }

  const batchId = createBonusBatch();
  logBonusEvent(batchId, { action: 'batch_created', details: `строк во входном списке: ${[...dupCounts.values()].reduce((a, b) => a + b, 0)}, уникальных телефонов: ${totals.size}` });

  for (const [phone, requestedDeduct] of totals.entries()) {
    const dupCount = dupCounts.get(phone) || 1;
    let name = null;
    let balance = 0;
    let pending = 0;
    try {
      const customer = await getCustomer(phone);
      balance = customer.availableBonuses || 0;
      pending = customer.notActiveBonuses || 0;
      name = (customer.person && customer.person.fn) || null;
    } catch (e) {
      logBonusEvent(batchId, { phone, action: 'preview_error', details: String(e.message || e) });
    }
    addBonusBatchItem(batchId, {
      phone,
      name,
      dup_count: dupCount,
      requested_deduct: requestedDeduct,
      balance_at_preview: balance,
      pending_at_preview: pending,
    });
    if (dupCount > 1) {
      logBonusEvent(batchId, { phone, action: 'duplicate_summed', amount: requestedDeduct, details: `${dupCount} строк в исходном списке сложены в одну сумму` });
    }
  }

  res.redirect(`/bonuses/${batchId}`);
});

bonusesRouter.get('/:id', (req, res) => {
  const batch = getBonusBatch(req.params.id);
  if (!batch) return res.status(404).send('Партия не найдена');
  const items = decorateItems(listBonusBatchItems(batch.id));

  const needsActivationPartial = items.filter((it) => it.still_pending && it.deducted_step1 > 0);
  const needsActivationZero = items.filter((it) => it.still_pending && (it.deducted_step1 || 0) === 0);
  const dupItems = items.filter((it) => it.dup_count > 1);

  const totals = {
    requested: items.reduce((s, it) => s + it.requested_deduct, 0),
    deducted: items.reduce((s, it) => s + it.total_deducted, 0),
    remaining: items.reduce((s, it) => s + Math.max(0, it.shortfall), 0),
  };
  const uncollectible = items.filter(
    (it) => it.shortfall > 0.001 && !it.still_pending && batch.status !== 'preview'
  );

  res.render('bonus-batch', {
    batch: { ...batch, created_at_display: utcSqlToMskDisplay(batch.created_at) },
    items,
    needsActivationPartial,
    needsActivationZero,
    dupItems,
    totals,
    uncollectible,
  });
});

bonusesRouter.post('/:id/execute', async (req, res) => {
  const batch = getBonusBatch(req.params.id);
  if (!batch) return res.status(404).send('Партия не найдена');
  if (batch.status !== 'preview') return res.redirect(`/bonuses/${batch.id}`);

  const items = listBonusBatchItems(batch.id);
  let anyStep2Needed = false;

  for (const it of items) {
    const actualDeduct = Math.min(it.requested_deduct, it.balance_at_preview || 0);
    let error = null;
    if (actualDeduct > 0) {
      try {
        await patchBalance(it.phone, -actualDeduct);
        logBonusEvent(batch.id, { phone: it.phone, action: 'deduct_step1', amount: actualDeduct, details: `баланс до списания: ${it.balance_at_preview}` });
      } catch (e) {
        error = String(e.message || e);
        logBonusEvent(batch.id, { phone: it.phone, action: 'error', details: error });
      }
    }
    const shortfall = it.requested_deduct - (error ? 0 : actualDeduct);
    const step2Needed = !error && shortfall > 0.001 && (it.pending_at_preview || 0) > 0;
    if (step2Needed) anyStep2Needed = true;
    updateBonusBatchItemStep1(it.id, {
      deducted_step1: error ? 0 : actualDeduct,
      balance_at_preview: it.balance_at_preview,
      pending_at_preview: it.pending_at_preview,
      step2_needed: step2Needed,
      error,
    });
  }

  setBonusBatchStatus(batch.id, anyStep2Needed ? 'executed' : 'done');
  logBonusEvent(batch.id, { action: anyStep2Needed ? 'step1_finished_awaiting_activation' : 'step1_finished_done' });
  res.redirect(`/bonuses/${batch.id}`);
});

bonusesRouter.post('/:id/followup', async (req, res) => {
  const batch = getBonusBatch(req.params.id);
  if (!batch) return res.status(404).send('Партия не найдена');

  const items = listBonusBatchItems(batch.id).filter((it) => it.step2_needed === 1 && it.step2_done === 0);
  if (items.length === 0) return res.redirect(`/bonuses/${batch.id}`);

  let stillPendingCount = 0;

  for (const it of items) {
    const shortfall = it.requested_deduct - (it.deducted_step1 || 0);
    let error = null;
    let freshBalance = 0;
    let freshPending = 0;
    try {
      const customer = await getCustomer(it.phone);
      freshBalance = customer.availableBonuses || 0;
      freshPending = customer.notActiveBonuses || 0;
    } catch (e) {
      error = String(e.message || e);
      logBonusEvent(batch.id, { phone: it.phone, action: 'error', details: error });
      updateBonusBatchItemStep2(it.id, { deducted_step2: 0, step2_needed: true, pending_at_preview: it.pending_at_preview, error });
      stillPendingCount += 1;
      continue;
    }

    const deductNow = Math.min(shortfall, freshBalance);
    if (deductNow > 0) {
      try {
        await patchBalance(it.phone, -deductNow);
        logBonusEvent(batch.id, { phone: it.phone, action: 'deduct_step2', amount: deductNow, details: `баланс после активации: ${freshBalance}` });
      } catch (e) {
        error = String(e.message || e);
        logBonusEvent(batch.id, { phone: it.phone, action: 'error', details: error });
      }
    }

    const stillNeedsMore = !error && freshPending > 0 && deductNow < shortfall;
    if (stillNeedsMore) {
      stillPendingCount += 1;
      logBonusEvent(batch.id, { phone: it.phone, action: 'still_not_activated', details: `ожидает: ${freshPending}, недосписано: ${shortfall - deductNow}` });
    }
    updateBonusBatchItemStep2(it.id, {
      deducted_step2: error ? 0 : deductNow,
      step2_needed: stillNeedsMore,
      pending_at_preview: freshPending,
      error,
    });
  }

  setBonusBatchStatus(batch.id, stillPendingCount > 0 ? 'executed' : 'done');
  logBonusEvent(batch.id, { action: stillPendingCount > 0 ? 'step2_finished_partial' : 'step2_finished_done' });
  res.redirect(`/bonuses/${batch.id}`);
});
