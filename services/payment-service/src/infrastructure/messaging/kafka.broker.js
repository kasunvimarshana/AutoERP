'use strict';
const { Kafka } = require('kafkajs');
const { logger } = require('../../utils/logger');
class KafkaBroker {
  constructor(config) {
    this.kafka = new Kafka({ brokers: config.brokers || ['localhost:9092'], clientId: config.clientId || 'payment-service' });
    this.producer = null; this.consumer = null;
  }
  async publish(topic, message, options = {}) {
    try {
      const producer = await this.getProducer();
      await producer.send({ topic, messages: [{ key: options.key || null, value: JSON.stringify({ event: topic, payload: message, timestamp: new Date().toISOString() }) }] });
      logger.info('Message published to Kafka', { topic }); return true;
    } catch (error) { logger.error('Kafka publish failed', { error: error.message, topic }); return false; }
  }
  async subscribe(topic, handler) {
    if (!this.consumer) { this.consumer = this.kafka.consumer({ groupId: 'payment-service-group' }); await this.consumer.connect(); }
    await this.consumer.subscribe({ topic, fromBeginning: false });
    await this.consumer.run({ eachMessage: async ({ topic, partition, message }) => { const data = JSON.parse(message.value.toString()); await handler(data, message); } });
  }
  acknowledge() {} reject() {}
  async isConnected() { return this.producer !== null; }
  async disconnect() {
    if (this.producer) await this.producer.disconnect();
    if (this.consumer) await this.consumer.disconnect();
    this.producer = null; this.consumer = null;
  }
  async getProducer() {
    if (!this.producer) { this.producer = this.kafka.producer(); await this.producer.connect(); }
    return this.producer;
  }
}
module.exports = KafkaBroker;
