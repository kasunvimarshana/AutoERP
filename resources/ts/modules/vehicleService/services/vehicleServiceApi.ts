import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { Dashboard, JobCard, JobCardInput, Lookup, Page, ServiceType } from '../types/vehicleService.types';

type RecordAny = Record<string, any>;

function money(value: unknown) { return String(value ?? '0.0000'); }
function mapServiceType(record: RecordAny): ServiceType {
    return { code: record.code ?? undefined, description: record.description ?? undefined, id: record.id, isActive: Boolean(record.is_active), name: record.name, standardHours: record.standard_hours ?? undefined };
}
function mapJob(record: RecordAny): JobCard {
    return {
        adjustmentTotal: money(record.adjustment_total),
        balance: money(record.balance),
        chargeTotal: money(record.charge_total),
        creditAdjustmentTotal: money(record.credit_adjustment_total),
        debitAdjustmentTotal: money(record.debit_adjustment_total),
        customerId: record.linked_customer_id,
        customerName: record.customer_name ?? undefined,
        discountTotal: money(record.discount_total),
        estimatedHours: record.estimated_hours ?? undefined,
        financeStatus: record.finance_status,
        grandTotal: money(record.grand_total),
        grossTotal: money(record.gross_total),
        headerDiscountTotal: money(record.header_discount_total),
        headerTaxTotal: money(record.header_tax_total),
        id: record.id,
        inventoryStatus: record.inventory_status,
        invoiceLinks: record.invoice_links,
        invoiceStatus: record.invoice_status,
        jobCardNumber: record.job_card_number,
        laborItems: record.labor_items,
        laborSubtotal: money(record.labor_item_subtotal),
        lineDiscountTotal: money(record.line_discount_total),
        lineTaxTotal: money(record.line_tax_total),
        nonInventoryItems: record.non_inventory_items,
        nonInventorySubtotal: money(record.non_inventory_item_subtotal),
        notes: record.notes ?? undefined,
        paidAmount: money(record.paid_amount),
        parts: record.parts,
        partsSubtotal: money(record.parts_subtotal),
        paymentStatus: record.payment_status,
        payments: record.payments,
        priority: record.priority,
        promisedDeliveryDateTime: record.promised_delivery_date_time ?? undefined,
        reference: record.reference ?? undefined,
        registrationNumber: record.registration_number ?? undefined,
        reportedIssue: record.reported_issue ?? undefined,
        resolutionNotes: record.resolution_notes ?? undefined,
        serviceTypeId: record.service_type_id ?? undefined,
        serviceTypeName: record.service_type_name ?? undefined,
        startOdometer: record.start_odometer ?? undefined,
        status: record.status,
        subtotal: money(record.subtotal),
        taxTotal: money(record.tax_total),
        vehicleId: record.vehicle_id,
        warehouseId: record.warehouse_id,
        warehouseName: record.warehouse_name ?? undefined,
    };
}
function page<T>(response: ApiCollectionResponse<RecordAny>, currentPage: number, perPage: number, mapper: (record: RecordAny) => T): Page<T> {
    return { items: response.data.map(mapper), meta: { currentPage: response.meta?.current_page ?? currentPage, lastPage: response.meta?.last_page ?? 1, perPage: response.meta?.per_page ?? perPage, total: response.meta?.total ?? response.data.length } };
}
function line(line: any) {
    return {
        actual_hours: line.actualHours || null,
        description: line.description || null,
        discount_type: line.discountType || null,
        discount_value: line.discountValue || '0',
        employee_id: line.employeeId || null,
        item_id: line.itemId || null,
        quantity: line.quantity,
        tax_amount: line.taxAmount || '0',
        unit_cost: line.unitCost || null,
        unit_price: line.unitPrice,
        uom_id: line.uomId,
        warehouse_id: line.warehouseId || null,
    };
}
function payload(input: JobCardInput) {
    return {
        estimated_hours: input.estimatedHours || null,
        header_adjustment_amount: input.headerAdjustmentAmount || '0',
        header_adjustment_effect: input.headerAdjustmentEffect || 'add',
        header_charge_amount: input.headerChargeAmount || '0',
        header_discount_type: input.headerDiscountType || null,
        header_discount_value: input.headerDiscountValue || '0',
        header_tax_amount: input.headerTaxAmount || '0',
        job_card_number: input.jobCardNumber || null,
        labor_items: input.laborItems.map(line),
        linked_customer_id: input.customerId,
        non_inventory_items: input.nonInventoryItems.map((item) => ({ ...line(item), item_id: undefined, name: item.name })),
        notes: input.notes || null,
        parts: input.parts.map(line),
        priority: input.priority,
        promised_delivery_date_time: input.promisedDeliveryDateTime || null,
        reference: input.reference || null,
        reported_issue: input.reportedIssue || null,
        resolution_notes: input.resolutionNotes || null,
        service_type_id: input.serviceTypeId || null,
        start_odometer: input.startOdometer || null,
        vehicle_id: input.vehicleId,
        warehouse_id: input.warehouseId,
    };
}

export const vehicleServiceApi = {
    async dashboard() { const response = await httpClient<ApiResponse<Dashboard>>('/api/vehicle-service/dashboard'); return response.data; },
    async lookup(type: string, search?: string) { const response = await httpClient<ApiResponse<Lookup[]>>(`/api/vehicle-service/lookups/${type}`, { query: { limit: 100, search } }); return response.data; },
    async listServiceTypes(query: { page: number; perPage: number; search?: string }) { const response = await httpClient<ApiCollectionResponse<RecordAny>>('/api/vehicle-service/service-types', { query: { page: query.page, per_page: query.perPage, search: query.search } }); return page(response, query.page, query.perPage, mapServiceType); },
    async createServiceType(input: Omit<ServiceType, 'id'>) { const response = await httpClient<ApiResponse<RecordAny>>('/api/vehicle-service/service-types', { body: { code: input.code || null, description: input.description || null, is_active: input.isActive, name: input.name, standard_hours: input.standardHours || null }, method: 'POST' }); return mapServiceType(response.data); },
    async updateServiceType(id: number, input: Omit<ServiceType, 'id'>) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/service-types/${id}`, { body: { code: input.code || null, description: input.description || null, is_active: input.isActive, name: input.name, standard_hours: input.standardHours || null }, method: 'PUT' }); return mapServiceType(response.data); },
    async removeServiceType(id: number) { await httpClient<void>(`/api/vehicle-service/service-types/${id}`, { method: 'DELETE' }); },
    async listJobs(query: { page: number; perPage: number; search?: string; status?: string }) { const response = await httpClient<ApiCollectionResponse<RecordAny>>('/api/vehicle-service/job-cards', { query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status } }); return page(response, query.page, query.perPage, mapJob); },
    async getJob(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}`); return mapJob(response.data); },
    async createJob(input: JobCardInput) { const response = await httpClient<ApiResponse<RecordAny>>('/api/vehicle-service/job-cards', { body: payload(input), method: 'POST' }); return mapJob(response.data); },
    async updateJob(id: number, input: JobCardInput) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}`, { body: payload(input), method: 'PUT' }); return mapJob(response.data); },
    async startJob(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}/start`, { method: 'POST' }); return mapJob(response.data); },
    async completeJob(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}/complete`, { method: 'POST' }); return mapJob(response.data); },
    async cancelJob(id: number, reason?: string) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}/cancel`, { body: { reason }, method: 'POST' }); return mapJob(response.data); },
    async invoiceJob(id: number) { const response = await httpClient<ApiResponse<RecordAny>>(`/api/vehicle-service/job-cards/${id}/invoice`, { method: 'POST' }); return response.data; },
    async removeJob(id: number) { await httpClient<void>(`/api/vehicle-service/job-cards/${id}`, { method: 'DELETE' }); },
};
