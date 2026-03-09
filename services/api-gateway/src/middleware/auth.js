'use strict';

const axios = require('axios');
const logger = require('../utils/logger');

const AUTH_SERVICE_URL = process.env.AUTH_SERVICE_URL || 'http://auth-service:9000';

/**
 * Auth Middleware
 *
 * Validates bearer tokens against the Auth Service.
 * Attaches user data to request for downstream use.
 */
async function authMiddleware(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({
      success: false,
      message: 'Unauthenticated. Bearer token required.',
    });
  }

  const token = authHeader.substring(7);

  try {
    const response = await axios.post(
      `${AUTH_SERVICE_URL}/api/auth/validate`,
      {},
      {
        headers: { Authorization: `Bearer ${token}` },
        timeout: 5000,
      }
    );

    if (!response.data.success) {
      return res.status(401).json({
        success: false,
        message: 'Invalid or expired token.',
      });
    }

    // Attach user data to request headers for downstream services
    const user = response.data.data;
    req.headers['X-Auth-User-ID'] = String(user.id);
    req.headers['X-Auth-Tenant-ID'] = String(user.tenant_id);
    req.headers['X-Auth-Roles'] = JSON.stringify(user.roles || []);

    next();
  } catch (err) {
    logger.error('Auth service error:', { error: err.message });

    if (err.response?.status === 401) {
      return res.status(401).json({ success: false, message: 'Invalid or expired token.' });
    }

    return res.status(503).json({ success: false, message: 'Authentication service unavailable.' });
  }
}

module.exports = authMiddleware;
