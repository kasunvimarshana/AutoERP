'use strict';

const express             = require('express');
const notificationService = require('../services/notificationService');
const logger              = require('../logger');

const router = express.Router();

/**
 * GET /notifications/:id
 * Retrieve a single notification by its MongoDB ID.
 */
router.get('/:id', async (req, res) => {
  try {
    const notification = await notificationService.getNotification(req.params.id);
    if (!notification) {
      return res.status(404).json({ message: 'Notification not found' });
    }
    res.json({ data: notification });
  } catch (err) {
    logger.error('[Route] GET /notifications/:id error', { error: err.message });
    res.status(500).json({ message: 'Internal server error' });
  }
});

/**
 * GET /notifications/order/:orderId
 * Retrieve all notifications for a given order ID.
 */
router.get('/order/:orderId', async (req, res) => {
  try {
    const notifications = await notificationService.getNotificationsByOrder(req.params.orderId);
    res.json({
      data: notifications,
      meta: { total: notifications.length },
    });
  } catch (err) {
    logger.error('[Route] GET /notifications/order/:orderId error', { error: err.message });
    res.status(500).json({ message: 'Internal server error' });
  }
});

module.exports = router;
