'use strict';

/**
 * Tenant Middleware
 *
 * Extracts tenant context from request headers and forwards it downstream.
 * Supports X-Tenant-ID header or auth user's tenant.
 */
function tenantMiddleware(req, res, next) {
  const tenantId = req.headers['x-tenant-id'] || req.headers['X-Auth-Tenant-ID'];

  if (!tenantId) {
    return res.status(400).json({
      success: false,
      message: 'Tenant context required. Provide X-Tenant-ID header.',
    });
  }

  // Normalize and forward to downstream services
  req.headers['X-Tenant-ID'] = tenantId;

  next();
}

module.exports = tenantMiddleware;
