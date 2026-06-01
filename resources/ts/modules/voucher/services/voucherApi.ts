import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    getVoucherById,
    getVoucherTypeById,
    paymentImpactPreview,
    postingPreview,
    voucherDashboardMetrics,
    vouchers,
    voucherSettings,
    voucherTypes,
} from '../mock/voucherMock';
import type {
    Voucher,
    VoucherAllocation,
    VoucherAuditEntry,
    VoucherDashboardMetric,
    VoucherPaymentImpactPreview,
    VoucherPostingPreview,
    VoucherSettings,
    VoucherType,
} from '../types/voucher.types';

type BackendRecord = Record<string, unknown>;
type VoucherListQuery = {
    page?: number;
    partyType?: string;
    perPage?: number;
    search?: string;
    status?: string;
    type?: string;
};

const VOUCHER_API_MODE = import.meta.env.VITE_VOUCHER_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return VOUCHER_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    return realCall();
}

function asString(value: unknown, fallback = '') {
    return value === null || value === undefined ? fallback : String(value);
}

function asBoolean(value: unknown, fallback = false) {
    if (typeof value === 'boolean') {
        return value;
    }

    if (value === 1 || value === '1' || value === 'true') {
        return true;
    }

    if (value === 0 || value === '0' || value === 'false') {
        return false;
    }

    return fallback;
}

function normalizeVoucherType(raw: BackendRecord): VoucherType {
    const fallback = getVoucherTypeById(asString(raw.id ?? raw.code));

    return {
        ...fallback,
        activeFlag: asBoolean(raw.is_active ?? raw.activeFlag, fallback.activeFlag),
        category: asString(raw.category ?? raw.voucher_category, fallback.category),
        code: asString(raw.code, fallback.code),
        defaultDocumentDefinition: asString(raw.document_definition_id ?? raw.default_document_definition, fallback.defaultDocumentDefinition),
        defaultSequence: asString(raw.default_sequence ?? raw.sequence_code, fallback.defaultSequence),
        direction: asString(raw.direction, fallback.direction) as VoucherType['direction'],
        id: asString(raw.id, fallback.id),
        name: asString(raw.name, fallback.name),
        requiresApproval: asBoolean(raw.requires_approval, fallback.requiresApproval),
        requiresBalancedLines: asBoolean(raw.requires_balanced_lines ?? raw.requires_balance_validation, fallback.requiresBalancedLines),
        requiresPaymentMethod: asBoolean(raw.requires_payment_method, fallback.requiresPaymentMethod),
        status: asBoolean(raw.is_active, fallback.activeFlag) ? 'active' : 'inactive',
        updatedAt: asString(raw.updated_at, fallback.updatedAt),
    };
}

function normalizeVoucher(raw: BackendRecord): Voucher {
    const fallback = getVoucherById(asString(raw.id ?? raw.voucher_number));

    return {
        ...fallback,
        approvalStatus: asString(raw.approval_status, fallback.approvalStatus) as Voucher['approvalStatus'],
        currency: asString(raw.currency_code ?? raw.currency_id, fallback.currency),
        description: asString(raw.description, fallback.description),
        id: asString(raw.id, fallback.id),
        party: asString(raw.party_name ?? raw.party_id, fallback.party),
        partyType: asString(raw.party_type, fallback.partyType),
        paymentMethod: asString(raw.payment_method_name ?? raw.payment_method_id, fallback.paymentMethod),
        paymentStatus: asString(raw.payment_status, fallback.paymentStatus) as Voucher['paymentStatus'],
        postingStatus: asString(raw.posting_status, fallback.postingStatus) as Voucher['postingStatus'],
        referenceNumber: asString(raw.reference_number, fallback.referenceNumber),
        sourceReference: {
            sourceId: asString(raw.source_id ?? raw.reference_id, fallback.sourceReference.sourceId),
            sourceModule: asString(raw.source_module ?? raw.reference_module, fallback.sourceReference.sourceModule),
            sourceNumber: asString(raw.source_number ?? raw.reference_number, fallback.sourceReference.sourceNumber),
            sourceType: asString(raw.source_type ?? raw.reference_type, fallback.sourceReference.sourceType),
        },
        status: asString(raw.status, fallback.status) as Voucher['status'],
        totalAmount: asString(raw.total_amount, fallback.totalAmount),
        totalCredit: asString(raw.total_credit, fallback.totalCredit),
        totalDebit: asString(raw.total_debit, fallback.totalDebit),
        updatedAt: asString(raw.updated_at, fallback.updatedAt),
        voucherDate: asString(raw.voucher_date, fallback.voucherDate),
        voucherNumber: asString(raw.voucher_number, fallback.voucherNumber),
        voucherType: asString(raw.voucher_type_name ?? raw.voucher_type_id, fallback.voucherType),
    };
}

function normalizeAllocation(raw: BackendRecord): VoucherAllocation {
    return {
        allocatedAmount: asString(raw.allocated_amount, 'Backend calculated'),
        id: asString(raw.id),
        status: asString(raw.status, 'allocated'),
        targetModule: asString(raw.target_module, 'generic'),
        targetReference: asString(raw.target_reference ?? raw.target_id, 'Backend target'),
        targetType: asString(raw.target_type, 'source_document'),
    };
}

function normalizeHistory(raw: BackendRecord): VoucherAuditEntry {
    return {
        actor: asString(raw.changed_by_name ?? raw.actor, 'Backend user'),
        id: asString(raw.id),
        note: asString(raw.reason ?? raw.note ?? raw.to_status, 'Voucher history event'),
        timestamp: asString(raw.changed_at ?? raw.created_at, 'Backend timestamp'),
        type: asString(raw.to_status ?? raw.type, 'status'),
    };
}

function normalizeMetric(raw: BackendRecord): VoucherDashboardMetric {
    return {
        label: asString(raw.label ?? raw.name ?? raw.metric, 'Voucher metric'),
        tone: asString(raw.tone ?? raw.status ?? 'neutral'),
        value: asString(raw.value ?? raw.count ?? raw.total, '0'),
    };
}

function normalizeSettings(raw: BackendRecord): VoucherSettings {
    return {
        allowDirectPosting: asBoolean(raw.allow_direct_posting ?? raw.allowDirectPosting),
        allowPartialAllocation: asBoolean(raw.allow_partial_allocation ?? raw.allowPartialAllocation, true),
        defaultDocumentDefinition: asString(raw.default_document_definition ?? raw.defaultDocumentDefinition),
        defaultPaymentMethod: asString(raw.default_payment_method ?? raw.defaultPaymentMethod),
        defaultSequence: asString(raw.default_sequence ?? raw.defaultSequence),
        requireApproval: asBoolean(raw.require_approval ?? raw.requireApproval, true),
    };
}

function normalizePostingPreviewResponse(input: unknown, raw: unknown): ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']> {
    const maybePreview = raw as Partial<ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']>>;

    if (maybePreview.calculated && Array.isArray(maybePreview.breakdown)) {
        return {
            breakdown: maybePreview.breakdown,
            calculated: maybePreview.calculated,
            errors: maybePreview.errors ?? [],
            input: maybePreview.input ?? input,
            warnings: maybePreview.warnings ?? [],
        };
    }

    const response = raw as Partial<ApiResponse<unknown>>;

    return {
        breakdown: [{ effect: 'Backend returned voucher posting source data', source: response.data ?? raw }],
        calculated: {
            balanced: 'Backend validated',
            creditTotal: 'Backend returned',
            debitTotal: 'Backend returned',
            eligibility: 'Backend checked voucher posting eligibility',
            journalImpact: 'Backend posting source returned',
        },
        errors: [],
        input,
        warnings: [],
    };
}

function normalizePaymentImpactPreviewResponse(input: unknown, raw: unknown): ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']> {
    const maybePreview = raw as Partial<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']>>;

    if (maybePreview.calculated) {
        return {
            breakdown: maybePreview.breakdown ?? [],
            calculated: maybePreview.calculated,
            errors: maybePreview.errors ?? [],
            input: maybePreview.input ?? input,
            warnings: maybePreview.warnings ?? [],
        };
    }

    const response = raw as Partial<ApiResponse<BackendRecord>>;
    const data = (response.data && typeof response.data === 'object' ? response.data : response) as BackendRecord;

    return {
        breakdown: Array.isArray(data.breakdown) ? data.breakdown as VoucherPaymentImpactPreview['breakdown'] : [],
        calculated: {
            allocationBalance: asString(data.allocation_balance ?? data.allocationBalance, 'Backend calculated'),
            paymentImpact: asString(data.payment_impact ?? data.paymentImpact, 'Backend calculated'),
            paymentMethodValidation: asString(data.payment_method_validation ?? data.paymentMethodValidation, 'Backend validated'),
            settlementStatus: asString(data.settlement_status ?? data.settlementStatus, 'Backend calculated'),
        },
        errors: [],
        input,
        warnings: [],
    };
}

export const voucherApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<VoucherDashboardMetric>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/voucher/dashboard/summary');
                return { ...response, data: response.data.map(normalizeMetric) };
            },
            () => mockCollectionResponse(voucherDashboardMetrics),
        ),
    },
    types: {
        list: (query: Pick<VoucherListQuery, 'page' | 'perPage' | 'search' | 'status'> = {}): Promise<ApiCollectionResponse<VoucherType>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/voucher/types', {
                    query: {
                        page: query.page,
                        per_page: query.perPage ?? 25,
                        search: query.search,
                        status: query.status,
                    },
                });
                return { ...response, data: response.data.map(normalizeVoucherType) };
            },
            () => mockCollectionResponse(voucherTypes),
        ),
        get: (id: string): Promise<ApiResponse<VoucherType>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/voucher/types/${id}`);
                return { ...response, data: normalizeVoucherType(response.data) };
            },
            () => mockResponse(getVoucherTypeById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/types', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/types/${id}`, { body: input, method: 'PATCH' }), () => mockResponse({ id, input })),
        activate: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/types/${id}/activate`, { method: 'PATCH' }), () => mockResponse({ action: 'activate', id })),
        deactivate: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/types/${id}/deactivate`, { method: 'PATCH' }), () => mockResponse({ action: 'deactivate', id })),
    },
    vouchers: {
        list: (query: VoucherListQuery = {}): Promise<ApiCollectionResponse<Voucher>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/voucher/vouchers', {
                    query: {
                        page: query.page,
                        party_type: query.partyType,
                        per_page: query.perPage ?? 25,
                        search: query.search,
                        status: query.status,
                        type: query.type,
                    },
                });
                return { ...response, data: response.data.map(normalizeVoucher) };
            },
            () => mockCollectionResponse(vouchers),
        ),
        get: (id: string): Promise<ApiResponse<Voucher>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/voucher/vouchers/${id}`);
                return { ...response, data: normalizeVoucher(response.data) };
            },
            () => mockResponse(getVoucherById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/vouchers', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}`, { body: input, method: 'PATCH' }), () => mockResponse({ id, input })),
        deleteDraft: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}`, { method: 'DELETE' }), () => mockResponse({ action: 'delete-draft', id })),
        syncLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/lines`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        submit: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/submit`, { method: 'POST' }), () => mockResponse({ action: 'submit', id })),
        approve: (id: string, input?: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/approve`, { body: input, method: 'POST' }), () => mockResponse({ action: 'approve', id, input })),
        reject: (id: string, input?: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/reject`, { body: input, method: 'POST' }), () => mockResponse({ action: 'reject', id, input })),
        post: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/post`, { method: 'POST' }), () => mockResponse({ action: 'post', id })),
        cancel: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/cancel`, { method: 'POST' }), () => mockResponse({ action: 'cancel', id })),
        reverse: (id: string, input?: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${id}/reverse`, { body: input, method: 'POST' }), () => mockResponse({ action: 'reverse', id, input })),
        history: (id: string): Promise<ApiCollectionResponse<VoucherAuditEntry>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/voucher/vouchers/${id}/history`);
                return { ...response, data: response.data.map(normalizeHistory) };
            },
            () => mockCollectionResponse(getVoucherById(id).activity),
        ),
    },
    allocations: {
        list: (voucherId: string, query: Pick<VoucherListQuery, 'page' | 'perPage' | 'search' | 'status'> = {}): Promise<ApiCollectionResponse<VoucherAllocation>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/voucher/vouchers/${voucherId}/allocations`, {
                    query: {
                        page: query.page,
                        per_page: query.perPage ?? 25,
                        search: query.search,
                        status: query.status,
                    },
                });
                return { ...response, data: response.data.map(normalizeAllocation) };
            },
            () => mockCollectionResponse(getVoucherById(voucherId).allocations),
        ),
        create: (voucherId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${voucherId}/allocations`, { body: input, method: 'POST' }), () => mockResponse({ input, voucherId })),
        update: (allocationId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/allocations/${allocationId}`, { body: input, method: 'PATCH' }), () => mockResponse({ allocationId, input })),
        preview: (input: unknown): Promise<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']>> => withMockFallback(
            async () => normalizePaymentImpactPreviewResponse(
                input,
                await httpClient<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']> | ApiResponse<BackendRecord>>('/api/voucher/allocations/preview', { body: input, method: 'POST' }),
            ),
            () => mockPreviewResponse(input, paymentImpactPreview.calculated, paymentImpactPreview.breakdown, paymentImpactPreview.warnings),
        ),
    },
    utilities: {
        previewNumber: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/utilities/preview-number', { body: input, method: 'POST' }), () => mockResponse({ input, voucherNumber: 'Backend sequence preview' })),
        validateBalance: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/utilities/validate-balance', { body: input, method: 'POST' }), () => mockResponse({ input, validation: 'Backend balance validation' })),
        validatePaymentMethod: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/utilities/validate-payment-method', { body: input, method: 'POST' }), () => mockResponse({ input, validation: 'Backend payment method validation' })),
        previewPosting: (voucherId: string): Promise<ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']>> => withMockFallback(
            async () => normalizePostingPreviewResponse(
                { voucherId },
                await httpClient<ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']> | ApiResponse<unknown>>(`/api/voucher/utilities/${voucherId}/preview-posting`),
            ),
            () => mockPreviewResponse({ voucherId }, getVoucherById(voucherId).postingPreview.calculated, getVoucherById(voucherId).postingPreview.breakdown, getVoucherById(voucherId).postingPreview.warnings),
        ),
        previewPaymentImpact: (input: unknown): Promise<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']>> => withMockFallback(
            async () => normalizePaymentImpactPreviewResponse(
                input,
                await httpClient<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']> | ApiResponse<BackendRecord>>('/api/voucher/utilities/preview-payment-impact', { body: input, method: 'POST' }),
            ),
            () => mockPreviewResponse(input, paymentImpactPreview.calculated, paymentImpactPreview.breakdown, paymentImpactPreview.warnings),
        ),
    },
    documents: {
        preview: (voucherId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${voucherId}/document/preview`), () => mockResponse(getVoucherById(voucherId).document)),
        generate: (voucherId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/voucher/vouchers/${voucherId}/document/generate`, { method: 'POST' }), () => mockResponse({ action: 'generate-voucher-document', voucherId })),
    },
    settings: {
        get: (): Promise<ApiResponse<VoucherSettings>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>('/api/voucher/settings');
                return { ...response, data: normalizeSettings(response.data) };
            },
            () => mockResponse(voucherSettings),
        ),
        update: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/voucher/settings', { body: input, method: 'PATCH' }), () => mockResponse(input)),
    },
    previews: {
        posting: (input: unknown): Promise<ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']>> => withMockFallback(
            async () => normalizePostingPreviewResponse(
                input,
                await httpClient<ApiPreviewResponse<unknown, VoucherPostingPreview['calculated']> | ApiResponse<unknown>>('/api/voucher/previews/posting', { body: input, method: 'POST' }),
            ),
            () => mockPreviewResponse(input, postingPreview.calculated, postingPreview.breakdown, postingPreview.warnings),
        ),
        paymentImpact: (input: unknown): Promise<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']>> => withMockFallback(
            async () => normalizePaymentImpactPreviewResponse(
                input,
                await httpClient<ApiPreviewResponse<unknown, VoucherPaymentImpactPreview['calculated']> | ApiResponse<BackendRecord>>('/api/voucher/previews/payment-impact', { body: input, method: 'POST' }),
            ),
            () => mockPreviewResponse(input, paymentImpactPreview.calculated, paymentImpactPreview.breakdown, paymentImpactPreview.warnings),
        ),
    },
};
