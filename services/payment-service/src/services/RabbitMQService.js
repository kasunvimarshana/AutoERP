'use strict';

const amqplib = require('amqplib');
const config = require('../config');
const logger = require('../utils/logger');

class RabbitMQService {
  constructor() {
    /** @type {import('amqplib').Connection | null} */
    this._connection = null;
    /** @type {import('amqplib').Channel | null} */
    this._channel = null;
    this._retryCount = 0;
    this._isShuttingDown = false;
    this._reconnectTimer = null;
    /** @type {Array<{ queue: string, handler: Function }>} */
    this._consumers = [];
  }

  // ── Connection ─────────────────────────────────────────────────────────────

  /**
   * Connect to RabbitMQ with exponential-backoff retry.
   */
  async connect() {
    const { maxRetries, initialDelay, maxDelay } = config.rabbitmq.reconnect;

    while (this._retryCount <= maxRetries) {
      try {
        logger.info('RabbitMQ: connecting…', { attempt: this._retryCount + 1 });
        this._connection = await amqplib.connect(config.rabbitmq.url);
        this._channel = await this._connection.createChannel();
        this._channel.prefetch(config.rabbitmq.prefetch);

        await this._assertTopology();
        this._retryCount = 0;

        this._connection.on('error', (err) => {
          logger.error('RabbitMQ: connection error', { error: err.message });
        });
        this._connection.on('close', () => {
          if (!this._isShuttingDown) {
            logger.warn('RabbitMQ: connection closed unexpectedly — reconnecting…');
            this._scheduleReconnect();
          }
        });

        // Re-register any consumers that existed before the reconnect
        for (const { queue, handler } of this._consumers) {
          await this._startConsumer(queue, handler);
        }

        logger.info('RabbitMQ: connected');
        return;
      } catch (err) {
        this._retryCount += 1;
        if (this._retryCount > maxRetries) {
          logger.error('RabbitMQ: max retries exceeded', { error: err.message });
          throw err;
        }
        const delay = Math.min(initialDelay * 2 ** (this._retryCount - 1), maxDelay);
        logger.warn(`RabbitMQ: connection failed — retry in ${delay}ms`, { error: err.message });
        await new Promise((r) => setTimeout(r, delay));
      }
    }
  }

  /** Declare exchanges and queues. */
  async _assertTopology() {
    const ch = this._channel;

    // Events exchange (fanout / topic for saga orchestration)
    await ch.assertExchange(config.rabbitmq.exchange, 'topic', { durable: true });

    // Commands exchange
    await ch.assertExchange(config.rabbitmq.commandsExchange, 'direct', { durable: true });

    // Dead-letter queue for failed payment commands
    await ch.assertQueue(config.rabbitmq.dlq, { durable: true });

    // Payment commands queue with DLQ binding
    await ch.assertQueue(config.rabbitmq.paymentQueue, {
      durable: true,
      arguments: {
        'x-dead-letter-exchange': '',
        'x-dead-letter-routing-key': config.rabbitmq.dlq,
      },
    });
    await ch.bindQueue(
      config.rabbitmq.paymentQueue,
      config.rabbitmq.commandsExchange,
      config.rabbitmq.paymentQueue
    );
  }

  _scheduleReconnect() {
    if (this._reconnectTimer) return;
    const delay = Math.min(
      config.rabbitmq.reconnect.initialDelay * 2 ** this._retryCount,
      config.rabbitmq.reconnect.maxDelay
    );
    this._reconnectTimer = setTimeout(async () => {
      this._reconnectTimer = null;
      this._retryCount += 1;
      try {
        await this.connect();
      } catch (err) {
        logger.error('RabbitMQ: reconnect failed', { error: err.message });
      }
    }, delay);
  }

  // ── Publishing ─────────────────────────────────────────────────────────────

  /**
   * Publish a raw message to an exchange.
   *
   * @param {string} exchange
   * @param {string} routingKey
   * @param {object} payload
   */
  async publish(exchange, routingKey, payload) {
    if (!this._channel) throw new Error('RabbitMQ channel not available');

    const content = Buffer.from(JSON.stringify(payload));
    const options = {
      persistent: true,
      contentType: 'application/json',
      timestamp: Date.now(),
    };

    const ok = this._channel.publish(exchange, routingKey, content, options);
    if (!ok) {
      logger.warn('RabbitMQ: publish returned false (back-pressure)', { exchange, routingKey });
    }
    logger.debug('RabbitMQ: published', { exchange, routingKey });
  }

  /**
   * Publish a domain event to the events exchange.
   *
   * @param {string} eventName  e.g. 'payment.processed'
   * @param {object} payload
   */
  async publishEvent(eventName, payload) {
    await this.publish(config.rabbitmq.exchange, eventName, {
      event: eventName,
      timestamp: new Date().toISOString(),
      ...payload,
    });
  }

  /**
   * Publish a command to the commands exchange.
   *
   * @param {string} service  target service queue name
   * @param {string} command  command type e.g. 'PROCESS_PAYMENT'
   * @param {object} payload
   */
  async publishCommand(service, command, payload) {
    await this.publish(config.rabbitmq.commandsExchange, service, {
      command,
      timestamp: new Date().toISOString(),
      ...payload,
    });
  }

  // ── Consuming ──────────────────────────────────────────────────────────────

  /**
   * Subscribe to a queue. The handler receives the parsed JSON payload.
   * On success the message is acked; on failure it is nacked with
   * requeue=false so it goes to the DLQ.
   *
   * @param {string} queue
   * @param {(payload: object, msg: import('amqplib').ConsumeMessage) => Promise<void>} handler
   */
  async subscribe(queue, handler) {
    // Store consumer so it can be re-registered after reconnect
    if (!this._consumers.find((c) => c.queue === queue)) {
      this._consumers.push({ queue, handler });
    }
    await this._startConsumer(queue, handler);
  }

  /**
   * Internal: actually attach the AMQP consumer.
   * @param {string} queue
   * @param {Function} handler
   */
  async _startConsumer(queue, handler) {
    if (!this._channel) throw new Error('RabbitMQ channel not available');

    await this._channel.consume(queue, async (msg) => {
      if (!msg) return; // consumer cancelled

      let payload;
      try {
        payload = JSON.parse(msg.content.toString());
      } catch (parseErr) {
        logger.error('RabbitMQ: failed to parse message', { queue, error: parseErr.message });
        this._channel.nack(msg, false, false);
        return;
      }

      try {
        await handler(payload, msg);
        this._channel.ack(msg);
      } catch (err) {
        logger.error('RabbitMQ: handler error — sending to DLQ', {
          queue,
          error: err.message,
          payload,
        });
        this._channel.nack(msg, false, false); // requeue=false → DLQ
      }
    });

    logger.info('RabbitMQ: consumer registered', { queue });
  }

  // ── Lifecycle ──────────────────────────────────────────────────────────────

  async close() {
    this._isShuttingDown = true;
    if (this._reconnectTimer) {
      clearTimeout(this._reconnectTimer);
      this._reconnectTimer = null;
    }
    try {
      if (this._channel) await this._channel.close();
      if (this._connection) await this._connection.close();
      logger.info('RabbitMQ: connection closed gracefully');
    } catch (err) {
      logger.warn('RabbitMQ: error during close', { error: err.message });
    }
  }

  /** @returns {boolean} */
  get isConnected() {
    return this._connection !== null && this._channel !== null;
  }
}

module.exports = new RabbitMQService();
