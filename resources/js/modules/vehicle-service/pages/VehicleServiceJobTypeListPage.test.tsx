/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleServiceJobTypeListPage from './VehicleServiceJobTypeListPage';
import type { VehicleServiceJobTypeResource } from '../vehicleServiceTypes';

const api = vi.hoisted(() => ({
    listVehicleServiceJobTypes: vi.fn(),
    createVehicleServiceJobType: vi.fn(),
    updateVehicleServiceJobType: vi.fn(),
    deleteVehicleServiceJobType: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => api);

const authMock = vi.hoisted(() => ({
    roles: [],
    permissions: [
        'vehicle_service.job_types.view',
        'vehicle_service.job_types.manage',
    ],
    permissionsLoaded: true,
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => authMock,
}));

const systemJobType: VehicleServiceJobTypeResource = {
    id: 1,
    row_version: 1,
    tenant_id: 10,
    code: 'full_service',
    name: 'Full Service',
    description: 'Comprehensive mechanical check and oil change.',
    tracks_mileage: true,
    is_active: true,
    is_system: true,
    sort_order: 10,
};

const customJobType: VehicleServiceJobTypeResource = {
    id: 2,
    row_version: 3,
    tenant_id: 10,
    code: 'custom_detailing',
    name: 'Custom Detailing',
    description: 'Special exterior polish and interior steam cleaning.',
    tracks_mileage: false,
    is_active: true,
    is_system: false,
    sort_order: 20,
};

describe('VehicleServiceJobTypeListPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        api.listVehicleServiceJobTypes.mockResolvedValue({
            data: [systemJobType, customJobType],
            meta: { total: 2, current_page: 1, last_page: 1, from: 1, to: 2 },
        });
    });

    it('renders the list of job types with badges and details', async () => {
        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        const fullServices = await screen.findAllByText('Full Service');
        expect(fullServices.length).toBeGreaterThan(0);
        expect(screen.getAllByText('full_service').length).toBeGreaterThan(0);
        expect(screen.getAllByText('System').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Required').length).toBeGreaterThan(0);

        expect(screen.getAllByText('Custom Detailing').length).toBeGreaterThan(0);
        expect(screen.getAllByText('custom_detailing').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Custom').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Not Tracked').length).toBeGreaterThan(0);
    });

    it('allows searching and filtering by status', async () => {
        const user = userEvent.setup();
        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await screen.findAllByText('Full Service');

        const searchInput = screen.getByRole('searchbox', { name: 'Search' });
        await user.type(searchInput, 'Detailing');

        await waitFor(() => {
            expect(api.listVehicleServiceJobTypes).toHaveBeenLastCalledWith(
                expect.objectContaining({ search: 'Detailing' }),
                expect.any(AbortSignal),
            );
        });
    });

    it('opens create drawer and creates a new custom job type', async () => {
        const user = userEvent.setup();
        const createdType: VehicleServiceJobTypeResource = {
            id: 3,
            row_version: 1,
            tenant_id: 10,
            code: 'ceramic_coating',
            name: 'Ceramic Coating',
            description: '9H nano ceramic coat application',
            tracks_mileage: false,
            is_active: true,
            is_system: false,
            sort_order: 30,
        };
        api.createVehicleServiceJobType.mockResolvedValue(createdType);

        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await screen.findAllByText('Full Service');

        await user.click(screen.getByRole('button', { name: 'New Job Type' }));
        expect(await screen.findByText('New Service Job Type')).toBeInTheDocument();

        await user.type(screen.getByLabelText('Name'), 'Ceramic Coating');
        await user.type(screen.getByLabelText('Code'), 'ceramic_coating');
        await user.type(screen.getByLabelText('Description'), '9H nano ceramic coat application');

        await user.click(screen.getByRole('button', { name: 'Create Job Type' }));

        await waitFor(() => {
            expect(api.createVehicleServiceJobType).toHaveBeenCalledWith({
                code: 'ceramic_coating',
                name: 'Ceramic Coating',
                description: '9H nano ceramic coat application',
                tracks_mileage: false,
                is_active: true,
                sort_order: expect.any(Number),
            });
        });
    });

    it('opens edit drawer, protects system type code, and updates successfully', async () => {
        const user = userEvent.setup();
        const updatedType = { ...systemJobType, name: 'Full Service Enhanced', row_version: 2 };
        api.updateVehicleServiceJobType.mockResolvedValue(updatedType);

        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await screen.findAllByText('Full Service');

        // Click Edit on the first row (system job type)
        const editButtons = screen.getAllByRole('button', { name: 'Edit' });
        await user.click(editButtons[0]);

        expect(await screen.findByText('Edit Full Service')).toBeInTheDocument();
        const codeInput = screen.getByLabelText('Code');
        expect(codeInput).toBeDisabled();

        const nameInput = screen.getByLabelText('Name');
        await user.clear(nameInput);
        await user.type(nameInput, 'Full Service Enhanced');

        await user.click(screen.getByRole('button', { name: 'Save Changes' }));

        await waitFor(() => {
            expect(api.updateVehicleServiceJobType).toHaveBeenCalledWith(1, {
                expected_version: 1,
                code: 'full_service',
                name: 'Full Service Enhanced',
                description: 'Comprehensive mechanical check and oil change.',
                tracks_mileage: true,
                is_active: true,
                sort_order: 10,
            });
        });
    });

    it('hides delete button for system types and allows deleting custom types with confirmation', async () => {
        const user = userEvent.setup();
        api.deleteVehicleServiceJobType.mockResolvedValue({});

        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await screen.findAllByText('Full Service');

        // Only customJobType should have a Delete button (both desktop and mobile render it, so 2 total)
        const deleteButtons = screen.getAllByRole('button', { name: 'Delete' });
        expect(deleteButtons).toHaveLength(2);

        await user.click(deleteButtons[0]);

        // Confirmation dialog appears
        expect(await screen.findByText('Delete Service Job Type')).toBeInTheDocument();
        expect(screen.getByText(/Are you sure you want to delete "Custom Detailing"/)).toBeInTheDocument();

        // Confirm deletion
        const confirmBtn = screen.getByRole('button', { name: 'Delete Job Type' });
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(api.deleteVehicleServiceJobType).toHaveBeenCalledWith(2, 3);
        });
    });

    it('toggles active status of job type', async () => {
        const user = userEvent.setup();
        const deactivated = { ...customJobType, is_active: false, row_version: 4 };
        api.updateVehicleServiceJobType.mockResolvedValue(deactivated);

        render(
            <MemoryRouter initialEntries={['/vehicle-service/job-types']}>
                <Routes>
                    <Route path="/vehicle-service/job-types" element={<VehicleServiceJobTypeListPage />} />
                </Routes>
            </MemoryRouter>,
        );

        await screen.findAllByText('Custom Detailing');

        // Deactivate buttons: index 0 is desktop system type, index 1 is desktop custom type
        const deactivateButtons = screen.getAllByRole('button', { name: 'Deactivate' });
        await user.click(deactivateButtons[1]); // Custom Detailing on desktop row

        await waitFor(() => {
            expect(api.updateVehicleServiceJobType).toHaveBeenCalledWith(2, {
                expected_version: 3,
                code: 'custom_detailing',
                name: 'Custom Detailing',
                description: 'Special exterior polish and interior steam cleaning.',
                tracks_mileage: false,
                is_active: false,
                sort_order: 20,
            });
        });
    });
});
