'use strict';
const { sequelize }  = require('../infrastructure/database');
const { redisClient } = require('../infrastructure/cache');

class HealthController {
  async health(req, res) {
    const checks = {
      database: await this._checkDb(),
      cache:    await this._checkCache(),
    };
    const healthy = Object.values(checks).every(c => c.status === 'ok');
    res.status(healthy ? 200 : 503).json({
      status: healthy ? 'healthy' : 'degraded',
      service: 'payment-service',
      language: 'Node.js',
      timestamp: new Date().toISOString(),
      checks,
    });
  }

  ping(_req, res) {
    res.json({ status: 'ok', service: 'payment-service', language: 'Node.js', timestamp: new Date().toISOString() });
  }

  async _checkDb() {
    try { await sequelize.authenticate(); return { status: 'ok', driver: 'mysql' }; }
    catch (e) { return { status: 'error', message: e.message }; }
  }

  async _checkCache() {
    try {
      const key = `hc_${Date.now()}`;
      await redisClient.set(key, '1', { EX: 5 });
      const v = await redisClient.get(key);
      await redisClient.del(key);
      return { status: v === '1' ? 'ok' : 'error' };
    } catch (e) { return { status: 'error', message: e.message }; }
  }
}

module.exports = HealthController;
