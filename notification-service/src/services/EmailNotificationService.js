'use strict';

const nodemailer                  = require('nodemailer');
const NotificationServiceContract = require('../contracts/NotificationServiceContract');
const logger                      = require('../utils/logger');

/**
 * Email notification delivery via SMTP (Nodemailer).
 *
 * Implements NotificationServiceContract so it can be
 * swapped for another channel (SMS, push, etc.) without
 * changing any calling code.
 */
class EmailNotificationService extends NotificationServiceContract {
  constructor() {
    super();

    this.transporter = nodemailer.createTransport({
      host:   process.env.SMTP_HOST   || 'localhost',
      port:   parseInt(process.env.SMTP_PORT || '587', 10),
      secure: false,
      auth: process.env.SMTP_USER
        ? { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS }
        : undefined,
    });

    if (process.env.SMTP_USER && !process.env.SMTP_PASS) {
      logger.warn('SMTP_USER is set but SMTP_PASS is missing – email authentication will likely fail');
    }
  }

  /** @inheritdoc */
  getChannel() {
    return 'email';
  }

  /**
   * Send an email notification.
   *
   * @param {{ recipient: string, subject: string, body: string, html?: string }}
   * @returns {Promise<{ success: boolean, messageId?: string }>}
   */
  async send({ recipient, subject, body, html }) {
    const from = process.env.SMTP_FROM || 'noreply@saas.local';

    try {
      const info = await this.transporter.sendMail({
        from,
        to:      recipient,
        subject,
        text:    body,
        html:    html || body,
      });

      logger.info('Email sent', { messageId: info.messageId, recipient });

      return { success: true, messageId: info.messageId };

    } catch (err) {
      logger.error('Email send failed', { recipient, error: err.message });
      return { success: false, error: err.message };
    }
  }
}

module.exports = EmailNotificationService;
