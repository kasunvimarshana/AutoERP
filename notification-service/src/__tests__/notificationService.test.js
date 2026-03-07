'use strict';

// Mock mongoose before requiring service
jest.mock('mongoose', () => {
  const mockSchema = {
    index: jest.fn().mockReturnThis(),
  };
  return {
    connect:    jest.fn().mockResolvedValue(undefined),
    connection: { readyState: 1 },
    Schema:     jest.fn(() => mockSchema),
    model:      jest.fn(() => {
      function MockNotification(data) {
        Object.assign(this, data, {
          _id:    'mock-id-' + Math.random().toString(36).substring(2, 11),
          status: data.status || 'pending',
        });
        this.save = jest.fn().mockResolvedValue(this);
      }
      MockNotification.findById = jest.fn().mockResolvedValue({
        _id:       'mock-id-123',
        orderId:   'order-001',
        sagaId:    'saga-001',
        type:      'order_confirmation',
        status:    'sent',
        recipient: 'test@example.com',
      });
      MockNotification.find = jest.fn().mockReturnValue({
        sort: jest.fn().mockReturnValue({
          lean: jest.fn().mockResolvedValue([
            { orderId: 'order-001', sagaId: 'saga-001', type: 'order_confirmation', status: 'sent' },
          ]),
        }),
      });
      MockNotification.prototype.save = jest.fn(function() {
        return Promise.resolve(this);
      });
      return MockNotification;
    }),
  };
});

// Mock nodemailer
jest.mock('nodemailer', () => ({
  createTransport: jest.fn(() => ({
    sendMail: jest.fn().mockResolvedValue({ messageId: 'mock-message-id-12345' }),
  })),
}));

// Reload modules after mocking
beforeEach(() => {
  jest.resetModules();
});

describe('NotificationService', () => {
  let notificationService;
  let nodemailer;

  beforeEach(() => {
    nodemailer           = require('nodemailer');
    notificationService  = require('../services/notificationService');
  });

  describe('sendOrderConfirmation', () => {
    it('should create a notification record and send confirmation email', async () => {
      const mockSendMail = jest.fn().mockResolvedValue({ messageId: 'test-msg-id' });
      nodemailer.createTransport.mockReturnValue({ sendMail: mockSendMail });

      notificationService = require('../services/notificationService');

      const items = [
        { product_id: 'prod-001', product_name: 'Test Widget', quantity: 2, price: 29.99 },
      ];

      // Should not throw
      await expect(
        notificationService.sendOrderConfirmation(
          'saga-001',
          'order-001',
          'customer@test.com',
          items,
          59.98
        )
      ).resolves.toBeDefined();
    });

    it('should handle email sending failure gracefully', async () => {
      const mockSendMail = jest.fn().mockRejectedValue(new Error('SMTP connection refused'));
      nodemailer.createTransport.mockReturnValue({ sendMail: mockSendMail });

      notificationService = require('../services/notificationService');

      await expect(
        notificationService.sendOrderConfirmation(
          'saga-fail-001',
          'order-fail-001',
          'bad@test.com',
          [],
          0
        )
      ).rejects.toThrow('SMTP connection refused');
    });
  });

  describe('sendOrderFailure', () => {
    it('should send failure notification successfully', async () => {
      const mockSendMail = jest.fn().mockResolvedValue({ messageId: 'fail-msg-id' });
      nodemailer.createTransport.mockReturnValue({ sendMail: mockSendMail });

      notificationService = require('../services/notificationService');

      await expect(
        notificationService.sendOrderFailure(
          'saga-002',
          'order-002',
          'customer@test.com',
          'Insufficient stock'
        )
      ).resolves.toBeDefined();
    });
  });

  describe('getNotification', () => {
    it('should retrieve notifications by order ID', async () => {
      notificationService = require('../services/notificationService');

      const results = await notificationService.getNotificationsByOrder('order-001');

      expect(Array.isArray(results)).toBe(true);
      expect(results.length).toBeGreaterThan(0);
      expect(results[0].orderId).toBe('order-001');
    });

    it('should retrieve a single notification by ID', async () => {
      notificationService = require('../services/notificationService');

      const result = await notificationService.getNotification('mock-id-123');

      expect(result).toBeDefined();
      expect(result.orderId).toBe('order-001');
    });
  });
});
