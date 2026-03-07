'use strict';

module.exports = {
  port: parseInt(process.env.PORT || '8003', 10),

  mongodb: {
    uri: process.env.MONGODB_URI || 'mongodb://mongo:27017/notifications_db',
  },

  rabbitmq: {
    url: process.env.RABBITMQ_URL || 'amqp://guest:guest@rabbitmq:5672/',
    exchanges: {
      commands: 'saga.commands',
      replies:  'saga.replies',
    },
    queues: {
      sendNotification: 'send-notification',
      sagaReplies:      'saga-replies',
    },
  },

  email: {
    host: process.env.EMAIL_HOST || 'smtp.ethereal.email',
    port: parseInt(process.env.EMAIL_PORT || '587', 10),
    user: process.env.EMAIL_USER || '',
    pass: process.env.EMAIL_PASS || '',
    from: process.env.EMAIL_FROM || 'noreply@saga-orders.local',
  },
};
