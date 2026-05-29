import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    advancePayments,
    allocationPreview,
    cashRegisters,
    checks,
    getPaymentById,
    paymentActivity,
    paymentAllocations,
    paymentDashboardMetrics,
    paymentGroups,
    paymentMethods,
    payments,
    postingPreview,
    refunds,
    sourceReferences,
    writeOffs,
} from '../mock/paymentMock';
import type {
    AdvancePayment,
    CashRegister,
    CheckPayment,
    Payment,
    PaymentAllocation,
    PaymentAllocationPreview,
    PaymentAuditEntry,
    PaymentFormInput,
    PaymentGroup,
    PaymentMethod,
    PaymentPostingPreview,
    PaymentSourceReference,
    Refund,
    WriteOff,
} from '../types/payment.types';

type BackendRecord = Record<string, unknown>;

const PAYMENT_API_MODE = import.meta.env.VITE_PAYMENT_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return PAYMENT_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (PAYMENT_API_MODE === 'real') {
            throw error;
        }

        if (error instanceof ApiError && !fallbackStatuses.includes(error.status)) {
            throw error;
        }

        return mockCall();
    }
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function normalizePayment(raw: BackendRecord): Payment {
    const metadata = raw.metadata && typeof raw.metadata === 'object' ? raw.metadata as BackendRecord : {};
    const paymentId = asString(raw.id);

    return {
        allocatedAmount: asString(raw.allocated_amount ?? metadata.allocated_amount, 'Backend calculated'),
        amount: asString(raw.amount, 'Backend amount'),
        currency: asString(metadata.currency_code ?? raw.currency_code, 'Backend currency'),
        direction: normalizeDirection(raw.direction ?? metadata.direction),
        id: paymentId,
        methodName: asString(metadata.payment_method_name ?? raw.payment_method_name, 'Backend method'),
        party: asString(metadata.party_name ?? raw.party_name ?? raw.party_id, 'Backend party'),
        partyType: asString(raw.party_type ?? metadata.party_type, 'generic'),
        paymentDate: asString(raw.payment_date, 'Backend date'),
        paymentNumber: asString(raw.payment_number, `PAY-${paymentId}`),
        reference: asString(raw.reference),
        sourceModule: asString(metadata.source_module),
        sourceReference: asString(metadata.source_reference ?? raw.reference),
        status: normalizeStatus(raw.status),
        unallocatedAmount: asString(metadata.unallocated_amount, 'Backend calculated'),
        updatedAt: asString(raw.updated_at, 'Backend timestamp'),
    };
}

function normalizeDirection(value: unknown): Payment['direction'] {
    const direction = asString(value, 'generic_receipt');
    if (direction === 'inbound') {
        return 'generic_receipt';
    }
    if (direction === 'outbound') {
        return 'generic_payment';
    }
    return ['customer_receipt', 'supplier_payment', 'generic_receipt', 'generic_payment'].includes(direction)
        ? direction as Payment['direction']
        : 'generic_receipt';
}

function normalizeStatus(value: unknown): Payment['status'] {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: Payment['status'][] = ['draft', 'pending', 'posted', 'partially_allocated', 'fully_allocated', 'reconciled', 'voided', 'reversed', 'failed'];

    return allowed.includes(status as Payment['status']) ? status as Payment['status'] : 'draft';
}

function toPaymentPayload(input: PaymentFormInput) {
    return {
        account_id: input.accountId ? Number(input.accountId) : 1,
        amount: input.amount,
        currency_id: input.currency === 'LKR' ? 1 : undefined,
        direction: input.direction === 'supplier_payment' || input.direction === 'generic_payment' ? 'outbound' : 'inbound',
        metadata: {
            direction: input.direction,
            source_id: input.sourceId,
            source_module: input.sourceModule,
            source_reference: input.sourceReference,
            source_type: input.sourceType,
        },
        notes: input.notes,
        party_id: input.partyId ? Number(input.partyId) : undefined,
        party_type: input.partyType || undefined,
        payment_date: input.paymentDate,
        payment_method_id: Number(input.paymentMethodId || 1),
        payment_number: `FRONTEND-DRAFT-${Date.now()}`,
        reference: input.reference,
    };
}

function mockPaymentFromInput(input: PaymentFormInput): Payment {
    return {
        allocatedAmount: 'Backend calculated',
        amount: input.amount,
        currency: input.currency,
        direction: input.direction,
        id: 'mock-payment-draft',
        methodName: 'Selected method',
        party: input.partyType || 'Selected party',
        partyType: input.partyType,
        paymentDate: input.paymentDate,
        paymentNumber: 'Backend sequence pending',
        reference: input.reference,
        sourceModule: input.sourceModule,
        sourceReference: input.sourceReference,
        status: 'draft',
        unallocatedAmount: 'Backend calculated',
        updatedAt: 'Mock timestamp',
    };
}

export const paymentApi = {
    listDashboardMetrics: () => mockCollectionResponse(paymentDashboardMetrics),

    listPayments: (): Promise<ApiCollectionResponse<Payment>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/payment/payments');

            return { ...response, data: response.data.map(normalizePayment) };
        },
        () => mockCollectionResponse(payments),
    ),

    getPayment: (paymentId: string): Promise<ApiResponse<Payment>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/payment/payments/${paymentId}`);

            return { ...response, data: normalizePayment(response.data) };
        },
        () => mockResponse(getPaymentById(paymentId)),
    ),

    createPayment: (input: PaymentFormInput): Promise<ApiResponse<Payment | PaymentFormInput>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/payment/payments', { body: toPaymentPayload(input), method: 'POST' });

            return { ...response, data: normalizePayment(response.data) };
        },
        () => mockResponse(mockPaymentFromInput(input)),
    ),

    updatePayment: (paymentId: string, input: PaymentFormInput): Promise<ApiResponse<Payment | PaymentFormInput>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/payment/payments/${paymentId}`, { body: toPaymentPayload(input), method: 'PUT' });

            return { ...response, data: normalizePayment(response.data) };
        },
        () => mockResponse({ ...mockPaymentFromInput(input), id: paymentId }),
    ),

    postPayment: (paymentId: string) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/post`, { method: 'POST' }),
        () => mockResponse({ action: 'post-requested', paymentId }),
    ),

    reversePayment: (paymentId: string, input?: unknown) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/reverse`, { body: input ?? {}, method: 'POST' }),
        () => mockResponse({ action: 'reverse-requested', input, paymentId }),
    ),

    voidPayment: (paymentId: string) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}`, { method: 'DELETE' }),
        () => mockResponse({ action: 'void-requested', paymentId }),
    ),

    refundPayment: (paymentId: string, input?: unknown) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/refund`, { body: input ?? {}, method: 'POST' }),
        () => mockResponse({ action: 'refund-requested', input, paymentId }),
    ),

    listPaymentMethods: (): Promise<ApiCollectionResponse<PaymentMethod>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<PaymentMethod>>('/api/payment/payment-methods'),
        () => mockCollectionResponse(paymentMethods),
    ),

    createPaymentMethod: (input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<PaymentMethod>>('/api/payment/payment-methods', { body: input, method: 'POST' }),
        () => mockResponse(input),
    ),

    updatePaymentMethod: (methodId: string, input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<PaymentMethod>>(`/api/payment/payment-methods/${methodId}`, { body: input, method: 'PUT' }),
        () => mockResponse(input),
    ),

    listPaymentGroups: (): Promise<ApiCollectionResponse<PaymentGroup>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<PaymentGroup>>('/api/payment/payment-groups'),
        () => mockCollectionResponse(paymentGroups),
    ),

    listAllocations: (): Promise<ApiCollectionResponse<PaymentAllocation>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<PaymentAllocation>>('/api/payment/payment-allocations'),
        () => mockCollectionResponse(paymentAllocations),
    ),

    previewAllocation: (paymentId: string, input: unknown): Promise<ApiPreviewResponse<unknown, PaymentAllocationPreview['calculated']>> => withMockFallback(
        async () => {
            const response = await httpClient<ApiResponse<PaymentAllocationPreview>>(`/api/payment/payments/${paymentId}/engines/preview-allocation`, { body: input, method: 'POST' });
            const preview = response.data;

            return {
                breakdown: preview.breakdown,
                calculated: preview.calculated,
                errors: preview.errors,
                input,
                warnings: preview.warnings,
            };
        },
        () => mockPreviewResponse(input, allocationPreview.calculated, allocationPreview.breakdown, allocationPreview.warnings),
    ),

    allocatePayment: (paymentId: string, input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/allocate`, { body: input, method: 'POST' }),
        () => mockResponse({ action: 'allocate-requested', input, paymentId }),
    ),

    unallocatePayment: (paymentId: string, input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/unallocate`, { body: input, method: 'POST' }),
        () => mockResponse({ action: 'unallocate-requested', input, paymentId }),
    ),

    listAdvancePayments: (): Promise<ApiCollectionResponse<AdvancePayment>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<AdvancePayment>>('/api/payment/advance-payments'),
        () => mockCollectionResponse(advancePayments),
    ),

    createAdvancePayment: (input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<AdvancePayment>>('/api/payment/advance-payments', { body: input, method: 'POST' }),
        () => mockResponse(input),
    ),

    allocateAdvancePayment: (input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<unknown>>('/api/payment/advance-payment-allocations', { body: input, method: 'POST' }),
        () => mockResponse(input),
    ),

    listRefunds: () => mockCollectionResponse(refunds),

    createRefund: (paymentId: string, input: unknown) => paymentApi.refundPayment(paymentId, input),

    listWriteOffs: (): Promise<ApiCollectionResponse<WriteOff>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<WriteOff>>('/api/payment/write-offs'),
        () => mockCollectionResponse(writeOffs),
    ),

    createWriteOff: (input: unknown) => withMockFallback(
        () => httpClient<ApiResponse<WriteOff>>('/api/payment/write-offs', { body: input, method: 'POST' }),
        () => mockResponse(input),
    ),

    listCashRegisters: (): Promise<ApiCollectionResponse<CashRegister>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<CashRegister>>('/api/payment/cash-registers'),
        () => mockCollectionResponse(cashRegisters),
    ),

    listChecks: (): Promise<ApiCollectionResponse<CheckPayment>> => withMockFallback(
        () => httpClient<ApiCollectionResponse<CheckPayment>>('/api/payment/checks'),
        () => mockCollectionResponse(checks),
    ),

    getPaymentActivity: (_paymentId: string): Promise<ApiCollectionResponse<PaymentAuditEntry>> => mockCollectionResponse(paymentActivity),

    listSourceReferences: (_paymentId: string): Promise<ApiCollectionResponse<PaymentSourceReference>> => mockCollectionResponse(sourceReferences),

    getPaymentPostingPreview: (_paymentId: string): Promise<ApiResponse<PaymentPostingPreview>> => mockResponse(postingPreview),
};
