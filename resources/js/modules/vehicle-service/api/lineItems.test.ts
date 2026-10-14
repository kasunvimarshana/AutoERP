import { beforeEach, describe, expect, it, vi } from 'vitest';
import { endpoints } from '@/shared/api/endpoints';
import { apiClient } from '@/shared/api/apiClient';
import { searchVehicleServiceLineItems } from './lineItems';

vi.mock('@/shared/api/apiClient', () => ({
    apiClient: { get: vi.fn() },
}));

describe('vehicle service line item lookup API', () => {
    beforeEach(() => vi.clearAllMocks());

    it('uses the combined lookup endpoint and keeps its result ordering and pagination', async () => {
        const data = [
            { id: 2, name: 'WASH & VACUUM', item_type: 'combo' },
            { id: 1, name: 'ACURA WIPER WASH', item_type: 'stock' },
        ];
        const meta = {
            current_page: 2,
            from: 21,
            last_page: 3,
            per_page: 20,
            to: 40,
            total: 50,
        };
        vi.mocked(apiClient.get).mockResolvedValue({ data: { data, meta } } as never);
        const signal = new AbortController().signal;

        await expect(searchVehicleServiceLineItems({
            search: 'wash',
            page: 2,
            perPage: 20,
            signal,
        })).resolves.toEqual({ data, meta });

        expect(apiClient.get).toHaveBeenCalledWith(
            `${endpoints.vehicleService}/job-line-items/lookup`,
            {
                params: { search: 'wash', page: 2, per_page: 20 },
                signal,
            },
        );
    });
});
