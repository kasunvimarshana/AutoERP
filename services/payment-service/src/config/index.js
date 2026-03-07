'use strict';

require('dotenv').config();

const config = {
  port: parseInt(process.env.PORT || '8003', 10),

  nodeEnv: process.env.NODE_ENV || 'development',

  mongodb: {
    uri: process.env.MONGODB_URI || 'mongodb://localhost:27017/payment_service',
    options: {
      serverSelectionTimeoutMS: 5000,
      socketTimeoutMS: 45000,
    },
  },

  rabbitmq: {
    url: process.env.RABBITMQ_URL || 'amqp://localhost:5672',
    exchange: process.env.RABBITMQ_EXCHANGE || 'saga.events',
    commandsExchange: process.env.RABBITMQ_COMMANDS_EXCHANGE || 'saga.commands',
    paymentQueue: process.env.RABBITMQ_PAYMENT_QUEUE || 'payment.commands',
    dlq: process.env.RABBITMQ_DLQ || 'payment.commands.dlq',
    prefetch: parseInt(process.env.RABBITMQ_PREFETCH || '10', 10),
    reconnect: {
      maxRetries: parseInt(process.env.RABBITMQ_MAX_RETRIES || '10', 10),
      initialDelay: parseInt(process.env.RABBITMQ_INITIAL_DELAY || '1000', 10),
      maxDelay: parseInt(process.env.RABBITMQ_MAX_DELAY || '30000', 10),
    },
  },

  payment: {
    successRate: parseFloat(process.env.PAYMENT_SUCCESS_RATE || '0.9'),
    refundSuccessRate: parseFloat(process.env.REFUND_SUCCESS_RATE || '0.95'),
    simulatedLatencyMs: parseInt(process.env.PAYMENT_LATENCY_MS || '200', 10),
  },

  jwt: {
    secret: process.env.JWT_SECRET || 'dev-secret-change-in-production',
    expiresIn: process.env.JWT_EXPIRES_IN || '1h',
  },

  log: {
    level: process.env.LOG_LEVEL || 'info',
  },
};

module.exports = config;
