'use strict';

const amqp    = require('amqplib');
const Inventory = require('../models/Inventory');
const logger  = require('../middleware/logger');

/**
 * ProductEventConsumer
 *
 * Subscribes to the RabbitMQ "product_events" exchange and handles
 * product.created, product.updated, and product.deleted events
 * published by the Laravel Product Service.
 *
 * This is the key cross-service event-driven integration:
 * - product.created  → create default inventory record
 * - product.updated  → sync product_name / product_sku in inventory records
 * - product.deleted  → cascade delete all related inventory records
 *
 * Connection resilience: exponential backoff reconnect on failure.
 */

const EXCHANGE       = process.env.RABBITMQ_EXCHANGE        || 'product_events';
const QUEUE          = process.env.RABBITMQ_QUEUE           || 'inventory_product_events';
const ROUTING_KEYS   = (process.env.RABBITMQ_ROUTING_KEYS   || 'product.created,product.updated,product.deleted').split(',');
const RECONNECT_DELAY = parseInt(process.env.RABBITMQ_RECONNECT_DELAY_MS || '5000', 10);
const MAX_RETRIES     = parseInt(process.env.RABBITMQ_MAX_RETRIES         || '10', 10);

let retryCount = 0;

/**
 * Connect to RabbitMQ and start consuming product events.
 * Implements exponential backoff on connection failure.
 */
async function connectAndConsume() {
  const rabbitmqUrl = process.env.RABBITMQ_URL || 'amqp://guest:guest@rabbitmq:5672/';

  try {
    logger.info('ProductEventConsumer: Connecting to RabbitMQ...', { url: rabbitmqUrl.replace(/:[^:@]+@/, ':***@') });

    const connection = await amqp.connect(rabbitmqUrl);
    const channel    = await connection.createChannel();

    retryCount = 0; // Reset retry counter on successful connection

    // Declare the same topic exchange as the Product Service
    await channel.assertExchange(EXCHANGE, 'topic', { durable: true });

    // Declare an exclusive queue for this service instance
    const { queue } = await channel.assertQueue(QUEUE, {
      durable: true,       // Survive RabbitMQ restarts
      arguments: {
        'x-dead-letter-exchange': `${EXCHANGE}.dlx`, // Dead-letter exchange for failed messages
      },
    });

    // Bind to all product routing keys
    for (const routingKey of ROUTING_KEYS) {
      await channel.bindQueue(queue, EXCHANGE, routingKey.trim());
      logger.info(`ProductEventConsumer: Bound queue "${queue}" to "${routingKey}"`);
    }

    // Prefetch 1 ensures fair dispatch and prevents message flooding
    channel.prefetch(1);

    logger.info('ProductEventConsumer: Waiting for product events...');

    // Start consuming messages
    channel.consume(queue, async (msg) => {
      if (!msg) return;

      try {
        const payload      = JSON.parse(msg.content.toString());
        const { event }    = payload;

        logger.info(`ProductEventConsumer: Received event "${event}"`, { payload });

        switch (event) {
          case 'product.created':
            await handleProductCreated(payload);
            break;
          case 'product.updated':
            await handleProductUpdated(payload);
            break;
          case 'product.deleted':
            await handleProductDeleted(payload);
            break;
          default:
            logger.warn(`ProductEventConsumer: Unknown event type "${event}"`);
        }

        // Acknowledge the message on success
        channel.ack(msg);
      } catch (err) {
        logger.error('ProductEventConsumer: Error processing message', {
          error:   err.message,
          content: msg.content.toString(),
        });
        // Negative acknowledge — requeue once, then send to DLX
        channel.nack(msg, false, false);
      }
    });

    // Handle connection-level errors (reconnect)
    connection.on('error', (err) => {
      logger.error('ProductEventConsumer: Connection error', { error: err.message });
      scheduleReconnect();
    });

    connection.on('close', () => {
      logger.warn('ProductEventConsumer: Connection closed, reconnecting...');
      scheduleReconnect();
    });
  } catch (err) {
    logger.error('ProductEventConsumer: Failed to connect', { error: err.message });
    scheduleReconnect();
  }
}

/**
 * Schedule a reconnect with exponential backoff.
 */
function scheduleReconnect() {
  if (retryCount >= MAX_RETRIES) {
    logger.error(`ProductEventConsumer: Max retries (${MAX_RETRIES}) reached. Giving up.`);
    return;
  }

  // Caps the exponent at 5 so the maximum single delay is RECONNECT_DELAY * 2^5
  // e.g., 5000ms * 32 = 160 seconds maximum between retries
  const delay = RECONNECT_DELAY * Math.pow(2, Math.min(retryCount, 5));
  retryCount++;

  logger.info(`ProductEventConsumer: Reconnecting in ${delay}ms (attempt ${retryCount}/${MAX_RETRIES})...`);
  setTimeout(connectAndConsume, delay);
}

/**
 * Handle product.created event.
 *
 * Creates a default inventory record for the new product.
 * The initial quantity is seeded from the product's stock value.
 *
 * @param {object} payload - { event, product_id, name, sku, stock, ... }
 */
async function handleProductCreated(payload) {
  const { product_id, name, sku, stock = 0 } = payload;

  try {
    // Check if inventory already exists to ensure idempotency
    const existing = await Inventory.findOne({
      product_id,
      warehouse_location: 'Main Warehouse',
    });

    if (existing) {
      logger.info('ProductEventConsumer: Inventory already exists for product, skipping', { product_id });
      return;
    }

    const inventory = new Inventory({
      product_id,
      product_name:       name,
      product_sku:        sku,
      quantity:           stock,
      warehouse_location: 'Main Warehouse',
    });

    await inventory.save();

    logger.info('ProductEventConsumer: Created inventory record for new product', {
      product_id,
      product_name: name,
      quantity:     stock,
    });
  } catch (err) {
    logger.error('ProductEventConsumer: Failed to create inventory for product', {
      product_id,
      error: err.message,
    });
    throw err; // Re-throw so the message is nacked and retried
  }
}

/**
 * Handle product.updated event.
 *
 * Syncs product_name and product_sku in all related inventory records.
 * This handles the case where a product is renamed — the Inventory Service
 * updates its denormalized copies to maintain consistency.
 *
 * @param {object} payload - { event, product_id, name, sku, previous_name, ... }
 */
async function handleProductUpdated(payload) {
  const { product_id, name, sku, previous_name } = payload;

  try {
    const updateData = {
      product_name: name,
    };

    if (sku !== undefined) {
      updateData.product_sku = sku;
    }

    const result = await Inventory.updateMany(
      { product_id },
      { $set: updateData }
    );

    logger.info('ProductEventConsumer: Updated inventory records for product', {
      product_id,
      new_name:      name,
      previous_name: previous_name || 'N/A',
      updated_count: result.modifiedCount,
    });
  } catch (err) {
    logger.error('ProductEventConsumer: Failed to update inventory for product', {
      product_id,
      error: err.message,
    });
    throw err;
  }
}

/**
 * Handle product.deleted event.
 *
 * Deletes ALL inventory records associated with the deleted product.
 * This implements the cross-service cascade delete pattern using
 * event-driven messaging instead of database foreign keys.
 *
 * @param {object} payload - { event, product_id, name, ... }
 */
async function handleProductDeleted(payload) {
  const { product_id, name } = payload;

  try {
    const result = await Inventory.deleteMany({ product_id });

    logger.info('ProductEventConsumer: Cascade-deleted inventory records for deleted product', {
      product_id,
      product_name:  name,
      deleted_count: result.deletedCount,
    });
  } catch (err) {
    logger.error('ProductEventConsumer: Failed to cascade-delete inventory for product', {
      product_id,
      error: err.message,
    });
    throw err;
  }
}

module.exports = {
  connectAndConsume,
  // Expose handlers for unit testing (not used in production)
  _test: {
    handleProductCreated,
    handleProductUpdated,
    handleProductDeleted,
  },
};
