import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type { DashboardDateRange, DashboardSummary } from './dashboardTypes';

export async function getDashboardSummary(range: DashboardDateRange, signal?: AbortSignal): Promise<DashboardSummary> {
    const response = await apiClient.get<ApiResource<DashboardSummary>>(`${endpoints.reports}/dashboard`, {
        params: range,
        signal,
    });

    return response.data.data;
}
