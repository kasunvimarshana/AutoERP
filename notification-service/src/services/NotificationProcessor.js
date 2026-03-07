'use strict';

const Notification           = require('../models/Notification');
const EmailNotificationService = require('./EmailNotificationService');
const logger                 = require('../utils/logger');

/**
 * Processes incoming notification requests.
 *
 * Persists the notification to MongoDB, then dispatches it
 * to the appropriate channel service.  Supports both HTTP
 * (called by Order Service) and AMQP (RabbitMQ event bus) ingestion.
 */
class NotificationProcessor {
  constructor() {
    // Channel registry – add new channels here without changing process()
    this.channelServices = {
      email: new EmailNotificationService(),
    };
  }

  /**
   * Process and send a notification.
   *
   * @param {Object} notificationData
   * @param {string} notificationData.type
   * @param {string} notificationData.orderId
   * @param {string} notificationData.tenantId
   * @param {string} notificationData.recipient
   * @param {Object} notificationData.payload
   * @param {string} [notificationData.channel='email']
   * @returns {Promise<import('../models/Notification').NotificationDocument>}
   */
  async process(notificationData) {
    const {
      type,
      orderId,
      tenantId,
      recipient,
      payload = {},
      channel = 'email',
    } = notificationData;

    // Persist to MongoDB first (regardless of delivery outcome)
    const notification = await Notification.create({
      type,
      orderId,
      tenantId,
      recipient,
      payload,
      status: 'pending',
    });

    // Mask recipient email to avoid exposing PII in logs
    const maskedRecipient = recipient.replace(/(?<=.).(?=[^@]*@)/g, '*');
    logger.info('Notification created', { id: notification.id, type, recipient: maskedRecipient });

    // Build the message content based on notification type
    const { subject, body, html } = this.buildMessage(type, payload);

    // Dispatch to the appropriate channel service
    const service = this.channelServices[channel];

    if (!service) {
      const errMsg = `Unknown notification channel: ${channel}`;
      logger.warn(errMsg);
      await notification.updateOne({ status: 'failed', errorMessage: errMsg });
      return notification;
    }

    const result = await service.send({ recipient, subject, body, html });

    if (result.success) {
      await notification.updateOne({ status: 'sent', sentAt: new Date() });
      notification.status = 'sent';
    } else {
      await notification.updateOne({ status: 'failed', errorMessage: result.error });
      notification.status = 'failed';
    }

    return notification;
  }

  /**
   * Build subject/body based on notification type.
   *
   * @param {string} type
   * @param {Object} payload
   * @returns {{ subject: string, body: string, html: string }}
   */
  buildMessage(type, payload) {
    const templates = {
      order_confirmed: {
        subject: `Order Confirmed – #${payload.order_id || 'N/A'}`,
        body:    `Your order has been confirmed. Order ID: ${payload.order_id}`,
        html:    `<h2>Order Confirmed</h2><p>Your order <strong>#${payload.order_id}</strong> has been confirmed.</p>`,
      },
      order_cancelled: {
        subject: `Order Cancelled – #${payload.order_id || 'N/A'}`,
        body:    `Your order has been cancelled. Order ID: ${payload.order_id}`,
        html:    `<h2>Order Cancelled</h2><p>Your order <strong>#${payload.order_id}</strong> has been cancelled.</p>`,
      },
      user_registered: {
        subject: 'Welcome to our platform!',
        body:    `Welcome! Your account has been created.`,
        html:    `<h2>Welcome!</h2><p>Your account has been created successfully.</p>`,
      },
      low_stock: {
        subject: `Low Stock Alert – ${payload.product_name || 'Product'}`,
        body:    `Stock level for "${payload.product_name}" is below reorder threshold.`,
        html:    `<h2>Low Stock Alert</h2><p>Stock level for <strong>${payload.product_name}</strong> is below the reorder threshold.</p>`,
      },
    };

    return templates[type] || {
      subject: 'Notification',
      body:    JSON.stringify(payload),
      html:    `<pre>${JSON.stringify(payload, null, 2)}</pre>`,
    };
  }
}

module.exports = NotificationProcessor;
