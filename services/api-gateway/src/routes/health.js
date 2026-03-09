'use strict';

const { Router } = require('express');
const axios = require('axios');

const router = Router();

const services = {
  'auth-service': process.env.AUTH_SERVICE_URL || 'http://auth-service:9000',
  'inventory-service': process.env.INVENTORY_SERVICE_URL || 'http://inventory-service:9000',
  'order-service': process.env.ORDER_SERVICE_URL || 'http://order-service:9000',
  'notification-service': process.env.NOTIFICATION_SERVICE_URL || 'http://notification-service:9000',
  'saga-orchestrator': process.env.SAGA_ORCHESTRATOR_URL || 'http://saga-orchestrator:9000',
};

/**
 * GET /health
 * Aggregated health check across all downstream services.
 */
router.get('/', async (req, res) => {
  const checks = {};

  await Promise.allSettled(
    Object.entries(services).map(async ([name, url]) => {
      try {
        const response = await axios.get(`${url}/api/health/live`, { timeout: 3000 });
        checks[name] = { status: response.status === 200 ? 'ok' : 'degraded' };
      } catch (err) {
        checks[name] = { status: 'error', message: err.message };
      }
    })
  );

  const healthy = Object.values(checks).every(c => c.status === 'ok');

  return res.status(healthy ? 200 : 503).json({
    service: 'api-gateway',
    status: healthy ? 'healthy' : 'degraded',
    timestamp: new Date().toISOString(),
    checks,
  });
});

/**
 * GET /health/live - Liveness probe
 */
router.get('/live', (req, res) => {
  res.json({ status: 'alive' });
});

/**
 * GET /health/ready - Readiness probe
 */
router.get('/ready', (req, res) => {
  res.json({ status: 'ready' });
});

module.exports = router;
