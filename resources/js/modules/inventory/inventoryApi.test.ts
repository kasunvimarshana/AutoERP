import { beforeEach, describe, expect, it, vi } from 'vitest';
import { searchInventoryBatches, searchInventorySerials } from './inventoryApi';

const apiClientMock = vi.hoisted(() => ({
    get: vi.fn(),
}));

vi.mock('@/shared/api/apiClient', () => ({
    apiClient: apiClientMock,
}));

describe('inventory API lookup adapters', () => {
    beforeEach(() => {
        apiClientMock.get.mockReset();
    });

    it('searches active batches with inventory dimensions and readable labels', async () => {
        const signal = new AbortController().signal;
        apiClientMock.get.mockResolvedValueOnce({
            data: {
                data: [{
                    id: 5,
                    batch_number: 'BATCH-1',
                    item: { id: 1, name: 'Oil Filter' },
                    variant: { id: 2, name: 'Premium' },
                }],
                meta: { current_page: 1, from: 1, last_page: 1, per_page: 10, to: 1, total: 1 },
            },
        });

        const result = await searchInventoryBatches(
            { search: 'BATCH', page: 1, perPage: 10, signal },
            { itemId: 1, itemVariantId: 2 },
        );

        expect(apiClientMock.get).toHaveBeenCalledWith('/api/v1/inventory/batches', {
            params: {
                search: 'BATCH',
                page: 1,
                per_page: 10,
                item_id: 1,
                item_variant_id: 2,
                status: 'active',
            },
            signal,
        });
        expect(result.data).toEqual([{ id: 5, code: 'BATCH-1', name: 'Oil Filter / Premium' }]);
    });

    it('searches available serials with stock location filters', async () => {
        const signal = new AbortController().signal;
        apiClientMock.get.mockResolvedValueOnce({
            data: {
                data: [{
                    id: 9,
                    serial_number: 'SN-9',
                    item: { id: 1, name: 'Scanner' },
                }],
            },
        });

        const result = await searchInventorySerials(
            { search: 'SN', page: 2, perPage: 25, signal },
            { itemId: 1, itemVariantId: 3, warehouseId: 4, warehouseLocationId: 5, batchId: 6 },
        );

        expect(apiClientMock.get).toHaveBeenCalledWith('/api/v1/inventory/serials', {
            params: {
                search: 'SN',
                page: 2,
                per_page: 25,
                item_id: 1,
                item_variant_id: 3,
                warehouse_id: 4,
                warehouse_location_id: 5,
                batch_id: 6,
                status: 'available',
            },
            signal,
        });
        expect(result.data).toEqual([{ id: 9, code: 'SN-9', name: 'Scanner' }]);
    });
});
