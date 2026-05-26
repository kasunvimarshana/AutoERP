import { useQuery } from '@tanstack/react-query';
import { pricingApi } from './api';
import type { PriceListFilters } from './types';

const pricingKeys = {
    all: ['pricing'] as const,
    priceLists: () => [...pricingKeys.all, 'price-lists'] as const,
    priceList: (filters: PriceListFilters) => [...pricingKeys.priceLists(), filters] as const,
    priceListItems: (priceListId: number, page: number, perPage: number) => [...pricingKeys.all, 'price-list-items', priceListId, page, perPage] as const,
};

export function usePriceLists(filters: PriceListFilters) {
    return useQuery({
        queryKey: pricingKeys.priceList(filters),
        queryFn: () => pricingApi.listPriceLists(filters),
    });
}

export function usePriceListItems(priceListId: number, page = 1, perPage = 25, enabled = true) {
    return useQuery({
        queryKey: pricingKeys.priceListItems(priceListId, page, perPage),
        queryFn: () => pricingApi.listPriceListItems(priceListId, page, perPage),
        enabled,
    });
}
