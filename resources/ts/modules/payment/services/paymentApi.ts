import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import { getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
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
    PaymentMethodFormInput,
    PaymentPostingPreview,
    PaymentSourceReference,
    Refund,
    WriteOff,
} from '../types/payment.types';

type BackendRecord = Record<string, unknown>;

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asNumberString(value: unknown, fallback = '0.0000') {
    if (value === null || value === undefined || value === '') return fallback;
    return Number(value).toFixed(4);
}

function asBool(value: unknown, fallback = false) {
    return value === null || value === undefined ? fallback : Boolean(value);
}

function record(value: unknown): BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function collectionPayload<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    return { ...response, data: response.data.map(mapper) };
}

function contextQuery() {
    const params = new URLSearchParams();
    const tenantId = getStoredTenantId();
    const organizationUnitId = getStoredOrganizationUnitId();
    if (tenantId) params.set('tenant_id', tenantId);
    if (organizationUnitId) params.set('organization_unit_id', organizationUnitId);
    params.set('per_page', '100');

    return `?${params.toString()}`;
}

function contextPayload(input: BackendRecord = {}): BackendRecord {
    return {
        ...input,
        organization_unit_id: input.organization_unit_id ?? numberOrUndefined(getStoredOrganizationUnitId()),
        tenant_id: input.tenant_id ?? numberOrUndefined(getStoredTenantId()),
    };
}

function numberOrUndefined(value: string | null) {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function normalizeDirection(value: unknown): Payment['direction'] {
    const direction = asString(value, 'generic_receipt');
    if (direction === 'inbound') return 'generic_receipt';
    if (direction === 'outbound') return 'generic_payment';
    return ['customer_receipt', 'supplier_payment', 'generic_receipt', 'generic_payment'].includes(direction)
        ? direction as Payment['direction']
        : 'generic_receipt';
}

function backendDirection(direction: Payment['direction']) {
    return direction === 'supplier_payment' || direction === 'generic_payment' ? 'outbound' : 'inbound';
}

function normalizeStatus(value: unknown): Payment['status'] {
    const status = asString(value, 'draft').toLowerCase();
    const allowed: Payment['status'][] = ['draft', 'pending', 'posted', 'partially_allocated', 'fully_allocated', 'reconciled', 'voided', 'reversed', 'failed'];
    return allowed.includes(status as Payment['status']) ? status as Payment['status'] : 'draft';
}

function normalizePayment(raw: BackendRecord): Payment {
    const metadata = record(raw.metadata);
    const amount = Number(raw.amount ?? 0);
    const allocated = Number(raw.allocated_amount ?? 0);

    return {
        allocatedAmount: asNumberString(raw.allocated_amount),
        amount: asNumberString(raw.amount),
        currency: asString(raw.currency_code ?? metadata.currency_code, 'LKR'),
        direction: normalizeDirection(metadata.direction ?? raw.direction),
        id: asString(raw.id),
        methodName: asString(metadata.payment_method_name ?? raw.payment_method_name ?? raw.payment_method_id, 'Payment method'),
        party: asString(metadata.party_name ?? raw.payer_name ?? raw.payee_name ?? raw.party_id ?? raw.party_type, 'Generic party'),
        partyType: asString(raw.party_type ?? metadata.party_type, 'generic'),
        paymentDate: asString(raw.payment_date).slice(0, 10),
        paymentNumber: asString(raw.payment_number),
        reference: asString(raw.reference),
        sourceModule: asString(raw.source_module ?? metadata.source_module),
        sourceReference: asString(raw.source_reference ?? metadata.source_reference),
        sourceType: asString(raw.source_type ?? metadata.source_type),
        status: normalizeStatus(raw.status),
        unallocatedAmount: asNumberString(Math.max(0, amount - allocated)),
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeMethod(raw: BackendRecord): PaymentMethod {
    return {
        accountId: asString(raw.account_id),
        accountName: asString(record(raw.metadata).account_name ?? raw.account_name),
        code: asString(raw.code),
        id: asString(raw.id),
        isActive: asBool(raw.is_active, true),
        name: asString(raw.name),
        type: asString(raw.type, 'other') as PaymentMethod['type'],
    };
}

function normalizeGroup(raw: BackendRecord): PaymentGroup {
    return {
        direction: asString(raw.direction),
        groupType: asString(raw.group_type ?? raw.type),
        id: asString(raw.id),
        reference: asString(raw.reference),
        status: asString(raw.status, 'draft'),
        totalAmount: asNumberString(raw.total_amount),
        transactionNumber: asString(raw.transaction_number ?? raw.group_number ?? raw.id),
    };
}

function normalizeAllocation(raw: BackendRecord): PaymentAllocation {
    return {
        allocatedAmount: asNumberString(raw.allocated_amount),
        allocationDate: asString(raw.allocation_date ?? raw.created_at).slice(0, 10),
        documentNumber: asString(raw.source_reference ?? raw.reference ?? raw.document_id),
        documentType: asString(raw.document_type ?? raw.source_type),
        id: asString(raw.id),
        paymentId: asString(raw.payment_id),
        reference: asString(raw.reference),
        status: asString(raw.status, 'active'),
    };
}

function normalizeAdvance(raw: BackendRecord): AdvancePayment {
    const metadata = record(raw.metadata);
    return {
        advanceDate: asString(raw.advance_date).slice(0, 10),
        advanceNumber: asString(raw.advance_number),
        amount: asNumberString(raw.amount),
        currency: asString(raw.currency_code ?? metadata.currency_code, 'LKR'),
        id: asString(raw.id),
        party: asString(metadata.party_name ?? raw.party_id ?? raw.party_type, 'Generic party'),
        partyType: asString(raw.party_type),
        remainingAmount: asNumberString(raw.remaining_amount),
        status: asString(raw.status, 'open'),
        type: asString(raw.type, 'generic'),
    };
}

function normalizeWriteOff(raw: BackendRecord): WriteOff {
    return {
        amount: asNumberString(raw.amount),
        documentNumber: asString(raw.source_reference ?? raw.reference ?? raw.document_id),
        documentType: asString(raw.document_type),
        id: asString(raw.id),
        reason: asString(raw.reason),
        reference: asString(raw.reference),
        status: asString(raw.status, 'draft'),
    };
}

function normalizeCashRegister(raw: BackendRecord): CashRegister {
    return {
        assignedUser: asString(raw.assigned_user_id, 'Unassigned'),
        code: asString(raw.code),
        currentBalance: asNumberString(raw.current_balance),
        id: asString(raw.id),
        name: asString(raw.name),
        openingBalance: asNumberString(raw.opening_balance),
        status: asString(raw.status, 'closed'),
    };
}

function normalizeCheck(raw: BackendRecord): CheckPayment {
    const metadata = record(raw.metadata);
    return {
        amount: asNumberString(raw.amount),
        bank: asString(metadata.bank_name ?? raw.bank_account_id),
        checkNumber: asString(raw.check_number),
        dueDate: asString(raw.due_date ?? raw.check_date).slice(0, 10),
        id: asString(raw.id),
        linkedPayment: asString(raw.payment_id),
        party: asString(raw.party_id ?? raw.party_type, 'Generic party'),
        status: asString(raw.status, 'pending'),
        type: asString(raw.type),
    };
}

function paymentPayload(input: PaymentFormInput): BackendRecord {
    return contextPayload({
        amount: input.amount,
        direction: backendDirection(input.direction),
        exchange_rate: 1,
        metadata: {
            currency_code: input.currency,
            direction: input.direction,
            party_name: input.partyName,
            payment_method_name: input.paymentMethodName,
        },
        notes: input.notes,
        party_id: numberOrUndefined(input.partyId ?? null),
        party_role: input.direction.includes('receipt') ? 'payer' : 'payee',
        party_type: input.partyType || 'external_party',
        payee_name: input.direction.includes('payment') ? input.partyName : undefined,
        payer_name: input.direction.includes('receipt') ? input.partyName : undefined,
        payment_date: input.paymentDate,
        payment_method_id: Number(input.paymentMethodId),
        reference: input.reference,
        source_id: numberOrUndefined(input.sourceId ?? null),
        source_module: input.sourceModule || undefined,
        source_reference: input.sourceReference || undefined,
        source_type: input.sourceType || undefined,
    });
}

export const paymentApi = {
    allocatePayment: (paymentId: string, input: { allocatedAmount: string; documentId: string; documentType: string; reference?: string }) => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/allocate`, {
        body: contextPayload({
            allocated_amount: input.allocatedAmount,
            document_id: Number(input.documentId),
            document_type: input.documentType,
            reference: input.reference,
        }),
        method: 'POST',
    }),
    createPayment: async (input: PaymentFormInput): Promise<ApiResponse<Payment>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/payment/payments', { body: paymentPayload(input), method: 'POST' });
        return { ...response, data: normalizePayment(response.data ?? record(response)) };
    },
    createPaymentMethod: async (input: PaymentMethodFormInput): Promise<ApiResponse<PaymentMethod>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>('/api/payment/payment-methods', {
            body: contextPayload({
                account_id: numberOrUndefined(input.accountId ?? null),
                code: input.code,
                is_active: input.isActive,
                name: input.name,
                type: input.type,
            }),
            method: 'POST',
        });
        return { ...response, data: normalizeMethod(response.data ?? record(response)) };
    },
    createRefund: (paymentId: string, input: unknown) => paymentApi.refundPayment(paymentId, input),
    createWriteOff: async (input: unknown) => httpClient<ApiResponse<WriteOff>>('/api/payment/write-offs', { body: contextPayload(record(input)), method: 'POST' }),
    getPayment: async (paymentId: string): Promise<ApiResponse<Payment>> => {
        const response = await httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/payment/payments/${paymentId}`);
        const raw = 'data' in response && response.data ? response.data as BackendRecord : response as BackendRecord;
        return { data: normalizePayment(raw) };
    },
    getPaymentActivity: async (paymentId: string): Promise<ApiCollectionResponse<PaymentAuditEntry>> => {
        const response = await paymentApi.getPayment(paymentId);
        return {
            data: [
                {
                    actor: 'Backend',
                    description: `Payment status is ${response.data.status}.`,
                    id: `${paymentId}-status`,
                    time: response.data.updatedAt,
                },
            ],
        };
    },
    getPaymentPostingPreview: async (_paymentId: string): Promise<ApiResponse<PaymentPostingPreview>> => ({
        data: {
            breakdown: [{ label: 'Posting preview', value: 'Finance posting preview endpoint is not configured for Payment yet.' }],
            errors: [],
            journalImpact: [],
            warnings: ['Posting preview is unavailable until Finance exposes a generic preview endpoint for Payment.'],
        },
    }),
    listAdvancePayments: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/advance-payments${contextQuery()}`), normalizeAdvance),
    listAllocations: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/payment-allocations${contextQuery()}`), normalizeAllocation),
    listCashRegisters: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/cash-registers${contextQuery()}`), normalizeCashRegister),
    listChecks: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/checks${contextQuery()}`), normalizeCheck),
    listDashboardMetrics: async (): Promise<ApiCollectionResponse<{ label: string; tone: string; value: string }>> => {
        const [payments, methods, allocations] = await Promise.all([paymentApi.listPayments(), paymentApi.listPaymentMethods(), paymentApi.listAllocations()]);
        return {
            data: [
                { label: 'Payments', tone: 'active', value: String(payments.data.length) },
                { label: 'Draft', tone: 'draft', value: String(payments.data.filter((payment) => payment.status === 'draft').length) },
                { label: 'Posted', tone: 'posted', value: String(payments.data.filter((payment) => payment.status === 'posted').length) },
                { label: 'Methods', tone: 'active', value: String(methods.data.length) },
                { label: 'Allocations', tone: 'active', value: String(allocations.data.length) },
                { label: 'Backend-owned', tone: 'pending', value: 'Balances' },
            ],
        };
    },
    listPaymentGroups: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/payment-groups${contextQuery()}`), normalizeGroup),
    listPaymentMethods: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/payment-methods${contextQuery()}`), normalizeMethod),
    listPayments: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/payments${contextQuery()}`), normalizePayment),
    listRefunds: async (): Promise<ApiCollectionResponse<Refund>> => ({ data: [] }),
    listSourceReferences: async (paymentId: string): Promise<ApiCollectionResponse<PaymentSourceReference>> => {
        const response = await paymentApi.getPayment(paymentId);
        return {
            data: response.data.sourceReference
                ? [{
                    id: `${paymentId}-source`,
                    label: response.data.sourceReference,
                    sourceModule: response.data.sourceModule ?? '',
                    sourceReference: response.data.sourceReference,
                    sourceType: response.data.sourceType ?? '',
                }]
                : [],
        };
    },
    listWriteOffs: async () => collectionPayload(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/payment/write-offs${contextQuery()}`), normalizeWriteOff),
    postPayment: (paymentId: string) => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/post`, { method: 'POST' }),
    previewAllocation: async (paymentId: string, input: { allocatedAmount: string; documentId: string; documentType: string }): Promise<ApiPreviewResponse<unknown, PaymentAllocationPreview['calculated']>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/payment/payments/${paymentId}/engines/preview-allocation`, {
            body: contextPayload({
                allocated_amount: input.allocatedAmount,
                document_id: Number(input.documentId),
                document_type: input.documentType,
            }),
            method: 'POST',
        });
        const preview = response.data;
        return {
            breakdown: [
                { label: 'Payment amount', value: asNumberString(preview.payment_amount) },
                { label: 'Currently allocated', value: asNumberString(preview.allocated_amount_total) },
                { label: 'Requested allocation', value: asNumberString(preview.requested_allocation_amount) },
            ],
            calculated: {
                allocatedAmount: asNumberString(preview.allocated_amount_total),
                remainingUnallocatedAmount: asNumberString(preview.remaining_after_allocation),
                targetRemainingBalance: asString(preview.can_allocate) === 'true' ? 'Backend allowed' : 'Backend rejected',
            },
            errors: asBool(preview.can_allocate) ? [] : ['Backend rejected this allocation request.'],
            input,
            warnings: asBool(preview.duplicate_allocation_exists) ? ['Duplicate allocation exists for this target.'] : [],
        };
    },
    refundPayment: (paymentId: string, input?: unknown) => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/refund`, { body: input ?? {}, method: 'POST' }),
    reversePayment: (paymentId: string, input?: unknown) => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/reverse`, { body: input ?? {}, method: 'POST' }),
    unallocatePayment: (paymentId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/payment/payments/${paymentId}/engines/unallocate`, { body: input, method: 'POST' }),
    updatePayment: async (paymentId: string, input: PaymentFormInput): Promise<ApiResponse<Payment>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/payment/payments/${paymentId}`, { body: paymentPayload(input), method: 'PUT' });
        return { ...response, data: normalizePayment(response.data ?? record(response)) };
    },
    updatePaymentMethod: async (methodId: string, input: PaymentMethodFormInput): Promise<ApiResponse<PaymentMethod>> => {
        const response = await httpClient<ApiResponse<BackendRecord>>(`/api/payment/payment-methods/${methodId}`, {
            body: contextPayload({
                account_id: numberOrUndefined(input.accountId ?? null),
                code: input.code,
                is_active: input.isActive,
                name: input.name,
                type: input.type,
            }),
            method: 'PUT',
        });
        return { ...response, data: normalizeMethod(response.data ?? record(response)) };
    },
    voidPayment: (paymentId: string) => httpClient<void>(`/api/payment/payments/${paymentId}`, { method: 'DELETE' }),
};
