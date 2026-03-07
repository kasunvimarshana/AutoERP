'use strict';

const mongoose = require('mongoose');
const logger   = require('../utils/logger');

/**
 * MongoDB connection manager.
 * Exported as a singleton to share the connection across the app.
 */
const database = {
  /**
   * Establish connection to MongoDB.
   */
  async connect() {
    const uri = process.env.MONGODB_URI;

    if (!uri) {
      throw new Error('MONGODB_URI environment variable is not set');
    }

    await mongoose.connect(uri, {
      serverSelectionTimeoutMS: 5000,
      socketTimeoutMS: 45000,
    });

    mongoose.connection.on('error', err => {
      logger.error('MongoDB connection error', { error: err.message });
    });

    mongoose.connection.on('disconnected', () => {
      logger.warn('MongoDB disconnected');
    });
  },

  /**
   * Gracefully close the MongoDB connection.
   */
  async disconnect() {
    await mongoose.disconnect();
    logger.info('MongoDB disconnected gracefully');
  },
};

module.exports = database;
