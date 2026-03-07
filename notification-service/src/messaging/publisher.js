'use strict';

const amqp   = require('amqplib');
const config = require('../config');
const logger = require('../logger');

let channel = null;

/**
 * Get or create a RabbitMQ channel for publishing.
 */
async function getChannel() {
  if (channel) return channel;

  const conn = await amqp.connect(config.rabbitmq.url);
  channel    = await conn.createChannel();

  await channel.assertExchange(config.rabbitmq.exchanges.commands, 'direct', { durable: true });
  await channel.assertExchange(config.rabbitmq.exchanges.replies,  'direct', { durable: true });

  const queues = [
    { name: 'reserve-inventory',  exchange: config.rabbitmq.exchanges.commands },
    { name: 'release-inventory',  exchange: config.rabbitmq.exchanges.commands },
    { name: 'process-payment',    exchange: config.rabbitmq.exchanges.commands },
    { name: 'refund-payment',     exchange: config.rabbitmq.exchanges.commands },
    { name: 'send-notification',  exchange: config.rabbitmq.exchanges.commands },
    { name: 'saga-replies',       exchange: config.rabbitmq.exchanges.replies   },
  ];

  for (const q of queues) {
    await channel.assertQueue(q.name, { durable: true });
    await channel.bindQueue(q.name, q.exchange, q.name);
  }

  conn.on('error',  err => { logger.error('[Publisher] Connection error', { error: err.message }); channel = null; });
  conn.on('close',  ()  => { logger.warn('[Publisher] Connection closed'); channel = null; });

  logger.info('[Publisher] RabbitMQ channel ready');
  return channel;
}

/**
 * Publish a reply message to the saga.replies exchange.
 */
async function publishReply(exchange, routingKey, message) {
  const ch   = await getChannel();
  const body = Buffer.from(JSON.stringify(message));

  ch.publish(exchange, routingKey, body, {
    persistent:   true,
    contentType:  'application/json',
    timestamp:    Math.floor(Date.now() / 1000),
  });

  logger.debug('[Publisher] Message published', { exchange, routingKey, sagaId: message.saga_id });
}

module.exports = { publishReply };
