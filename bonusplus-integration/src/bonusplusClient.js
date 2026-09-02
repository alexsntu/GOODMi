const API_BASE = process.env.BONUSPLUS_API_BASE || 'https://bonusplus.pro/api';
const API_KEY_B64 = Buffer.from(process.env.BONUSPLUS_API_KEY || '', 'utf8').toString('base64');

async function bonusPlusFetch(path, options = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      'Authorization': `ApiKey ${API_KEY_B64}`,
      'Content-Type': 'application/json',
      ...options.headers,
    },
  });

  if (!res.ok) {
    const body = await res.text().catch(() => '');
    const err = new Error(`BonusPlus API ${options.method || 'GET'} ${path} -> ${res.status}: ${body}`);
    err.status = res.status;
    throw err;
  }

  return res.json();
}

export function getCustomerByPhone(phone) {
  return bonusPlusFetch(`/customer?phone=${encodeURIComponent(phone)}`);
}

export function getBonusActivitiesByPhone(phone, { rowCount = 50, startRow = 1 } = {}) {
  return bonusPlusFetch('/retail/bonusActivities', {
    method: 'POST',
    body: JSON.stringify({ phone, rowCount, startRow, sort: -1 }),
  });
}
