'use strict';

const { v4: uuidv4 } = require('uuid');
const Payment = require('../models/Payment');
const Refund = require('../models/Refund');
const gateway = require('./PaymentGatewayService');
const logger = require('../utils/logger');

class PaymentService {
  /**
   * Process a payment for the given saga/order.
   * Idempotent: if a payment with the same sagaId already exists, the
   * existing record is returned without contacting the gateway again.
   *
   * @param {string} sagaId
   * @param {string} orderId
   * @param {string} tenantId
   * @param {number} amount
   * @param {string} currency
   * @param {{ type: string, last4?: string, brand?: string }} paymentMethod
   * @returns {Promise<{ paymentId: string, status: string, gatewayTransactionId?: string, errorMessage?: string }>}
   */
  async processPayment(sagaId, orderId, tenantId, amount, currency, paymentMethod) {
    logger.info('PaymentService: processPayment start', { sagaId, orderId, tenantId, amount, currency });

    // ── Idempotency guard ─────────────────────────────────────────────────────
    const existing = await Payment.findOne({ sagaId, orderId });
    if (existing) {
      logger.info('PaymentService: duplicate payment detected — returning cached result', {
        sagaId,
        orderId,
        paymentId: existing.paymentId,
        status: existing.status,
      });
      return {
        paymentId: existing.paymentId,
        status: existing.status,
        gatewayTransactionId: existing.gatewayTransactionId,
        errorMessage: existing.errorMessage,
      };
    }

    // ── Create payment record (status: processing) ────────────────────────────
    const paymentId = uuidv4();
    const payment = await Payment.create({
      paymentId,
      orderId,
      sagaId,
      tenantId,
      amount,
      currency: currency.toUpperCase(),
      status: 'processing',
      paymentMethod: {
        type: paymentMethod.type,
        last4: paymentMethod.last4 || null,
        brand: paymentMethod.brand || null,
      },
    });

    // ── Call gateway ──────────────────────────────────────────────────────────
    try {
      const result = await gateway.processPayment({
        amount,
        currency,
        paymentMethod,
        orderId,
        metadata: { sagaId, tenantId },
      });

      if (result.success) {
        payment.status = 'completed';
        payment.gatewayTransactionId = result.transactionId;
        payment.gatewayResponse = result.gatewayResponse;
        payment.processedAt = new Date();
        await payment.save();

        logger.info('PaymentService: payment completed', {
          paymentId,
          sagaId,
          transactionId: result.transactionId,
        });

        return {
          paymentId,
          status: 'completed',
          gatewayTransactionId: result.transactionId,
        };
      }

      // Declined by gateway (e.g. insufficient funds)
      payment.status = 'failed';
      payment.errorMessage = result.errorMessage;
      payment.gatewayResponse = result.gatewayResponse;
      await payment.save();

      logger.warn('PaymentService: payment declined', { paymentId, sagaId, error: result.errorMessage });

      return {
        paymentId,
        status: 'failed',
        errorMessage: result.errorMessage,
        errorCode: result.errorCode,
      };
    } catch (err) {
      // Unexpected gateway error (timeout etc.)
      payment.status = 'failed';
      payment.errorMessage = err.message;
      payment.gatewayResponse = { error: err.message, code: err.code };
      await payment.save();

      logger.error('PaymentService: gateway error during payment', {
        paymentId,
        sagaId,
        error: err.message,
      });

      return {
        paymentId,
        status: 'failed',
        errorMessage: err.message,
        errorCode: err.code || 'gateway_error',
      };
    }
  }

  /**
   * Refund a payment identified by sagaId/orderId.
   * Idempotent: duplicate refund requests for the same sagaId return the
   * existing refund record.
   *
   * @param {string} sagaId
   * @param {string} orderId
   * @param {string} [reason]
   * @returns {Promise<{ refundId: string, paymentId: string, status: string, errorMessage?: string }>}
   */
  async refundPayment(sagaId, orderId, reason = 'Order cancellation') {
    logger.info('PaymentService: refundPayment start', { sagaId, orderId });

    // ── Idempotency guard ─────────────────────────────────────────────────────
    const existingRefund = await Refund.findOne({ sagaId, orderId });
    if (existingRefund) {
      logger.info('PaymentService: duplicate refund detected — returning cached result', {
        sagaId,
        orderId,
        refundId: existingRefund.refundId,
      });
      return {
        refundId: existingRefund.refundId,
        paymentId: existingRefund.paymentId,
        status: existingRefund.status,
        errorMessage: existingRefund.errorMessage,
      };
    }

    // ── Find original payment ─────────────────────────────────────────────────
    const payment = await Payment.findOne({ sagaId, orderId });
    if (!payment) {
      throw Object.assign(
        new Error(`No payment found for sagaId=${sagaId} orderId=${orderId}`),
        { code: 'PAYMENT_NOT_FOUND' }
      );
    }

    if (payment.status !== 'completed') {
      throw Object.assign(
        new Error(`Cannot refund payment with status '${payment.status}'`),
        { code: 'INVALID_PAYMENT_STATUS' }
      );
    }

    // ── Create refund record ──────────────────────────────────────────────────
    const refundId = uuidv4();
    const refund = await Refund.create({
      refundId,
      paymentId: payment.paymentId,
      sagaId,
      orderId,
      amount: payment.amount,
      reason,
      status: 'pending',
    });

    // ── Call gateway ──────────────────────────────────────────────────────────
    try {
      const result = await gateway.refundPayment({
        transactionId: payment.gatewayTransactionId,
        amount: payment.amount,
        reason,
      });

      if (result.success) {
        refund.status = 'completed';
        refund.gatewayRefundId = result.refundId;
        refund.gatewayResponse = result.gatewayResponse;
        refund.processedAt = new Date();
        await refund.save();

        payment.status = 'refunded';
        payment.refundedAt = new Date();
        await payment.save();

        logger.info('PaymentService: refund completed', { refundId, sagaId });

        return {
          refundId,
          paymentId: payment.paymentId,
          status: 'completed',
          gatewayRefundId: result.refundId,
        };
      }

      refund.status = 'failed';
      refund.errorMessage = result.errorMessage;
      refund.gatewayResponse = result.gatewayResponse;
      await refund.save();

      logger.warn('PaymentService: refund declined', { refundId, sagaId, error: result.errorMessage });

      return {
        refundId,
        paymentId: payment.paymentId,
        status: 'failed',
        errorMessage: result.errorMessage,
      };
    } catch (err) {
      refund.status = 'failed';
      refund.errorMessage = err.message;
      await refund.save();

      logger.error('PaymentService: gateway error during refund', { refundId, sagaId, error: err.message });

      return {
        refundId,
        paymentId: payment.paymentId,
        status: 'failed',
        errorMessage: err.message,
        errorCode: err.code || 'gateway_error',
      };
    }
  }

  /**
   * Retrieve a paginated, tenant-scoped list of payments.
   *
   * @param {string} tenantId
   * @param {{ page?: number, limit?: number, status?: string }} [opts]
   */
  async listPayments(tenantId, opts = {}) {
    const page = Math.max(1, parseInt(opts.page || 1, 10));
    const limit = Math.min(100, Math.max(1, parseInt(opts.limit || 20, 10)));
    const skip = (page - 1) * limit;

    const filter = { tenantId };
    if (opts.status) filter.status = opts.status;

    const [data, total] = await Promise.all([
      Payment.find(filter).sort({ createdAt: -1 }).skip(skip).limit(limit),
      Payment.countDocuments(filter),
    ]);

    return {
      data,
      pagination: { page, limit, total, pages: Math.ceil(total / limit) },
    };
  }

  /**
   * Get a single payment by paymentId, scoped to a tenant.
   *
   * @param {string} paymentId
   * @param {string} tenantId
   */
  async getPayment(paymentId, tenantId) {
    return Payment.findOne({ paymentId, tenantId });
  }

  /**
   * Get a payment by orderId, scoped to a tenant.
   *
   * @param {string} orderId
   * @param {string} tenantId
   */
  async getPaymentByOrder(orderId, tenantId) {
    return Payment.findOne({ orderId, tenantId }).sort({ createdAt: -1 });
  }
}

module.exports = new PaymentService();
