'use strict';

const express  = require('express');
const mongoose = require('mongoose');
const config   = require('./config');
const logger   = require('./logger');
const { startConsumer } = require('./messaging/consumer');
const notificationsRouter = require('./routes/notifications');

const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ── Health check ──────────────────────────────────────────────────────────────
app.get('/health', (_req, res) => {
  res.json({
    status:  'ok',
    service: 'notification-service',
    version: '1.0.0',
    db:      mongoose.connection.readyState === 1 ? 'connected' : 'disconnected',
    time:    new Date().toISOString(),
  });
});

// ── Routes ────────────────────────────────────────────────────────────────────
app.use('/notifications', notificationsRouter);

// ── 404 handler ───────────────────────────────────────────────────────────────
app.use((_req, res) => {
  res.status(404).json({ message: 'Route not found' });
});

// ── Error handler ─────────────────────────────────────────────────────────────
app.use((err, _req, res, _next) => {
  logger.error('[App] Unhandled error', { error: err.message, stack: err.stack });
  res.status(500).json({ message: 'Internal server error' });
});

// ── Bootstrap ─────────────────────────────────────────────────────────────────
async function bootstrap() {
  try {
    await mongoose.connect(config.mongodb.uri);
    logger.info('[App] MongoDB connected', { uri: config.mongodb.uri });
  } catch (err) {
    logger.error('[App] MongoDB connection failed', { error: err.message });
    // Continue without MongoDB in dev/test — notifications will fail gracefully
  }

  // Start RabbitMQ consumer (non-blocking)
  startConsumer().catch(err => {
    logger.error('[App] RabbitMQ consumer failed to start', { error: err.message });
  });

  app.listen(config.port, () => {
    logger.info(`[App] Notification service listening on port ${config.port}`);
  });
}

// Only bootstrap when running directly (not in tests)
if (require.main === module) {
  bootstrap();
}

module.exports = app;
