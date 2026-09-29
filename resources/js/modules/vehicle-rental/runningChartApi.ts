import type { ChartRegisterFilters, ChartRegisterRow } from './runningCharts';
import { apiClient } from '@/shared/api/apiClient';
import type { ApiCollection, ApiResource } from '@/shared/types/api';
import { PAGE_SIZE } from './agreements';
import { USE_API, type VehicleUse } from './vehicleUse';
import { CHART_API, type ChartFacts, type ChartHistory, type RunningChart, type RunningChartAction } from './runningCharts';
export const listCharts = (use: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<RunningChart>>(`${USE_API}/${use}/running-charts`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
export const createChart = (use: VehicleUse, facts: ChartFacts, corrects?: number) => apiClient.post<ApiResource<RunningChart>>(`${USE_API}/${use.id}/running-charts`, { ...facts, expected_version: use.row_version, corrects_chart_id: corrects ?? null }).then(r => r.data.data);
export const updateChart = (chart: RunningChart, facts: ChartFacts) => apiClient.put<ApiResource<RunningChart>>(`${CHART_API}/${chart.id}`, { ...facts, expected_version: chart.row_version }).then(r => r.data.data);
export const transitionChart = (chart: RunningChart, action: RunningChartAction, reason?: string) => apiClient.post<ApiResource<RunningChart>>(`${CHART_API}/${chart.id}/${action}`, { expected_version: chart.row_version, reason }).then(r => r.data.data);
export const chartHistory = (chart: number, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<ChartHistory>>(`${CHART_API}/${chart}/history`, { params: { page, per_page: PAGE_SIZE }, signal }).then(r => r.data);

export const listChartRegister = (filters: ChartRegisterFilters, page: number, signal?: AbortSignal) => apiClient.get<ApiCollection<ChartRegisterRow>>(CHART_API, { params: { ...filters, page, per_page: PAGE_SIZE }, signal }).then(r => r.data);
