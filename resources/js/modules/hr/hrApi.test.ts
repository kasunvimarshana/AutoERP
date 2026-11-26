/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { beforeEach, describe, expect, it, vi } from 'vitest';
import { apiClient } from '@/shared/api/apiClient';
import { listHolidayCalendars, listShiftAssignments, listShifts, updateShift } from './hrApi';

vi.mock('@/shared/api/apiClient', () => ({
    apiClient: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
    },
}));

describe('hrApi pagination normalization and OCC payloads', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('normalizes Laravel LengthAwarePaginator responses into ApiCollection.meta', async () => {
        vi.mocked(apiClient.get).mockResolvedValueOnce({
            data: {
                data: [{ id: 1, code: 'DAY', name: 'Day Shift' }],
                current_page: 2,
                last_page: 4,
                per_page: 25,
                total: 90,
                from: 26,
                to: 50,
            },
        });

        const res = await listShifts({ page: 2, per_page: 25 });

        expect(apiClient.get).toHaveBeenCalledWith('/api/v1/hr/shifts', {
            params: { page: 2, per_page: 25 },
            signal: undefined,
        });
        expect(res.data).toHaveLength(1);
        expect(res.meta).toEqual({
            current_page: 2,
            last_page: 4,
            per_page: 25,
            total: 90,
            from: 26,
            to: 50,
        });
    });

    it('preserves existing meta when backend returns ApiResource collection', async () => {
        vi.mocked(apiClient.get).mockResolvedValueOnce({
            data: {
                data: [{ id: 5 }],
                meta: { current_page: 1, last_page: 2, per_page: 25, total: 30, from: 1, to: 25 },
            },
        });

        const res = await listShiftAssignments({ page: 1, per_page: 25, is_cancelled: false });
        expect(res.meta?.total).toBe(30);
    });

    it('forwards calendar_year and pagination on listHolidayCalendars and expected_version on updateShift', async () => {
        vi.mocked(apiClient.get).mockResolvedValueOnce({
            data: { data: [], current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
        });
        await listHolidayCalendars({ calendar_year: 2026, page: 1, per_page: 25 });
        expect(apiClient.get).toHaveBeenCalledWith('/api/v1/hr/holiday-calendars', {
            params: { calendar_year: 2026, page: 1, per_page: 25 },
            signal: undefined,
        });

        vi.mocked(apiClient.put).mockResolvedValueOnce({ data: { data: { id: 10, row_version: 4 } } });
        await updateShift(10, { name: 'Updated', expected_version: 3 });
        expect(apiClient.put).toHaveBeenCalledWith('/api/v1/hr/shifts/10', {
            name: 'Updated',
            expected_version: 3,
        });
    });
});
