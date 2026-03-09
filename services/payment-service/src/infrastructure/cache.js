'use strict';
const { createClient } = require('redis');
const { logger } = require('../utils/logger');
const redisClient = createClient({
  socket: {
    host: process.env.REDIS_HOST || 'redis',
    port: parseInt(process.env.REDIS_PORT, 10) || 6379,
    reconnectStrategy: (retries) => Math.min(retries * 100, 3000),
  },
  password: process.env.REDIS_PASSWORD || undefined,
});
redisClient.on('error', (err) => logger.error('Redis error', { error: err.message }));
redisClient.on('connect', () => logger.info('Redis connected'));
async function connectRedis() {
  if (!redisClient.isOpen) await redisClient.connect();
}
module.exports = { redisClient, connectRedis };
