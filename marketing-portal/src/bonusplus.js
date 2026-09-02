const API_BASE = process.env.BONUSPLUS_API_BASE || 'https://bonusplus.pro/api';

function authHeader() {
  const key = process.env.BONUSPLUS_API_KEY || '';
  return 'ApiKey ' + Buffer.from(key, 'utf8').toString('base64');
}

export function isConfigured() {
  return Boolean(process.env.BONUSPLUS_API_KEY);
}

async function bpFetch(path, options = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      Authorization: authHeader(),
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    const err = new Error(`BonusPlus ${options.method || 'GET'} ${path} -> ${res.status}: ${body}`);
    err.status = res.status;
    throw err;
  }
  return res.json();
}

export function normalizePhone(raw) {
  const digits = String(raw || '').replace(/\D/g, '');
  if (digits.length === 11 && digits.startsWith('8')) return '7' + digits.slice(1);
  if (digits.length === 10) return '7' + digits;
  return digits;
}

export function isValidPhone(digits) {
  return /^7\d{10}$/.test(digits);
}

// GET /customer?phone= — активные (availableBonuses) и ожидающие (notActiveBonuses) бонусы.
export function getCustomer(phone) {
  return bpFetch(`/customer?phone=${encodeURIComponent(phone)}`);
}

// PATCH /customer/{phone}/balance — amount отрицательный = списание, положительный = начисление.
// НЕ использовать с transactionType:30 для "активации" ожидающих бонусов — это отдельное
// начисление, а не перевод, приводит к задвоению (проверено эмпирически 2026-09-02).
export function patchBalance(phone, amount) {
  return bpFetch(`/customer/${encodeURIComponent(phone)}/balance`, {
    method: 'PATCH',
    body: JSON.stringify({ amount }),
  });
}

// Парсит текст со строками "телефон сумма", суммирует дубли телефонов.
export function parsePhoneAmountList(text) {
  const totals = new Map();
  const dupCounts = new Map();
  for (const rawLine of String(text || '').split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line) continue;
    const parts = line.split(/[\s,;]+/).filter(Boolean);
    if (parts.length < 2) continue;
    const phone = normalizePhone(parts[0]);
    const amount = Number(parts[1].replace(',', '.'));
    if (!isValidPhone(phone) || !Number.isFinite(amount) || amount <= 0) continue;
    totals.set(phone, (totals.get(phone) || 0) + amount);
    dupCounts.set(phone, (dupCounts.get(phone) || 0) + 1);
  }
  return { totals, dupCounts };
}
