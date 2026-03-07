'use strict';

require('dotenv').config();

const mongoose = require('mongoose');
const app = require('./app');
const rabbitmq = require('./services/RabbitMQService');
const { startPaymentCommandConsumer } = require('./consumers/PaymentCommandConsumer');
const config = require('./config');
const logger = require('./utils/logger');

let server;

async function bootstrap() {
  // ── MongoDB ─────────────────────────────────────────────────────────────────
  logger.info('Connecting to MongoDB…', { uri: config.mongodb.uri.replace(/\/\/.*@/, '//<credentials>@') });
  await mongoose.connect(config.mongodb.uri, config.mongodb.options);
  logger.info('MongoDB connected');

  // ── RabbitMQ ────────────────────────────────────────────────────────────────
  logger.info('Connecting to RabbitMQ…');
  await rabbitmq.connect();
  await startPaymentCommandConsumer();

  // ── HTTP server ─────────────────────────────────────────────────────────────
  server = app.listen(config.port, () => {
    logger.info(`Payment service listening on port ${config.port}`, {
      env: config.nodeEnv,
      port: config.port,
    });
  });

  server.on('error', (err) => {
    logger.error('HTTP server error', { error: err.message });
    process.exit(1);
  });
}

// ── Graceful shutdown ─────────────────────────────────────────────────────────

async function shutdown(signal) {
  logger.info(`${signal} received — shutting down gracefully…`);

  // Stop accepting new connections
  if (server) {
    await new Promise((resolve) => server.close(resolve));
    logger.info('HTTP server closed');
  }

  // Close RabbitMQ
  await rabbitmq.close();
  logger.info('RabbitMQ connection closed');

  // Close MongoDB
  await mongoose.connection.close();
  logger.info('MongoDB connection closed');

  process.exit(0);
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

process.on('unhandledRejection', (reason) => {
  logger.error('Unhandled promise rejection', { reason: String(reason) });
});

process.on('uncaughtException', (err) => {
  logger.error('Uncaught exception', { error: err.message, stack: err.stack });
  process.exit(1);
});

bootstrap().catch((err) => {
  logger.error('Bootstrap failed', { error: err.message });
  process.exit(1);
});
