'use strict';
require('dotenv').config();
const app = require('./app');
const { logger } = require('./utils/logger');
const { connectDatabase } = require('./infrastructure/database');
const { connectRedis } = require('./infrastructure/cache');

const PORT = parseInt(process.env.PORT, 10) || 3000;

async function bootstrap() {
  try {
    await connectDatabase();
    logger.info('Database connected');
    await connectRedis();
    logger.info('Redis connected');
    app.listen(PORT, '0.0.0.0', () => {
      logger.info(`Payment Service running on port ${PORT}`, {
        env: process.env.NODE_ENV,
        pid: process.pid,
      });
    });
  } catch (error) {
    logger.error('Failed to start', { error: error.message });
    process.exit(1);
  }
}

process.on('SIGTERM', () => { logger.info('SIGTERM – shutting down'); process.exit(0); });
process.on('SIGINT',  () => { logger.info('SIGINT – shutting down');  process.exit(0); });

bootstrap();
