import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { customerApi } from '../../customer/services/customerApi';
import { hrApi } from '../../hr/services/hrApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { itemApi } from '../../item/services/itemApi';
import { supplierApi } from '../../supplier/services/supplierApi';
import type {
    VehicleRentalAgreement,
    VehicleRentalAgreementFormInput,
    VehicleRentalAgreementLine,
    VehicleRentalAgreementLineInput,
    VehicleRentalAuditEntry,
    VehicleRentalAvailabilityPreview,
    VehicleRentalBillingPreview,
    VehicleRentalBreakdown,
    VehicleRentalDashboardMetric,
    VehicleRentalInvoice,
    VehicleRentalLookupOption,
    VehicleRentalPayment,
    VehicleRentalPaymentFormInput,
    VehicleRentalProviderPayable,
    VehicleRentalReplacement,
    VehicleRentalRunningChart,
    VehicleRentalRunningChartFormInput,
    VehicleRentalRunningChartLine,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

type BackendRecord = Record<string, unknown>;

function record(value: unknown): BackendRecord {
    return value !== null && typeof value === 'object' && !Array.isArray(value) ? value as BackendRecord : {};
}

function asString(value: unknown, fallback = ''): string {
    return value === null || value === undefined || value === '' ? fallback : String(value);
}

function optionalString(value: unknown): string | undefined {
    const text = asString(value).trim();
    return text === '' ? undefined : text;
}

function numberOrUndefined(value: string | number | null | undefined): number | undefined {
    const parsed = Number(value);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : undefined;
}

function decimal(value: unknown, fallback = '0.0000'): string {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed.toFixed(4) : fallback;
}

function contextQuery(extra: Record<string, string | number | boolean | null | undefined> = {}) {
    return {
        organization_unit_id: numberOrUndefined(getStoredOrganizationUnitId()),
        per_page: 100,
        tenant_id: numberOrUndefined(getStoredTenantId()),
        ...extra,
    };
}

function contextPayload(payload: BackendRecord = {}): BackendRecord {
    return {
        ...payload,
        organization_unit_id: payload.organization_unit_id ?? numberOrUndefined(getStoredOrganizationUnitId()),
        tenant_id: payload.tenant_id ?? numberOrUndefined(getStoredTenantId()),
    };
}

function userId(): number | undefined {
    return numberOrUndefined(getStoredAuthSession().user?.id);
}

function collection<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord, index: number) => T): ApiCollectionResponse<T> {
    return { ...response, data: response.data.map(mapper) };
}

function labelFrom(raw: BackendRecord, prefix: string, fallback: string): string {
    const nested = record(raw[prefix]);
    const direct = asString(raw[`${prefix}_label`]);
    const code = asString(nested.code ?? nested.sku ?? raw[`${prefix}_code`] ?? raw[`${prefix}_number`] ?? raw[`${prefix}_reference`]);
    const name = asString(nested.display_name ?? nested.name ?? raw[`${prefix}_name`]);

    if (direct) {
        return direct;
    }

    if (code && name) {
        return `${code} - ${name}`;
    }

    return name || code || fallback;
}

function option(id: unknown, label: unknown, secondary?: unknown): VehicleRentalLookupOption {
    return { id: asString(id), label: asString(label, `#${asString(id)}`), secondary: optionalString(secondary) };
}

function emptyAvailability(input: BackendRecord = {}): VehicleRentalAvailabilityPreview {
    return {
        breakdown: Object.entries(input).map(([label, value]) => ({ label, value: asString(value) })),
        calculated: {
            availabilityDecision: asString(input.availability_status ?? input.decision ?? 'Backend availability not requested'),
            conflicts: asString(input.conflicts ?? '0'),
            replacementOption: asString(input.replacement_option ?? 'Not evaluated'),
            vehicleStatus: asString(input.vehicle_status ?? input.availability_status ?? 'Unknown'),
        },
        errors: [],
        input,
        warnings: Array.isArray(input.warnings) ? input.warnings.map(String) : [],
    };
}

function billingPreview(raw: BackendRecord): VehicleRentalBillingPreview {
    return {
        breakdown: [
            { label: 'Subtotal', value: decimal(raw.estimated_subtotal ?? raw.subtotal) },
            { label: 'Discount', value: decimal(raw.estimated_discount_total ?? raw.discount_total) },
            { label: 'Tax', value: decimal(raw.estimated_tax_total ?? raw.tax_total) },
            { label: 'Paid', value: decimal(raw.paid_total) },
        ],
        calculated: {
            discountTotal: decimal(raw.estimated_discount_total ?? raw.discount_total),
            grandTotal: decimal(raw.estimated_grand_total ?? raw.grand_total),
            providerPayable: decimal(raw.provider_payable_total ?? raw.provider_payable),
            rentalCharge: decimal(raw.estimated_subtotal ?? raw.subtotal),
            taxTotal: decimal(raw.estimated_tax_total ?? raw.tax_total),
        },
        errors: [],
        input: raw,
        warnings: [],
    };
}

function normalizeLine(raw: BackendRecord, index: number): VehicleRentalAgreementLine {
    return {
        backendAmount: decimal(raw.line_total ?? raw.total_amount),
        chargeScope: asString(raw.charge_scope, 'customer') as VehicleRentalAgreementLine['chargeScope'],
        description: asString(raw.description, `Rental line ${index + 1}`),
        id: asString(raw.id, `line-${index}`),
        item: labelFrom(raw, 'item', asString(raw.item_id, 'Rental charge item')),
        rentalUnit: asString(raw.billing_basis ?? raw.rental_unit, 'day') as VehicleRentalAgreementLine['rentalUnit'],
        usageBasis: `${decimal(raw.quantity)} ${labelFrom(raw, 'uom', asString(raw.uom_id, 'UOM'))}`,
    };
}

function normalizeRateRule(raw: BackendRecord, index: number) {
    return {
        id: asString(raw.id, `rule-${index}`),
        ruleName: asString(raw.rule_name ?? raw.name, `Rule ${index + 1}`),
        ruleType: asString(raw.rule_type, 'rate'),
        scope: asString(raw.scope ?? raw.charge_scope, 'customer'),
        valuePreview: decimal(raw.rate_value ?? raw.fixed_amount ?? raw.rate_multiplier),
    };
}

function normalizeRunningLine(raw: BackendRecord, index: number): VehicleRentalRunningChartLine {
    return {
        chargePreview: decimal(raw.customer_charge_amount),
        driver: labelFrom(raw, 'driver', asString(raw.driver_id, 'Driver not selected')),
        endReading: asString(raw.end_reading ?? raw.end_odometer ?? raw.end_meter),
        id: asString(raw.id, `chart-line-${index}`),
        lineNumber: asString(raw.line_number, String(index + 1)),
        providerCostPreview: decimal(raw.provider_cost_amount),
        startReading: asString(raw.start_reading ?? raw.start_odometer ?? raw.start_meter),
        usagePreview: `${decimal(raw.total_hours)} hrs / ${decimal(raw.total_km)} km`,
        vehicle: labelFrom(raw, 'rental_vehicle', asString(raw.rental_vehicle_id, 'Rental vehicle')),
    };
}

function normalizeInvoiceFromAgreement(agreement: VehicleRentalAgreement): VehicleRentalInvoice {
    return {
        billingPreview: agreement.billingPreview.calculated.grandTotal,
        customer: agreement.customer,
        documentStatus: agreement.documentPreview.status,
        id: agreement.id,
        invoiceNumber: agreement.documentPreview.documentNumber || agreement.agreementNumber,
        sourceAgreement: agreement.agreementNumber,
        status: agreement.status === 'closed' || agreement.status === 'invoiceable' ? 'invoiceable' : agreement.status,
    };
}

function normalizeProviderPayable(raw: BackendRecord, index = 0): VehicleRentalProviderPayable {
    return {
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id, 'Agreement'),
        financeStatus: asString(raw.finance_status, 'draft'),
        id: asString(raw.id, `provider-payable-${index}`),
        payableNumber: asString(raw.payable_number ?? raw.reference, `VRP-${asString(raw.id, String(index + 1))}`),
        payablePreview: decimal(raw.grand_total ?? raw.subtotal),
        paymentStatus: asString(raw.payment_status, Number(raw.paid_total ?? 0) > 0 ? 'paid' : 'unpaid'),
        provider: labelFrom(raw, 'provider', asString(raw.provider_id, 'Provider')),
        sourceReference: asString(raw.source_reference ?? raw.source_type ?? raw.agreement_id, 'Rental agreement'),
        status: asString(raw.status, 'pending'),
    };
}

function normalizePayment(raw: BackendRecord, index = 0, agreement?: VehicleRentalAgreement): VehicleRentalPayment {
    return {
        allocationPreview: decimal(raw.amount ?? raw.allocated_amount),
        amount: decimal(raw.amount ?? raw.allocated_amount),
        customer: agreement?.customer ?? labelFrom(raw, 'customer', asString(raw.customer_id, 'Customer')),
        id: asString(raw.id ?? raw.payment_id, `payment-${index}`),
        method: asString(raw.method ?? raw.payment_method, 'Existing payment'),
        paymentNumber: asString(raw.payment_number ?? raw.reference ?? raw.payment_id, `Payment ${index + 1}`),
        sourceInvoice: agreement?.agreementNumber ?? asString(raw.document_id ?? raw.agreement_id, 'Rental agreement'),
        status: asString(raw.status, 'allocated'),
    };
}

function normalizeReplacement(raw: BackendRecord, index = 0): VehicleRentalReplacement {
    return {
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id),
        id: asString(raw.id, `replacement-${index}`),
        originalVehicle: labelFrom(raw, 'original_rental_vehicle', asString(raw.original_rental_vehicle_id, 'Original vehicle')),
        reason: asString(raw.reason),
        replacementNumber: asString(raw.replacement_number ?? raw.reference, `Replacement ${index + 1}`),
        replacementVehicle: labelFrom(raw, 'replacement_rental_vehicle', asString(raw.replacement_rental_vehicle_id, 'Replacement vehicle')),
        status: asString(raw.status, 'draft'),
    };
}

function normalizeBreakdown(raw: BackendRecord, index = 0): VehicleRentalBreakdown {
    return {
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id),
        breakdownNumber: asString(raw.breakdown_number ?? raw.reference, `Breakdown ${index + 1}`),
        id: asString(raw.id, `breakdown-${index}`),
        resolution: asString(raw.resolution ?? raw.notes),
        status: asString(raw.status, 'open'),
        vehicle: labelFrom(raw, 'rental_vehicle', asString(raw.rental_vehicle_id, 'Rental vehicle')),
    };
}

function normalizeAudit(raw: BackendRecord, index = 0): VehicleRentalAuditEntry {
    return {
        actor: asString(raw.changed_by_name ?? raw.actor ?? raw.changed_by, 'System'),
        id: asString(raw.id, `activity-${index}`),
        note: asString(raw.reason ?? raw.workflow_action ?? raw.to_status ?? 'Vehicle rental activity'),
        timestamp: asString(raw.changed_at ?? raw.created_at),
        type: asString(raw.workflow_action ?? raw.type, 'activity'),
    };
}

function normalizeAgreement(raw: BackendRecord): VehicleRentalAgreement {
    const lines = Array.isArray(raw.lines) ? raw.lines.map((line, index) => normalizeLine(record(line), index)) : [];
    const rates = Array.isArray(raw.rates) ? raw.rates.map((rate, index) => ({
        baseRate: decimal(record(rate).base_rate),
        id: asString(record(rate).id, `rate-${index}`),
        name: asString(record(rate).rate_name, `Rate ${index + 1}`),
        rentalUnit: asString(record(rate).rate_model ?? record(rate).billing_basis, asString(raw.rate_model, 'day')),
        status: asString(record(rate).status, 'active'),
    })) : [];
    const rateRules = Array.isArray(raw.rate_rules) ? raw.rate_rules.map((rule, index) => normalizeRateRule(record(rule), index)) : [];
    const runningCharts = Array.isArray(raw.running_charts) ? raw.running_charts.map((chart) => normalizeRunningChart(record(chart))) : [];
    const providerPayables = Array.isArray(raw.provider_payables) ? raw.provider_payables.map((payable, index) => normalizeProviderPayable(record(payable), index)) : [];
    const replacements = Array.isArray(raw.replacements) ? raw.replacements.map((replacement, index) => normalizeReplacement(record(replacement), index)) : [];
    const breakdowns = Array.isArray(raw.breakdowns) ? raw.breakdowns.map((breakdown, index) => normalizeBreakdown(record(breakdown), index)) : [];
    const payments = Array.isArray(raw.payment_links) ? raw.payment_links.map((payment, index) => normalizePayment(record(payment), index)) : [];

    const agreement: VehicleRentalAgreement = {
        activity: [],
        agreementNumber: asString(raw.agreement_number, `VRA-${asString(raw.id)}`),
        availabilityPreview: emptyAvailability(raw),
        billingPreview: billingPreview(raw),
        customer: labelFrom(raw, 'customer', asString(raw.customer_id, 'Customer not selected')),
        documentPreview: {
            documentNumber: asString(raw.invoice_number ?? raw.document_number, asString(raw.agreement_number, `VRA-${asString(raw.id)}`)),
            status: asString(raw.invoice_status, 'pending'),
            template: 'Vehicle rental agreement/invoice document',
        },
        driver: labelFrom(raw, 'driver', asString(raw.assigned_driver_id, 'Driver not selected')),
        endAt: asString(raw.end_datetime),
        financePreview: {
            breakdown: [
                { account: 'AR impact', effect: decimal(raw.outstanding_balance) },
                { account: 'Provider payable', effect: decimal(raw.provider_payable_total) },
            ],
            calculated: {
                apImpact: decimal(raw.provider_payable_total),
                arImpact: decimal(raw.outstanding_balance ?? raw.estimated_grand_total),
                eligibility: ['completed', 'closed', 'invoiceable'].includes(asString(raw.status)) ? 'Eligible' : 'Requires backend workflow transition',
                journalStatus: asString(raw.finance_status, 'draft'),
            },
            errors: [],
            input: raw,
            warnings: [],
        },
        id: asString(raw.id),
        invoices: [],
        lines,
        mode: asString(raw.driver_mode, 'without_driver') as VehicleRentalAgreement['mode'],
        payments,
        provider: labelFrom(raw, 'provider', asString(raw.provider_id, 'Provider not selected')),
        providerPayables,
        ratePlan: rates[0] ?? {
            baseRate: decimal(raw.estimated_subtotal),
            id: 'stored-rate',
            name: asString(raw.rate_model, 'Stored rate model'),
            rentalUnit: asString(raw.billing_frequency, 'day'),
            status: 'active',
        },
        rateRules,
        replacements,
        breakdowns,
        rentalUnit: asString(raw.billing_frequency ?? raw.rate_model, 'day') as VehicleRentalAgreement['rentalUnit'],
        runningCharts,
        sourceReference: {
            sourceId: optionalString(raw.source_id),
            sourceModule: asString(raw.source_module, 'vehicle_rental'),
            sourceNumber: optionalString(raw.source_reference ?? raw.agreement_number),
            sourceType: asString(raw.source_type, 'agreement'),
        },
        startAt: asString(raw.start_datetime),
        status: asString(raw.status, 'draft') as VehicleRentalAgreement['status'],
        updatedAt: asString(raw.updated_at),
        vehicle: labelFrom(raw, 'rental_vehicle', asString(raw.rental_vehicle_id, 'Rental vehicle')),
        vehicleSource: asString(raw.vehicle_source ?? raw.source_type, 'own_fleet') as VehicleRentalAgreement['vehicleSource'],
        workflowStatus: asString(raw.status, 'draft'),
    };

    agreement.invoices = [normalizeInvoiceFromAgreement(agreement)].filter(() => agreement.documentPreview.status !== 'pending' || agreement.status === 'invoiceable' || agreement.status === 'closed');

    return agreement;
}

function normalizeRunningChart(raw: BackendRecord): VehicleRentalRunningChart {
    const lines = Array.isArray(raw.lines) ? raw.lines.map((line, index) => normalizeRunningLine(record(line), index)) : [];

    return {
        agreementNumber: asString(raw.agreement_number ?? raw.agreement_id),
        billingPreview: billingPreview({
            estimated_grand_total: raw.customer_bill_total,
            estimated_subtotal: raw.customer_bill_total,
            provider_payable_total: raw.provider_cost_total,
        }),
        chartNumber: asString(raw.chart_number, `VRC-${asString(raw.id)}`),
        customer: labelFrom(raw, 'customer', 'Customer'),
        driver: labelFrom(raw, 'driver', asString(raw.driver_id, 'Driver')),
        endAt: asString(raw.end_datetime ?? raw.end_at),
        id: asString(raw.id),
        lines,
        providerPayablePreview: decimal(raw.provider_cost_total),
        startAt: asString(raw.start_datetime ?? raw.start_at ?? raw.chart_date),
        status: asString(raw.status, 'draft'),
        vehicle: labelFrom(raw, 'rental_vehicle', asString(raw.rental_vehicle_id, 'Rental vehicle')),
    };
}

function linePayload(input: VehicleRentalAgreementLineInput, index: number): BackendRecord {
    return {
        billing_basis: input.billingBasis || 'day',
        charge_scope: input.chargeScope || 'customer',
        description: input.description || 'Rental charge',
        id: numberOrUndefined(input.id),
        item_id: numberOrUndefined(input.itemId),
        line_number: index + 1,
        line_type: input.lineType || 'base_rental',
        quantity: Number(input.quantity || 0),
        unit_rate: Number(input.unitRate || 0),
        uom_id: numberOrUndefined(input.uomId),
    };
}

function agreementPayload(input: VehicleRentalAgreementFormInput): BackendRecord {
    return contextPayload({
        agreement_date: input.agreementDate || new Date().toISOString().slice(0, 10),
        agreement_number: input.agreementNumber || `VRA-${Date.now()}`,
        billing_frequency: input.billingFrequency || null,
        customer_id: numberOrUndefined(input.customerId),
        deposit_amount: Number(input.depositAmount || 0),
        driver_mode: input.driverMode || 'without_driver',
        end_datetime: input.endAt || null,
        kilometer_limit: Number(input.kilometerLimit || 0),
        lines: input.lines.map(linePayload),
        provider_id: numberOrUndefined(input.providerId),
        rate_model: input.rateModel || 'daily',
        rental_vehicle_id: numberOrUndefined(input.rentalVehicleId),
        start_datetime: input.startAt || null,
        status: input.status || 'draft',
        customer_notes: input.notes || null,
    });
}

function runningChartPayload(input: VehicleRentalRunningChartFormInput): BackendRecord {
    return contextPayload({
        agreement_id: numberOrUndefined(input.agreementId),
        chart_date: input.chartDate || new Date().toISOString().slice(0, 10),
        chart_number: input.chartNumber || `VRC-${Date.now()}`,
        driver_id: numberOrUndefined(input.driverId),
        end_datetime: input.endAt || null,
        rental_vehicle_id: numberOrUndefined(input.rentalVehicleId),
        start_datetime: input.startAt || null,
        status: input.status || 'draft',
        remarks: input.notes || null,
    });
}

async function safeCollection<T>(callback: () => Promise<ApiCollectionResponse<T>>): Promise<T[]> {
    try {
        return (await callback()).data;
    } catch {
        return [];
    }
}

export const vehicleRentalApi = {
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<VehicleRentalDashboardMetric>> => {
            const [agreements, charts, providerPayables] = await Promise.all([
                vehicleRentalApi.agreements.list(),
                vehicleRentalApi.runningCharts.list(),
                vehicleRentalApi.providerPayables.list(),
            ]);

            return {
                data: [
                    { label: 'Agreements', tone: 'backend', value: String(agreements.data.length) },
                    { label: 'Running', tone: 'active', value: String(agreements.data.filter((row) => row.status === 'running' || row.status === 'active').length) },
                    { label: 'Invoiceable', tone: 'invoice', value: String(agreements.data.filter((row) => row.status === 'invoiceable' || row.status === 'closed').length) },
                    { label: 'Running charts', tone: 'usage', value: String(charts.data.length) },
                    { label: 'Provider payables', tone: 'provider', value: String(providerPayables.data.length) },
                    { label: 'Backend routes', tone: 'real', value: 'Real API' },
                ],
            };
        },
    },
    availability: {
        list: async (): Promise<ApiCollectionResponse<{ availability: string; decision: string; id: string; source: string; vehicle: string; window: string }>> => {
            const vehicles = await vehicleRentalApi.lookups.rentalVehicles();
            return {
                data: vehicles.data.map((vehicle) => ({
                    availability: vehicle.secondary ?? 'Backend status',
                    decision: 'Select dates to preview',
                    id: vehicle.id,
                    source: 'Rental fleet',
                    vehicle: vehicle.label,
                    window: 'Not requested',
                })),
            };
        },
        preview: async (input: BackendRecord): Promise<ApiPreviewResponse<BackendRecord, VehicleRentalAvailabilityPreview['calculated']>> => {
            const query = contextQuery({
                end_datetime: asString(input.end_datetime || input.endAt),
                exclude_agreement_id: numberOrUndefined(input.exclude_agreement_id as string | number | undefined),
                rental_vehicle_id: numberOrUndefined(input.rental_vehicle_id as string | number | undefined),
                start_datetime: asString(input.start_datetime || input.startAt),
            });
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/vehicle-rental/vehicle-availability', { query });
            const preview = emptyAvailability(response.data);
            return { breakdown: preview.breakdown, calculated: preview.calculated, errors: preview.errors, input, warnings: preview.warnings };
        },
    },
    agreements: {
        create: async (input: VehicleRentalAgreementFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/vehicle-rental/agreements', { body: agreementPayload(input), method: 'POST' });
            return { ...response, data: normalizeAgreement(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<VehicleRentalAgreement>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}`);
            const agreement = normalizeAgreement(response.data);
            const activity = await safeCollection(() => vehicleRentalApi.agreements.history(id));
            return { data: { ...agreement, activity } };
        },
        history: async (id: string): Promise<ApiCollectionResponse<VehicleRentalAuditEntry>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/vehicle-rental/status-history/agreement/${id}`, { query: contextQuery() }), normalizeAudit),
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleRentalAgreement>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/agreements', { query: contextQuery(query) }), normalizeAgreement),
        previewBilling: async (id: string, input: BackendRecord = {}): Promise<ApiPreviewResponse<BackendRecord, VehicleRentalBillingPreview['calculated']>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}/billing-preview`, { body: contextPayload(input), method: 'POST' });
            const preview = billingPreview(response.data);
            return { breakdown: preview.breakdown, calculated: preview.calculated, errors: preview.errors, input, warnings: preview.warnings };
        },
        syncExtraCharges: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/extra-charges/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        syncLines: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/agreements/${id}/lines/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        transition: (id: string, status: string, reason?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${id}/transition`, { body: contextPayload({ actor_id: userId(), reason, status }), method: 'POST' }),
        update: async (id: string, input: VehicleRentalAgreementFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}`, { body: agreementPayload(input), method: 'PUT' });
            return { ...response, data: normalizeAgreement(response.data) };
        },
    },
    breakdowns: {
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/breakdowns', { body: contextPayload(record(input)), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<VehicleRentalBreakdown>> => {
            const agreements = await vehicleRentalApi.agreements.list();
            return { data: agreements.data.flatMap((agreement) => agreement.breakdowns) };
        },
    },
    finance: {
        post: (entityType: string, entityId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/post`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
        previewPosting: async (agreementId: string) => ({ data: (await vehicleRentalApi.agreements.get(agreementId)).data.financePreview }),
        reverse: (entityType: string, entityId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/reverse`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
    },
    invoices: {
        generate: (agreementId: string, documentTypeId?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/invoice`, { body: contextPayload({ actor_id: userId(), document_type_id: numberOrUndefined(documentTypeId) }), method: 'POST' }),
        get: async (id: string): Promise<ApiResponse<VehicleRentalInvoice>> => {
            const agreement = await vehicleRentalApi.agreements.get(id);
            return { data: normalizeInvoiceFromAgreement(agreement.data) };
        },
        list: async (): Promise<ApiCollectionResponse<VehicleRentalInvoice>> => {
            const agreements = await vehicleRentalApi.agreements.list();
            return { data: agreements.data.filter((agreement) => agreement.documentPreview.status !== 'pending' || agreement.status === 'invoiceable' || agreement.status === 'closed').map(normalizeInvoiceFromAgreement) };
        },
        preview: async (agreementId: string) => vehicleRentalApi.agreements.previewBilling(agreementId),
    },
    lookups: {
        customers: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await customerApi.listCustomers({ perPage: 200 });
            return { data: response.data.map((customer) => option(customer.id, `${customer.code} - ${customer.name}`, customer.status)) };
        },
        employees: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await hrApi.employees.list({ perPage: 200, status: 'active' });
            return { data: response.data.map((employee) => option(employee.id, `${employee.code} - ${employee.displayName}`, employee.status)) };
        },
        itemUnits: async (itemId: string): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await itemApi.getItemUnits(itemId);
            return { data: response.data.filter((unit) => unit.id && unit.unit !== 'Not configured').map((unit) => option(unit.id, unit.unit, unit.purpose)) };
        },
        items: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await itemApi.listItems({ perPage: 200, status: 'active' });
            return { data: response.data.map((item) => option(item.id, `${item.code} - ${item.name}`, item.itemType)) };
        },
        rentalVehicles: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/rental-vehicles', { query: contextQuery() }), (raw) => option(raw.id, `${asString(raw.internal_code ?? raw.registration_number, `RV-${asString(raw.id)}`)} - ${asString(raw.display_name ?? raw.make_model, 'Rental vehicle')}`, asString(raw.availability_status ?? raw.source_type))),
        suppliers: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await supplierApi.listSuppliers({ perPage: 200 });
            return { data: response.data.map((supplier) => option(supplier.id, `${supplier.code} - ${supplier.name}`, supplier.status)) };
        },
        uoms: async (): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await inventoryApi.listUoms();
            return { data: response.data.map((uom) => option(uom.id, uom.secondary ? `${uom.secondary} - ${uom.label}` : uom.label)) };
        },
    },
    payments: {
        allocate: (agreementId: string, input: VehicleRentalPaymentFormInput) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/payments/allocate`, { body: contextPayload({ amount: Number(input.amount), document_id: numberOrUndefined(input.documentId), document_type: input.documentType || 'document', payment_id: numberOrUndefined(input.paymentId) }), method: 'POST' }),
        create: (input: VehicleRentalPaymentFormInput) => vehicleRentalApi.payments.allocate(input.agreementId, input),
        list: async (): Promise<ApiCollectionResponse<VehicleRentalPayment>> => {
            const agreements = await vehicleRentalApi.agreements.list();
            return { data: agreements.data.flatMap((agreement) => agreement.payments.map((payment) => ({ ...payment, customer: agreement.customer, sourceInvoice: agreement.agreementNumber }))) };
        },
        previewAllocation: (input: VehicleRentalPaymentFormInput): ApiResponse<VehicleRentalPayment> => ({
            data: {
                allocationPreview: 'Allocation will be validated by backend when submitted',
                amount: input.amount,
                customer: 'Backend customer',
                id: 'preview',
                method: 'Existing payment allocation',
                paymentNumber: input.paymentId ? `Payment #${input.paymentId}` : 'Select payment',
                sourceInvoice: input.documentId ? `Document #${input.documentId}` : `Agreement #${input.agreementId}`,
                status: 'preview',
            },
        }),
    },
    providerPayables: {
        allocatePayment: (providerPayableId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/provider-payables/${providerPayableId}/payments/allocate`, { body: contextPayload(record(input)), method: 'POST' }),
        create: (agreementId: string, input: unknown = {}) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/provider-payables`, { body: contextPayload(record(input)), method: 'POST' }),
        get: async (id: string): Promise<ApiResponse<VehicleRentalProviderPayable>> => {
            const response = await vehicleRentalApi.providerPayables.list();
            const payable = response.data.find((row) => row.id === id);
            if (!payable) {
                throw new Error('Provider payable not found.');
            }

            return { data: payable };
        },
        list: async (agreementId?: string): Promise<ApiCollectionResponse<VehicleRentalProviderPayable>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/provider-payables', { query: contextQuery({ agreement_id: numberOrUndefined(agreementId) }) }), normalizeProviderPayable),
    },
    replacements: {
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/replacements', { body: contextPayload(record(input)), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<VehicleRentalReplacement>> => {
            const agreements = await vehicleRentalApi.agreements.list();
            return { data: agreements.data.flatMap((agreement) => agreement.replacements) };
        },
    },
    runningCharts: {
        create: async (input: VehicleRentalRunningChartFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/vehicle-rental/running-charts', { body: runningChartPayload(input), method: 'POST' });
            return { ...response, data: normalizeRunningChart(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<VehicleRentalRunningChart>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/running-charts/${id}`);
            return { ...response, data: normalizeRunningChart(response.data) };
        },
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleRentalRunningChart>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/running-charts', { query: contextQuery(query) }), normalizeRunningChart),
        previewBilling: async (id: string) => {
            const chart = await vehicleRentalApi.runningCharts.get(id);
            return { data: chart.data.billingPreview };
        },
        syncLines: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/running-charts/${id}/lines/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        transition: (id: string, status: string, reason?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/running-charts/${id}/transition`, { body: contextPayload({ actor_id: userId(), reason, status }), method: 'POST' }),
        update: async (id: string, input: VehicleRentalRunningChartFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/running-charts/${id}`, { body: runningChartPayload(input), method: 'PUT' });
            return { ...response, data: normalizeRunningChart(response.data) };
        },
    },
    settings: {
        get: () => httpClient<ApiResponse<VehicleRentalSettings>>('/api/vehicle-rental/settings', { query: contextQuery() }),
        initialize: () => httpClient<ApiResponse<VehicleRentalSettings>>('/api/vehicle-rental/settings/initialize', { body: contextPayload({}), method: 'POST' }),
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/settings', { body: contextPayload(record(input)), method: 'POST' }),
    },
};
