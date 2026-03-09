'use strict';

const { Router } = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const healthRouter = require('./health');
const authMiddleware = require('../middleware/auth');
const tenantMiddleware = require('../middleware/tenant');

const router = Router();

const serviceUrls = {
  auth: process.env.AUTH_SERVICE_URL || 'http://auth-service:9000',
  inventory: process.env.INVENTORY_SERVICE_URL || 'http://inventory-service:9000',
  order: process.env.ORDER_SERVICE_URL || 'http://order-service:9000',
  notification: process.env.NOTIFICATION_SERVICE_URL || 'http://notification-service:9000',
  saga: process.env.SAGA_ORCHESTRATOR_URL || 'http://saga-orchestrator:9000',
};

/**
 * Create a proxy middleware for a downstream service.
 * Forwards all headers and adds X-Forwarded-For.
 */
function createProxy(target) {
  return createProxyMiddleware({
    target,
    changeOrigin: true,
    on: {
      error: (err, req, res) => {
        res.status(502).json({
          success: false,
          message: 'Service temporarily unavailable.',
          service: target,
        });
      },
    },
    pathRewrite: {},
  });
}

// ── Health check ──────────────────────────────────────────────────────────────
router.use('/health', healthRouter);

// ── Auth Service routes (no auth required for login/register) ─────────────────
router.use('/api/auth', createProxy(serviceUrls.auth));

// ── Protected routes (auth + tenant required) ─────────────────────────────────
router.use(
  '/api/users',
  authMiddleware,
  tenantMiddleware,
  createProxy(serviceUrls.auth)
);

router.use(
  '/api/products',
  authMiddleware,
  tenantMiddleware,
  createProxy(serviceUrls.inventory)
);

router.use(
  '/api/orders',
  authMiddleware,
  tenantMiddleware,
  createProxy(serviceUrls.order)
);

router.use(
  '/api/notifications',
  authMiddleware,
  tenantMiddleware,
  createProxy(serviceUrls.notification)
);

router.use(
  '/api/webhooks',
  authMiddleware,
  tenantMiddleware,
  createProxy(serviceUrls.notification)
);

router.use(
  '/api/sagas',
  authMiddleware,
  createProxy(serviceUrls.saga)
);

// ── 404 fallback ──────────────────────────────────────────────────────────────
router.use((req, res) => {
  res.status(404).json({
    success: false,
    message: `Route ${req.method} ${req.path} not found.`,
  });
});

module.exports = router;
