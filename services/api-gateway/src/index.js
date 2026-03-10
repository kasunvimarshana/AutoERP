'use strict';

require('dotenv').config();
const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const rateLimit = require('express-rate-limit');
const morgan = require('morgan');
const cors = require('cors');
const helmet = require('helmet');
const jwt = require('jsonwebtoken');
const winston = require('winston');

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'change-me-in-production';

// ─── Logger ───────────────────────────────────────────────────────────────────
const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  transports: [new winston.transports.Console()],
});

// ─── Service Registry ─────────────────────────────────────────────────────────
const serviceRegistry = {
  orders:        process.env.ORDER_SERVICE_URL        || 'http://order-service:8000',
  payments:      process.env.PAYMENT_SERVICE_URL      || 'http://payment-service:8000',
  inventory:     process.env.INVENTORY_SERVICE_URL    || 'http://inventory-service:8000',
  notifications: process.env.NOTIFICATION_SERVICE_URL || 'http://notification-service:3001',
};

// ─── Global Middleware ────────────────────────────────────────────────────────
app.use(helmet());
app.use(cors());
app.use(morgan('combined'));
app.use(express.json());

// Correlation-ID injection
app.use((req, _res, next) => {
  req.correlationId =
    req.headers['x-correlation-id'] ||
    `gw-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  next();
});

// ─── Rate Limiter ─────────────────────────────────────────────────────────────
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS || '900000', 10),
  max:      parseInt(process.env.RATE_LIMIT_MAX       || '100',    10),
  standardHeaders: true,
  legacyHeaders:   false,
  message: { error: 'Too many requests, please try again later.' },
});
app.use('/api/', limiter);

// ─── JWT Authentication Middleware ────────────────────────────────────────────
function authenticate(req, res, next) {
  const authHeader = req.headers['authorization'];
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Missing or malformed Authorization header.' });
  }
  const token = authHeader.slice(7);
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    req.user = decoded;
    // Forward tenant + user info to downstream services
    req.headers['x-tenant-id'] = decoded.tenantId || '';
    req.headers['x-user-id']   = decoded.sub       || '';
    req.headers['x-user-role'] = decoded.role       || 'guest';
    next();
  } catch (err) {
    logger.warn('JWT verification failed', { error: err.message });
    return res.status(401).json({ error: 'Invalid or expired token.' });
  }
}

// ─── Proxy Factory ────────────────────────────────────────────────────────────
function makeProxy(target) {
  return createProxyMiddleware({
    target,
    changeOrigin: true,
    on: {
      proxyReq: (proxyReq, req) => {
        proxyReq.setHeader('X-Correlation-ID', req.correlationId);
        proxyReq.setHeader('X-Forwarded-By', 'api-gateway');
      },
      error: (err, _req, res) => {
        logger.error('Proxy error', { error: err.message, target });
        res.status(502).json({ error: 'Bad Gateway – upstream service unavailable.' });
      },
    },
  });
}

// ─── Routes ───────────────────────────────────────────────────────────────────
app.get('/health', (_req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    services: Object.entries(serviceRegistry).map(([name, url]) => ({ name, url })),
  });
});

// Public auth endpoint (order-service issues tokens for demo)
app.post('/api/auth/login', makeProxy(serviceRegistry.orders));

// Protected routes
app.use('/api/orders',        authenticate, makeProxy(serviceRegistry.orders));
app.use('/api/payments',      authenticate, makeProxy(serviceRegistry.payments));
app.use('/api/inventory',     authenticate, makeProxy(serviceRegistry.inventory));
app.use('/api/notifications', authenticate, makeProxy(serviceRegistry.notifications));

// ─── 404 Handler ─────────────────────────────────────────────────────────────
app.use((_req, res) => {
  res.status(404).json({ error: 'Route not found.' });
});

// ─── Global Error Handler ─────────────────────────────────────────────────────
app.use((err, _req, res, _next) => {
  logger.error('Unhandled error', { error: err.message, stack: err.stack });
  res.status(500).json({ error: 'Internal server error.' });
});

// ─── Start ────────────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  logger.info(`API Gateway listening on port ${PORT}`);
  logger.info('Service registry:', serviceRegistry);
});

module.exports = app;
