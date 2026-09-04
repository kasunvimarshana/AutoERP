import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import VehicleServiceJobDetailPage from './VehicleServiceJobDetailPage';

const fixture = vi.hoisted(() => ({ job: {} as VehicleServiceJob, permissions: [] as string[] }));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissionsLoaded: true, permissions: fixture.permissions }),
}));
vi.mock('@/shared/hooks/useApi', () => ({
    useApi: () => ({ data: fixture.job, loading: false, error: null, setData: vi.fn(), reload: vi.fn() }),
}));
vi.mock('../components/VehicleServiceSummaryPanel', () => ({ VehicleServiceSummaryPanel: () => <p>Job overview</p> }));

function showJob() {
    render(<MemoryRouter initialEntries={['/vehicle-service/jobs/4']}>
        <Routes><Route path="/vehicle-service/jobs/:id" element={<VehicleServiceJobDetailPage />} /></Routes>
    </MemoryRouter>);
}

describe('Vehicle Service cancellation access and billing guidance', () => {
    beforeEach(() => {
        fixture.job = { id: 4, job_number: 'VSJ-000004', status: 'completed', row_version: 5, invoice_links: [], payment_links: [] } as unknown as VehicleServiceJob;
        fixture.permissions = [vehicleServicePermissions.jobsTransition, vehicleServicePermissions.jobsCancelCompleted];
    });

    it.each(['invoiced', 'partially_paid', 'paid'] as const)('guides an authorized user through billing reversal for a %s job without offering direct cancellation', (status) => {
        fixture.job.status = status;
        showJob();
        expect(screen.getByRole('region', { name: 'Job cancellation steps' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Open linked payments' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Open linked invoices' })).toBeInTheDocument();
        expect(screen.queryByText('Cancel job')).not.toBeInTheDocument();
    });

    it('does not offer cancellation guidance or a restore action for an already cancelled job', () => {
        fixture.job.status = 'cancelled';
        showJob();
        expect(screen.getByText('No further actions are available for a cancelled job.')).toBeInTheDocument();
        expect(screen.queryByRole('region', { name: 'Job cancellation steps' })).not.toBeInTheDocument();
        expect(screen.queryByText('Cancel job')).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /restore|reopen/i })).not.toBeInTheDocument();
    });

    it('offers completed-job cancellation only with the elevated permission', async () => {
        showJob();
        await userEvent.setup().click(screen.getByText('More actions'));
        expect(screen.getByRole('button', { name: 'Cancel job' })).toBeVisible();
    });

    it('does not offer completed-job cancellation to an ordinary transition user', () => {
        fixture.permissions = [vehicleServicePermissions.jobsTransition];
        showJob();
        expect(screen.queryByText('Cancel job')).not.toBeInTheDocument();
    });
});
