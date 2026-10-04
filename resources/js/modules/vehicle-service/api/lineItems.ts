import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiCollection } from '@/shared/types/api';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import type { ItemLookupResource } from '@/shared/api/lookupApi';

const endpoint = `${endpoints.vehicleService}/job-line-items/lookup`;

export async function searchVehicleServiceLineItems(
    params: LookupLoadParams,
): Promise<LookupResult<ItemLookupResource>> {
    const response = await apiClient.get<ApiCollection<ItemLookupResource>>(endpoint, {
        params: {
            search: params.search,
            page: params.page,
            per_page: params.perPage,
        },
        signal: params.signal,
    });

    return {
        data: response.data.data,
        meta: response.data.meta,
    };
}
