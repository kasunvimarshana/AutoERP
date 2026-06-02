import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { customerApi } from '../../customer/services/customerApi';
import { hrApi } from '../../hr/services/hrApi';
import { supplierApi } from '../../supplier/services/supplierApi';
import { vehicleApi } from '../../vehicle/services/vehicleApi';
import type {
    VehicleRentalAgreement,
    VehicleRentalAgreementFormInput,
    VehicleRentalAvailabilityPreview,
    VehicleRentalBillingPreview,
    VehicleRentalDashboardMetric,
    VehicleRentalInvoice,
    VehicleRentalLookupOption,
    VehicleRentalPayment,
    VehicleRentalProviderPayable,
    VehicleRentalRunningChart,
    VehicleRentalRunningChartFormInput,
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

function asBoolean(value: unknown, fallback = false): boolean {
    if (typeof value === 'boolean') return value;
    if (value === 1 || value === '1' || value === 'true') return true;
    if (value === 0 || value === '0' || value === 'false') return false;
    return fallback;
}

function contextQuery(extra: Record<string, string | number | boolean | null | undefined> = {}) {
    return {
        organization_unit_id: numberOrUndefined(getStoredOrganizationUnitId()),
        per_page: 25,
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

function scalarQuery(input: BackendRecord): Record<string, string | number | boolean | null | undefined> {
    return Object.fromEntries(
        Object.entries(input).filter((entry): entry is [string, string | number | boolean | null | undefined] => (
            ['string', 'number', 'boolean', 'undefined'].includes(typeof entry[1]) || entry[1] === null
        )),
    );
}

function userId(): number | undefined {
    return numberOrUndefined(getStoredAuthSession().user?.id);
}

function collectionMeta<T>(response: ApiCollectionResponse<T>) {
    return response.meta ?? {
        current_page: 1,
        from: response.data.length ? 1 : 0,
        last_page: 1,
        per_page: response.data.length,
        to: response.data.length,
        total: response.data.length,
    };
}

function collection<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    const data = response.data.map(mapper);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

function nestedLabel(raw: BackendRecord, key: string, fallback: string): string {
    const nested = record(raw[key]);
    const direct = asString(raw[`${key}_label`]);
    const code = asString(nested.code ?? nested.registration_number ?? nested.sku);
    const name = asString(nested.display_name ?? nested.name);
    if (direct) return direct;
    if (code && name) return `${code} - ${name}`;
    return name || code || fallback;
}

function option(id: unknown, label: unknown, secondary?: unknown): VehicleRentalLookupOption {
    return {
        id: asString(id),
        label: asString(label, `#${asString(id)}`),
        secondary: optionalString(secondary),
    };
}

function emptyBilling(raw: BackendRecord): VehicleRentalBillingPreview {
    const rentalCharge = Number(raw.customer_bill_total ?? raw.estimated_grand_total ?? raw.invoiced_total ?? 0);
    const providerPayable = Number(raw.provider_cost_total ?? raw.provider_payable_total ?? 0);
    return {
        breakdown: [
            { label: 'Lessee receivable', value: decimal(rentalCharge) },
            { label: 'Lessor payable', value: decimal(providerPayable) },
            { label: 'Margin preview', value: decimal(rentalCharge - providerPayable) },
        ],
        calculated: {
            discountTotal: decimal(raw.estimated_discount_total),
            grandTotal: decimal(rentalCharge || raw.estimated_grand_total),
            providerPayable: decimal(providerPayable),
            rentalCharge: decimal(rentalCharge),
            taxTotal: decimal(raw.estimated_tax_total),
        },
        errors: [],
        input: raw,
        warnings: [],
    };
}

function emptyAvailability(raw: BackendRecord): VehicleRentalAvailabilityPreview {
    return {
        breakdown: [],
        calculated: {
            availabilityDecision: asString(raw.status, 'Backend availability checked when requested'),
            conflicts: '0',
            replacementOption: asString(raw.vehicle_source, 'No replacement selected'),
            vehicleStatus: asString(raw.vehicle_status, 'Backend controlled'),
        },
        errors: [],
        input: raw,
        warnings: [],
    };
}

function normalizeLine(raw: BackendRecord, index = 0): VehicleRentalAgreement['lines'][number] {
    return {
        backendAmount: decimal(raw.line_total),
        chargeScope: asString(raw.charge_scope, 'customer') as VehicleRentalAgreement['lines'][number]['chargeScope'],
        description: asString(raw.description, 'Agreement line'),
        id: asString(raw.id, `line-${index}`),
        item: nestedLabel(raw, 'item', asString(raw.item_id, 'Backend item')),
        rentalUnit: asString(raw.billing_basis, 'day') as VehicleRentalAgreement['lines'][number]['rentalUnit'],
        usageBasis: asString(raw.billing_basis, 'Backend usage basis'),
    };
}

function normalizeRunningLine(raw: BackendRecord, index = 0): VehicleRentalRunningChart['lines'][number] {
    return {
        chargePreview: decimal(raw.customer_charge_amount),
        driver: nestedLabel(raw, 'driver', asString(raw.driver_id, 'Not assigned')),
        endReading: decimal(raw.end_km),
        id: asString(raw.id, `running-line-${index}`),
        lineNumber: asString(raw.line_number, String(index + 1)),
        providerCostPreview: decimal(raw.provider_cost_amount),
        startReading: decimal(raw.start_km),
        usagePreview: `${decimal(raw.total_km)} km / ${decimal(raw.total_hours)} h`,
        vehicle: nestedLabel(raw, 'vehicle', asString(raw.rental_vehicle_id, 'Vehicle')),
    };
}

function normalizeRunningChart(raw: BackendRecord): VehicleRentalRunningChart {
    const lines = Array.isArray(raw.lines) ? raw.lines.map((line, index) => normalizeRunningLine(record(line), index)) : [];
    return {
        agreementId: asString(raw.agreement_id),
        agreementNumber: asString(raw.agreement_id_label ?? raw.agreement_number ?? raw.agreement_id),
        billingPreview: emptyBilling(raw),
        chartNumber: asString(raw.chart_number, `Chart #${asString(raw.id)}`),
        customer: nestedLabel(raw, 'lessee', asString(raw.customer_id, 'Lessee')),
        driver: nestedLabel(raw, 'driver', asString(raw.driver_id, 'Not assigned')),
        endAt: asString(raw.end_datetime),
        id: asString(raw.id),
        lines,
        providerPayablePreview: decimal(raw.provider_cost_total),
        side: asString(raw.agreement_side, 'lessee') as VehicleRentalRunningChart['side'],
        startAt: asString(raw.chart_date),
        status: asString(raw.status, 'draft'),
        vehicle: nestedLabel(raw, 'vehicle', asString(raw.rental_vehicle_id, 'Vehicle')),
    };
}

function normalizeProviderPayable(raw: BackendRecord): VehicleRentalProviderPayable {
    return {
        agreementNumber: asString(raw.agreement_id_label ?? raw.agreement_number ?? raw.agreement_id),
        financeStatus: asString(raw.finance_status, 'draft'),
        id: asString(raw.id),
        payableNumber: asString(raw.payable_number, `Payable #${asString(raw.id)}`),
        payablePreview: decimal(raw.grand_total),
        paymentStatus: asString(raw.payment_status, 'unpaid'),
        provider: nestedLabel(raw, 'lessor', asString(raw.provider_party_name ?? raw.provider_id, 'Provider')),
        sourceReference: asString(raw.source_entity_type, 'lessor agreement'),
        status: asString(raw.status, 'pending'),
    };
}

function normalizeAgreement(raw: BackendRecord): VehicleRentalAgreement {
    const role = asString(raw.agreement_role, 'lessee') as VehicleRentalAgreement['agreementRole'];
    const runningCharts = Array.isArray(raw.running_charts) ? raw.running_charts.map((row) => normalizeRunningChart(record(row))) : [];
    const providerPayables = Array.isArray(raw.provider_payables) ? raw.provider_payables.map((row) => normalizeProviderPayable(record(row))) : [];
    const billingPreview = emptyBilling(raw);

    return {
        activity: Array.isArray(raw.status_history)
            ? raw.status_history.map((entry, index) => ({
                actor: asString(record(entry).changed_by, 'System'),
                id: asString(record(entry).id, `activity-${index}`),
                note: asString(record(entry).reason ?? record(entry).action_name, 'Rental activity'),
                timestamp: asString(record(entry).changed_at ?? record(entry).created_at),
                type: asString(record(entry).action_name, 'activity'),
            }))
            : [],
        agreementNumber: asString(raw.agreement_number, `Agreement #${asString(raw.id)}`),
        agreementRole: role,
        availabilityPreview: emptyAvailability(raw),
        billingPreview,
        customer: nestedLabel(raw, 'lessee', asString(raw.customer_id, 'Lessee not selected')),
        documentPreview: {
            documentNumber: asString(raw.agreement_number, `Agreement #${asString(raw.id)}`),
            status: asString(raw.invoice_status, 'pending'),
            template: role === 'lessee' ? 'Lessee agreement document' : 'Lessor agreement document',
        },
        driver: nestedLabel(raw, 'driver', asString(raw.assigned_driver_id, 'Not assigned')),
        endAt: asString(raw.end_datetime),
        financePreview: {
            breakdown: [
                { account: 'Lessee receivable', effect: decimal(raw.invoiced_total) },
                { account: 'Lessor payable', effect: decimal(raw.provider_payable_total) },
            ],
            calculated: {
                apImpact: decimal(raw.provider_payable_total),
                arImpact: decimal(raw.invoiced_total),
                eligibility: asString(raw.status, 'draft'),
                journalStatus: asString(raw.finance_status, 'draft'),
            },
            errors: [],
            input: raw,
            warnings: [],
        },
        id: asString(raw.id),
        invoices: role === 'lessee' && Number(raw.invoiced_total ?? 0) > 0 ? [{
            billingPreview: decimal(raw.invoiced_total),
            customer: nestedLabel(raw, 'lessee', asString(raw.customer_id, 'Lessee')),
            documentStatus: asString(raw.invoice_status, 'pending'),
            id: asString(raw.id),
            invoiceNumber: asString(raw.agreement_number),
            sourceAgreement: asString(raw.agreement_number),
            status: asString(raw.invoice_status, 'pending'),
        }] : [],
        lesseeAgreementId: optionalString(raw.lessee_agreement_id),
        lessorAgreementId: optionalString(raw.lessor_agreement_id),
        lines: Array.isArray(raw.lines) ? raw.lines.map((line, index) => normalizeLine(record(line), index)) : [],
        mode: asString(raw.driver_mode, 'without_driver') as VehicleRentalAgreement['mode'],
        payments: [],
        provider: nestedLabel(raw, 'lessor', asString(raw.lessor_party_name ?? raw.provider_id, 'Provider not selected')),
        providerPayables,
        ratePlan: {
            baseRate: decimal(record((Array.isArray(raw.rates) ? raw.rates[0] : undefined)).base_rate),
            id: asString(record((Array.isArray(raw.rates) ? raw.rates[0] : undefined)).id, 'default'),
            name: asString(record((Array.isArray(raw.rates) ? raw.rates[0] : undefined)).rate_name, role === 'lessee' ? 'Lessee rate' : 'Lessor rate'),
            rentalUnit: asString(raw.rate_model, 'day'),
            status: 'active',
        },
        rateRules: Array.isArray(raw.rate_rules) ? raw.rate_rules.map((rule, index) => ({
            id: asString(record(rule).id, `rule-${index}`),
            ruleName: asString(record(rule).rule_name, 'Rule'),
            ruleType: asString(record(rule).rule_type, 'usage'),
            scope: asString(record(rule).charge_scope, role),
            valuePreview: decimal(record(rule).rate_value ?? record(rule).fixed_amount),
        })) : [],
        replacements: [],
        breakdowns: [],
        rentalOperationUuid: optionalString(raw.rental_operation_uuid),
        rentalUnit: asString(raw.rate_model, 'day') as VehicleRentalAgreement['rentalUnit'],
        runningCharts,
        sourceReference: {
            sourceId: optionalString(raw.parent_agreement_id),
            sourceModule: 'vehicle_rental',
            sourceNumber: optionalString(role === 'lessee' ? raw.lessor_agreement_id_label : raw.lessee_agreement_id_label),
            sourceType: role === 'lessee' ? 'lessee_agreement' : 'lessor_agreement',
        },
        startAt: asString(raw.start_datetime),
        status: asString(raw.status, 'draft') as VehicleRentalAgreement['status'],
        updatedAt: asString(raw.updated_at),
        vehicle: nestedLabel(raw, 'vehicle', asString(raw.rental_vehicle_id, 'Vehicle not selected')),
        vehicleSource: asString(raw.vehicle_source, role === 'lessor' ? 'external_provider' : 'own_fleet') as VehicleRentalAgreement['vehicleSource'],
        workflowStatus: asString(raw.status, 'draft'),
    };
}

function normalizeSettings(raw: BackendRecord): VehicleRentalSettings {
    return {
        _raw: raw,
        allowExternalProviderVehicles: asBoolean(raw.allow_external_provider_vehicle),
        allowReplacementVehicle: asBoolean(raw.allow_replacement_vehicle),
        allowWithDriverRental: asBoolean(raw.allow_with_driver, true),
        agreementSequence: asString(raw.rental_agreement_document_definition_label ?? raw.rental_agreement_document_definition_id, 'Backend sequence'),
        defaultProviderPayableAccount: asString(raw.default_provider_payable_account_label ?? raw.default_provider_payable_account_id, 'Not configured'),
        defaultRatePlan: asString(raw.default_price_list_label ?? raw.default_price_list_id, 'Not configured'),
        defaultTaxGroup: asString(raw.default_tax_group_label ?? raw.default_tax_group_id, 'Not configured'),
        invoiceDocumentDefinition: asString(raw.rental_invoice_document_definition_label ?? raw.rental_invoice_document_definition_id, 'Not configured'),
        invoiceSequence: asString(raw.rental_invoice_sequence_label ?? raw.rental_invoice_sequence_code, 'Backend sequence'),
        runningChartSequence: asString(raw.running_chart_document_definition_label ?? raw.running_chart_document_definition_id, 'Backend sequence'),
    };
}

function agreementPayload(input: VehicleRentalAgreementFormInput): BackendRecord {
    const shared = contextPayload({
        agreement_date: input.agreementDate || new Date().toISOString().slice(0, 10),
        allowed_daily_hours: Number(input.allowedDailyHours || 0),
        allowed_daily_km: Number(input.allowedDailyKm || 0),
        billing_frequency: input.billingFrequency || null,
        deposit_amount: Number(input.depositAmount || 0),
        driver_mode: input.driverMode || 'without_driver',
        end_datetime: input.endAt || null,
        lessor_party_id: numberOrUndefined(input.lessorPartyId),
        lessor_party_name: input.lessorPartyName || null,
        lessor_party_type: input.lessorPartyType || 'supplier',
        pickup_location: input.pickupLocation || null,
        rate_model: input.rateModel || 'day',
        rental_vehicle_id: numberOrUndefined(input.rentalVehicleId),
        return_location: input.returnLocation || null,
        start_datetime: input.startAt || null,
        status: input.status || 'draft',
        terms_and_conditions: input.terms || null,
    });

    return {
        ...shared,
        customer_id: numberOrUndefined(input.customerId),
        provider_id: numberOrUndefined(input.providerId),
        lessee_agreement: {
            agreement_number: input.lesseeAgreementNumber || null,
            customer_notes: input.lesseeTerms || null,
        },
        lessee_rates: [{
            base_rate: Number(input.lesseeBaseRate || 0),
            charge_scope: 'customer',
            effective_from: input.startAt || new Date().toISOString(),
            is_default: true,
            rate_model: input.rateModel || 'day',
            rate_name: 'Lessee rate',
            usage_basis: input.rateModel || 'day',
        }],
        lessor_agreement: {
            agreement_number: input.lessorAgreementNumber || null,
            internal_notes: input.lessorTerms || null,
        },
        lessor_rates: [{
            base_rate: Number(input.lessorBaseRate || 0),
            charge_scope: 'provider',
            effective_from: input.startAt || new Date().toISOString(),
            is_default: true,
            rate_model: input.rateModel || 'day',
            rate_name: 'Lessor rate',
            usage_basis: input.rateModel || 'day',
        }],
    };
}

function runningChartPayload(input: VehicleRentalRunningChartFormInput): BackendRecord {
    return contextPayload({
        chart_date: input.date,
        deduction_amount: Number(input.deductions || 0),
        driver_charge_amount: Number(input.driverCharges || 0),
        driver_id: numberOrUndefined(input.driverId),
        end_km: Number(input.endMeter || 0),
        fuel_amount: Number(input.fuel || 0),
        lessee_agreement_id: numberOrUndefined(input.lesseeAgreementId),
        lessor_agreement_id: numberOrUndefined(input.lessorAgreementId),
        mileage_charge_amount: Number(input.mileageCharges || 0),
        notes: input.notes || null,
        other_expense_amount: Number(input.extraCharges || 0),
        rental_vehicle_id: numberOrUndefined(input.rentalVehicleId),
        start_km: Number(input.startMeter || 0),
        total_hours: Number(input.durationHours || 0),
        total_km: Number(input.runningDistance || 0),
    });
}

export const vehicleRentalApi = {
    agreements: {
        createLinked: async (input: VehicleRentalAgreementFormInput) => {
            const response = await httpClient<ApiResponse<{ lessee_agreement: BackendRecord; lessor_agreement: BackendRecord }>>('/api/vehicle-rental/agreements/linked', {
                body: agreementPayload(input),
                method: 'POST',
            });
            return {
                ...response,
                data: {
                    lesseeAgreement: normalizeAgreement(record(response.data.lessee_agreement)),
                    lessorAgreement: normalizeAgreement(record(response.data.lessor_agreement)),
                },
            };
        },
        get: async (id: string): Promise<ApiResponse<VehicleRentalAgreement>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}`);
            return { ...response, data: normalizeAgreement(response.data) };
        },
        history: async (id: string) => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/vehicle-rental/status-history/agreement/${id}`, { query: contextQuery() });
            return collection(response, (row) => ({
                actor: asString(row.changed_by, 'System'),
                id: asString(row.id),
                note: asString(row.reason ?? row.action_name, 'Rental activity'),
                timestamp: asString(row.changed_at ?? row.created_at),
                type: asString(row.action_name, 'activity'),
            }));
        },
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleRentalAgreement>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/agreements', { query: contextQuery(query) });
            return collection(response, normalizeAgreement);
        },
        previewBilling: async (id: string, input: unknown): Promise<ApiPreviewResponse<unknown, VehicleRentalBillingPreview['calculated']>> => {
            const response = await httpClient<ApiResponse<VehicleRentalBillingPreview>>(`/api/vehicle-rental/agreements/${id}/billing-preview`, {
                body: contextPayload(record(input)),
                method: 'POST',
            });
            return response.data;
        },
        transition: (id: string, status: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${id}/transition`, {
            body: contextPayload({ actor_id: userId(), status }),
            method: 'POST',
        }),
        update: async (id: string, input: VehicleRentalAgreementFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/agreements/${id}`, {
                body: agreementPayload(input),
                method: 'PUT',
            });
            return { ...response, data: normalizeAgreement(response.data) };
        },
    },
    availability: {
        preview: (input: unknown): Promise<ApiPreviewResponse<unknown, VehicleRentalAvailabilityPreview['calculated']>> =>
            httpClient<ApiPreviewResponse<unknown, VehicleRentalAvailabilityPreview['calculated']>>('/api/vehicle-rental/vehicle-availability', {
                method: 'GET',
                query: contextQuery(scalarQuery(record(input))),
            }),
    },
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<VehicleRentalDashboardMetric>> => {
            const [lessee, lessor, charts, payables] = await Promise.all([
                vehicleRentalApi.agreements.list({ agreement_role: 'lessee', per_page: 1 }),
                vehicleRentalApi.agreements.list({ agreement_role: 'lessor', per_page: 1 }),
                vehicleRentalApi.runningCharts.list({ per_page: 1 }),
                vehicleRentalApi.providerPayables.list({ per_page: 1 }),
            ]);
            return {
                data: [
                    { label: 'Lessee agreements', tone: 'lessee', value: String(lessee.meta?.total ?? lessee.data.length) },
                    { label: 'Lessor agreements', tone: 'lessor', value: String(lessor.meta?.total ?? lessor.data.length) },
                    { label: 'Running charts', tone: 'usage', value: String(charts.meta?.total ?? charts.data.length) },
                    { label: 'Provider payables', tone: 'payable', value: String(payables.meta?.total ?? payables.data.length) },
                ],
            };
        },
    },
    finance: {
        post: (entityType: string, entityId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/post`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
        reverse: (entityType: string, entityId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/${entityType}/${entityId}/finance/reverse`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
    },
    invoices: {
        generateLessee: (agreementId: string, input: unknown = {}) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/invoice`, { body: contextPayload({ actor_id: userId(), ...record(input) }), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<VehicleRentalInvoice>> => {
            const agreements = await vehicleRentalApi.agreements.list({ agreement_role: 'lessee' });
            return { data: agreements.data.flatMap((agreement) => agreement.invoices) };
        },
    },
    lookups: {
        customers: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await customerApi.lookupCustomers({ perPage: 25, search });
            return { data: response.data.map((customer) => option(customer.id, customer.label, customer.secondary)) };
        },
        drivers: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await hrApi.employees.lookup(search);
            return { data: response.data.map((employee) => option(employee.id, `${employee.code} - ${employee.displayName}`, employee.status)) };
        },
        lesseeAgreements: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await vehicleRentalApi.agreements.list({ agreement_role: 'lessee', search });
            return { data: response.data.map((agreement) => option(agreement.id, `${agreement.agreementNumber} - ${agreement.customer}`, agreement.vehicle)) };
        },
        lessorAgreements: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await vehicleRentalApi.agreements.list({ agreement_role: 'lessor', search });
            return { data: response.data.map((agreement) => option(agreement.id, `${agreement.agreementNumber} - ${agreement.provider}`, agreement.vehicle)) };
        },
        providers: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await supplierApi.lookupSuppliers({ perPage: 25, search });
            return { data: response.data.map((supplier) => option(supplier.id, supplier.label, supplier.secondary)) };
        },
        rentalVehicles: async (search = ''): Promise<ApiCollectionResponse<VehicleRentalLookupOption>> => {
            const response = await vehicleApi.list({ perPage: 25, search });
            return { data: response.data.map((vehicle) => option(vehicle.id, `${vehicle.registrationNumber} - ${vehicle.make} ${vehicle.model}`, vehicle.status)) };
        },
    },
    payments: {
        allocateLessee: (agreementId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${agreementId}/payments/allocate`, { body: contextPayload({ actor_id: userId(), ...record(input) }), method: 'POST' }),
        allocateLessor: (providerPayableId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/provider-payables/${providerPayableId}/payments/allocate`, { body: contextPayload({ actor_id: userId(), ...record(input) }), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<VehicleRentalPayment>> => ({ data: [] }),
    },
    providerPayables: {
        create: (lessorAgreementId: string, input: unknown = {}) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/agreements/${lessorAgreementId}/provider-payables`, { body: contextPayload({ actor_id: userId(), ...record(input) }), method: 'POST' }),
        get: async (id: string): Promise<ApiResponse<VehicleRentalProviderPayable>> => {
            const response = await vehicleRentalApi.providerPayables.list();
            return { data: response.data.find((payable) => payable.id === id) ?? response.data[0] };
        },
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleRentalProviderPayable>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/provider-payables', { query: contextQuery(query) });
            return collection(response, normalizeProviderPayable);
        },
    },
    breakdowns: {
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/breakdowns', { body: contextPayload(record(input)), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<import('../types/vehicleRental.types').VehicleRentalBreakdown>> => ({ data: [] }),
    },
    replacements: {
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/replacements', { body: contextPayload(record(input)), method: 'POST' }),
        list: async (): Promise<ApiCollectionResponse<import('../types/vehicleRental.types').VehicleRentalReplacement>> => ({ data: [] }),
    },
    runningCharts: {
        createCombined: async (input: VehicleRentalRunningChartFormInput) => {
            const response = await httpClient<ApiResponse<{ lessee_running_chart: BackendRecord; lessor_running_chart: BackendRecord; margin_preview?: BackendRecord }>>('/api/vehicle-rental/running-charts/combined-entry', {
                body: runningChartPayload(input),
                method: 'POST',
            });
            return {
                ...response,
                data: {
                    lesseeRunningChart: normalizeRunningChart(record(response.data.lessee_running_chart)),
                    lessorRunningChart: normalizeRunningChart(record(response.data.lessor_running_chart)),
                    marginPreview: record(response.data.margin_preview),
                },
            };
        },
        get: async (id: string): Promise<ApiResponse<VehicleRentalRunningChart>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-rental/running-charts/${id}`);
            return { ...response, data: normalizeRunningChart(response.data) };
        },
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleRentalRunningChart>> => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-rental/running-charts', { query: contextQuery(query) });
            return collection(response, normalizeRunningChart);
        },
        transition: (id: string, status: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-rental/workflow/running-charts/${id}/transition`, { body: contextPayload({ actor_id: userId(), status }), method: 'POST' }),
    },
    settings: {
        get: async () => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>('/api/vehicle-rental/settings', { query: contextQuery() });
            return { ...response, data: normalizeSettings(record(response.data)) };
        },
        initialize: () => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/settings/initialize', { body: contextPayload(), method: 'POST' }),
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-rental/settings', { body: contextPayload(record(input)), method: 'POST' }),
    },
};
