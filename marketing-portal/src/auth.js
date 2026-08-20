import bcrypt from 'bcryptjs';

export function checkPassword(password) {
  const hash = process.env.ADMIN_PASSWORD_HASH;
  if (!hash) return false;
  return bcrypt.compareSync(password, hash);
}

export function requireSession(req, res, next) {
  if (req.session?.authenticated) return next();
  res.redirect('/login');
}

export function requireApiToken(req, res, next) {
  const header = req.get('authorization') || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (token && token === process.env.INGEST_API_TOKEN) return next();
  res.status(401).json({ error: 'unauthorized' });
}
