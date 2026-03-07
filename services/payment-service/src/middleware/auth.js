'use strict';

const jwt = require('jsonwebtoken');
const config = require('../config');
const logger = require('../utils/logger');

/**
 * Verify the Bearer JWT token in the Authorization header.
 * Attaches the decoded `tenant` object (with tenant_id) to `req.tenant`.
 *
 * Returns 401 on missing/invalid token, 403 on insufficient permissions.
 */
function authenticate(req, res, next) {
  const authHeader = req.headers['authorization'];
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Missing or malformed Authorization header' });
  }

  const token = authHeader.slice(7);

  try {
    const decoded = jwt.verify(token, config.jwt.secret);

    if (!decoded.tenant_id) {
      return res.status(403).json({ error: 'Token does not contain tenant_id claim' });
    }

    req.tenant = {
      id: decoded.tenant_id,
      sub: decoded.sub,
      roles: decoded.roles || [],
    };

    next();
  } catch (err) {
    logger.debug('auth: JWT verification failed', { error: err.message });
    if (err.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Token has expired' });
    }
    return res.status(401).json({ error: 'Invalid token' });
  }
}

module.exports = { authenticate };
