'use strict';
/**
 * Payment Controller - thin HTTP layer. Saga participant.
 */
const PaymentService = require('../services/payment.service');
const { logger } = require('../utils/logger');

class PaymentController {
  constructor() { this.svc = new PaymentService(); }

  async process(req, res) {
    try {
      const { saga_id, tenant_id, customer_id, amount, currency, payment_method } = req.body;
      const result = await this.svc.processPayment({
        sagaId: saga_id,
        tenantId: tenant_id || req.headers['x-tenant-id'],
        customerId: customer_id,
        amount: parseFloat(amount),
        currency: currency || 'USD',
        paymentMethod: payment_method,
      });
      res.status(200).json({ success: true, data: result, payment_id: result.id });
    } catch (err) {
      logger.error('Payment processing failed', { error: err.message, sagaId: req.body.saga_id });
      res.status(422).json({ success: false, message: err.message, error_code: err.code || 'PAYMENT_FAILED' });
    }
  }

  async refund(req, res) {
    try {
      const result = await this.svc.refundPayment(req.params.paymentId, {
        sagaId: req.body.saga_id,
        reason: req.body.reason || 'Saga compensation rollback',
      });
      res.json({ success: true, data: result });
    } catch (err) {
      res.status(500).json({ success: false, message: err.message, error_code: 'REFUND_FAILED' });
    }
  }

  async show(req, res) {
    try {
      const p = await this.svc.getPayment(req.params.paymentId);
      if (!p) return res.status(404).json({ success: false, message: 'Payment not found.' });
      res.json({ success: true, data: p });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
  }

  async index(req, res) {
    try {
      const tenantId = req.headers['x-tenant-id'] || req.query.tenant_id;
      const { page, per_page } = req.query;
      const result = await this.svc.listPayments(tenantId, {
        page: parseInt(page, 10) || 1,
        perPage: per_page ? parseInt(per_page, 10) : undefined,
      });
      res.json({ success: true, data: result });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
  }

  async status(req, res) {
    try {
      const p = await this.svc.getPayment(req.params.paymentId);
      res.json({ success: true, data: { id: p?.id, status: p?.status } });
    } catch (err) { res.status(404).json({ success: false, message: 'Payment not found.' }); }
  }
}

module.exports = PaymentController;
