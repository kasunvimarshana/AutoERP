'use strict';

const mongoose = require('mongoose');

const notificationSchema = new mongoose.Schema(
  {
    orderId: {
      type: String,
      required: true,
      index: true,
    },
    sagaId: {
      type: String,
      required: true,
      index: true,
    },
    type: {
      type: String,
      enum: ['order_confirmation', 'order_failure'],
      required: true,
    },
    status: {
      type: String,
      enum: ['pending', 'sent', 'failed'],
      default: 'pending',
    },
    recipient: {
      type: String,
      required: true,
    },
    content: {
      subject: { type: String },
      body:    { type: String },
    },
    sentAt:       { type: Date, default: null },
    errorMessage: { type: String, default: null },
  },
  {
    timestamps: true,
    versionKey: false,
  }
);

module.exports = mongoose.model('Notification', notificationSchema);
