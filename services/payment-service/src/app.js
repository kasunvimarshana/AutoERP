'use strict';

const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const mongoose = require('mongoose');
const rabbitmq = require('./services/RabbitMQService');
const paymentsRouter = require('./routes/payments');
const logger = require('./utils/logger');

const app = express();

// ── Security & CORS ───────────────────────────────────────────────────────────
app.use(helmet());
app.use(cors({
  origin: process.env.CORS_ORIGIN || '*',
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization'],
}));

// ── Rate limiting ─────────────────────────────────────────────────────────────
app.use(
  rateLimit({
    windowMs: 15 * 60 * 1000,
    max: parseInt(process.env.RATE_LIMIT_MAX || '100', 10),
    standardHeaders: true,
    legacyHeaders: false,
    message: { error: 'Too many requests — please try again later.' },
  })
);

// ── Body parsing ──────────────────────────────────────────────────────────────
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: false }));

// ── Health check (unauthenticated) ────────────────────────────────────────────
app.get('/health', (_req, res) => {
  const mongoStatus = mongoose.connection.readyState === 1 ? 'up' : 'down';
  const rabbitStatus = rabbitmq.isConnected ? 'up' : 'down';

  const healthy = mongoStatus === 'up' && rabbitStatus === 'up';
  res.status(healthy ? 200 : 503).json({
    status: healthy ? 'ok' : 'degraded',
    service: 'payment-service',
    timestamp: new Date().toISOString(),
    dependencies: {
      mongodb: mongoStatus,
      rabbitmq: rabbitStatus,
    },
  });
});

// ── API routes ────────────────────────────────────────────────────────────────
app.use('/api/v1/payments', paymentsRouter);

// ── 404 handler ───────────────────────────────────────────────────────────────
app.use((_req, res) => {
  res.status(404).json({ error: 'Route not found' });
});

// ── Global error handler ──────────────────────────────────────────────────────
// eslint-disable-next-line no-unused-vars
app.use((err, _req, res, _next) => {
  logger.error('Unhandled error', {
    error: err.message,
    stack: process.env.NODE_ENV !== 'production' ? err.stack : undefined,
  });

  const statusCode = err.statusCode || err.status || 500;
  res.status(statusCode).json({
    error: statusCode === 500 ? 'Internal server error' : err.message,
  });
});

module.exports = app;
