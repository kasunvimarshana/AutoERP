'use strict';

const mongoose = require('mongoose');

/**
 * Notification MongoDB document schema.
 *
 * Uses MongoDB (NoSQL) in contrast to the MySQL/PostgreSQL
 * used by the Laravel services, demonstrating polyglot persistence.
 *
 * @typedef {Object} NotificationDocument
 * @property {string} type         - Notification type (order_confirmed, order_cancelled, etc.)
 * @property {string} orderId      - Associated order UUID
 * @property {string} tenantId     - Tenant this notification belongs to
 * @property {string} recipient    - Email address of the recipient
 * @property {Object} payload      - Notification-specific data
 * @property {string} status       - pending|sent|failed
 * @property {string|null} errorMessage - Error details if status is 'failed'
 * @property {Date}   sentAt       - Timestamp when successfully sent
 */
const notificationSchema = new mongoose.Schema(
  {
    type: {
      type: String,
      required: true,
      enum: ['order_confirmed', 'order_cancelled', 'order_shipped', 'low_stock', 'user_registered'],
    },
    orderId:      { type: String, index: true },
    tenantId:     { type: String, required: true, index: true },
    recipient:    { type: String, required: true },
    payload:      { type: mongoose.Schema.Types.Mixed, default: {} },
    status:       { type: String, enum: ['pending', 'sent', 'failed'], default: 'pending' },
    errorMessage: { type: String, default: null },
    sentAt:       { type: Date,   default: null },
  },
  {
    timestamps: true,
    // Enable virtual id field (MongoDB default _id is mapped to id in JSON)
    toJSON:   { virtuals: true },
    toObject: { virtuals: true },
  }
);

// Compound index for tenant-level queries
notificationSchema.index({ tenantId: 1, createdAt: -1 });

module.exports = mongoose.model('Notification', notificationSchema);
