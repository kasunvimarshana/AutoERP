'use strict';

const express    = require('express');
const helmet     = require('helmet');
const cors       = require('cors');
const morgan     = require('morgan');
const logger     = require('./utils/logger');

const notificationRoutes = require('./routes/notificationRoutes');

const app = express();

// ── Security & parsing middleware ────────────────────────────────────────────
app.use(helmet());
app.use(cors());
app.use(express.json({ limit: '1mb' }));
app.use(morgan('combined', { stream: { write: msg => logger.http(msg.trim()) } }));

// ── Routes ───────────────────────────────────────────────────────────────────
app.use('/api/v1/notifications', notificationRoutes);

// Health check (used by Docker healthcheck and API Gateway)
app.get('/health', (_req, res) => {
  res.json({ status: 'ok', service: 'notification-service' });
});

// ── 404 handler ───────────────────────────────────────────────────────────────
app.use((_req, res) => {
  res.status(404).json({ message: 'Not Found' });
});

// ── Global error handler ──────────────────────────────────────────────────────
// eslint-disable-next-line no-unused-vars
app.use((err, _req, res, _next) => {
  logger.error('Unhandled error', { error: err.message, stack: err.stack });
  res.status(500).json({ message: 'Internal Server Error' });
});

module.exports = app;
