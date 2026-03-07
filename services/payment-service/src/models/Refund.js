'use strict';

const mongoose = require('mongoose');
const { v4: uuidv4 } = require('uuid');

const refundSchema = new mongoose.Schema(
  {
    refundId: {
      type: String,
      required: true,
      unique: true,
      default: () => uuidv4(),
      index: true,
    },
    paymentId: {
      type: String,
      required: true,
      trim: true,
      index: true,
      ref: 'Payment',
    },
    sagaId: {
      type: String,
      required: true,
      trim: true,
      index: true,
    },
    orderId: {
      type: String,
      required: true,
      trim: true,
      index: true,
    },
    amount: {
      type: Number,
      required: true,
      min: 0,
    },
    reason: {
      type: String,
      trim: true,
      default: 'Order cancellation',
    },
    status: {
      type: String,
      required: true,
      enum: ['pending', 'completed', 'failed'],
      default: 'pending',
      index: true,
    },
    gatewayRefundId: {
      type: String,
      trim: true,
      sparse: true,
      index: true,
    },
    gatewayResponse: {
      type: mongoose.Schema.Types.Mixed,
      default: null,
    },
    processedAt: {
      type: Date,
      default: null,
    },
    errorMessage: {
      type: String,
      default: null,
    },
  },
  {
    timestamps: true,
    versionKey: false,
    toJSON: {
      transform: (_doc, ret) => {
        delete ret._id;
        return ret;
      },
    },
  }
);

// Idempotency: one refund per saga+order
refundSchema.index({ sagaId: 1, orderId: 1 }, { unique: true });

const Refund = mongoose.model('Refund', refundSchema);

module.exports = Refund;
