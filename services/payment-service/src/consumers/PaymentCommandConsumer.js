'use strict';

const rabbitmq = require('../services/RabbitMQService');
const sagaHandler = require('../saga/PaymentSagaHandler');
const config = require('../config');
const logger = require('../utils/logger');

const COMMAND_HANDLERS = {
  PROCESS_PAYMENT: (payload) => sagaHandler.handleProcessPayment(payload),
  REFUND_PAYMENT: (payload) => sagaHandler.handleRefundPayment(payload),
};

/**
 * Subscribe to the payment.commands queue and route messages to the
 * appropriate saga handler based on the `command` field.
 *
 * ACK  on successful handling.
 * NACK (requeue=false) on failure → message goes to the DLQ.
 */
async function startPaymentCommandConsumer() {
  await rabbitmq.subscribe(config.rabbitmq.paymentQueue, async (payload, _msg) => {
    const { command } = payload;

    if (!command) {
      logger.error('PaymentCommandConsumer: received message without command field', { payload });
      throw new Error('Missing command field'); // → DLQ
    }

    const handler = COMMAND_HANDLERS[command];
    if (!handler) {
      logger.error('PaymentCommandConsumer: unknown command', { command });
      throw new Error(`Unknown command: ${command}`); // → DLQ
    }

    logger.info('PaymentCommandConsumer: dispatching command', { command, sagaId: payload.sagaId });
    await handler(payload);
  });

  logger.info('PaymentCommandConsumer: listening on queue', { queue: config.rabbitmq.paymentQueue });
}

module.exports = { startPaymentCommandConsumer };
