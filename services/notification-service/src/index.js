'use strict';

require('dotenv').config();
const express          = require('express');
const amqplib          = require('amqplib');
const { createClient } = require('redis');
const nodemailer       = require('nodemailer');
const winston          = require('winston');

const PORT         = process.env.PORT         || 3001;
const RABBITMQ_URL = process.env.RABBITMQ_URL || 'amqp://admin:admin_password@rabbitmq:5672';
const REDIS_URL    = process.env.REDIS_URL    || 'redis://redis:6379';

// ─── Logger ──────────────────────────────────────────────────────────────────
const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.json()
  ),
  transports: [new winston.transports.Console()],
});

// ─── Email Transporter ───────────────────────────────────────────────────────
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.ethereal.email',
  port: parseInt(process.env.SMTP_PORT || '587', 10),
  auth: {
    user: process.env.SMTP_USER || '',
    pass: process.env.SMTP_PASS || '',
  },
});

// ─── Redis Client ────────────────────────────────────────────────────────────
const redisClient = createClient({ url: REDIS_URL });
redisClient.on('error', (err) => logger.error('Redis error', { error: err.message }));

// ─── Notification Templates ──────────────────────────────────────────────────
const templates = {
  order_confirmed: (data) => ({
    subject: `Order Confirmed – #${data.order_id}`,
    text:    `Hello! Your order ${data.order_id} has been confirmed. Total: $${data.amount || 'N/A'}.`,
    html:    `<h2>Order Confirmed!</h2><p>Your order <strong>${data.order_id}</strong> has been confirmed.</p>`,
  }),
  order_failed: (data) => ({
    subject: `Order Failed – #${data.order_id}`,
    text:    `Sorry, your order ${data.order_id} could not be processed. Reason: ${data.reason || 'unknown'}.`,
    html:    `<h2>Order Failed</h2><p>Order <strong>${data.order_id}</strong> failed. Reason: ${data.reason || 'unknown'}.</p>`,
  }),
  payment_processed: (data) => ({
    subject: `Payment Received – #${data.order_id}`,
    text:    `Payment for order ${data.order_id} was successfully processed.`,
    html:    `<h2>Payment Successful</h2><p>Payment for order <strong>${data.order_id}</strong> processed.</p>`,
  }),
  payment_failed: (data) => ({
    subject: `Payment Failed – #${data.order_id}`,
    text:    `Payment for order ${data.order_id} failed. Please update your payment method.`,
    html:    `<h2>Payment Failed</h2><p>Payment for order <strong>${data.order_id}</strong> failed.</p>`,
  }),
};

// ─── Core Send Function ──────────────────────────────────────────────────────
async function sendNotification(data) {
  const idempotencyKey = `notif:${data.order_id}:${data.event}`;

  // Deduplication via Redis
  const alreadySent = await redisClient.get(idempotencyKey);
  if (alreadySent) {
    logger.info('Notification already sent (deduplicated)', { key: idempotencyKey });
    return { status: 'deduplicated' };
  }

  const templateFn = templates[data.event];
  if (!templateFn) {
    logger.warn('Unknown notification event', { event: data.event });
    return { status: 'unknown_event' };
  }

  const { subject, text, html } = templateFn(data);

  // In production, resolve customer email from a user/profile service
  const toEmail = data.customer_email || `customer-${data.customer_id}@example.com`;

  try {
    const info = await transporter.sendMail({
      from:    process.env.FROM_EMAIL || 'no-reply@example.com',
      to:      toEmail,
      subject,
      text,
      html,
    });

    logger.info('Notification sent', {
      order_id:  data.order_id,
      event:     data.event,
      messageId: info.messageId,
    });

    // Mark as sent with 24-hour TTL to prevent duplicates
    await redisClient.setEx(idempotencyKey, 86400, 'sent');

    return { status: 'sent', messageId: info.messageId };
  } catch (err) {
    logger.error('Failed to send notification', { error: err.message, order_id: data.order_id });
    throw err;
  }
}

// ─── RabbitMQ Consumer ───────────────────────────────────────────────────────
async function startRabbitMQConsumer() {
  try {
    const conn     = await amqplib.connect(RABBITMQ_URL);
    const channel  = await conn.createChannel();
    const exchange = 'order.events';

    await channel.assertExchange(exchange, 'topic', { durable: true });

    const { queue } = await channel.assertQueue('notification.queue', { durable: true });

    // Subscribe to all order.* events
    await channel.bindQueue(queue, exchange, 'order.*');

    logger.info('RabbitMQ consumer started', { exchange, queue: 'notification.queue' });

    channel.consume(queue, async (msg) => {
      if (!msg) return;
      try {
        const data = JSON.parse(msg.content.toString());
        logger.info('Event received from RabbitMQ', { routingKey: msg.fields.routingKey });

        // Extract event name from routing key, e.g. "order.confirmed" → "order_confirmed"
        const eventName = msg.fields.routingKey.replaceAll('.', '_');
        await sendNotification({ ...data, event: eventName });
        channel.ack(msg);
      } catch (err) {
        logger.error('Error processing RabbitMQ message', { error: err.message });
        channel.nack(msg, false, false); // Dead-letter on failure
      }
    });

    conn.on('close', () => {
      logger.warn('RabbitMQ connection closed, retrying in 5s...');
      setTimeout(startRabbitMQConsumer, 5000);
    });

    conn.on('error', (err) => {
      logger.error('RabbitMQ connection error', { error: err.message });
    });
  } catch (err) {
    logger.error('Failed to connect to RabbitMQ', { error: err.message });
    setTimeout(startRabbitMQConsumer, 5000);
  }
}

// ─── Express App ─────────────────────────────────────────────────────────────
const app = express();
app.use(express.json());

app.get('/health', (_req, res) => {
  res.json({
    service:   'notification-service',
    status:    'healthy',
    timestamp: new Date().toISOString(),
  });
});

app.post('/api/notifications/send', async (req, res) => {
  try {
    const result = await sendNotification(req.body);
    res.json(result);
  } catch (err) {
    logger.error('Notification send error', { error: err.message });
    res.status(500).json({ error: 'Failed to send notification.' });
  }
});

app.get('/api/notifications/health', (_req, res) => {
  res.json({ status: 'ok' });
});

// ─── Bootstrap ───────────────────────────────────────────────────────────────
async function bootstrap() {
  try {
    await redisClient.connect();
    logger.info('Redis connected');
  } catch (err) {
    logger.error('Redis connection failed', { error: err.message });
  }

  await startRabbitMQConsumer();

  app.listen(PORT, () => {
    logger.info(`Notification Service listening on port ${PORT}`);
  });
}

bootstrap().catch((err) => {
  logger.error('Bootstrap failed', { error: err.message });
  process.exit(1);
});

module.exports = app;
