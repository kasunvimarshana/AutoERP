'use strict';

const NotificationProcessor = require('../src/services/NotificationProcessor');
const Notification           = require('../src/models/Notification');

// Mock Mongoose model
jest.mock('../src/models/Notification');
jest.mock('../src/services/EmailNotificationService');

describe('NotificationProcessor', () => {
  let processor;

  beforeEach(() => {
    jest.clearAllMocks();
    processor = new NotificationProcessor();
  });

  it('creates a notification record and sends via email', async () => {
    const mockNotification = {
      id: 'notif-123',
      status: 'pending',
      updateOne: jest.fn().mockResolvedValue({}),
    };

    Notification.create.mockResolvedValue(mockNotification);

    // Mock email service send to succeed
    const emailServiceSendMock = jest.fn().mockResolvedValue({ success: true, messageId: 'msg-abc' });
    processor.channelServices.email = { send: emailServiceSendMock, getChannel: () => 'email' };

    const result = await processor.process({
      type:      'order_confirmed',
      orderId:   'order-1',
      tenantId:  'tenant-1',
      recipient: 'user@example.com',
      payload:   { order_id: 'order-1' },
    });

    expect(Notification.create).toHaveBeenCalledWith(expect.objectContaining({
      type:      'order_confirmed',
      recipient: 'user@example.com',
      status:    'pending',
    }));

    expect(emailServiceSendMock).toHaveBeenCalled();
    expect(mockNotification.updateOne).toHaveBeenCalledWith(
      expect.objectContaining({ status: 'sent' })
    );
  });

  it('marks notification as failed when send fails', async () => {
    const mockNotification = {
      id: 'notif-456',
      status: 'pending',
      updateOne: jest.fn().mockResolvedValue({}),
    };

    Notification.create.mockResolvedValue(mockNotification);

    processor.channelServices.email = {
      send: jest.fn().mockResolvedValue({ success: false, error: 'SMTP timeout' }),
      getChannel: () => 'email',
    };

    const result = await processor.process({
      type:      'order_confirmed',
      orderId:   'order-2',
      tenantId:  'tenant-1',
      recipient: 'user@example.com',
      payload:   { order_id: 'order-2' },
    });

    expect(mockNotification.updateOne).toHaveBeenCalledWith(
      expect.objectContaining({ status: 'failed', errorMessage: 'SMTP timeout' })
    );
  });

  it('builds correct email template for order_confirmed type', () => {
    const message = processor.buildMessage('order_confirmed', { order_id: 'ORD-999' });

    expect(message.subject).toContain('ORD-999');
    expect(message.body).toContain('ORD-999');
    expect(message.html).toContain('ORD-999');
  });

  it('throws and propagates when MongoDB create fails', async () => {
    Notification.create.mockRejectedValue(new Error('MongoDB connection refused'));

    await expect(
      processor.process({
        type:      'order_confirmed',
        orderId:   'order-3',
        tenantId:  'tenant-1',
        recipient: 'user@example.com',
        payload:   { order_id: 'order-3' },
      })
    ).rejects.toThrow('MongoDB connection refused');
  });
});
