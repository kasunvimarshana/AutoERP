'use strict';

const paymentService = require('../services/PaymentService');
const rabbitmq = require('../services/RabbitMQService');
const logger = require('../utils/logger');

class PaymentSagaHandler {
  /**
   * Handle the PROCESS_PAYMENT saga command.
   *
   * Expected payload:
   * {
   *   sagaId: string,
   *   orderId: string,
   *   tenantId: string,
   *   amount: number,
   *   currency: string,
   *   paymentMethod: { type, last4?, brand? }
   * }
   *
   * Publishes:
   *   payment.processed  — on success
   *   payment.failed     — on failure
   *
   * @param {object} payload
   */
  async handleProcessPayment(payload) {
    const { sagaId, orderId, tenantId, amount, currency, paymentMethod } = payload;

    logger.info('SagaHandler: handleProcessPayment', { sagaId, orderId });

    if (!sagaId || !orderId || !tenantId || !amount || !currency || !paymentMethod) {
      throw new Error(
        'handleProcessPayment: missing required fields: sagaId, orderId, tenantId, amount, currency, paymentMethod'
      );
    }

    try {
      const result = await paymentService.processPayment(
        sagaId,
        orderId,
        tenantId,
        amount,
        currency,
        paymentMethod
      );

      if (result.status === 'completed') {
        await rabbitmq.publishEvent('payment.processed', {
          sagaId,
          orderId,
          tenantId,
          paymentId: result.paymentId,
          gatewayTransactionId: result.gatewayTransactionId,
          amount,
          currency,
        });
        logger.info('SagaHandler: published payment.processed', { sagaId, orderId });
      } else {
        await rabbitmq.publishEvent('payment.failed', {
          sagaId,
          orderId,
          tenantId,
          paymentId: result.paymentId,
          errorMessage: result.errorMessage,
          errorCode: result.errorCode,
        });
        logger.warn('SagaHandler: published payment.failed', {
          sagaId,
          orderId,
          error: result.errorMessage,
        });
      }
    } catch (err) {
      logger.error('SagaHandler: unexpected error in handleProcessPayment', {
        sagaId,
        orderId,
        error: err.message,
      });
      await rabbitmq.publishEvent('payment.failed', {
        sagaId,
        orderId,
        tenantId,
        errorMessage: err.message,
        errorCode: err.code || 'internal_error',
      });
      // Re-throw so the consumer nacks the message → DLQ
      throw err;
    }
  }

  /**
   * Handle the REFUND_PAYMENT saga command.
   *
   * Expected payload:
   * {
   *   sagaId: string,
   *   orderId: string,
   *   reason?: string
   * }
   *
   * Publishes:
   *   payment.refunded — always (status field indicates success/failure)
   *
   * @param {object} payload
   */
  async handleRefundPayment(payload) {
    const { sagaId, orderId, reason } = payload;

    logger.info('SagaHandler: handleRefundPayment', { sagaId, orderId });

    if (!sagaId || !orderId) {
      throw new Error('handleRefundPayment: missing required fields: sagaId, orderId');
    }

    try {
      const result = await paymentService.refundPayment(sagaId, orderId, reason);

      await rabbitmq.publishEvent('payment.refunded', {
        sagaId,
        orderId,
        refundId: result.refundId,
        paymentId: result.paymentId,
        status: result.status,
        errorMessage: result.errorMessage || null,
      });

      logger.info('SagaHandler: published payment.refunded', {
        sagaId,
        orderId,
        status: result.status,
      });
    } catch (err) {
      logger.error('SagaHandler: unexpected error in handleRefundPayment', {
        sagaId,
        orderId,
        error: err.message,
      });
      await rabbitmq.publishEvent('payment.refunded', {
        sagaId,
        orderId,
        status: 'failed',
        errorMessage: err.message,
        errorCode: err.code || 'internal_error',
      });
      throw err;
    }
  }
}

module.exports = new PaymentSagaHandler();
