'use strict';

/**
 * PaymentService tests — all external dependencies mocked.
 * No MongoDB or RabbitMQ connection required.
 */

// ── In-memory stores (declared before jest.mock hoisting) ─────────────────────
const store = { payments: [], refunds: [] };

// ── Model mocks ───────────────────────────────────────────────────────────────

jest.mock('../src/models/Payment', () => ({
  findOne: jest.fn(),
  find: jest.fn(),
  countDocuments: jest.fn(),
  create: jest.fn(),
}));

jest.mock('../src/models/Refund', () => ({
  findOne: jest.fn(),
  create: jest.fn(),
}));

jest.mock('../src/services/PaymentGatewayService', () => ({
  processPayment: jest.fn(),
  refundPayment: jest.fn(),
}));

// Silence logger output in tests
jest.mock('../src/utils/logger', () => ({
  info: jest.fn(),
  warn: jest.fn(),
  error: jest.fn(),
  debug: jest.fn(),
}));

const gateway = require('../src/services/PaymentGatewayService');
const paymentService = require('../src/services/PaymentService');
const Payment = require('../src/models/Payment');
const Refund = require('../src/models/Refund');

// ── Helpers ───────────────────────────────────────────────────────────────────

function makePaymentParams(overrides = {}) {
  return {
    sagaId: 'saga-001',
    orderId: 'order-001',
    tenantId: 'tenant-001',
    amount: 99.99,
    currency: 'USD',
    paymentMethod: { type: 'card', last4: '4242', brand: 'Visa' },
    ...overrides,
  };
}

function makePaymentDoc(overrides = {}) {
  const doc = {
    paymentId: 'pay-uuid-001',
    sagaId: 'saga-001',
    orderId: 'order-001',
    tenantId: 'tenant-001',
    amount: 99.99,
    currency: 'USD',
    status: 'processing',
    paymentMethod: { type: 'card', last4: '4242', brand: 'Visa' },
    gatewayTransactionId: null,
    gatewayResponse: null,
    errorMessage: null,
    processedAt: null,
    refundedAt: null,
    ...overrides,
  };
  doc.save = jest.fn().mockResolvedValue(doc);
  return doc;
}

function makeRefundDoc(overrides = {}) {
  const doc = {
    refundId: 'ref-uuid-001',
    paymentId: 'pay-uuid-001',
    sagaId: 'saga-001',
    orderId: 'order-001',
    amount: 99.99,
    status: 'pending',
    gatewayRefundId: null,
    errorMessage: null,
    processedAt: null,
    ...overrides,
  };
  doc.save = jest.fn().mockResolvedValue(doc);
  return doc;
}

afterEach(() => {
  jest.clearAllMocks();
});

// ── Tests: processPayment ─────────────────────────────────────────────────────

describe('PaymentService.processPayment', () => {
  test('creates a payment and returns completed on gateway success', async () => {
    Payment.findOne.mockResolvedValue(null); // no existing payment
    const doc = makePaymentDoc();
    Payment.create.mockResolvedValue(doc);
    gateway.processPayment.mockResolvedValue({
      success: true,
      transactionId: 'txn_abc',
      gatewayResponse: { id: 'txn_abc', status: 'succeeded' },
    });

    const p = makePaymentParams();
    const result = await paymentService.processPayment(
      p.sagaId, p.orderId, p.tenantId, p.amount, p.currency, p.paymentMethod
    );

    expect(result.status).toBe('completed');
    expect(result.gatewayTransactionId).toBe('txn_abc');
    expect(doc.save).toHaveBeenCalled();
    expect(doc.status).toBe('completed');
  });

  test('idempotency — same sagaId+orderId returns cached result without calling gateway', async () => {
    const existing = makePaymentDoc({ status: 'completed', gatewayTransactionId: 'txn_cached' });
    Payment.findOne.mockResolvedValue(existing);

    const p = makePaymentParams();
    const result = await paymentService.processPayment(
      p.sagaId, p.orderId, p.tenantId, p.amount, p.currency, p.paymentMethod
    );

    expect(gateway.processPayment).not.toHaveBeenCalled();
    expect(Payment.create).not.toHaveBeenCalled();
    expect(result.status).toBe('completed');
    expect(result.gatewayTransactionId).toBe('txn_cached');
  });

  test('records failed status on gateway decline (insufficient funds)', async () => {
    Payment.findOne.mockResolvedValue(null);
    const doc = makePaymentDoc({ sagaId: 'saga-fail', orderId: 'order-fail' });
    Payment.create.mockResolvedValue(doc);
    gateway.processPayment.mockResolvedValue({
      success: false,
      errorCode: 'insufficient_funds',
      errorMessage: 'Insufficient funds',
      gatewayResponse: {},
    });

    const p = makePaymentParams({ sagaId: 'saga-fail', orderId: 'order-fail' });
    const result = await paymentService.processPayment(
      p.sagaId, p.orderId, p.tenantId, p.amount, p.currency, p.paymentMethod
    );

    expect(result.status).toBe('failed');
    expect(result.errorCode).toBe('insufficient_funds');
    expect(doc.status).toBe('failed');
    expect(doc.save).toHaveBeenCalled();
  });

  test('records failed status on gateway exception (timeout)', async () => {
    Payment.findOne.mockResolvedValue(null);
    const doc = makePaymentDoc({ sagaId: 'saga-timeout', orderId: 'order-timeout' });
    Payment.create.mockResolvedValue(doc);
    gateway.processPayment.mockRejectedValue(
      Object.assign(new Error('Gateway timeout'), { code: 'gateway_timeout' })
    );

    const p = makePaymentParams({ sagaId: 'saga-timeout', orderId: 'order-timeout' });
    const result = await paymentService.processPayment(
      p.sagaId, p.orderId, p.tenantId, p.amount, p.currency, p.paymentMethod
    );

    expect(result.status).toBe('failed');
    expect(result.errorCode).toBe('gateway_timeout');
    expect(doc.save).toHaveBeenCalled();
  });
});

// ── Tests: refundPayment ──────────────────────────────────────────────────────

describe('PaymentService.refundPayment', () => {
  test('refunds a completed payment successfully', async () => {
    Refund.findOne.mockResolvedValue(null); // no existing refund
    const paymentDoc = makePaymentDoc({ status: 'completed', gatewayTransactionId: 'txn_refundable' });
    Payment.findOne.mockResolvedValue(paymentDoc);
    const refundDoc = makeRefundDoc();
    Refund.create.mockResolvedValue(refundDoc);
    gateway.refundPayment.mockResolvedValue({
      success: true,
      refundId: 're_001',
      gatewayResponse: { id: 're_001', status: 'succeeded' },
    });

    const result = await paymentService.refundPayment('saga-001', 'order-001');

    expect(result.status).toBe('completed');
    expect(result.refundId).toBeDefined();
    expect(paymentDoc.status).toBe('refunded');
    expect(paymentDoc.save).toHaveBeenCalled();
    expect(refundDoc.save).toHaveBeenCalled();
  });

  test('idempotency — duplicate refund request returns existing refund without calling gateway', async () => {
    const existingRefund = makeRefundDoc({ status: 'completed', gatewayRefundId: 're_cached' });
    Refund.findOne.mockResolvedValue(existingRefund);

    const result = await paymentService.refundPayment('saga-001', 'order-001');

    expect(gateway.refundPayment).not.toHaveBeenCalled();
    expect(Refund.create).not.toHaveBeenCalled();
    expect(result.status).toBe('completed');
  });

  test('throws PAYMENT_NOT_FOUND when no payment exists', async () => {
    Refund.findOne.mockResolvedValue(null);
    Payment.findOne.mockResolvedValue(null);

    await expect(
      paymentService.refundPayment('no-saga', 'no-order')
    ).rejects.toMatchObject({ code: 'PAYMENT_NOT_FOUND' });
  });

  test('throws INVALID_PAYMENT_STATUS when payment is not completed', async () => {
    Refund.findOne.mockResolvedValue(null);
    const paymentDoc = makePaymentDoc({ status: 'pending' });
    Payment.findOne.mockResolvedValue(paymentDoc);

    await expect(
      paymentService.refundPayment('saga-001', 'order-001')
    ).rejects.toMatchObject({ code: 'INVALID_PAYMENT_STATUS' });
  });

  test('records failed refund when gateway declines', async () => {
    Refund.findOne.mockResolvedValue(null);
    const paymentDoc = makePaymentDoc({ status: 'completed', gatewayTransactionId: 'txn_x' });
    Payment.findOne.mockResolvedValue(paymentDoc);
    const refundDoc = makeRefundDoc();
    Refund.create.mockResolvedValue(refundDoc);
    gateway.refundPayment.mockResolvedValue({
      success: false,
      errorCode: 'refund_failed',
      errorMessage: 'Gateway rejected refund',
      gatewayResponse: {},
    });

    const result = await paymentService.refundPayment('saga-001', 'order-001');

    expect(result.status).toBe('failed');
    expect(result.errorMessage).toBe('Gateway rejected refund');
    expect(refundDoc.save).toHaveBeenCalled();
  });
});
