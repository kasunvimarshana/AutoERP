'use strict';

/**
 * ProductEventConsumer Unit Tests
 *
 * Tests the event handler functions in isolation,
 * without a real RabbitMQ or MongoDB connection.
 */

// Mock amqplib so no real RabbitMQ connection is attempted
jest.mock('amqplib', () => ({
  connect: jest.fn().mockRejectedValue(new Error('No RabbitMQ in tests')),
}));

// Mock the Inventory model
jest.mock('../src/models/Inventory');
const Inventory = require('../src/models/Inventory');

// Import the consumer module (amqplib is already mocked)
const { _test } = require('../src/events/productEventConsumer');
const {
  handleProductCreated,
  handleProductUpdated,
  handleProductDeleted,
} = _test;

beforeEach(() => {
  jest.clearAllMocks();
});

describe('handleProductCreated', () => {
  it('creates an inventory record for a new product', async () => {
    Inventory.findOne.mockResolvedValue(null); // No existing record
    const saveMock = jest.fn().mockResolvedValue({});
    Inventory.mockImplementation(() => ({ save: saveMock }));

    await handleProductCreated({
      event:      'product.created',
      product_id: 1,
      name:       'Test Widget',
      sku:        'TW-001',
      stock:      150,
    });

    expect(Inventory.findOne).toHaveBeenCalledWith({
      product_id:         1,
      warehouse_location: 'Main Warehouse',
    });
    expect(saveMock).toHaveBeenCalledTimes(1);
  });

  it('is idempotent - skips creation if record already exists', async () => {
    Inventory.findOne.mockResolvedValue({ product_id: 2, product_name: 'Widget' });
    const saveMock = jest.fn();
    Inventory.mockImplementation(() => ({ save: saveMock }));

    await handleProductCreated({
      event:      'product.created',
      product_id: 2,
      name:       'Widget',
      sku:        'W-001',
      stock:      50,
    });

    expect(saveMock).not.toHaveBeenCalled();
  });

  it('throws on save failure (enables nack/retry)', async () => {
    Inventory.findOne.mockResolvedValue(null);
    const saveMock = jest.fn().mockRejectedValue(new Error('DB write failed'));
    Inventory.mockImplementation(() => ({ save: saveMock }));

    await expect(
      handleProductCreated({ event: 'product.created', product_id: 3, name: 'P3', stock: 0 })
    ).rejects.toThrow('DB write failed');
  });
});

describe('handleProductUpdated', () => {
  it('syncs product_name and sku in all inventory records', async () => {
    Inventory.updateMany.mockResolvedValue({ modifiedCount: 2 });

    await handleProductUpdated({
      event:         'product.updated',
      product_id:    5,
      name:          'New Name',
      sku:           'NEW-001',
      previous_name: 'Old Name',
    });

    expect(Inventory.updateMany).toHaveBeenCalledWith(
      { product_id: 5 },
      { $set: { product_name: 'New Name', product_sku: 'NEW-001' } }
    );
  });

  it('updates only product_name when sku is undefined', async () => {
    Inventory.updateMany.mockResolvedValue({ modifiedCount: 1 });

    await handleProductUpdated({
      event:      'product.updated',
      product_id: 6,
      name:       'Another Name',
    });

    const callArg = Inventory.updateMany.mock.calls[0][1];
    expect(callArg.$set.product_name).toBe('Another Name');
    expect(callArg.$set).not.toHaveProperty('product_sku');
  });

  it('throws on DB failure (enables nack/retry)', async () => {
    Inventory.updateMany.mockRejectedValue(new Error('Update failed'));

    await expect(
      handleProductUpdated({ event: 'product.updated', product_id: 7, name: 'X' })
    ).rejects.toThrow('Update failed');
  });
});

describe('handleProductDeleted', () => {
  it('deletes all inventory records for a product', async () => {
    Inventory.deleteMany.mockResolvedValue({ deletedCount: 3 });

    await handleProductDeleted({
      event:      'product.deleted',
      product_id: 9,
      name:       'Deleted Product',
    });

    expect(Inventory.deleteMany).toHaveBeenCalledWith({ product_id: 9 });
  });

  it('handles product with no inventory gracefully', async () => {
    Inventory.deleteMany.mockResolvedValue({ deletedCount: 0 });

    await expect(
      handleProductDeleted({ event: 'product.deleted', product_id: 999, name: 'Ghost' })
    ).resolves.not.toThrow();
  });

  it('throws on DB failure (enables nack/retry)', async () => {
    Inventory.deleteMany.mockRejectedValue(new Error('Delete failed'));

    await expect(
      handleProductDeleted({ event: 'product.deleted', product_id: 10, name: 'P10' })
    ).rejects.toThrow('Delete failed');
  });
});
