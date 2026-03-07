'use strict';

const express   = require('express');
const { body, validationResult } = require('express-validator');
const NotificationProcessor = require('../services/NotificationProcessor');
const Notification  = require('../models/Notification');
const logger    = require('../utils/logger');

const router    = express.Router();
const processor = new NotificationProcessor();

/**
 * POST /api/v1/notifications
 *
 * HTTP ingestion endpoint called synchronously by the Order Service
 * Saga (SendNotificationStep).
 *
 * Also accepts async events from RabbitMQ via consumer.js.
 */
router.post(
  '/',
  [
    body('type').notEmpty().isString(),
    body('tenant_id').notEmpty().isString(),
    body('recipient').notEmpty().isEmail(),
    body('payload').optional().isObject(),
  ],
  async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(422).json({ errors: errors.array() });
    }

    try {
      const notification = await processor.process({
        type:      req.body.type,
        orderId:   req.body.order_id,
        tenantId:  req.body.tenant_id,
        recipient: req.body.recipient,
        payload:   req.body.payload || {},
      });

      return res.status(201).json({
        message: 'Notification queued.',
        data:    notification,
      });

    } catch (err) {
      logger.error('Failed to process notification request', { error: err.message });
      return res.status(500).json({ message: 'Failed to process notification.' });
    }
  }
);

/**
 * GET /api/v1/notifications
 *
 * List notifications for a tenant (X-Tenant-ID header required).
 */
router.get('/', async (req, res) => {
  const tenantId = req.headers['x-tenant-id'];

  if (!tenantId) {
    return res.status(403).json({ message: 'Missing X-Tenant-ID header.' });
  }

  try {
    const page    = parseInt(req.query.page  || '1',  10);
    const perPage = Math.min(parseInt(req.query.limit || '20', 10), 100);
    const skip    = (page - 1) * perPage;

    const [notifications, total] = await Promise.all([
      Notification.find({ tenantId })
        .sort({ createdAt: -1 })
        .skip(skip)
        .limit(perPage),
      Notification.countDocuments({ tenantId }),
    ]);

    return res.json({
      data: notifications,
      meta: {
        total,
        page,
        per_page: perPage,
        last_page: Math.ceil(total / perPage),
      },
    });

  } catch (err) {
    logger.error('Failed to list notifications', { error: err.message });
    return res.status(500).json({ message: 'Failed to retrieve notifications.' });
  }
});

module.exports = router;
