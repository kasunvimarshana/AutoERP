'use strict';

const request = require('supertest');

/**
 * Inventory Service - API Tests
 *
 * Uses Jest module mocking to avoid needing a real MongoDB instance.
 * Tests validate controller logic, validation, and HTTP response structure.
 */

// Mock mongoose to avoid DB connection
jest.mock('mongoose', () => {
  const actualMongoose = jest.requireActual('mongoose');
  return {
    ...actualMongoose,
    connect: jest.fn().mockResolvedValue({}),
    connection: {
      ...actualMongoose.connection,
      on: jest.fn(),
    },
  };
});

// Mock the Inventory model methods
jest.mock('../src/models/Inventory');
const Inventory = require('../src/models/Inventory');

// Mock the event consumer so RabbitMQ is not needed
jest.mock('../src/events/productEventConsumer', () => ({
  connectAndConsume: jest.fn().mockResolvedValue(undefined),
}));

// We import app AFTER setting up mocks
const app = require('../src/app');

// ─── Mock Data ────────────────────────────────────────────────────────────────

const mockInventoryRecord = {
  _id:                '65a000000000000000000001',
  id:                 '65a000000000000000000001',
  product_id:         1,
  product_name:       'Test Product',
  product_sku:        'TP-001',
  quantity:           100,
  reserved_quantity:  0,
  warehouse_location: 'Main Warehouse',
  reorder_threshold:  10,
  is_active:          true,
  available_quantity: 100,
  needs_reorder:      false,
  createdAt:          new Date().toISOString(),
  updatedAt:          new Date().toISOString(),
};

beforeEach(() => {
  jest.clearAllMocks();
});

// ─── GET /api/v1/inventory ────────────────────────────────────────────────────

describe('GET /api/v1/inventory', () => {
  it('returns empty list when no records exist', async () => {
    Inventory.find.mockReturnValue({
      skip: jest.fn().mockReturnValue({
        limit: jest.fn().mockReturnValue({
          sort: jest.fn().mockResolvedValue([]),
        }),
      }),
    });
    Inventory.countDocuments.mockResolvedValue(0);

    const res = await request(app).get('/api/v1/inventory');
    expect(res.status).toBe(200);
    expect(res.body.success).toBe(true);
    expect(res.body.data).toHaveLength(0);
    expect(res.body.meta.total).toBe(0);
  });

  it('returns inventory records with pagination meta', async () => {
    Inventory.find.mockReturnValue({
      skip: jest.fn().mockReturnValue({
        limit: jest.fn().mockReturnValue({
          sort: jest.fn().mockResolvedValue([mockInventoryRecord]),
        }),
      }),
    });
    Inventory.countDocuments.mockResolvedValue(1);

    const res = await request(app).get('/api/v1/inventory');
    expect(res.status).toBe(200);
    expect(res.body.data).toHaveLength(1);
    expect(res.body.meta.total).toBe(1);
    expect(res.body.meta.current_page).toBe(1);
  });

  it('applies product_name filter', async () => {
    Inventory.find.mockReturnValue({
      skip: jest.fn().mockReturnValue({
        limit: jest.fn().mockReturnValue({
          sort: jest.fn().mockResolvedValue([mockInventoryRecord]),
        }),
      }),
    });
    Inventory.countDocuments.mockResolvedValue(1);

    const res = await request(app).get('/api/v1/inventory?product_name=Test');
    expect(res.status).toBe(200);
    const callArg = Inventory.find.mock.calls[0][0];
    expect(callArg).toHaveProperty('product_name');
  });

  it('applies product_id filter as integer', async () => {
    Inventory.find.mockReturnValue({
      skip: jest.fn().mockReturnValue({
        limit: jest.fn().mockReturnValue({
          sort: jest.fn().mockResolvedValue([mockInventoryRecord]),
        }),
      }),
    });
    Inventory.countDocuments.mockResolvedValue(1);

    const res = await request(app).get('/api/v1/inventory?product_id=1');
    expect(res.status).toBe(200);
    const callArg = Inventory.find.mock.calls[0][0];
    expect(callArg.product_id).toBe(1);
  });
});

// ─── POST /api/v1/inventory ───────────────────────────────────────────────────

describe('POST /api/v1/inventory', () => {
  it('creates an inventory record', async () => {
    const saveMock = jest.fn().mockResolvedValue(mockInventoryRecord);
    Inventory.mockImplementation(() => ({
      save: saveMock,
      ...mockInventoryRecord,
    }));

    const res = await request(app)
      .post('/api/v1/inventory')
      .send({
        product_id:   1,
        product_name: 'New Product',
        quantity:     50,
      });

    expect(res.status).toBe(201);
    expect(res.body.success).toBe(true);
  });

  it('returns 422 for missing required fields', async () => {
    const res = await request(app).post('/api/v1/inventory').send({});
    expect(res.status).toBe(422);
    expect(res.body.errors).toBeDefined();
  });

  it('returns 422 for negative quantity', async () => {
    const res = await request(app)
      .post('/api/v1/inventory')
      .send({ product_id: 1, product_name: 'Product', quantity: -5 });
    expect(res.status).toBe(422);
  });

  it('returns 409 for duplicate key error', async () => {
    const saveMock = jest.fn().mockRejectedValue({ code: 11000, message: 'Duplicate key' });
    Inventory.mockImplementation(() => ({ save: saveMock }));

    const res = await request(app)
      .post('/api/v1/inventory')
      .send({ product_id: 1, product_name: 'Test', quantity: 10 });
    expect(res.status).toBe(409);
  });
});

// ─── GET /api/v1/inventory/:id ────────────────────────────────────────────────

describe('GET /api/v1/inventory/:id', () => {
  it('returns a single inventory record', async () => {
    Inventory.findById.mockResolvedValue(mockInventoryRecord);

    const res = await request(app).get('/api/v1/inventory/65a000000000000000000001');
    expect(res.status).toBe(200);
    expect(res.body.success).toBe(true);
    expect(res.body.data.product_name).toBe('Test Product');
  });

  it('returns 404 for unknown id', async () => {
    Inventory.findById.mockResolvedValue(null);

    const res = await request(app).get('/api/v1/inventory/65a000000000000000000099');
    expect(res.status).toBe(404);
  });

  it('returns 404 for invalid ObjectId format', async () => {
    Inventory.findById.mockRejectedValue({ name: 'CastError' });

    const res = await request(app).get('/api/v1/inventory/invalid-id');
    expect(res.status).toBe(404);
  });
});

// ─── PUT /api/v1/inventory/:id ────────────────────────────────────────────────

describe('PUT /api/v1/inventory/:id', () => {
  it('updates an inventory record', async () => {
    const updated = { ...mockInventoryRecord, quantity: 200, notes: 'Restocked' };
    Inventory.findByIdAndUpdate.mockResolvedValue(updated);

    const res = await request(app)
      .put('/api/v1/inventory/65a000000000000000000001')
      .send({ quantity: 200, notes: 'Restocked' });

    expect(res.status).toBe(200);
    expect(res.body.data.quantity).toBe(200);
  });

  it('returns 404 for non-existent record', async () => {
    Inventory.findByIdAndUpdate.mockResolvedValue(null);

    const res = await request(app)
      .put('/api/v1/inventory/65a000000000000000000099')
      .send({ quantity: 10 });
    expect(res.status).toBe(404);
  });

  it('returns 422 for negative quantity', async () => {
    const res = await request(app)
      .put('/api/v1/inventory/65a000000000000000000001')
      .send({ quantity: -1 });
    expect(res.status).toBe(422);
  });
});

// ─── DELETE /api/v1/inventory/:id ─────────────────────────────────────────────

describe('DELETE /api/v1/inventory/:id', () => {
  it('deletes an inventory record', async () => {
    Inventory.findByIdAndDelete.mockResolvedValue(mockInventoryRecord);

    const res = await request(app).delete('/api/v1/inventory/65a000000000000000000001');
    expect(res.status).toBe(200);
    expect(res.body.success).toBe(true);
  });

  it('returns 404 for non-existent record', async () => {
    Inventory.findByIdAndDelete.mockResolvedValue(null);

    const res = await request(app).delete('/api/v1/inventory/65a000000000000000000099');
    expect(res.status).toBe(404);
  });
});

// ─── Unknown routes ────────────────────────────────────────────────────────────

describe('Unknown routes', () => {
  it('returns 404 for unmatched routes', async () => {
    const res = await request(app).get('/api/v1/nonexistent');
    expect(res.status).toBe(404);
  });
});
