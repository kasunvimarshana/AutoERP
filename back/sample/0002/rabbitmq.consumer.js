// src/events/rabbitmq.consumer.js
'use strict';

const amqplib = require('amqplib');
const Inventory = require('../models/inventory.model');
const logger = require('../config/logger');

const EXCHANGE = process.env.RABBITMQ_EXCHANGE || 'product_events';
const QUEUE = 'inventory_service_queue';

/**
 * RabbitMQ Consumer for Inventory Service
 *
 * Subscribes to the `product_events` fanout exchange and reacts to:
 *   product.created → initialise a zero-stock inventory record
 *   product.updated → sync the denormalised productName field
 *   product.deleted → soft-delete all inventory records for the product
 *
 * Retry / DLQ strategy
 * ─────────────────────
 * Failed messages are nack'd with requeue=false and routed to a dead-letter
 * queue for human inspection. In production, add exponential back-off.
 */
class RabbitMQConsumer {
  constructor() {
    this.connection = null;
    this.channel = null;
  }

  async connect(retries = 10, delayMs = 3000) {
    for (let i = 1; i <= retries; i++) {
      try {
        this.connection = await amqplib.connect(process.env.RABBITMQ_URL);
        this.channel = await this.connection.createChannel();

        // Declare DLX (dead-letter exchange)
        await this.channel.assertExchange('product_events_dlx', 'fanout', { durable: true });
        await this.channel.assertQueue('inventory_dead_letter', { durable: true });
        await this.channel.bindQueue('inventory_dead_letter', 'product_events_dlx', '');

        // Main exchange (fanout – receives all product events)
        await this.channel.assertExchange(EXCHANGE, 'fanout', { durable: true });

        // Service-specific durable queue
        await this.channel.assertQueue(QUEUE, {
          durable: true,
          arguments: {
            'x-dead-letter-exchange': 'product_events_dlx',
          },
        });

        await this.channel.bindQueue(QUEUE, EXCHANGE, '');

        // Process one message at a time (fair dispatch)
        this.channel.prefetch(1);

        logger.info('[Consumer] Connected to RabbitMQ');

        this.connection.on('error', (err) => {
          logger.error('[Consumer] Connection error', { error: err.message });
        });

        this.connection.on('close', () => {
          logger.warn('[Consumer] Connection closed – reconnecting...');
          setTimeout(() => this.connect(), delayMs);
        });

        return;
      } catch (err) {
        logger.warn(`[Consumer] Connect attempt ${i}/${retries} failed: ${err.message}`);
        if (i === retries) throw err;
        await new Promise((r) => setTimeout(r, delayMs));
      }
    }
  }

  async startConsuming() {
    await this.channel.consume(QUEUE, async (msg) => {
      if (!msg) return;

      let envelope;
      try {
        envelope = JSON.parse(msg.content.toString());
      } catch {
        logger.error('[Consumer] Invalid JSON message – sending to DLQ');
        this.channel.nack(msg, false, false);
        return;
      }

      const { event_type, payload } = envelope;
      logger.info(`[Consumer] Received: ${event_type}`);

      try {
        await this.route(event_type, payload);
        this.channel.ack(msg);
      } catch (err) {
        logger.error(`[Consumer] Handler failed for ${event_type}`, { error: err.message });
        // nack without requeue → goes to DLQ
        this.channel.nack(msg, false, false);
      }
    });

    logger.info(`[Consumer] Listening on queue: ${QUEUE}`);
  }

  // ── Event Router ────────────────────────────────────────────────────────────

  async route(eventType, payload) {
    switch (eventType) {
      case 'product.created':
        return this.onProductCreated(payload);
      case 'product.updated':
        return this.onProductUpdated(payload);
      case 'product.deleted':
        return this.onProductDeleted(payload);
      default:
        logger.warn(`[Consumer] Unhandled event: ${eventType}`);
    }
  }

  // ── Handlers ────────────────────────────────────────────────────────────────

  /**
   * product.created
   * Create a default inventory record so the product is immediately
   * visible in inventory queries with zero stock.
   */
  async onProductCreated(payload) {
    const exists = await Inventory.findOne({ sku: payload.sku });
    if (exists) {
      logger.warn(`[Consumer] Inventory already exists for SKU ${payload.sku}`);
      return;
    }

    const inventory = new Inventory({
      sku: payload.sku,
      productName: payload.name,
      quantity: 0,
      warehouseId: 'WH-DEFAULT',
      location: 'UNASSIGNED',
    });

    await inventory.save();
    logger.info(`[Consumer] Inventory created for SKU: ${payload.sku}`);
  }

  /**
   * product.updated
   * Sync the denormalised productName field. If the SKU changed, migrate the
   * inventory record to the new SKU.
   */
  async onProductUpdated(payload) {
    const { current, previous } = payload;

    // SKU rename: update the join key across all inventory records
    if (previous?.sku && previous.sku !== current.sku) {
      await Inventory.updateMany(
        { sku: previous.sku },
        { $set: { sku: current.sku, productName: current.name } }
      );
      logger.info(`[Consumer] SKU renamed ${previous.sku} → ${current.sku}`);
      return;
    }

    // Just a name change – update the denormalised field
    await Inventory.updateMany(
      { sku: current.sku },
      { $set: { productName: current.name } }
    );

    logger.info(`[Consumer] Inventory synced for SKU: ${current.sku}`);
  }

  /**
   * product.deleted
   * Compensating transaction: soft-delete all inventory records for the
   * product. This maintains referential consistency without a 2PC.
   */
  async onProductDeleted(payload) {
    const result = await Inventory.updateMany(
      { sku: payload.sku },
      { $set: { deletedAt: new Date() } }
    );

    logger.info(`[Consumer] Soft-deleted ${result.modifiedCount} inventory record(s) for SKU: ${payload.sku}`);
  }
}

module.exports = new RabbitMQConsumer();
