import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import { requestLookup } from '@/shared/api/lookupRequest';
import type { ApiResource } from '@/shared/types/api';
import type { PurchaseOrderDetailResult } from './purchaseOrderDetailTypes';
import type { EmployeeCommissionJobsResult } from './employeeReportResultTypes';
import type { GoodsReceiptSummaryParams, GoodsReceiptSummaryResult } from './goodsReceiptSummaryTypes';
import type { StockBalanceDetail, StockBalanceReportParams, StockBalanceReportResult, StockBalanceReportRow } from './stockBalanceReportTypes';
import type { PaymentMethodWiseDay } from './paymentMethodWiseSummaryTypes';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type {
    EmployeeCommissionReportParams,
    OperationalReportParams,
    OperationalReportResult,
    EmployeeCommissionReportResult,
    ReportDefinition,
    ReportFormat,
    ReportParams,
    ReportResult,
    TechnicianWorkReportParams,
    TechnicianWorkReportResult,
    SummaryReportResult,
    GrnPayablesReportParams,
    GrnPayablesReportResult,
    VehicleServiceHistoryParams,
    VehicleServiceHistoryResult,
    ExpenseReportParams,
    ExpenseReportResult,
    VehicleServiceSalesSummaryParams,
    VehicleServiceSalesSummaryResult,
    PaymentMethodWiseParams,
    PaymentMethodWiseResult,
} from './reportingTypes';

export async function listReports(signal?: AbortSignal): Promise<ReportDefinition[]> {
    const response = await apiClient.get<ApiResource<ReportDefinition[]>>(endpoints.reports, { signal });
    return response.data.data;
}

export function searchAgingWarehouses(params: LookupLoadParams): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(`${endpoints.reports}/inventory-aging/warehouses`, params);
}

export function searchAgingLocations(params: LookupLoadParams, warehouseId?: number): Promise<LookupResult<NamedResource>> {
    return requestLookup<NamedResource>(`${endpoints.reports}/inventory-aging/locations`, params, { warehouse_id: warehouseId });
}

export async function getReport(key: string, signal?: AbortSignal): Promise<ReportDefinition> {
    const response = await apiClient.get<ApiResource<ReportDefinition>>(`${endpoints.reports}/${key}`, { signal });
    return response.data.data;
}

export async function runReport(key: string, params: ReportParams, signal?: AbortSignal): Promise<ReportResult> {
    const response = await apiClient.get<ReportResult>(`${endpoints.reports}/${key}/run`, { params, signal });
    return response.data;
}

export async function exportReport(
    key: string,
    format: ReportFormat,
    params: ReportParams | TechnicianWorkReportParams | EmployeeCommissionReportParams | OperationalReportParams | GrnPayablesReportParams | GoodsReceiptSummaryParams | VehicleServiceHistoryParams | VehicleServiceSalesSummaryParams | ExpenseReportParams | PaymentMethodWiseParams,
    previewWindow?: Window | null,
): Promise<void> {
    const response = await apiClient.get<Blob>(`${endpoints.reports}/${key}/export/${format}`, {
        params,
        responseType: 'blob',
    });
    const url = URL.createObjectURL(response.data);

    if (format === 'print' || format === 'html') {
        if (!previewWindow || previewWindow.closed) {
            URL.revokeObjectURL(url);
            throw new Error('The report preview window was blocked. Allow popups for AutoERP and try again.');
        }

        previewWindow.opener = null;
        previewWindow.location.replace(url);
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
        return;
    }

    const link = document.createElement('a');
    link.href = url;
    link.download = responseFilename(response.headers['content-disposition'])
        ?? `${key.replaceAll('/', '-').replaceAll('.', '-')}.${format}`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1_000);
}

function responseFilename(contentDisposition?: string): string | null {
    if (!contentDisposition) return null;

    const encodedMatch = contentDisposition.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
    if (encodedMatch?.[1]) {
        try {
            return sanitizeFilename(decodeURIComponent(encodedMatch[1].trim().replace(/^"|"$/g, '')));
        } catch {
            // Fall through to the plain filename parameter.
        }
    }

    const plainMatch = contentDisposition.match(/filename\s*=\s*(?:"([^"]+)"|([^;]+))/i);
    const filename = plainMatch?.[1] ?? plainMatch?.[2];
    return filename ? sanitizeFilename(filename.trim()) : null;
}

function sanitizeFilename(filename: string): string | null {
    const sanitized = filename
        .replace(/[\\/:*?"<>|\u0000-\u001F]/g, '_')
        .replace(/^\.+/, '')
        .trim();

    return sanitized !== '' ? sanitized.slice(0, 240) : null;
}

export async function runTechnicianWorkReport(params: TechnicianWorkReportParams, signal?: AbortSignal): Promise<TechnicianWorkReportResult> {
    const response = await apiClient.get<TechnicianWorkReportResult>(`${endpoints.reports}/vehicle-service/technician-work`, { params, signal });
    return response.data;
}

export async function runEmployeeCommissionReport(params: EmployeeCommissionReportParams, signal?: AbortSignal): Promise<EmployeeCommissionReportResult> {
    const response = await apiClient.get<EmployeeCommissionReportResult>(`${endpoints.reports}/vehicle-service/employee-commissions`, { params, signal });
    return response.data;
}

export async function getEmployeeCommissionJobs(employeeId: number, params: EmployeeCommissionReportParams, signal?: AbortSignal): Promise<EmployeeCommissionJobsResult> {
    const response = await apiClient.get<EmployeeCommissionJobsResult>(`${endpoints.reports}/vehicle-service/employee-commissions/employees/${employeeId}/jobs`, { params, signal });
    return response.data;
}

export async function runVehicleServiceHistoryReport(params: VehicleServiceHistoryParams, signal?: AbortSignal): Promise<VehicleServiceHistoryResult> {
    const response = await apiClient.get<VehicleServiceHistoryResult>(`${endpoints.reports}/vehicle-service/service-history`, { params, signal });
    return response.data;
}

export async function runVehicleServiceSalesSummaryReport(params: VehicleServiceSalesSummaryParams, signal?: AbortSignal): Promise<VehicleServiceSalesSummaryResult> {
    const response = await apiClient.get<VehicleServiceSalesSummaryResult>(`${endpoints.reports}/vehicle-service/sales-summary`, { params, signal });
    return response.data;
}

export async function runOperationalReport(key: string, params: OperationalReportParams, signal?: AbortSignal): Promise<OperationalReportResult> {
    const response = await apiClient.get<OperationalReportResult>(`${endpoints.reports}/${key}`, { params, signal });
    return response.data;
}

export async function runPurchaseOrderDetailReport(params: OperationalReportParams, signal?: AbortSignal): Promise<PurchaseOrderDetailResult> {
    const response = await apiClient.get<PurchaseOrderDetailResult>(`${endpoints.reports}/purchase/detailed`, { params, signal });
    return response.data;
}

export async function runGoodsReceiptSummaryReport(params: GoodsReceiptSummaryParams, signal?: AbortSignal): Promise<GoodsReceiptSummaryResult> {
    const response = await apiClient.get<GoodsReceiptSummaryResult>(`${endpoints.reports}/purchase/goods-receipt-summary`, { params, signal });
    return response.data;
}

export async function runGrnPayablesReport(
    params: GrnPayablesReportParams,
    signal?: AbortSignal,
): Promise<GrnPayablesReportResult> {
    const response = await apiClient.get<GrnPayablesReportResult>(`${endpoints.reports}/purchase/grn-payables`, {
        params,
        signal,
    });

    return response.data;
}

export async function runSummaryReport(
    params: { date_from: string; date_to: string },
    signal?: AbortSignal,
): Promise<SummaryReportResult> {
    const response = await apiClient.get<ApiResource<SummaryReportResult>>(`${endpoints.reports}/summary`, {
        params,
        signal,
    });

    return response.data.data;
}

export async function runPaymentMethodWiseSummaryReport(
    params: PaymentMethodWiseParams,
    signal?: AbortSignal,
): Promise<PaymentMethodWiseResult> {
    const response = await apiClient.get<ApiResource<PaymentMethodWiseResult>>(`${endpoints.reports}/payment-method-wise-summary`, { params, signal });
    return response.data.data;
}

export async function runStockBalanceReport(params: StockBalanceReportParams, signal?: AbortSignal): Promise<StockBalanceReportResult> {
    const response = await apiClient.get<StockBalanceReportResult>(`${endpoints.reports}/inventory.stock-balance/run`, { params, signal });
    return response.data;
}

export async function loadStockBalanceDetails(row: StockBalanceReportRow, signal?: AbortSignal): Promise<StockBalanceDetail[]> {
    const response = await apiClient.get<ApiResource<StockBalanceDetail[]>>(`${endpoints.reports}/inventory.stock-balance/details`, {
        params: {
            item_id: row.item_id, item_variant_id: row.item_variant_id ?? undefined,
            base_uom_id: row.base_uom_id ?? undefined, warehouse_id: row.warehouse_id,
            location_id: row.location_id ?? undefined,
        }, signal,
    });
    return response.data.data;
}

export async function loadPaymentMethodWiseDocuments(
    params: PaymentMethodWiseParams,
    date: string,
    signal?: AbortSignal,
): Promise<PaymentMethodWiseDay[]> {
    const response = await apiClient.get<ApiResource<PaymentMethodWiseDay[]>>(`${endpoints.reports}/payment-method-wise-summary/documents`, {
        params: { ...params, date_from: date, date_to: date }, signal,
    });
    return response.data.data;
}

export async function runExpenseReport(
    params: ExpenseReportParams,
    signal?: AbortSignal,
): Promise<ExpenseReportResult> {
    const response = await apiClient.get<ExpenseReportResult>(`${endpoints.reports}/expenses`, { params, signal });

    return response.data;
}
