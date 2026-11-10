import type { ChartRegisterFilters, ChartRegisterRow } from './runningCharts';
import { apiClient } from '@/shared/api/apiClient';
import { exportReportBlob } from '@/shared/utils/reportExport';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { PAGE_SIZE } from './agreements';
import { USE_API, type VehicleUse } from './vehicleUse';
import { CHART_API, type ChartFacts, type ChartHistory, type RunningChart, type RunningChartAction } from './runningCharts';
export const listCharts = (use: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<RunningChart>>(`${USE_API}/${use}/running-charts`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
export const getChart = (id: number, signal?: AbortSignal) => apiClient.get<ApiResource<RunningChart>>(`${CHART_API}/${id}`, { signal }).then(r => r.data.data);
export const createChart = (use: VehicleUse, facts: ChartFacts, corrects?: number) => apiClient.post<ApiResource<RunningChart>>(`${USE_API}/${use.id}/running-charts`, { ...facts, expected_version: use.row_version, corrects_chart_id: corrects ?? null }).then(r => r.data.data);
export const updateChart = (chart: RunningChart, facts: ChartFacts) => apiClient.put<ApiResource<RunningChart>>(`${CHART_API}/${chart.id}`, { ...facts, expected_version: chart.row_version }).then(r => r.data.data);
export const deleteChart = (chartId: number) => apiClient.delete(`${CHART_API}/${chartId}`).then(r => r.data);
export const transitionChart = (chart: RunningChart, action: RunningChartAction, reason?: string) => apiClient.post<ApiResource<RunningChart>>(`${CHART_API}/${chart.id}/${action}`, { expected_version: chart.row_version, reason }).then(r => r.data.data);
export const chartHistory = (chart: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<ChartHistory>>(`${CHART_API}/${chart}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export const listChartRegister = (filters: ChartRegisterFilters, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<ChartRegisterRow>>(CHART_API, { params: { ...filters, page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export interface RunningChartReportParams {
    agreement_id: number;
    start_date: string;
    end_date: string;
    vehicle_use_id?: number | null;
    sheet_mode?: 'lessee' | 'owner';
}

export interface RunningChartReportData {
    header: {
        company_name: string;
        report_title: string;
        rental_mode: string;
        client_name: string;
        duration: string;
        start_date: string;
        end_date: string;
        page: number;
        report_date: string;
        agreement_number: string;
        agreement_id: number;
        agreement_vehicle_number: string;
        is_owner_sheet?: boolean;
        lessor_id?: string;
        owner_name?: string;
    };
    rows: Array<{
        date: string;
        raw_date: string;
        start_odometer: string;
        finish_odometer: string;
        comparative_mileage: number;
        cumulative_mileage: number;
        on_time: string;
        off_time: string;
        lessee?: string;
        ot_hours: string;
        ot_type: string;
        night_out: string;
        pass: string;
        garage_km: string;
        remarks: string;
        driver_name: string;
        is_active_day: boolean;
    }>;
    summary: {
        total_comparative_mileage: number;
        final_cumulative_mileage: number;
        total_normal_ot_hours: string;
        total_double_ot_hours: string;
        total_triple_ot_hours: string;
        total_ot_hours: string;
        total_night_out: number;
        total_garage_km: number;
        total_pass: string;
        included_km: number;
        excess_km: number;
    };
    footer: {
        software_provider: string;
    };
}

export const fetchRunningChartReport = (params: RunningChartReportParams, signal?: AbortSignal) =>
    apiClient.get<RunningChartReportData>('/api/v1/vehicle-rental/reports/running-chart-sheet', { params, signal }).then(r => r.data);

export const getRunningChartReportExportUrl = (params: RunningChartReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        agreement_id: String(params.agreement_id),
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.vehicle_use_id ? { vehicle_use_id: String(params.vehicle_use_id) } : {}),
        ...(params.sheet_mode ? { sheet_mode: params.sheet_mode } : {}),
    });
    return `/api/v1/vehicle-rental/reports/running-chart-sheet/export/${format}?${query.toString()}`;
};

export const exportRunningChartReport = (
    params: RunningChartReportParams,
    format: 'pdf' | 'print' | 'html',
    previewWindow?: Window | null,
) => {
    const filename = `running-chart-${params.agreement_id}-${params.start_date}-to-${params.end_date}.${format === 'pdf' ? 'pdf' : 'html'}`;
    return exportReportBlob({
        url: `/api/v1/vehicle-rental/reports/running-chart-sheet/export/${format}`,
        params: {
            agreement_id: params.agreement_id,
            start_date: params.start_date,
            end_date: params.end_date,
            ...(params.vehicle_use_id ? { vehicle_use_id: params.vehicle_use_id } : {}),
            ...(params.sheet_mode ? { sheet_mode: params.sheet_mode } : {}),
        },
        format,
        previewWindow,
        filename,
    });
};
