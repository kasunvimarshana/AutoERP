'use strict';

require('dotenv').config();

const express   = require('express');
const mongoose  = require('mongoose');
const cors      = require('cors');
const morgan    = require('morgan');
const logger    = require('./middleware/logger');
const inventoryRoutes       = require('./routes/inventoryRoutes');
const { connectAndConsume } = require('./events/productEventConsumer');

const app  = express();
const PORT = process.env.PORT || 8002;

// ─── Middleware ────────────────────────────────────────────────────────────────

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(morgan('combined', {
  stream: { write: (message) => logger.http(message.trim()) },
}));

// ─── Routes ───────────────────────────────────────────────────────────────────

app.get('/', (req, res) => {
  res.json({
    service: 'Inventory Service',
    version: '1.0.0',
    status:  'running',
    tech:    'Node.js + Express + MongoDB',
  });
});

app.use('/api/v1/inventory', inventoryRoutes);

// ─── 404 Handler ──────────────────────────────────────────────────────────────

app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: `Route ${req.method} ${req.path} not found.`,
  });
});

// ─── Global Error Handler ─────────────────────────────────────────────────────

app.use((err, req, res, _next) => {
  logger.error('Unhandled error', { error: err.message, stack: err.stack });
  res.status(500).json({
    success: false,
    message: 'Internal server error.',
    error:   process.env.NODE_ENV !== 'production' ? err.message : undefined,
  });
});

// ─── Database Connection ──────────────────────────────────────────────────────

async function connectDatabase() {
  const mongoUri = process.env.MONGO_URI || 'mongodb://mongodb:27017/inventory_service';

  try {
    await mongoose.connect(mongoUri, {
      serverSelectionTimeoutMS: 5000,
      socketTimeoutMS:          45000,
    });
    logger.info('MongoDB connected', { uri: mongoUri });
  } catch (err) {
    logger.error('MongoDB connection failed', { error: err.message });
    process.exit(1);
  }
}

// ─── Start Server ─────────────────────────────────────────────────────────────

async function startServer() {
  await connectDatabase();

  // Start RabbitMQ consumer (non-blocking — runs in background)
  connectAndConsume().catch((err) => {
    logger.warn('RabbitMQ consumer failed to start (will retry)', { error: err.message });
  });

  app.listen(PORT, () => {
    logger.info(`Inventory Service running on port ${PORT}`);
  });
}

// Only start the server if this file is run directly (not when imported in tests)
if (require.main === module) {
  startServer().catch((err) => {
    logger.error('Failed to start server', { error: err.message });
    process.exit(1);
  });
}

module.exports = app;
