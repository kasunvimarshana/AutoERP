'use strict';

require('dotenv').config();

const app      = require('./app');
const logger   = require('./utils/logger');
const database = require('./config/database');
const consumer = require('./messaging/consumer');

const PORT = process.env.PORT || 3000;

/**
 * Bootstrap the notification service.
 * Connects to MongoDB, starts the Express HTTP server,
 * and starts the RabbitMQ consumer for async notifications.
 */
async function bootstrap() {
  try {
    // 1. Connect to MongoDB
    await database.connect();
    logger.info('Connected to MongoDB');

    // 2. Start HTTP server
    const server = app.listen(PORT, () => {
      logger.info(`Notification service listening on port ${PORT}`);
    });

    // 3. Start RabbitMQ consumer (async, non-blocking)
    consumer.start().catch(err => {
      logger.error('RabbitMQ consumer error', { error: err.message });
    });

    // Graceful shutdown
    const shutdown = async (signal) => {
      logger.info(`${signal} received – shutting down`);
      server.close(async () => {
        await consumer.stop();
        await database.disconnect();
        process.exit(0);
      });
    };

    process.on('SIGTERM', () => shutdown('SIGTERM'));
    process.on('SIGINT',  () => shutdown('SIGINT'));

  } catch (err) {
    logger.error('Failed to start notification service', { error: err.message });
    process.exit(1);
  }
}

bootstrap();
