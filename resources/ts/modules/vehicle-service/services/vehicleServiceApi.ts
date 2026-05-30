import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { ApiError } from '../../../services/api/apiErrors';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    getInvoiceById,
    getJobCardById,
    jobCards,
    serviceInvoices,
    servicePayments,
    serviceTypes,
    vehicleServiceDashboardMetrics,
    vehicleServiceSettings,
} from '../mock/vehicleServiceMock';
import type {
    VehicleServiceCalculationPreview,
    VehicleServiceDashboardMetric,
    VehicleServiceFinancePostingPreview,
    VehicleServiceInvoice,
    VehicleServiceJobCard,
    VehicleServicePayment,
    VehicleServiceStockAvailabilityPreview,
    VehicleServiceType,
} from '../types/vehicleService.types';

type BackendRecord = Record<string, unknown>;

const VEHICLE_SERVICE_API_MODE = import.meta.env.VITE_VEHICLE_SERVICE_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return VEHICLE_SERVICE_API_MODE === 'mock';
}

async function withMockFallback<T>(realCall: () => Promise<T>, mockCall: () => Promise<T>, fallbackStatuses = [401, 403, 404, 419, 422]): Promise<T> {
    if (shouldUseMockOnly()) {
        return mockCall();
    }

    try {
        return await realCall();
    } catch (error) {
        if (VEHICLE_SERVICE_API_MODE === 'real') {
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

function normalizeJobCard(raw: BackendRecord): VehicleServiceJobCard {
    const lines = Array.isArray(raw.lines) ? raw.lines as BackendRecord[] : [];

    return {
        ...getJobCardById(asString(raw.id)),
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        expectedCompletion: asString(raw.expected_completion_at ?? raw.expected_completion_date),
        id: asString(raw.id),
        jobCardNumber: asString(raw.job_card_number, `JC-${asString(raw.id)}`),
        lines: lines.length
            ? lines.map((line, index) => ({
                backendCalculatedAmount: asString(line.line_total, 'Backend calculated'),
                description: asString(line.description),
                discountPreview: asString(line.discount_amount, 'Backend calculated'),
                id: asString(line.id, `line-${index}`),
                invoiceable: line.invoiceable !== false,
                item: asString(line.item_name ?? line.item_id, 'Backend item'),
                lineType: asString(line.line_type, 'service') as VehicleServiceJobCard['lines'][number]['lineType'],
                quantity: asString(line.quantity, 'Backend quantity'),
                stockBehavior: asString(line.stock_behavior, 'Backend stock behavior'),
                taxPreview: asString(line.tax_amount, 'Backend calculated'),
                uom: asString(line.uom_name ?? line.uom_id, 'Backend UOM'),
            }))
            : getJobCardById(asString(raw.id)).lines,
        odometer: asString(raw.odometer_reading, 'Backend value'),
        openedAt: asString(raw.opened_at ?? raw.created_at),
        partyContext: {
            billingCustomer: {
                id: raw.billing_customer_id === null || raw.billing_customer_id === undefined ? undefined : asString(raw.billing_customer_id),
                name: asString(raw.billing_customer_name ?? raw.billing_customer_id, 'Backend billing party'),
                type: asString(raw.billing_customer_type, 'customer') as VehicleServiceJobCard['partyContext']['billingCustomer']['type'],
            },
            mismatchNotice: asString((raw.party_context as BackendRecord | undefined)?.mismatch_notice ?? ''),
            payer: {
                id: raw.payer_id === null || raw.payer_id === undefined ? undefined : asString(raw.payer_id),
                name: asString(raw.payer_name ?? raw.payer_id, 'Backend payer'),
                type: asString(raw.payer_type, 'customer') as VehicleServiceJobCard['partyContext']['payer']['type'],
            },
            serviceCustomer: {
                id: raw.service_customer_id === null || raw.service_customer_id === undefined ? undefined : asString(raw.service_customer_id),
                name: asString(raw.service_customer_name ?? raw.service_customer_id, 'Backend service customer'),
                type: asString(raw.service_customer_type, 'customer') as VehicleServiceJobCard['partyContext']['serviceCustomer']['type'],
            },
            vehicleOwner: {
                ownershipId: raw.vehicle_ownership_id === null || raw.vehicle_ownership_id === undefined ? undefined : asString(raw.vehicle_ownership_id),
                ownershipRole: asString((raw.party_context as BackendRecord | undefined)?.ownership_role, 'Backend owner role'),
                ownershipType: asString((raw.party_context as BackendRecord | undefined)?.ownership_type, 'Backend ownership type'),
                owner: {
                    id: raw.vehicle_owner_id === null || raw.vehicle_owner_id === undefined ? undefined : asString(raw.vehicle_owner_id),
                    name: asString(raw.vehicle_owner_name, 'Backend vehicle owner'),
                    type: asString(raw.vehicle_owner_type, 'company') as VehicleServiceJobCard['partyContext']['vehicleOwner']['owner']['type'],
                },
            },
        },
        serviceAdvisor: asString(raw.service_advisor_name ?? raw.service_advisor_id, 'Backend advisor'),
        serviceType: asString(raw.service_type_name ?? raw.service_type_id, 'Backend service type'),
        status: asString(raw.status, 'open') as VehicleServiceJobCard['status'],
        updatedAt: asString(raw.updated_at),
        vehicle: asString(raw.vehicle_number ?? raw.vehicle_id, 'Backend vehicle'),
        workflowStatus: asString(raw.workflow_status, 'Backend workflow state'),
    };
}

function normalizeServiceType(raw: BackendRecord): VehicleServiceType {
    return {
        code: asString(raw.code ?? raw.service_type_code, `TYPE-${asString(raw.id)}`),
        description: asString(raw.description),
        id: asString(raw.id),
        name: asString(raw.name),
        status: asString(raw.status, raw.is_active === false ? 'inactive' : 'active') as VehicleServiceType['status'],
        updatedAt: asString(raw.updated_at),
    };
}

export const vehicleServiceApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<VehicleServiceDashboardMetric>> => mockCollectionResponse(vehicleServiceDashboardMetrics),
    },
    serviceTypes: {
        list: (): Promise<ApiCollectionResponse<VehicleServiceType>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-types');
                return { ...response, data: response.data.map(normalizeServiceType) };
            },
            () => mockCollectionResponse(serviceTypes),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-types', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-types/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        activate: (id: string) => mockResponse({ action: 'activate-service-type', id }),
        deactivate: (id: string) => mockResponse({ action: 'deactivate-service-type', id }),
    },
    jobCards: {
        list: (): Promise<ApiCollectionResponse<VehicleServiceJobCard>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-job-cards');
                return { ...response, data: response.data.map(normalizeJobCard) };
            },
            () => mockCollectionResponse(jobCards),
        ),
        get: (id: string): Promise<ApiResponse<VehicleServiceJobCard>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-service/vehicle-service-job-cards/${id}`);
                return { ...response, data: normalizeJobCard(response.data) };
            },
            () => mockResponse(getJobCardById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/job-cards/aggregate', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${id}/aggregate`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        cancel: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${id}/transition`, { body: { action: 'cancel' }, method: 'POST' }), () => mockResponse({ action: 'cancel', id })),
        close: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${id}/transition`, { body: { action: 'close' }, method: 'POST' }), () => mockResponse({ action: 'close', id })),
        reopen: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${id}/transition`, { body: { action: 'reopen' }, method: 'POST' }), () => mockResponse({ action: 'reopen', id })),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/vehicle-service/status-history/job_card/${id}`), () => mockCollectionResponse(getJobCardById(id).audit)),
        ownerSummary: (vehicleId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicles/${vehicleId}/owner-summary`), () => mockResponse(getJobCardById('job-001').partyContext.vehicleOwner)),
        validatePartyContext: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/party-context/validate', { body: input, method: 'POST' }), () => mockResponse({ input, valid: true })),
    },
    lines: {
        list: (jobCardId: string) => mockCollectionResponse(getJobCardById(jobCardId).lines),
        syncServiceItems: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/lines/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        syncSpareParts: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/lines/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        syncLabour: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/labor-items/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        syncNonInventory: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/non-inventory-items/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        syncCustomerSupplied: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/customer-supplied-items/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        syncExternalServices: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/external-services/sync`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
        previewComboExpansion: (input: unknown) => mockPreviewResponse(input, { expansion: 'Backend combo/package expansion placeholder' }),
    },
    labour: {
        listAssignments: (jobCardId: string) => mockCollectionResponse(getJobCardById(jobCardId).labourAssignments),
        assign: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-labor-assignments', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-labor-assignments/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        remove: (id: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-labor-assignments/${id}`, { method: 'DELETE' }), () => mockResponse({ id })),
        previewIncentive: (input: unknown) => mockPreviewResponse(input, { incentive: 'Backend incentive/share preview' }),
    },
    diagnostics: {
        list: (jobCardId: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>('/api/vehicle-service/vehicle-service-diagnostics', { query: { job_card_id: jobCardId } }), () => mockCollectionResponse(getJobCardById(jobCardId).diagnostics)),
        upsert: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-diagnostics', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    inspections: {
        list: (jobCardId: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>('/api/vehicle-service/vehicle-service-inspections', { query: { job_card_id: jobCardId } }), () => mockCollectionResponse(getJobCardById(jobCardId).inspections)),
        upsert: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-inspections', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    stock: {
        previewAvailability: (input: unknown): Promise<ApiPreviewResponse<unknown, VehicleServiceStockAvailabilityPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, VehicleServiceStockAvailabilityPreview['calculated']>>('/api/vehicle-service/stock-availability', { query: input as Record<string, string | number | boolean>, method: 'GET' }),
            () => mockPreviewResponse(input, getJobCardById('job-001').stockPreview.calculated, getJobCardById('job-001').stockPreview.breakdown, getJobCardById('job-001').stockPreview.warnings),
        ),
        postConsumption: (jobCardId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/inventory/post`, { method: 'POST' }), () => mockResponse({ action: 'post-spare-consumption', jobCardId })),
        reverseConsumption: (jobCardId: string) => mockResponse({ action: 'reverse-spare-consumption', jobCardId }),
    },
    invoices: {
        list: (): Promise<ApiCollectionResponse<VehicleServiceInvoice>> => mockCollectionResponse(serviceInvoices),
        get: (id: string): Promise<ApiResponse<VehicleServiceInvoice>> => mockResponse(getInvoiceById(id)),
        preview: (jobCardId: string): Promise<ApiPreviewResponse<unknown, VehicleServiceCalculationPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, VehicleServiceCalculationPreview['calculated']>>(`/api/vehicle-service/job-cards/${jobCardId}/invoice-preview`, { method: 'POST' }),
            () => mockPreviewResponse({ jobCardId }, getJobCardById(jobCardId).invoicePreview.calculated, getJobCardById(jobCardId).invoicePreview.breakdown, getJobCardById(jobCardId).invoicePreview.warnings),
        ),
        generate: (jobCardId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/invoice`, { method: 'POST' }), () => mockResponse({ action: 'generate-service-invoice', jobCardId })),
        cancel: (invoiceId: string) => mockResponse({ action: 'cancel-service-invoice', invoiceId }),
        reverse: (invoiceId: string) => mockResponse({ action: 'reverse-service-invoice', invoiceId }),
    },
    payments: {
        list: (): Promise<ApiCollectionResponse<VehicleServicePayment>> => mockCollectionResponse(servicePayments),
        create: (input: unknown) => mockResponse(input),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { allocatedAmount: 'Backend calculated', unallocatedAmount: 'Backend calculated', targetBalance: 'Backend calculated' }),
        allocate: (jobCardId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/payments/allocate`, { body: input, method: 'POST' }), () => mockResponse({ input, jobCardId })),
    },
    finance: {
        previewPosting: (jobCardId: string): Promise<ApiResponse<VehicleServiceFinancePostingPreview>> => mockResponse(getJobCardById(jobCardId).financePreview),
        post: (jobCardId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/finance/post`, { method: 'POST' }), () => mockResponse({ action: 'post-finance', jobCardId })),
        reverse: (jobCardId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/finance/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-finance', jobCardId })),
    },
    settings: {
        get: () => withMockFallback(() => httpClient<ApiResponse<typeof vehicleServiceSettings>>('/api/vehicle-service/settings'), () => mockResponse(vehicleServiceSettings)),
        update: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/settings', { body: input, method: 'POST' }), () => mockResponse(input)),
        initialize: () => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-service/settings/initialize', { method: 'POST' }), () => mockResponse(vehicleServiceSettings)),
    },
    history: {
        list: () => mockCollectionResponse(jobCards.flatMap((jobCard) => jobCard.audit)),
    },
};
