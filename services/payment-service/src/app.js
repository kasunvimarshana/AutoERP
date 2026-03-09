'use strict';
/**
 * Express Application Configuration - Payment Service
 */
const express     = require('express');
const helmet      = require('helmet');
const cors        = require('cors');
const compression = require('compression');
const morgan      = require('morgan');
const { logger }  = require('./utils/logger');

const paymentRoutes = require('./routes/payment.routes');
const healthRoutes  = require('./routes/health.routes');

const app = express();

app.use(helmet());
app.use(cors({
  origin: '*',
  methods: ['GET','POST','PUT','PATCH','DELETE'],
  allowedHeaders: ['Content-Type','Authorization','X-Tenant-ID','X-Saga-ID','X-Internal-Service'],
}));
app.use(compression());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));
app.use(morgan('combined', { stream: { write: (msg) => logger.http(msg.trim()) } }));

app.use('/health',           healthRoutes);
app.use('/api/v1/payments',  paymentRoutes);

// 404
app.use((_req, res) => res.status(404).json({ success: false, message: 'Route not found', error_code: 'NOT_FOUND' }));

// Global error handler
app.use((err, _req, res, _next) => {
  logger.error('Unhandled error', { error: err.message });
  res.status(err.status || 500).json({
    success: false,
    message: process.env.NODE_ENV === 'production' ? 'An error occurred' : err.message,
    error_code: err.code || 'INTERNAL_ERROR',
  });
});

module.exports = app;
