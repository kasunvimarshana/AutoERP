'use strict';

const mongoose = require('mongoose');
const { v4: uuidv4 } = require('uuid');

const paymentMethodSchema = new mongoose.Schema(
  {
    type: { type: String, required: true, trim: true },
    last4: { type: String, trim: true, maxlength: 4 },
    brand: { type: String, trim: true },
  },
  { _id: false }
);

const paymentSchema = new mongoose.Schema(
  {
    paymentId: {
      type: String,
      required: true,
      unique: true,
      default: () => uuidv4(),
      index: true,
    },
    orderId: {
      type: String,
      required: true,
      trim: true,
      index: true,
    },
    sagaId: {
      type: String,
      required: true,
      trim: true,
      index: true,
    },
    tenantId: {
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
    currency: {
      type: String,
      required: true,
      uppercase: true,
      trim: true,
      maxlength: 3,
      default: 'USD',
    },
    status: {
      type: String,
      required: true,
      enum: ['pending', 'processing', 'completed', 'failed', 'refunded'],
      default: 'pending',
      index: true,
    },
    paymentMethod: {
      type: paymentMethodSchema,
      required: true,
    },
    gatewayTransactionId: {
      type: String,
      trim: true,
      sparse: true,
      index: true,
    },
    gatewayResponse: {
      type: mongoose.Schema.Types.Mixed,
      default: null,
    },
    metadata: {
      type: mongoose.Schema.Types.Mixed,
      default: {},
    },
    processedAt: {
      type: Date,
      default: null,
    },
    refundedAt: {
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

// Compound index for idempotency: one payment per saga+order
paymentSchema.index({ sagaId: 1, orderId: 1 }, { unique: true });

// Compound index for tenant-scoped queries
paymentSchema.index({ tenantId: 1, createdAt: -1 });

const Payment = mongoose.model('Payment', paymentSchema);

module.exports = Payment;
