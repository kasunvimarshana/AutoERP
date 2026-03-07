'use strict';

/**
 * Contract (interface) for notification sending strategies.
 *
 * JavaScript doesn't have native interfaces, but we define
 * an abstract-style base class that documents the expected API.
 * Concrete implementations (EmailNotificationService, SMSNotificationService,
 * PushNotificationService) must override these methods.
 *
 * This makes it easy to add new notification channels without
 * modifying existing consumers.
 */
class NotificationServiceContract {
  /**
   * Send a notification.
   *
   * @param {Object} options
   * @param {string} options.recipient   - Target (email, phone, device token)
   * @param {string} options.subject     - Message subject
   * @param {string} options.body        - Message body (plain text)
   * @param {string} [options.html]      - HTML body (optional, for email)
   * @returns {Promise<{ success: boolean, messageId?: string }>}
   */
  // eslint-disable-next-line no-unused-vars
  async send({ recipient, subject, body, html }) {
    throw new Error(`${this.constructor.name} must implement the send() method`);
  }

  /**
   * Identify the channel this service delivers to.
   * @returns {string}  e.g. 'email', 'sms', 'push'
   */
  getChannel() {
    throw new Error(`${this.constructor.name} must implement the getChannel() method`);
  }
}

module.exports = NotificationServiceContract;
