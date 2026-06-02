import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { mockCollectionResponse, mockPreviewResponse, mockResponse } from '../../../services/mock/mockResponse';
import {
    availabilityPreview,
    billingPreview,
    breakdowns,
    getAgreementById,
    getProviderPayableById,
    getRunningChartById,
    providerPayables,
    rentalAgreements,
    rentalDashboardMetrics,
    rentalInvoices,
    rentalPayments,
    replacements,
    runningCharts,
} from '../mock/vehicleRentalMock';
import type {
    VehicleRentalAgreement,
    VehicleRentalAvailabilityPreview,
    VehicleRentalBillingPreview,
    VehicleRentalDashboardMetric,
    VehicleRentalProviderPayable,
    VehicleRentalRunningChart,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

type BackendRecord = Record<string, unknown>;

const VEHICLE_RENTAL_API_MODE = import.meta.env.VITE_VEHICLE_RENTAL_API_MODE ?? 'auto';

function shouldUseMockOnly() {
    return VEHICLE_RENTAL_API_MODE === 'mock';
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

function record(value: unknown): BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function numberOrUndefined(value: string | number | null | undefined): number | undefined {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function asBoolean(value: unknown, fallback = false): boolean {
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

function contextQuery(query: Record<string, string | number | boolean | undefined> = {}) {
    return {
        organization_unit_id: numberOrUndefined(getStoredOrganizationUnitId()),
        per_page: 25,
        tenant_id: numberOrUndefined(getStoredTenantId()),
        ...query,
    };
}

function contextPayload(input: BackendRecord = {}): BackendRecord {
    return {
        ...input,
        organization_unit_id: input.organization_unit_id ?? numberOrUndefined(getStoredOrganizationUnitId()),
        tenant_id: input.tenant_id ?? numberOrUndefined(getStoredTenantId()),
    };
}

function normalizeAgreement(raw: BackendRecord): VehicleRentalAgreement {
    return {
        ...getAgreementById(asString(raw.id)),
        agreementNumber: asString(raw.agreement_number, `RA-${asString(raw.id)}`),
        customer: asString(raw.customer_name ?? raw.customer_id, 'Backend customer'),
        driver: asString(raw.driver_name ?? raw.driver_id, 'Backend driver'),
        endAt: asString(raw.rental_end_at ?? raw.end_at),
        id: asString(raw.id),
        mode: asString(raw.rental_mode, 'without_driver') as VehicleRentalAgreement['mode'],
        provider: asString(raw.provider_name ?? raw.provider_id, 'Backend provider'),
        rentalUnit: asString(raw.rental_unit, 'day') as VehicleRentalAgreement['rentalUnit'],
        startAt: asString(raw.rental_start_at ?? raw.start_at),
        status: asString(raw.status, 'draft') as VehicleRentalAgreement['status'],
        updatedAt: asString(raw.updated_at),
        vehicle: asString(raw.vehicle_number ?? raw.rental_vehicle_id, 'Backend vehicle'),
        vehicleSource: asString(raw.vehicle_source, 'own_fleet') as VehicleRentalAgreement['vehicleSource'],
        workflowStatus: asString(raw.workflow_status, 'Backend workflow state'),
    };
}

function normalizeRunningChart(raw: BackendRecord): VehicleRentalRunningChart {
    return {
        ...getRunningChartById(asString(raw.id)),
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id),
        chartNumber: asString(raw.chart_number, `RC-${asString(raw.id)}`),
        customer: asString(raw.customer_name, 'Backend customer'),
        driver: asString(raw.driver_name ?? raw.driver_id, 'Backend driver'),
        endAt: asString(raw.end_at),
        id: asString(raw.id),
        startAt: asString(raw.start_at),
        status: asString(raw.status, 'draft'),
        vehicle: asString(raw.vehicle_number ?? raw.rental_vehicle_id, 'Backend vehicle'),
    };
}

function normalizeProviderPayable(raw: BackendRecord): VehicleRentalProviderPayable {
    return {
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id),
        financeStatus: asString(raw.finance_status, 'draft'),
        id: asString(raw.id),
        payableNumber: asString(raw.payable_number, `VRP-${asString(raw.id)}`),
        payablePreview: asString(raw.grand_total, 'Backend calculated'),
        paymentStatus: asString(raw.payment_status, 'unpaid'),
        provider: asString(raw.provider_name ?? raw.provider_id, 'Backend provider'),
        sourceReference: asString(raw.source_reference ?? raw.source_entity_type, 'Backend source'),
        status: asString(raw.status, 'pending'),
    };
}

function normalizeSettings(raw: BackendRecord): VehicleRentalSettings {
    return {
        _raw: raw,
        allowExternalProviderVehicles: asBoolean(raw.allow_external_provider_vehicle),
        allowReplacementVehicle: asBoolean(raw.allow_replacement_vehicle),
        allowWithDriverRental: asBoolean(raw.allow_with_driver, true),
        agreementSequence: asString(raw.rental_agreement_document_definition_label ?? raw.rental_agreement_document_definition_id, 'Not configured'),
        defaultProviderPayableAccount: asString(raw.default_provider_payable_account_label ?? raw.default_provider_payable_account_id, 'Not configured'),
        defaultRatePlan: asString(raw.default_price_list_label ?? raw.default_price_list_id, 'Not configured'),
        defaultTaxGroup: asString(raw.default_tax_group_label ?? raw.default_tax_group_id, 'Not configured'),
        invoiceDocumentDefinition: asString(raw.rental_invoice_document_definition_label ?? raw.rental_invoice_document_definition_id, 'Not configured'),
        invoiceSequence: asString(raw.rental_invoice_sequence_label ?? raw.rental_invoice_sequence_code ?? raw.rental_invoice_document_definition_id, 'Not configured'),
        runningChartSequence: asString(raw.running_chart_document_definition_label ?? raw.running_chart_document_definition_id, 'Not configured'),
    };
}

export const vehicleRentalApi = {
    dashboard: {
        summary: (): Promise<ApiCollectionResponse<VehicleRentalDashboardMetric>> => mockCollectionResponse(rentalDashboardMetrics),
    },
    availability: {
        list: () => mockCollectionResponse([
            { availability: 'Backend availability status', id: 'av-001', vehicle: 'WP CAD-4521 | Toyota HiAce' },
            { availability: 'Backend conflict found', id: 'av-002', vehicle: 'WP KA-7781 | Nissan Caravan' },
            { availability: 'External provider available', id: 'av-003', vehicle: 'CP CAB-9410 | Mitsubishi L200' },
        ]),
        preview: (input: unknown): Promise<ApiPreviewResponse<unknown, VehicleRentalAvailabilityPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, VehicleRentalAvailabilityPreview['calculated']>>('/api/vehicle-rental/vehicle-availability', { method: 'GET', query: input as Record<string, string | number | boolean> }),
            () => mockPreviewResponse(input, availabilityPreview.calculated, availabilityPreview.breakdown, availabilityPreview.warnings),
        ),
    },
    agreements: {
        list: (): Promise<ApiCollectionResponse<VehicleRentalAgreement>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/agreements');
                return { ...response, data: response.data.map(normalizeAgreement) };
            },
            () => mockCollectionResponse(rentalAgreements),
        ),
        get: (id: string): Promise<ApiResponse<VehicleRentalAgreement>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}`);
                return { ...response, data: normalizeAgreement(response.data) };
            },
            () => mockResponse(getAgreementById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/agreements', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        syncLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/lines/sync`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
        syncRates: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/rates/sync`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
        syncRateRules: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/rate-rules/sync`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
        syncExtraCharges: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/extra-charges/sync`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
        previewBilling: (id: string, input: unknown): Promise<ApiPreviewResponse<unknown, VehicleRentalBillingPreview['calculated']>> => withMockFallback(
            () => httpClient<ApiPreviewResponse<unknown, VehicleRentalBillingPreview['calculated']>>(`/api/vehicle-rental/agreements/${id}/billing-preview`, { body: input, method: 'POST' }),
            () => mockPreviewResponse(input, getAgreementById(id).billingPreview.calculated, getAgreementById(id).billingPreview.breakdown, getAgreementById(id).billingPreview.warnings),
        ),
        transition: (id: string, action: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${id}/transition`, { body: { action }, method: 'POST' }), () => mockResponse({ action, id })),
        history: (id: string) => withMockFallback(() => httpClient<ApiCollectionResponse<unknown>>(`/api/vehicle-rental/status-history/agreement/${id}`), () => mockCollectionResponse(getAgreementById(id).activity)),
    },
    runningCharts: {
        list: (): Promise<ApiCollectionResponse<VehicleRentalRunningChart>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/running-charts');
                return { ...response, data: response.data.map(normalizeRunningChart) };
            },
            () => mockCollectionResponse(runningCharts),
        ),
        get: (id: string): Promise<ApiResponse<VehicleRentalRunningChart>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/running-charts/${id}`);
                return { ...response, data: normalizeRunningChart(response.data) };
            },
            () => mockResponse(getRunningChartById(id)),
        ),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/running-charts', { body: input, method: 'POST' }), () => mockResponse(input)),
        update: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/running-charts/${id}`, { body: input, method: 'PUT' }), () => mockResponse({ id, input })),
        syncLines: (id: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/running-charts/${id}/lines/sync`, { body: input, method: 'POST' }), () => mockResponse({ id, input })),
        transition: (id: string, action: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/running-charts/${id}/transition`, { body: { action }, method: 'POST' }), () => mockResponse({ action, id })),
        previewBilling: (id: string, input: unknown) => mockPreviewResponse(input, getRunningChartById(id).billingPreview.calculated, getRunningChartById(id).billingPreview.breakdown, getRunningChartById(id).billingPreview.warnings),
    },
    invoices: {
        list: () => mockCollectionResponse(rentalInvoices),
        get: (id: string) => mockResponse(rentalInvoices.find((invoice) => invoice.id === id || invoice.invoiceNumber === id) ?? rentalInvoices[0]),
        generate: (agreementId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/invoice`, { method: 'POST' }), () => mockResponse({ action: 'generate-rental-invoice', agreementId })),
        preview: (input: unknown) => mockPreviewResponse(input, billingPreview.calculated, billingPreview.breakdown, billingPreview.warnings),
    },
    payments: {
        list: () => mockCollectionResponse(rentalPayments),
        create: (input: unknown) => mockResponse(input),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { allocatedAmount: 'Backend calculated', remainingBalance: 'Backend calculated' }),
        allocate: (agreementId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/payments/allocate`, { body: input, method: 'POST' }), () => mockResponse({ agreementId, input })),
    },
    providerPayables: {
        list: (): Promise<ApiCollectionResponse<VehicleRentalProviderPayable>> => withMockFallback(
            async () => {
                const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/provider-payables');
                return { ...response, data: response.data.map(normalizeProviderPayable) };
            },
            () => mockCollectionResponse(providerPayables),
        ),
        get: (id: string) => mockResponse(getProviderPayableById(id)),
        preview: (input: unknown) => mockPreviewResponse(input, { providerPayable: 'Backend calculated', paymentEligibility: 'Backend workflow checked' }),
        create: (agreementId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/provider-payables`, { body: input, method: 'POST' }), () => mockResponse({ agreementId, input })),
        allocatePayment: (providerPayableId: string, input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/provider-payables/${providerPayableId}/payments/allocate`, { body: input, method: 'POST' }), () => mockResponse({ input, providerPayableId })),
    },
    replacements: {
        list: () => mockCollectionResponse(replacements),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/replacements', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    breakdowns: {
        list: () => mockCollectionResponse(breakdowns),
        create: (input: unknown) => withMockFallback(() => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/breakdowns', { body: input, method: 'POST' }), () => mockResponse(input)),
    },
    finance: {
        previewPosting: (agreementId: string) => mockResponse(getAgreementById(agreementId).financePreview),
        post: (entityType: string, entityId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/post`, { method: 'POST' }), () => mockResponse({ action: 'post-finance', entityId, entityType })),
        reverse: (entityType: string, entityId: string) => withMockFallback(() => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/reverse`, { method: 'POST' }), () => mockResponse({ action: 'reverse-finance', entityId, entityType })),
    },
    settings: {
        get: async () => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>('/api/vehicle-rental/settings', { query: contextQuery() });
            return { ...response, data: normalizeSettings(record(response.data)) };
        },
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/settings', { body: contextPayload(record(input)), method: 'POST' }),
        initialize: () => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/settings/initialize', { body: contextPayload(), method: 'POST' }),
    },
};
