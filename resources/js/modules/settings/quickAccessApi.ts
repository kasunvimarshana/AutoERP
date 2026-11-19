import { apiClient } from '@/shared/api/apiClient';
import type { QuickAccessItemConfig } from './quickAccessService';

export interface QuickAccessApiResponse {
    items: QuickAccessItemConfig[];
    is_default: boolean;
    row_version: number | null;
}

export async function fetchQuickAccessSettings(signal?: AbortSignal): Promise<QuickAccessApiResponse> {
    const response = await apiClient.get<{ data: QuickAccessApiResponse }>('/api/v1/user/quick-access', {
        signal,
    });
    return response.data.data;
}

export async function saveQuickAccessSettings(
    items: QuickAccessItemConfig[],
    rowVersion?: number | null,
    signal?: AbortSignal,
): Promise<QuickAccessApiResponse> {
    const response = await apiClient.put<{ data: QuickAccessApiResponse }>(
        '/api/v1/user/quick-access',
        {
            items,
            row_version: rowVersion ?? undefined,
        },
        { signal },
    );
    return response.data.data;
}

export async function resetQuickAccessSettings(signal?: AbortSignal): Promise<QuickAccessApiResponse> {
    const response = await apiClient.delete<{ data: QuickAccessApiResponse }>(
        '/api/v1/user/quick-access',
        { signal },
    );
    return response.data.data;
}
