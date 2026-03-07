'use strict';

const nodemailer = require('nodemailer');
const { v4: uuidv4 } = require('uuid');
const Notification = require('../models/Notification');
const config = require('../config');
const logger = require('../logger');

class NotificationService {
  constructor() {
    this.transporter = this._createTransporter();
  }

  /**
   * Create a nodemailer transporter.
   *
   * - **Production**: set EMAIL_HOST, EMAIL_PORT, EMAIL_USER, and EMAIL_PASS
   *   environment variables; a real SMTP connection will be established.
   * - **Development / Testing**: leave EMAIL_USER and EMAIL_PASS unset;
   *   a `jsonTransport` stub is used instead — messages are logged but never
   *   actually sent, so no external SMTP server is required.
   */
  _createTransporter() {
    if (config.email.user && config.email.pass) {
      return nodemailer.createTransport({
        host:   config.email.host,
        port:   config.email.port,
        secure: config.email.port === 465,
        auth: {
          user: config.email.user,
          pass: config.email.pass,
        },
      });
    }

    // No credentials provided — use a no-op transport that logs instead of sending
    logger.info('[NotificationService] No email credentials configured — using stub transport');
    return nodemailer.createTransport({
      jsonTransport: true,
    });
  }

  /**
   * Send an order confirmation notification.
   */
  async sendOrderConfirmation(sagaId, orderId, customerEmail, items, totalAmount) {
    logger.info('[NotificationService] Sending order confirmation', {
      sagaId,
      orderId,
      recipient: customerEmail,
    });

    const itemsList = Array.isArray(items)
      ? items.map(i => `  - ${i.product_name || i.product_id} × ${i.quantity} @ $${Number(i.price).toFixed(2)}`).join('\n')
      : '  (item details unavailable)';

    const subject = `Order Confirmation - Order #${orderId.slice(0, 8).toUpperCase()}`;
    const body = `
Dear Customer,

Your order has been confirmed! Here are the details:

Order ID: ${orderId}
Items:
${itemsList}

Total Amount: $${Number(totalAmount).toFixed(2)}

Thank you for your purchase!

The Saga Order Team
`.trim();

    const notification = new Notification({
      orderId,
      sagaId,
      type:      'order_confirmation',
      status:    'pending',
      recipient: customerEmail,
      content:   { subject, body },
    });

    await notification.save();

    try {
      const info = await this.transporter.sendMail({
        from:    config.email.from,
        to:      customerEmail,
        subject,
        text:    body,
      });

      notification.status = 'sent';
      notification.sentAt = new Date();
      await notification.save();

      logger.info('[NotificationService] Order confirmation sent', {
        sagaId,
        orderId,
        messageId: info.messageId,
      });

      return notification;
    } catch (err) {
      notification.status       = 'failed';
      notification.errorMessage = err.message;
      await notification.save();

      logger.error('[NotificationService] Failed to send order confirmation', {
        sagaId,
        orderId,
        error: err.message,
      });

      throw err;
    }
  }

  /**
   * Send an order failure notification.
   */
  async sendOrderFailure(sagaId, orderId, customerEmail, reason) {
    logger.info('[NotificationService] Sending order failure notification', {
      sagaId,
      orderId,
      recipient: customerEmail,
    });

    const subject = `Order Failed - Order #${orderId.slice(0, 8).toUpperCase()}`;
    const body = `
Dear Customer,

Unfortunately, your order could not be processed.

Order ID: ${orderId}
Reason: ${reason || 'An unexpected error occurred'}

No charges have been made. Please try again or contact support.

The Saga Order Team
`.trim();

    const notification = new Notification({
      orderId,
      sagaId,
      type:      'order_failure',
      status:    'pending',
      recipient: customerEmail,
      content:   { subject, body },
    });

    await notification.save();

    try {
      const info = await this.transporter.sendMail({
        from:    config.email.from,
        to:      customerEmail,
        subject,
        text:    body,
      });

      notification.status = 'sent';
      notification.sentAt = new Date();
      await notification.save();

      logger.info('[NotificationService] Order failure notification sent', {
        sagaId,
        orderId,
        messageId: info.messageId,
      });

      return notification;
    } catch (err) {
      notification.status       = 'failed';
      notification.errorMessage = err.message;
      await notification.save();

      logger.error('[NotificationService] Failed to send failure notification', {
        sagaId,
        error: err.message,
      });

      throw err;
    }
  }

  /**
   * Retrieve a notification by its MongoDB ID.
   */
  async getNotification(notificationId) {
    return Notification.findById(notificationId).lean();
  }

  /**
   * Retrieve all notifications for an order.
   */
  async getNotificationsByOrder(orderId) {
    return Notification.find({ orderId }).sort({ createdAt: -1 }).lean();
  }
}

module.exports = new NotificationService();
