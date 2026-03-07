'use strict';

const express = require('express');
const paymentService = require('../services/PaymentService');
const { authenticate } = require('../middleware/auth');
const {
  validateBody,
  validateQuery,
  createPaymentSchema,
  listPaymentsSchema,
} = require('../middleware/validation');
const logger = require('../utils/logger');

const router = express.Router();

// All routes in this file require a valid JWT
router.use(authenticate);

// ── GET /api/v1/payments ──────────────────────────────────────────────────────
router.get('/', validateQuery(listPaymentsSchema), async (req, res, next) => {
  try {
    const result = await paymentService.listPayments(req.tenant.id, req.query);
    res.json(result);
  } catch (err) {
    next(err);
  }
});

// ── GET /api/v1/payments/order/:orderId ───────────────────────────────────────
// Must be defined BEFORE /:paymentId to avoid route shadowing
router.get('/order/:orderId', async (req, res, next) => {
  try {
    const payment = await paymentService.getPaymentByOrder(
      req.params.orderId,
      req.tenant.id
    );
    if (!payment) {
      return res.status(404).json({ error: 'Payment not found for this order' });
    }
    res.json(payment);
  } catch (err) {
    next(err);
  }
});

// ── GET /api/v1/payments/:paymentId ──────────────────────────────────────────
router.get('/:paymentId', async (req, res, next) => {
  try {
    const payment = await paymentService.getPayment(
      req.params.paymentId,
      req.tenant.id
    );
    if (!payment) {
      return res.status(404).json({ error: 'Payment not found' });
    }
    res.json(payment);
  } catch (err) {
    next(err);
  }
});

// ── POST /api/v1/payments ─────────────────────────────────────────────────────
// Direct payment endpoint — useful for testing without going through the SAGA.
router.post('/', validateBody(createPaymentSchema), async (req, res, next) => {
  try {
    const { sagaId, orderId, amount, currency, paymentMethod, metadata } = req.body;
    const tenantId = req.tenant.id;

    const result = await paymentService.processPayment(
      sagaId,
      orderId,
      tenantId,
      amount,
      currency,
      paymentMethod,
      metadata
    );

    const statusCode = result.status === 'completed' ? 201 : 402;
    res.status(statusCode).json(result);
  } catch (err) {
    logger.error('POST /payments error', { error: err.message });
    next(err);
  }
});

module.exports = router;
