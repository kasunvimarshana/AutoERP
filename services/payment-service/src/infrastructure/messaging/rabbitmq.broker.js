'use strict';
const amqp = require('amqplib');
const { logger } = require('../../utils/logger');
class RabbitMQBroker {
  constructor(config) {
    this.url = config.url;
    this.exchange = config.exchange || 'payment.events';
    this.connection = null;
    this.channel = null;
  }
  async publish(topic, message, options = {}) {
    try {
      const channel = await this.getChannel();
      const exchange = options.exchange || this.exchange;
      const routingKey = options.routingKey || topic;
      await channel.assertExchange(exchange, 'topic', { durable: true });
      const body = JSON.stringify({ event: topic, payload: message, timestamp: new Date().toISOString(), message_id: require('uuid').v4() });
      channel.publish(exchange, routingKey, Buffer.from(body), { persistent: true, contentType: 'application/json' });
      logger.info('Message published to RabbitMQ', { topic, exchange, routingKey });
      return true;
    } catch (error) {
      logger.error('RabbitMQ publish failed', { error: error.message, topic });
      return false;
    }
  }
  async subscribe(queue, handler, options = {}) {
    const channel = await this.getChannel();
    const exchange = options.exchange || this.exchange;
    const routingKey = options.routingKey || '#';
    await channel.assertExchange(exchange, 'topic', { durable: true });
    await channel.assertQueue(queue, { durable: true });
    await channel.bindQueue(queue, exchange, routingKey);
    channel.prefetch(1);
    channel.consume(queue, async (msg) => { if (msg) { const data = JSON.parse(msg.content.toString()); await handler(data, msg); } }, { noAck: false });
  }
  async acknowledge(msg) { const channel = await this.getChannel(); channel.ack(msg); }
  async reject(msg, requeue = false) { const channel = await this.getChannel(); channel.nack(msg, false, requeue); }
  async isConnected() { return this.connection !== null; }
  async disconnect() {
    if (this.channel) await this.channel.close();
    if (this.connection) await this.connection.close();
    this.channel = null; this.connection = null;
  }
  async getChannel() {
    if (!this.connection) {
      this.connection = await amqp.connect(this.url);
      this.connection.on('error', (err) => { logger.error('RabbitMQ connection error', { error: err.message }); this.connection = null; this.channel = null; });
    }
    if (!this.channel) this.channel = await this.connection.createChannel();
    return this.channel;
  }
}
module.exports = RabbitMQBroker;
