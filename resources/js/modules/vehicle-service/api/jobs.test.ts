import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { VehicleServiceJobPayload } from '../vehicleServiceTypes';
import { vehicleServiceJobsEndpoint } from './endpoint';
import { createVehicleServiceJob, getVehicleServiceJobCreateDefaults } from './jobs';

const apiClientMocks = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
}));

vi.mock('@/shared/api/apiClient', () => ({ apiClient: apiClientMocks }));

describe('vehicle service job API', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('loads create defaults from the job-create endpoint', async () => {
        const defaultValue = {
            commission_type: 'percentage',
            commission_value: '7.500000',
        };
        apiClientMocks.get.mockResolvedValue({ data: { data: defaultValue } });

        await expect(getVehicleServiceJobCreateDefaults()).resolves.toEqual(defaultValue);
        expect(apiClientMocks.get).toHaveBeenCalledWith(
            `${vehicleServiceJobsEndpoint}/create-defaults`,
            { signal: undefined },
        );
    });

    it('preserves explicit supervisor commission overrides in create requests', async () => {
        const payload: VehicleServiceJobPayload = {
            job_date: '2026-07-16',
            type: 'full_service',
            customer_id: 5,
            vehicle_id: 14,
            supervisor_employee_id: 21,
            supervisor_commission_type: 'fixed',
            supervisor_commission_value: '2500.000000',
        };
        apiClientMocks.post.mockResolvedValue({ data: { data: { id: 91 } } });

        await createVehicleServiceJob(payload);

        expect(apiClientMocks.post).toHaveBeenCalledWith(vehicleServiceJobsEndpoint, payload);
    });
});
