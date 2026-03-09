'use strict';
/**
 * Payment Service - Saga participant.
 * processPayment()  → compensation: refundPayment()
 * refundPayment()   → idempotent rollback
 */
const { v4: uuidv4 } = require('uuid');
const Payment = require('../models/payment.model');
const MessageBrokerFactory = require('../infrastructure/messaging/broker.factory');
const { logger } = require('../utils/logger');

class PaymentService {
  constructor() {
    this.broker = MessageBrokerFactory.create();
  }

  async processPayment({ sagaId, tenantId, customerId, amount, currency, paymentMethod }) {
    if (amount <= 0) {
      const err = new Error('Payment amount must be greater than zero.');
      err.code = 'INVALID_AMOUNT';
      throw err;
    }

    const gatewayResult = await this._callGateway({ amount, currency, paymentMethod });
    if (!gatewayResult.success) {
      const err = new Error(gatewayResult.message || 'Payment gateway declined the transaction.');
      err.code = 'PAYMENT_DECLINED';
      throw err;
    }

    const payment = await Payment.create({
      id: uuidv4(),
      tenant_id: tenantId,
      customer_id: customerId,
      saga_id: sagaId,
      amount,
      currency: currency || 'USD',
      payment_method: paymentMethod,
      status: 'completed',
      gateway_transaction_id: gatewayResult.transactionId,
      gateway_response: JSON.stringify(gatewayResult),
    });

    await this.broker.publish('payment.processed', {
      saga_id: sagaId, payment_id: payment.id, tenant_id: tenantId,
      amount, currency, status: 'completed',
    }, { exchange: 'payment.events', routingKey: 'saga.payment.processed' });

    logger.info('Payment processed', { paymentId: payment.id, sagaId, amount });

    return {
      id: payment.id, saga_id: sagaId, amount: payment.amount,
      currency: payment.currency, status: payment.status,
      gateway_transaction_id: payment.gateway_transaction_id,
      created_at: payment.created_at,
    };
  }

  async refundPayment(paymentId, { sagaId, reason } = {}) {
    let payment = await Payment.findOne({ where: { id: paymentId } });
    if (!payment && sagaId) {
      payment = await Payment.findOne({ where: { saga_id: sagaId } });
    }
    if (!payment) throw new Error(`Payment not found: ${paymentId}`);

    if (payment.status === 'refunded') {
      logger.info('Payment already refunded (idempotent)', { paymentId });
      return { id: payment.id, status: 'refunded' };
    }

    await payment.update({ status: 'refunded', refund_reason: reason, refunded_at: new Date() });

    await this.broker.publish('payment.refunded', {
      saga_id: sagaId, payment_id: payment.id, tenant_id: payment.tenant_id,
      amount: payment.amount, status: 'refunded', reason,
    }, { exchange: 'payment.events', routingKey: 'saga.payment.refunded' });

    logger.info('Payment refunded (Saga compensation)', { paymentId: payment.id, sagaId });
    return { id: payment.id, saga_id: sagaId, amount: payment.amount, status: 'refunded', refund_reason: reason };
  }

  async getPayment(paymentId) {
    return Payment.findOne({ where: { id: paymentId } });
  }

  async listPayments(tenantId, { page = 1, perPage } = {}) {
    const where = tenantId ? { tenant_id: tenantId } : {};
    const options = { where, order: [['created_at', 'DESC']] };
    if (perPage) { options.limit = perPage; options.offset = (page - 1) * perPage; }

    const { count, rows } = await Payment.findAndCountAll(options);
    const data = rows.map(p => ({
      id: p.id, tenant_id: p.tenant_id, saga_id: p.saga_id,
      amount: p.amount, currency: p.currency, status: p.status,
      payment_method: p.payment_method, created_at: p.created_at,
    }));

    if (!perPage) return { data, total: count };
    return { data, meta: { total: count, per_page: perPage, current_page: page, last_page: Math.ceil(count / perPage) } };
  }

  async _callGateway({ amount, currency, paymentMethod }) {
    await new Promise(r => setTimeout(r, 50));
    if (paymentMethod === 'test_decline_card') return { success: false, message: 'Card declined by issuer.' };
    return { success: true, transactionId: `txn_${uuidv4().replace(/-/g, '').slice(0, 20)}` };
  }
}

module.exports = PaymentService;
