import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleServiceJobListPage from './VehicleServiceJobListPage';

const api = vi.hoisted(() => ({ listVehicleServiceJobs: vi.fn(), getVehicleServiceJobBoard: vi.fn() }));
vi.mock('../vehicleServiceApi', () => api);
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissions: [], permissionsLoaded: true }),
}));

const job = {
    id: 7, job_number: 'VSJ-000007', job_date: '2026-11-12', type: 'body_wash', type_label: 'Body Wash',
    status: 'draft', grand_total: '2500', customer: { id: 3, name: 'N. Perera' }, vehicle: { id: 5, name: 'CBA-2441' },
};

describe('VehicleServiceJobListPage views', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        api.listVehicleServiceJobs.mockResolvedValue({ data: [job], meta: { total: 1, current_page: 1, last_page: 1, from: 1, to: 1 } });
        api.getVehicleServiceJobBoard.mockResolvedValue({ lanes: {
            pending: { data: [job], total: 1, has_more: false },
            in_progress: { data: [], total: 0, has_more: false },
            completed: { data: [], total: 0, has_more: false },
            billing_closed: { data: [], total: 0, has_more: false },
        } });
    });

    it('opens the board by default and restores the existing list when List is selected', async () => {
        const user = userEvent.setup();
        render(<MemoryRouter initialEntries={['/vehicle-service/jobs']}><Routes><Route path="/vehicle-service/jobs" element={<VehicleServiceJobListPage />} /></Routes></MemoryRouter>);

        expect(await screen.findByRole('region', { name: 'Service job board' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Open VSJ-000007' })).toHaveAttribute('href', '/vehicle-service/jobs/7');
        expect(screen.getByText('Job total')).toBeInTheDocument();
        expect(screen.getByText(/2,500/)).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'List' }));
        expect(await screen.findByRole('columnheader', { name: 'Job' })).toBeInTheDocument();
        expect(screen.queryByRole('region', { name: 'Service job board' })).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Board' }));
        expect(await screen.findByRole('region', { name: 'Service job board' })).toBeInTheDocument();
    });

    it('passes date presets and search to the board endpoint', async () => {
        const user = userEvent.setup();
        render(<MemoryRouter initialEntries={['/vehicle-service/jobs']}><Routes><Route path="/vehicle-service/jobs" element={<VehicleServiceJobListPage />} /></Routes></MemoryRouter>);
        await screen.findByRole('region', { name: 'Service job board' });

        await user.click(screen.getByRole('button', { name: 'Today' }));
        await waitFor(() => expect(api.getVehicleServiceJobBoard).toHaveBeenLastCalledWith(expect.objectContaining({
            date_from: expect.any(String), date_to: expect.any(String),
        }), expect.any(AbortSignal)));
        await user.click(screen.getByRole('button', { name: 'This week' }));
        await waitFor(() => expect(api.getVehicleServiceJobBoard.mock.lastCall?.[0].date_to).not.toBe(api.getVehicleServiceJobBoard.mock.lastCall?.[0].date_from));
        const week = api.getVehicleServiceJobBoard.mock.lastCall?.[0];
        expect((Date.parse(week.date_to) - Date.parse(week.date_from)) / 86_400_000).toBe(6);
        await user.type(screen.getByRole('searchbox', { name: 'Search' }), 'CBA');
        await waitFor(() => expect(api.getVehicleServiceJobBoard).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'CBA' }), expect.any(AbortSignal)));
    });

    it('loads more jobs in one board column without advancing the others', async () => {
        api.getVehicleServiceJobBoard.mockImplementation(async (params: { pending_page: number }) => ({ lanes: {
            pending: { data: [job], total: 25, has_more: params.pending_page === 1 },
            in_progress: { data: [], total: 0, has_more: false },
            completed: { data: [], total: 0, has_more: false },
            billing_closed: { data: [], total: 0, has_more: false },
        } }));
        const user = userEvent.setup();
        render(<MemoryRouter initialEntries={['/vehicle-service/jobs']}><Routes><Route path="/vehicle-service/jobs" element={<VehicleServiceJobListPage />} /></Routes></MemoryRouter>);

        await user.click(await screen.findByRole('button', { name: 'Load more pending jobs' }));
        await waitFor(() => expect(api.getVehicleServiceJobBoard).toHaveBeenLastCalledWith(expect.objectContaining({
            pending_page: 2, in_progress_page: 1, completed_page: 1, billing_closed_page: 1,
        }), expect.any(AbortSignal)));
    });
});
