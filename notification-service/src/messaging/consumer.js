'use strict';

const amqp               = require('amqplib');
const config             = require('../config');
const logger             = require('../logger');
const { publishReply }   = require('./publisher');
const notificationService = require('../services/notificationService');

const MAX_RETRIES    = 5;
const RETRY_DELAY_MS = 3000;

/**
 * Connect to RabbitMQ with retry logic.
 */
async function connectWithRetry(url, attempt = 1) {
  try {
    const conn = await amqp.connect(url);
    logger.info('[Consumer] Connected to RabbitMQ', { attempt });
    return conn;
  } catch (err) {
    if (attempt > MAX_RETRIES) {
      throw new Error(`[Consumer] Failed to connect after ${MAX_RETRIES} attempts: ${err.message}`);
    }
    const delay = RETRY_DELAY_MS * attempt;
    logger.warn('[Consumer] RabbitMQ connection failed, retrying...', { attempt, delay, error: err.message });
    await new Promise(resolve => setTimeout(resolve, delay));
    return connectWithRetry(url, attempt + 1);
  }
}

/**
 * Start consuming messages from the send-notification queue.
 */
async function startConsumer() {
  const conn    = await connectWithRetry(config.rabbitmq.url);
  const channel = await conn.createChannel();

  await channel.assertExchange(config.rabbitmq.exchanges.commands, 'direct', { durable: true });
  await channel.assertExchange(config.rabbitmq.exchanges.replies,  'direct', { durable: true });

  const queueName = config.rabbitmq.queues.sendNotification;

  await channel.assertQueue(queueName, { durable: true });
  await channel.bindQueue(queueName, config.rabbitmq.exchanges.commands, queueName);

  // Also ensure the replies queue is declared
  await channel.assertQueue(config.rabbitmq.queues.sagaReplies, { durable: true });
  await channel.bindQueue(
    config.rabbitmq.queues.sagaReplies,
    config.rabbitmq.exchanges.replies,
    config.rabbitmq.queues.sagaReplies
  );

  await channel.prefetch(1);

  logger.info('[Consumer] Waiting for messages', { queue: queueName });

  channel.consume(queueName, async (msg) => {
    if (!msg) return;

    let parsed = null;
    try {
      parsed = JSON.parse(msg.content.toString());
      await handleMessage(parsed);
      channel.ack(msg);
    } catch (err) {
      logger.error('[Consumer] Failed to process message', {
        error:  err.message,
        sagaId: parsed?.saga_id || 'unknown',
      });
      channel.nack(msg, false, false); // discard — do not requeue to avoid infinite loop
    }
  });

  conn.on('error',  err => logger.error('[Consumer] Connection error', { error: err.message }));
  conn.on('close',  ()  => logger.warn('[Consumer] Connection closed — service restart required'));
}

/**
 * Handle an incoming send-notification command message.
 */
async function handleMessage(message) {
  const { saga_id: sagaId, order_id: orderId, payload } = message;

  logger.info('[Consumer] Processing notification command', { sagaId, orderId });

  const customerEmail = payload?.customer_email;
  const items         = payload?.items         || [];
  const totalAmount   = payload?.total_amount  || 0;

  try {
    await notificationService.sendOrderConfirmation(
      sagaId,
      orderId,
      customerEmail,
      items,
      totalAmount
    );

    await publishReply(
      config.rabbitmq.exchanges.replies,
      config.rabbitmq.queues.sagaReplies,
      {
        saga_id:   sagaId,
        order_id:  orderId,
        type:      'NOTIFICATION_SENT',
        success:   true,
        data:      { recipient: customerEmail },
        error:     '',
        timestamp: new Date().toISOString(),
      }
    );

    logger.info('[Consumer] Notification sent, reply published', { sagaId, orderId });
  } catch (err) {
    logger.error('[Consumer] Notification failed', { sagaId, orderId, error: err.message });

    await publishReply(
      config.rabbitmq.exchanges.replies,
      config.rabbitmq.queues.sagaReplies,
      {
        saga_id:   sagaId,
        order_id:  orderId,
        type:      'NOTIFICATION_FAILED',
        success:   false,
        data:      {},
        error:     err.message,
        timestamp: new Date().toISOString(),
      }
    );
  }
}

module.exports = { startConsumer };
