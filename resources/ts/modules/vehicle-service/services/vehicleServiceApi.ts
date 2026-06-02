import type { ApiCollectionResponse, ApiPreviewResponse, ApiResponse } from '../../../services/api/apiResponse';
import { getStoredAuthSession, getStoredOrganizationUnitId, getStoredTenantId } from '../../../services/api/authTokenStorage';
import { httpClient } from '../../../services/api/httpClient';
import { customerApi } from '../../customer/services/customerApi';
import { hrApi } from '../../hr/services/hrApi';
import { inventoryApi } from '../../inventory/services/inventoryApi';
import { itemApi } from '../../item/services/itemApi';
import { vehicleApi } from '../../vehicle/services/vehicleApi';
import type {
    VehicleServiceAuditEntry,
    VehicleServiceCalculationPreview,
    VehicleServiceDashboardMetric,
    VehicleServiceDiagnostic,
    VehicleServiceInspection,
    VehicleServiceInvoice,
    VehicleServiceJobCard,
    VehicleServiceJobCardFormInput,
    VehicleServiceJobCardLine,
    VehicleServiceJobCardLineFormInput,
    VehicleServiceLookupOption,
    VehicleServicePayment,
    VehicleServicePaymentFormInput,
    VehicleServiceSettings,
    VehicleServiceStockAvailabilityPreview,
    VehicleServiceType,
} from '../types/vehicleService.types';

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

function metaTotal<T>(response: ApiCollectionResponse<T>): number {
    return Number(response.meta?.total ?? response.data.length);
}

function collection<T>(response: ApiCollectionResponse<BackendRecord>, mapper: (row: BackendRecord) => T): ApiCollectionResponse<T> {
    const data = response.data.map(mapper);
    return { ...response, data, meta: collectionMeta({ ...response, data }) };
}

function labelFrom(raw: BackendRecord, prefix: string, fallback: string): string {
    const nested = record(raw[prefix]);
    const direct = asString(raw[`${prefix}_label`]);
    const code = asString(nested.code ?? nested.sku ?? raw[`${prefix}_code`] ?? raw[`${prefix}_number`]);
    const name = asString(nested.display_name ?? nested.name ?? raw[`${prefix}_name`]);

    if (direct) {
        return direct;
    }

    if (code && name) {
        return `${code} - ${name}`;
    }

    return name || code || fallback;
}

function simpleOption(id: unknown, label: unknown, secondary?: unknown): VehicleServiceLookupOption {
    return {
        id: asString(id),
        label: asString(label, `#${asString(id)}`),
        secondary: optionalString(secondary),
    };
}

function normalizeServiceType(raw: BackendRecord): VehicleServiceType {
    return {
        code: asString(raw.code ?? raw.service_type_code, `TYPE-${asString(raw.id)}`),
        description: asString(raw.description),
        id: asString(raw.id),
        name: asString(raw.name, 'Service type'),
        status: asString(raw.status, raw.is_active === false ? 'inactive' : 'active').toLowerCase() as VehicleServiceType['status'],
        updatedAt: asString(raw.updated_at),
    };
}

function normalizeSettings(raw: BackendRecord): VehicleServiceSettings {
    return {
        _raw: raw,
        allowCustomerSuppliedItems: asBoolean(raw.allow_customer_supplied_items, true),
        allowExternalServices: asBoolean(raw.allow_external_services, true),
        allowNegativeStock: asBoolean(raw.allow_negative_stock_for_service),
        defaultTaxGroup: asString(raw.default_tax_group_label ?? raw.default_tax_group_id, 'Not configured'),
        defaultWarehouse: asString(raw.default_warehouse_label ?? raw.default_warehouse_id, 'Not configured'),
        documentDefinition: asString(raw.service_invoice_document_definition_label ?? raw.service_invoice_document_definition_id ?? raw.service_invoice_document_type_code, 'Not configured'),
        invoiceSequence: asString(raw.invoice_sequence_label ?? raw.invoice_sequence_code ?? raw.service_invoice_document_type_code, 'Not configured'),
        jobCardSequence: asString(raw.job_card_sequence_label ?? raw.job_card_sequence_code ?? raw.service_number_prefix, 'Not configured'),
        stockConsumptionTiming: asString(raw.inventory_posting_trigger_status, 'Backend workflow'),
    };
}

function normalizeLine(raw: BackendRecord, index = 0): VehicleServiceJobCardLine {
    const lineType = asString(raw.line_type, raw.is_customer_supplied ? 'customer_supplied' : raw.is_external_service ? 'external_service' : 'spare_part');

    return {
        backendCalculatedAmount: decimal(raw.line_total_with_tax ?? raw.line_total),
        description: asString(raw.description),
        discountPreview: decimal(raw.discount_amount),
        id: asString(raw.id, `line-${index}`),
        invoiceable: raw.invoiceable !== false,
        item: labelFrom(raw, 'item', asString(raw.item_id, 'Item')),
        itemId: optionalString(raw.item_id),
        lineType: lineType === 'inventory' ? 'spare_part' : lineType as VehicleServiceJobCardLine['lineType'],
        quantity: decimal(raw.quantity),
        stockBehavior: raw.requires_stock_movement === false ? 'No stock impact' : asString(raw.inventory_status, 'Stock tracked by backend'),
        taxPreview: decimal(raw.tax_amount),
        unitPrice: decimal(raw.unit_price),
        uom: labelFrom(raw, 'uom', asString(raw.uom_id, 'UOM')),
        uomId: optionalString(raw.uom_id),
    };
}

function normalizeDiagnostic(raw: BackendRecord): VehicleServiceDiagnostic {
    return {
        diagnosticNumber: asString(raw.diagnostic_number ?? raw.reference, `Diagnostic #${asString(raw.id)}`),
        findings: asString(raw.findings),
        id: asString(raw.id),
        jobCardId: asString(raw.job_card_id),
        phase: asString(raw.phase, 'diagnosis'),
        recommendation: asString(raw.recommendation),
        status: asString(raw.status, 'draft'),
    };
}

function normalizeInspection(raw: BackendRecord): VehicleServiceInspection {
    return {
        id: asString(raw.id),
        inspectionNumber: asString(raw.inspection_number ?? raw.reference, `Inspection #${asString(raw.id)}`),
        jobCardId: asString(raw.job_card_id),
        notes: asString(raw.notes),
        phase: asString(raw.phase, 'inspection'),
        result: asString(raw.result, 'pending'),
        status: asString(raw.status, 'draft'),
    };
}

function normalizeAudit(raw: BackendRecord): VehicleServiceAuditEntry {
    return {
        actor: asString(raw.changed_by_name ?? raw.actor ?? raw.changed_by, 'System'),
        id: asString(raw.id),
        note: asString(raw.reason ?? raw.workflow_action ?? raw.to_status ?? 'Vehicle service activity'),
        timestamp: asString(raw.changed_at ?? raw.created_at),
        type: asString(raw.workflow_action ?? raw.type, 'activity'),
    };
}

function normalizeInvoicePreview(raw: BackendRecord): VehicleServiceCalculationPreview {
    return {
        breakdown: [
            { label: 'Header discount', value: decimal(raw.header_discount_amount) },
            { label: 'Header tax', value: decimal(raw.header_tax_amount) },
            { label: 'Lines previewed', value: String(Object.values(record(raw)).filter(Array.isArray).flat().length) },
        ],
        calculated: {
            discountTotal: decimal(raw.discount_total),
            grandTotal: decimal(raw.grand_total),
            subtotal: decimal(raw.subtotal),
            taxTotal: decimal(raw.tax_total),
        },
        errors: [],
        input: raw,
        warnings: [],
    };
}

function emptyInvoicePreview(raw: BackendRecord): VehicleServiceCalculationPreview {
    return {
        breakdown: [
            { label: 'Stored parts subtotal', value: decimal(raw.subtotal) },
            { label: 'Stored labour subtotal', value: decimal(raw.labor_item_subtotal) },
            { label: 'Stored non-inventory subtotal', value: decimal(raw.non_inventory_item_subtotal) },
        ],
        calculated: {
            discountTotal: decimal(raw.discount_total),
            grandTotal: decimal(raw.grand_total),
            subtotal: decimal(Number(raw.subtotal ?? 0) + Number(raw.labor_item_subtotal ?? 0) + Number(raw.non_inventory_item_subtotal ?? 0)),
            taxTotal: decimal(raw.tax_total),
        },
        errors: [],
        input: raw,
        warnings: [],
    };
}

function normalizeJobCard(raw: BackendRecord, extras: Partial<VehicleServiceJobCard> = {}): VehicleServiceJobCard {
    const partyContext = record(raw.party_context);
    const currentStatus = asString(raw.status, 'open').toLowerCase() as VehicleServiceJobCard['status'];
    const lines = Array.isArray(raw.lines) ? raw.lines.map((line, index) => normalizeLine(record(line), index)) : extras.lines ?? [];

    return {
        audit: extras.audit ?? [],
        customer: labelFrom(raw, 'customer', asString(raw.linked_customer_id, 'Customer not selected')),
        customerComplaint: asString(raw.reported_issue),
        diagnostics: extras.diagnostics ?? [],
        documentPreview: extras.documentPreview ?? {
            documentNumber: asString(raw.job_card_number, `Job #${asString(raw.id)}`),
            status: asString(raw.invoice_status, 'pending'),
            template: 'Vehicle service job/invoice document',
        },
        expectedCompletion: asString(raw.promised_delivery_date_time),
        financePreview: {
            breakdown: [{ account: 'Finance posting', effect: asString(raw.finance_status, 'draft') }],
            calculated: {
                arImpact: decimal(raw.balance),
                eligibility: currentStatus === 'completed' || currentStatus === 'invoiced' ? 'Eligible' : 'Requires workflow completion',
                journalStatus: asString(raw.finance_status, 'draft'),
            },
            errors: [],
            input: raw,
            warnings: [],
        },
        id: asString(raw.id),
        initialDiagnosis: asString(raw.technician_notes ?? raw.resolution_notes),
        inspections: extras.inspections ?? [],
        invoicePreview: extras.invoicePreview ?? emptyInvoicePreview(raw),
        jobCardNumber: asString(raw.job_card_number, `JC-${asString(raw.id)}`),
        labourAssignments: extras.labourAssignments ?? [],
        lines,
        nextServiceDate: asString(raw.next_service_date),
        odometer: asString(raw.start_odometer),
        openedAt: asString(raw.start_datetime ?? raw.received_at ?? raw.created_at),
        payments: extras.payments ?? [],
        partyContext: {
            billingCustomer: {
                id: optionalString(raw.billing_customer_id),
                name: asString(raw.billing_customer_name ?? raw.linked_customer_name ?? raw.linked_customer_id, 'Billing customer not selected'),
                type: asString(raw.billing_customer_type, 'customer') as VehicleServiceJobCard['partyContext']['billingCustomer']['type'],
            },
            mismatchNotice: asString(partyContext.mismatch_notice ?? partyContext.warnings),
            payer: {
                id: optionalString(raw.payer_id),
                name: asString(raw.payer_name ?? raw.billing_customer_name ?? raw.payer_id, 'Payer not selected'),
                type: asString(raw.payer_type, 'customer') as VehicleServiceJobCard['partyContext']['payer']['type'],
            },
            serviceCustomer: {
                id: optionalString(raw.service_customer_id ?? raw.linked_customer_id),
                name: asString(raw.service_customer_name ?? raw.linked_customer_name ?? raw.linked_customer_id, 'Service customer not selected'),
                type: asString(raw.service_customer_type, 'customer') as VehicleServiceJobCard['partyContext']['serviceCustomer']['type'],
            },
            vehicleOwner: {
                ownershipId: optionalString(raw.vehicle_ownership_id),
                ownershipRole: asString(record(partyContext.vehicle_owner).ownership_role ?? partyContext.ownership_role, 'Owner role not resolved'),
                ownershipType: asString(record(partyContext.vehicle_owner).ownership_type ?? partyContext.ownership_type, 'Owner type not resolved'),
                owner: {
                    id: optionalString(raw.vehicle_owner_id),
                    name: asString(raw.vehicle_owner_name, 'Vehicle owner not resolved'),
                    type: asString(raw.vehicle_owner_type, 'company') as VehicleServiceJobCard['partyContext']['vehicleOwner']['owner']['type'],
                },
            },
        },
        serviceAdvisor: labelFrom(raw, 'service_advisor', asString(raw.created_by ?? 'Not assigned')),
        serviceType: labelFrom(raw, 'service_type', asString(raw.service_type_id, 'Service type not selected')),
        sourceReference: {
            sourceId: optionalString(raw.source_id),
            sourceModule: asString(raw.source_module, 'vehicle_service'),
            sourceNumber: optionalString(raw.reference),
            sourceType: asString(raw.source_type, 'job_card'),
        },
        status: currentStatus,
        stockPreview: extras.stockPreview ?? {
            breakdown: [],
            calculated: {
                availabilityDecision: asString(raw.inventory_status, 'pending'),
                requestedQuantity: '0.0000',
                reservedQuantity: '0.0000',
                stockEffect: asString(raw.inventory_status, 'pending'),
            },
            errors: [],
            input: raw,
            warnings: [],
        },
        supervisor: labelFrom(raw, 'assigned_to_employee', asString(raw.assigned_to ?? 'Not assigned')),
        updatedAt: asString(raw.updated_at),
        vehicle: labelFrom(raw, 'vehicle', asString(raw.vehicle_id, 'Vehicle not selected')),
        workflowStatus: currentStatus,
    };
}

function normalizeInvoice(jobCard: VehicleServiceJobCard): VehicleServiceInvoice {
    return {
        billingCustomer: jobCard.partyContext.billingCustomer.name,
        documentStatus: jobCard.documentPreview.status,
        id: jobCard.id,
        invoiceNumber: jobCard.documentPreview.documentNumber || jobCard.jobCardNumber,
        jobCardNumber: jobCard.jobCardNumber,
        previewTotal: jobCard.invoicePreview.calculated.grandTotal,
        status: String(jobCard.workflowStatus) === 'invoiced' ? 'invoiced' : jobCard.documentPreview.status,
        updatedAt: jobCard.updatedAt,
    };
}

function normalizePaymentLink(raw: BackendRecord, jobCard?: VehicleServiceJobCard): VehicleServicePayment {
    return {
        allocationPreview: decimal(raw.allocated_amount ?? raw.amount),
        amount: decimal(raw.allocated_amount ?? raw.amount),
        id: asString(raw.id ?? raw.payment_id),
        method: asString(raw.method ?? 'Backend payment'),
        payer: jobCard?.partyContext.payer.name ?? asString(raw.payer_name ?? raw.payment_id, 'Payer'),
        paymentNumber: asString(raw.payment_number ?? raw.reference ?? raw.payment_id, `Payment #${asString(raw.payment_id ?? raw.id)}`),
        sourceInvoice: jobCard?.jobCardNumber ?? asString(raw.document_id ?? raw.job_card_id, 'Service job'),
        status: asString(raw.status, 'active'),
    };
}

function jobCardPayload(input: VehicleServiceJobCardFormInput): BackendRecord {
    return contextPayload({
        assigned_to: numberOrUndefined(input.supervisorId),
        billing_customer_id: numberOrUndefined(input.billingCustomerId),
        billing_customer_name: input.billingCustomerName || null,
        billing_customer_type: input.billingCustomerType || 'customer',
        header_discount_type: input.headerDiscountType || null,
        header_discount_value: input.headerDiscountValue ? Number(input.headerDiscountValue) : 0,
        header_tax_group_id: numberOrUndefined(input.headerTaxGroupId),
        job_card_number: input.jobCardNumber || `JC-${Date.now()}`,
        linked_customer_id: numberOrUndefined(input.serviceCustomerId),
        next_service_date: input.nextServiceDate || null,
        notes: input.notes || null,
        payer_id: numberOrUndefined(input.payerId),
        payer_name: input.payerName || null,
        payer_type: input.payerType || 'customer',
        price_list_id: numberOrUndefined(input.priceListId),
        priority: input.priority || 'medium',
        promised_delivery_date_time: input.expectedCompletion || null,
        received_at: input.receivedAt || null,
        reported_issue: input.customerComplaint || null,
        service_customer_id: numberOrUndefined(input.serviceCustomerId),
        service_customer_name: input.serviceCustomerName || null,
        service_customer_type: input.serviceCustomerType || 'customer',
        service_type_id: numberOrUndefined(input.serviceTypeId),
        start_datetime: input.openedAt || null,
        start_odometer: input.odometer ? Number(input.odometer) : null,
        status: input.status || 'open',
        technician_notes: input.initialDiagnosis || null,
        vehicle_id: numberOrUndefined(input.vehicleId),
        warehouse_id: numberOrUndefined(input.warehouseId),
        lines: input.lines.map(linePayload),
        labor_items: input.laborItems.map(linePayload),
        non_inventory_items: input.nonInventoryItems.map(linePayload),
    });
}

function linePayload(input: VehicleServiceJobCardLineFormInput): BackendRecord {
    return {
        account_id: numberOrUndefined(input.accountId),
        description: input.description || null,
        discount_type: input.discountType || null,
        discount_value: input.discountValue ? Number(input.discountValue) : 0,
        id: numberOrUndefined(input.id),
        item_id: Number(input.itemId),
        line_type: input.lineType === 'spare_part' ? 'inventory' : input.lineType,
        quantity: Number(input.quantity || 0),
        requires_stock_movement: input.requiresStockMovement,
        tax_group_id: numberOrUndefined(input.taxGroupId),
        unit_cost: input.unitCost ? Number(input.unitCost) : null,
        unit_price: Number(input.unitPrice || 0),
        uom_id: Number(input.uomId),
        warehouse_id: numberOrUndefined(input.warehouseId),
    };
}

async function safeCollection<T>(callback: () => Promise<ApiCollectionResponse<T>>): Promise<T[]> {
    try {
        return (await callback()).data;
    } catch {
        return [];
    }
}

async function safeResponse<T>(callback: () => Promise<ApiResponse<T>>, fallback: T): Promise<T> {
    try {
        return (await callback()).data;
    } catch {
        return fallback;
    }
}

export const vehicleServiceApi = {
    dashboard: {
        summary: async (): Promise<ApiCollectionResponse<VehicleServiceDashboardMetric>> => {
            const [jobCards, openJobs, invoiceableJobs, serviceTypes] = await Promise.all([
                vehicleServiceApi.jobCards.list({ per_page: 1 }),
                vehicleServiceApi.jobCards.list({ per_page: 1, status: 'open' }),
                vehicleServiceApi.jobCards.list({ invoice_status: 'invoiced', per_page: 1 }),
                vehicleServiceApi.serviceTypes.list({ per_page: 1 }),
            ]);

            return {
                data: [
                    { label: 'Job cards', tone: 'backend', value: String(metaTotal(jobCards)) },
                    { label: 'Open jobs', tone: 'open', value: String(metaTotal(openJobs)) },
                    { label: 'Invoiceable', tone: 'invoice', value: String(metaTotal(invoiceableJobs)) },
                    { label: 'Payments', tone: 'payment', value: 'Open payments' },
                    { label: 'Service types', tone: 'setup', value: String(metaTotal(serviceTypes)) },
                    { label: 'Backend routes', tone: 'real', value: 'Real API' },
                ],
            };
        },
    },
    diagnostics: {
        list: async (jobCardId: string): Promise<ApiCollectionResponse<VehicleServiceDiagnostic>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-diagnostics', { query: contextQuery({ job_card_id: jobCardId }) }), normalizeDiagnostic),
        upsert: (input: unknown) => httpClient<ApiResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-diagnostics', { body: contextPayload(record(input)), method: 'POST' }),
    },
    finance: {
        post: (jobCardId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/finance/post`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
        previewPosting: async (jobCardId: string) => {
            const jobCard = await vehicleServiceApi.jobCards.get(jobCardId);
            return { data: jobCard.data.financePreview };
        },
        reverse: (jobCardId: string, journalEntryId?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/finance/reverse`, { body: contextPayload({ actor_id: userId(), journal_entry_id: numberOrUndefined(journalEntryId) }), method: 'POST' }),
    },
    history: {
        list: async (): Promise<ApiCollectionResponse<VehicleServiceAuditEntry>> => {
            const jobCards = await vehicleServiceApi.jobCards.list({ per_page: 10 });
            const histories = await Promise.all(jobCards.data.map((jobCard) => vehicleServiceApi.jobCards.history(jobCard.id).then((response) => response.data)));

            return { data: histories.flat() };
        },
    },
    inspections: {
        list: async (jobCardId: string): Promise<ApiCollectionResponse<VehicleServiceInspection>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-inspections', { query: contextQuery({ job_card_id: jobCardId }) }), normalizeInspection),
        upsert: (input: unknown) => httpClient<ApiResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-inspections', { body: contextPayload(record(input)), method: 'POST' }),
    },
    invoices: {
        cancel: (invoiceId: string) => vehicleServiceApi.jobCards.transition(invoiceId, 'cancelled'),
        generate: (jobCardId: string, documentTypeId?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/invoice`, { body: contextPayload({ actor_id: userId(), document_type_id: numberOrUndefined(documentTypeId) }), method: 'POST' }),
        get: async (id: string): Promise<ApiResponse<VehicleServiceInvoice>> => {
            const jobCard = await vehicleServiceApi.jobCards.get(id);
            return { data: normalizeInvoice(jobCard.data) };
        },
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleServiceInvoice>> => {
            const response = await vehicleServiceApi.jobCards.list({ invoice_status: 'invoiced', per_page: query.per_page ?? 25, ...query });
            return { data: response.data.filter((jobCard) => jobCard.documentPreview.status !== 'pending' || jobCard.workflowStatus === 'invoiced').map(normalizeInvoice) };
        },
        preview: async (jobCardId: string): Promise<ApiPreviewResponse<unknown, VehicleServiceCalculationPreview['calculated']>> => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-service/job-cards/${jobCardId}/invoice-preview`, { body: contextPayload({}), method: 'POST' });
            const preview = normalizeInvoicePreview(response.data);
            return { breakdown: preview.breakdown, calculated: preview.calculated, errors: preview.errors, input: response.data, warnings: preview.warnings };
        },
        reverse: (invoiceId: string) => vehicleServiceApi.jobCards.transition(invoiceId, 'reversed'),
    },
    jobCards: {
        close: (id: string) => vehicleServiceApi.jobCards.transition(id, 'closed'),
        create: async (input: VehicleServiceJobCardFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/vehicle-service/job-cards/aggregate', { body: jobCardPayload(input), method: 'POST' });
            return { ...response, data: normalizeJobCard(response.data) };
        },
        get: async (id: string): Promise<ApiResponse<VehicleServiceJobCard>> => {
            const response = await httpClient<ApiResponse<BackendRecord> | BackendRecord>(`/api/vehicle-service/vehicle-service-job-cards/${id}`);
            const raw = 'data' in response && response.data ? response.data as BackendRecord : response as BackendRecord;
            const [lines, labourItems, assignments, diagnostics, inspections, audit, invoicePreview] = await Promise.all([
                vehicleServiceApi.lines.list(id),
                vehicleServiceApi.lines.listLaborItems(id),
                vehicleServiceApi.labour.listAssignments(id),
                safeCollection(() => vehicleServiceApi.diagnostics.list(id)),
                safeCollection(() => vehicleServiceApi.inspections.list(id)),
                safeCollection(() => vehicleServiceApi.jobCards.history(id)),
                safeResponse(async () => {
                    const preview = await vehicleServiceApi.invoices.preview(id);
                    return { data: { breakdown: preview.breakdown.map((row) => ({ label: asString(row.label), value: asString(row.value) })), calculated: preview.calculated, errors: preview.errors, input: preview.input as BackendRecord, warnings: preview.warnings } };
                }, emptyInvoicePreview(raw)),
            ]);

            return {
                data: normalizeJobCard(raw, {
                    audit,
                    diagnostics,
                    inspections,
                    invoicePreview,
                    labourAssignments: assignments.data,
                    lines: [...lines.data, ...labourItems.data],
                }),
            };
        },
        history: async (id: string): Promise<ApiCollectionResponse<VehicleServiceAuditEntry>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>(`/api/vehicle-service/status-history/job_card/${id}`, { query: contextQuery() }), normalizeAudit),
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleServiceJobCard>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-job-cards', { query: contextQuery(query) }), normalizeJobCard),
        ownerSummary: (vehicleId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicles/${vehicleId}/owner-summary`, { query: contextQuery() }),
        transition: (id: string, status: string, reason?: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${id}/transition`, { body: contextPayload({ actor_id: userId(), reason, status }), method: 'POST' }),
        update: async (id: string, input: VehicleServiceJobCardFormInput) => {
            const response = await httpClient<ApiResponse<BackendRecord>>(`/api/vehicle-service/job-cards/${id}/aggregate`, { body: jobCardPayload(input), method: 'PUT' });
            return { ...response, data: normalizeJobCard(response.data) };
        },
        validatePartyContext: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-service/party-context/validate', { body: contextPayload(record(input)), method: 'POST' }),
    },
    labour: {
        assign: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-labor-assignments', { body: contextPayload(record(input)), method: 'POST' }),
        listAssignments: async (jobCardId: string) => {
            const response = await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-labor-assignments', { query: contextQuery({ job_card_id: jobCardId }) });
            const laborItems = await vehicleServiceApi.lines.listLaborItems(jobCardId);
            const laborMap = new Map(laborItems.data.map((line) => [line.id, line.item]));
            const employees = await safeCollection(() => vehicleServiceApi.lookups.employees());
            const employeeMap = new Map(employees.map((employee) => [employee.id, employee.label]));
            return collection(response, (raw) => ({
                assignmentType: asString(raw.role ?? raw.split_type, 'Technician'),
                employee: employeeMap.get(asString(raw.employee_id)) ?? asString(raw.employee_id, 'Employee'),
                id: asString(raw.id),
                incentivePreview: decimal(raw.incentive_amount ?? raw.split_amount),
                labourItem: laborMap.get(asString(raw.labor_item_id)) ?? asString(raw.labor_item_id, 'Labour item'),
                status: asString(raw.status, 'assigned'),
            }));
        },
        remove: (id: string) => httpClient<void>(`/api/vehicle-service/vehicle-service-labor-assignments/${id}`, { method: 'DELETE' }),
        update: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-labor-assignments/${id}`, { body: contextPayload(record(input)), method: 'PUT' }),
    },
    lines: {
        list: async (jobCardId: string): Promise<ApiCollectionResponse<VehicleServiceJobCardLine>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-job-card-lines', { query: contextQuery({ job_card_id: jobCardId }) }), normalizeLine),
        listLaborItems: async (jobCardId: string): Promise<ApiCollectionResponse<VehicleServiceJobCardLine>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-labor-items', { query: contextQuery({ job_card_id: jobCardId }) }), (raw) => ({ ...normalizeLine(raw), lineType: 'labour' as const, stockBehavior: 'No stock impact' })),
        previewComboExpansion: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/item/items/preview-type-setup', { body: input, method: 'POST' }),
        syncLabour: (jobCardId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/labor-items/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        syncNonInventory: (jobCardId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/non-inventory-items/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        syncServiceItems: (jobCardId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/lines/sync`, { body: contextPayload(record(input)), method: 'POST' }),
        syncSpareParts: (jobCardId: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/job-cards/${jobCardId}/lines/sync`, { body: contextPayload(record(input)), method: 'POST' }),
    },
    lookups: {
        customers: async (): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await customerApi.listCustomers({ perPage: 25 });
            return { data: response.data.map((customer) => simpleOption(customer.id, `${customer.code} - ${customer.name}`, customer.status)) };
        },
        employees: async (): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await hrApi.employees.list({ perPage: 25, status: 'active' });
            return { data: response.data.map((employee) => simpleOption(employee.id, `${employee.code} - ${employee.displayName}`, employee.status)) };
        },
        itemUnits: async (itemId: string): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await itemApi.getItemUnits(itemId);
            return { data: response.data.filter((unit) => unit.id && unit.unit !== 'Not configured').map((unit) => simpleOption(unit.id, unit.unit, unit.purpose)) };
        },
        items: async (): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await itemApi.listItems({ perPage: 25, status: 'active' });
            return { data: response.data.map((item) => simpleOption(item.id, `${item.code} - ${item.name}`, item.itemType)) };
        },
        uoms: async (): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await inventoryApi.listUoms();
            return { data: response.data.map((uom) => simpleOption(uom.id, uom.secondary ? `${uom.secondary} - ${uom.label}` : uom.label)) };
        },
        vehicles: async (): Promise<ApiCollectionResponse<VehicleServiceLookupOption>> => {
            const response = await vehicleApi.list({ perPage: 25 });
            return { data: response.data.map((vehicle) => simpleOption(vehicle.id, `${vehicle.registrationNumber} - ${vehicle.make} ${vehicle.model}`, vehicle.status)) };
        },
        warehouses: inventoryApi.listWarehouses,
    },
    payments: {
        allocate: (jobCardId: string, input: VehicleServicePaymentFormInput) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/payments/allocate`, { body: contextPayload({ amount: Number(input.amount), document_id: numberOrUndefined(input.documentId), document_type: input.documentType || 'document', payment_id: numberOrUndefined(input.paymentId) }), method: 'POST' }),
        create: (input: VehicleServicePaymentFormInput) => vehicleServiceApi.payments.allocate(input.jobCardId, input),
        list: async (): Promise<ApiCollectionResponse<VehicleServicePayment>> => {
            const response = await httpClient<ApiResponse<{ job_cards?: BackendRecord[]; payment_links?: BackendRecord[] }>>('/api/vehicle-service/receivable-job-cards', { query: contextQuery() });
            const jobCards = (response.data.job_cards ?? []).map((row) => normalizeJobCard(row));
            const jobMap = new Map(jobCards.map((job) => [job.id, job]));
            const data = (response.data.payment_links ?? []).map((payment) => normalizePaymentLink(payment, jobMap.get(asString(payment.job_card_id))));
            return { data };
        },
        previewAllocation: (input: VehicleServicePaymentFormInput): ApiResponse<VehicleServicePayment> => ({
            data: {
                allocationPreview: 'Allocation will be validated by backend when submitted',
                amount: input.amount,
                id: 'preview',
                method: 'Existing payment allocation',
                payer: 'Backend payer',
                paymentNumber: input.paymentId ? `Payment #${input.paymentId}` : 'Select payment',
                sourceInvoice: input.documentId ? `Document #${input.documentId}` : `Job #${input.jobCardId}`,
                status: 'preview',
            },
        }),
    },
    serviceTypes: {
        activate: (id: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-types/${id}`, { body: { status: 'active' }, method: 'PUT' }),
        create: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-service/vehicle-service-types', { body: contextPayload(record(input)), method: 'POST' }),
        deactivate: (id: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-types/${id}`, { body: { status: 'inactive' }, method: 'PUT' }),
        list: async (query: Record<string, string | number | boolean | undefined> = {}): Promise<ApiCollectionResponse<VehicleServiceType>> => collection(await httpClient<ApiCollectionResponse<BackendRecord>>('/api/vehicle-service/vehicle-service-types', { query: contextQuery(query) }), normalizeServiceType),
        update: (id: string, input: unknown) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/vehicle-service-types/${id}`, { body: contextPayload(record(input)), method: 'PUT' }),
    },
    settings: {
        get: async () => {
            const response = await httpClient<ApiResponse<BackendRecord | null>>('/api/vehicle-service/settings', { query: contextQuery() });
            return { ...response, data: normalizeSettings(record(response.data)) };
        },
        initialize: () => httpClient<ApiResponse<unknown>>('/api/vehicle-service/settings/initialize', { body: contextPayload({}), method: 'POST' }),
        update: (input: unknown) => httpClient<ApiResponse<unknown>>('/api/vehicle-service/settings', { body: contextPayload(record(input)), method: 'POST' }),
    },
    stock: {
        postConsumption: (jobCardId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/inventory/post`, { body: contextPayload({ actor_id: userId() }), method: 'POST' }),
        previewAvailability: async (input: unknown): Promise<ApiPreviewResponse<unknown, VehicleServiceStockAvailabilityPreview['calculated']>> => {
            const safeInput = Object.fromEntries(
                Object.entries(record(input)).filter((entry): entry is [string, string | number | boolean | null | undefined] => (
                    ['string', 'number', 'boolean', 'undefined'].includes(typeof entry[1]) || entry[1] === null
                )),
            );
            const response = await httpClient<ApiResponse<BackendRecord>>('/api/vehicle-service/stock-availability', { query: contextQuery(safeInput) });
            return {
                breakdown: Object.entries(response.data).map(([label, value]) => ({ label, value })),
                calculated: {
                    availabilityDecision: asString(response.data.decision ?? response.data.status ?? 'Backend returned availability'),
                    requestedQuantity: decimal(response.data.requested_quantity ?? record(input).quantity),
                    reservedQuantity: decimal(response.data.reserved_quantity),
                    stockEffect: asString(response.data.stock_effect ?? response.data.available_quantity, 'Backend stock check'),
                },
                errors: [],
                input,
                warnings: Array.isArray(response.data.warnings) ? response.data.warnings.map(String) : [],
            };
        },
        reverseConsumption: (jobCardId: string) => httpClient<ApiResponse<unknown>>(`/api/vehicle-service/workflow/job-cards/${jobCardId}/inventory/post`, { body: contextPayload({ movement_type: 'return', actor_id: userId() }), method: 'POST' }),
    },
};
