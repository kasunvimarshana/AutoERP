'use strict';
const RabbitMQBroker = require('./rabbitmq.broker');
const KafkaBroker = require('./kafka.broker');
class MessageBrokerFactory {
  static create() {
    const driver = process.env.MESSAGE_BROKER_DRIVER || 'rabbitmq';
    switch (driver) {
      case 'kafka':
        return new KafkaBroker({ brokers: (process.env.KAFKA_BROKERS || 'localhost:9092').split(','), clientId: 'payment-service' });
      case 'rabbitmq':
      default:
        return new RabbitMQBroker({ url: process.env.RABBITMQ_URL || 'amqp://admin:secret@rabbitmq/saas_vhost', exchange: process.env.RABBITMQ_EXCHANGE || 'payment.events' });
    }
  }
}
module.exports = MessageBrokerFactory;
