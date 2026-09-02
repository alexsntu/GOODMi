import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import { getCustomerByPhone, getBonusActivitiesByPhone } from './bonusplusClient.js';
import { normalizePhone, isValidPhone } from './phone.js';

if (!process.env.BONUSPLUS_API_KEY) {
  console.error('Отсутствует BONUSPLUS_API_KEY — заполните .env по образцу .env.example');
  process.exit(1);
}

const app = express();
const allowedOrigin = process.env.ALLOWED_ORIGIN || 'https://goodmi.ru';

app.use(cors({ origin: allowedOrigin }));

app.get('/healthz', (req, res) => res.json({ ok: true }));

app.get('/api/bonuses', async (req, res) => {
  const phone = normalizePhone(req.query.phone);

  if (!isValidPhone(phone)) {
    return res.status(400).json({ error: 'Некорректный номер телефона' });
  }

  try {
    const [customer, activities] = await Promise.all([
      getCustomerByPhone(phone),
      getBonusActivitiesByPhone(phone),
    ]);

    res.json({
      balance: customer.availableBonuses ?? 0,
      pendingBonuses: customer.notActiveBonuses ?? 0,
      expiringAmount: customer.nearestBonusesExpirationAmount ?? 0,
      expiringDate: customer.nearestBonusesExpirationDate || null,
      tier: customer.discountCardName || null,
      nextTier: customer.nextCardName || null,
      purchasesSumToNextCard: customer.purchasesSumToNextCard ?? 0,
      totalBonusCredit: customer.totalBonusCredit ?? 0,
      totalBonusDebit: customer.totalBonusDebit ?? 0,
      history: (activities.bonusActivities || []).map((a) => ({
        date: a.receiptDate,
        type: a.transactionName,
        description: a.description,
        credit: a.bonusCredit ?? 0,
        debit: a.bonusDebit ?? 0,
        remainAmount: a.remainAmount ?? null,
      })),
    });
  } catch (err) {
    if (err.status === 404) {
      // клиент ещё не зарегистрирован в БонусПлюс
      return res.json({
        balance: 0,
        pendingBonuses: 0,
        expiringAmount: 0,
        expiringDate: null,
        tier: null,
        nextTier: null,
        purchasesSumToNextCard: 0,
        totalBonusCredit: 0,
        totalBonusDebit: 0,
        history: [],
      });
    }
    console.error(err);
    res.status(502).json({ error: 'Не удалось получить данные БонусПлюс' });
  }
});

const port = process.env.PORT || 3100;
app.listen(port, () => {
  console.log(`BonusPlus proxy listening on :${port}`);
});
