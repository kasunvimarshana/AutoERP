'use strict';

const request = require('supertest');
const app = require('../app');

describe('API Gateway Health', () => {
  it('GET /health/live returns 200 with alive status', async () => {
    const res = await request(app).get('/health/live');
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('alive');
  });

  it('GET /health/ready returns 200', async () => {
    const res = await request(app).get('/health/ready');
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('ready');
  });

  it('returns 404 for unknown routes', async () => {
    const res = await request(app).get('/unknown-route-xyz');
    expect(res.status).toBe(404);
    expect(res.body.success).toBe(false);
  });
});
