FROM node:18-alpine

RUN apk add --no-cache python3 make g++

WORKDIR /app

RUN addgroup -g 1001 nodejs && adduser -u 1001 -G nodejs -s /bin/sh -D portal

COPY package.json package-lock.json* ./
RUN npm install --omit=dev && npm cache clean --force

COPY src ./src
COPY public ./public

RUN mkdir -p /app/data && chown -R portal:nodejs /app

USER portal

ENV NODE_ENV=production
EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s \
  CMD node -e "fetch('http://localhost:3000/healthz').then(r => process.exit(r.ok ? 0 : 1)).catch(() => process.exit(1))"

CMD ["node", "src/index.js"]
