'use strict';

const amqp     = require('amqplib');
const logger   = require('../utils/logger');
const NotificationProcessor = require('../services/NotificationProcessor');

/**
 * RabbitMQ consumer for async notification processing.
 *
 * Listens on the 'notifications' queue for events published
 * by other microservices (e.g., Order Service after order confirmation).
 *
 * This is the Choreography-style complement to the HTTP-based
 * Orchestration pattern used by the Order Service Saga.
 */
const QUEUE_NAME = 'notifications';

let connection = null;
let channel    = null;
const processor = new NotificationProcessor();

const consumer = {
  /**
   * Connect to RabbitMQ and start consuming messages.
   */
  async start() {
    const url = process.env.RABBITMQ_URL || 'amqp://localhost';

    try {
      connection = await amqp.connect(url);
      channel    = await connection.createChannel();

      // Durable queue – survives broker restart
      await channel.assertQueue(QUEUE_NAME, { durable: true });

      // Process one message at a time
      channel.prefetch(1);

      logger.info(`RabbitMQ consumer started – listening on queue: ${QUEUE_NAME}`);

      channel.consume(QUEUE_NAME, async (msg) => {
        if (!msg) return;

        try {
          const content = JSON.parse(msg.content.toString());
          logger.info('Received notification event', { type: content.type });

          await processor.process(content);

          // Acknowledge the message after successful processing
          channel.ack(msg);

        } catch (err) {
          logger.error('Failed to process notification message', { error: err.message });

          // Reject and requeue on processing failure (retry once only –
          // redelivered messages are dead-lettered to prevent infinite loops)
          channel.nack(msg, false, !msg.fields.redelivered);
        }
      });

    } catch (err) {
      logger.error('Failed to connect to RabbitMQ', { error: err.message });
      // Retry after 5 seconds
      setTimeout(() => consumer.start(), 5000);
    }
  },

  /**
   * Gracefully close the consumer connection.
   */
  async stop() {
    try {
      if (channel)    await channel.close();
      if (connection) await connection.close();
      logger.info('RabbitMQ consumer stopped');
    } catch (err) {
      logger.error('Error stopping RabbitMQ consumer', { error: err.message });
    }
  },
};

module.exports = consumer;
