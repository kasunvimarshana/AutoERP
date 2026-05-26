import { apiClient, unwrapPaginated } from '../../api/client';
import type { ApiPaginatedEnvelope, PaginatedResult } from '../../types/api';
import { toQuery } from '../shared/api';
import type { PriceListFilters, PriceListItemRecord, PriceListRecord } from './types';

export const pricingApi = {
    listPriceLists(filters: PriceListFilters): Promise<PaginatedResult<PriceListRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<PriceListRecord>>('/pricing/price-lists', { query: toQuery(filters) })
            .then((payload) => unwrapPaginated(payload));
    },
    listPriceListItems(priceListId: number, page = 1, perPage = 25): Promise<PaginatedResult<PriceListItemRecord>> {
        return apiClient
            .get<ApiPaginatedEnvelope<PriceListItemRecord>>(`/pricing/price-lists/${priceListId}/items`, { query: { page, per_page: perPage } })
            .then((payload) => unwrapPaginated(payload));
    },
};
