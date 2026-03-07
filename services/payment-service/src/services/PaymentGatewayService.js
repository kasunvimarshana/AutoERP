'use strict';

const { v4: uuidv4 } = require('uuid');
const config = require('../config');
const logger = require('../utils/logger');

/**
 * Simulates a Stripe-like payment gateway with configurable success rates.
 * In production this would be replaced with actual gateway SDK calls.
 */
class PaymentGatewayService {
  /**
   * Simulate network/processing latency.
   * @param {number} [ms]
   */
  _delay(ms) {
    return new Promise((resolve) =>
      setTimeout(resolve, ms ?? config.payment.simulatedLatencyMs)
    );
  }

  /**
   * Process a payment through the simulated gateway.
   *
   * @param {{
   *   amount: number,
   *   currency: string,
   *   paymentMethod: { type: string, last4?: string, brand?: string },
   *   orderId: string,
   *   metadata?: object
   * }} params
   * @returns {Promise<{ success: boolean, transactionId?: string, refundId?: string, gatewayResponse: object, errorCode?: string, errorMessage?: string }>}
   */
  async processPayment(params) {
    const { amount, currency, paymentMethod, orderId, metadata = {} } = params;

    await this._delay();

    const roll = Math.random();
    const successThreshold = config.payment.successRate;
    const insufficientFundsThreshold = successThreshold + 0.05;
    // Anything above insufficientFundsThreshold → gateway timeout

    logger.debug('PaymentGateway: processing payment', {
      orderId,
      amount,
      currency,
      roll: roll.toFixed(4),
    });

    if (roll < successThreshold) {
      const transactionId = `txn_${uuidv4().replace(/-/g, '')}`;
      return {
        success: true,
        transactionId,
        gatewayResponse: {
          id: transactionId,
          object: 'charge',
          amount: Math.round(amount * 100),
          currency: currency.toLowerCase(),
          status: 'succeeded',
          paymentMethod: {
            type: paymentMethod.type,
            last4: paymentMethod.last4,
            brand: paymentMethod.brand,
          },
          metadata,
          created: Math.floor(Date.now() / 1000),
        },
      };
    }

    if (roll < insufficientFundsThreshold) {
      logger.warn('PaymentGateway: insufficient funds', { orderId, amount });
      return {
        success: false,
        errorCode: 'insufficient_funds',
        errorMessage: 'Your card has insufficient funds.',
        gatewayResponse: {
          error: {
            code: 'insufficient_funds',
            decline_code: 'insufficient_funds',
            message: 'Your card has insufficient funds.',
            type: 'card_error',
          },
        },
      };
    }

    // Gateway timeout scenario
    logger.warn('PaymentGateway: timeout', { orderId });
    throw Object.assign(
      new Error('Payment gateway timeout. Please try again.'),
      { code: 'gateway_timeout' }
    );
  }

  /**
   * Refund a previously captured transaction.
   *
   * @param {{
   *   transactionId: string,
   *   amount: number,
   *   reason?: string
   * }} params
   * @returns {Promise<{ success: boolean, refundId?: string, gatewayResponse: object, errorCode?: string, errorMessage?: string }>}
   */
  async refundPayment(params) {
    const { transactionId, amount, reason = 'requested_by_customer' } = params;

    await this._delay();

    const roll = Math.random();

    logger.debug('PaymentGateway: processing refund', {
      transactionId,
      amount,
      roll: roll.toFixed(4),
    });

    if (roll < config.payment.refundSuccessRate) {
      const refundId = `re_${uuidv4().replace(/-/g, '')}`;
      return {
        success: true,
        refundId,
        gatewayResponse: {
          id: refundId,
          object: 'refund',
          amount: Math.round(amount * 100),
          chargeId: transactionId,
          reason,
          status: 'succeeded',
          created: Math.floor(Date.now() / 1000),
        },
      };
    }

    logger.warn('PaymentGateway: refund failed', { transactionId });
    return {
      success: false,
      errorCode: 'refund_failed',
      errorMessage: 'The refund could not be processed by the gateway.',
      gatewayResponse: {
        error: {
          code: 'refund_failed',
          message: 'The refund could not be processed.',
          type: 'api_error',
        },
      },
    };
  }
}

module.exports = new PaymentGatewayService();
