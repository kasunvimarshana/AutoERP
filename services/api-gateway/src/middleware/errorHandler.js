'use strict';

const logger = require('../utils/logger');

/**
 * Global Error Handler Middleware
 */
function errorHandler(err, req, res, next) {
  logger.error('Unhandled error:', {
    message: err.message,
    stack: process.env.NODE_ENV === 'development' ? err.stack : undefined,
    url: req.url,
    method: req.method,
  });

  if (err.message && err.message.startsWith('CORS')) {
    return res.status(403).json({ success: false, message: err.message });
  }

  res.status(err.status || 500).json({
    success: false,
    message: process.env.NODE_ENV === 'production'
      ? 'An internal server error occurred.'
      : err.message,
  });
}

module.exports = errorHandler;
