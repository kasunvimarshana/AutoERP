import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactElement } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import VehicleCreatePage from './VehicleCreatePage';
import VehicleDetailPage from './VehicleDetailPage';
import VehicleEditPage from './VehicleEditPage';
import type { Vehicle } from './vehicleTypes';

const apiMocks = vi.hoisted(() => ({
    createVehicle: vi.fn(),
    createVehicleWithRelations: vi.fn(),
    getVehicle: vi.fn(),
    listVehicleAttributes: vi.fn(),
    listVehicleDocuments: vi.fn(),
    listVehicleStatusHistory: vi.fn(),
    updateVehicle: vi.fn(),
    searchVehicleCategories: vi.fn(),
    searchVehicleMakes: vi.fn(),
    searchVehicleModels: vi.fn(),
    searchVehicleTypes: vi.fn(),
}));

vi.mock('./vehicleApi', () => ({
    ...apiMocks,
    createVehicleDocument: vi.fn(),
    updateVehicleDocument: vi.fn(),
    deleteVehicleDocument: vi.fn(),
    createVehicleAttribute: vi.fn(),
    updateVehicleAttribute: vi.fn(),
    deleteVehicleAttribute: vi.fn(),
    listVehicleOwnerships: vi.fn(),
    createVehicleOwnership: vi.fn(),
    updateVehicleOwnership: vi.fn(),
    deleteVehicleOwnership: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        permissions: [
            'vehicle.create',
            'vehicle.update',
            'vehicle.documents.download',
        ],
    }),
}));

const vehicle: Vehicle = {
    id: 10,
    vehicle_number: 'VEH-10',
    code: 'VEH-CODE',
    registration_number: 'CAR-1000',
    make: { id: 1, code: 'TOYOTA', name: 'Toyota' },
    model: { id: 2, code: 'COROLLA', name: 'Corolla' },
    type: { id: 3, code: 'CAR', name: 'Car' },
    category: { id: 4, code: 'FLEET', name: 'Fleet' },
    current_ownerships: [{ id: 5, owner_type: 'company', owner_id: null, owner: null, ownership_type: 'company_owned', started_at: '2026-06-01T00:00:00.000000Z', is_current: true }],
    status: 'active',
    odometer_reading: '0.000000',
    odometer_unit: 'km',
};

describe('Vehicle route pages', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.createVehicle.mockResolvedValue(vehicle);
        apiMocks.getVehicle.mockResolvedValue(vehicle);
        apiMocks.listVehicleDocuments.mockResolvedValue(collection([
            { id: 1, document_type: 'registration', document_number: 'REG-1', issued_date: '2026-06-01', expiry_date: '2027-06-01', file_name: 'reg.pdf', has_file: true, status: 'active', notes: null },
        ]));
        apiMocks.listVehicleAttributes.mockResolvedValue(collection([{ id: 1, attribute_key: 'seat_count', attribute_value: '5', data_type: 'number', sort_order: 1 }]));
        apiMocks.listVehicleStatusHistory.mockResolvedValue(collection([]));
        apiMocks.searchVehicleMakes.mockResolvedValue(collection([]));
        apiMocks.searchVehicleModels.mockResolvedValue(collection([]));
        apiMocks.searchVehicleTypes.mockResolvedValue(collection([]));
        apiMocks.searchVehicleCategories.mockResolvedValue(collection([]));
        apiMocks.updateVehicle.mockResolvedValue(vehicle);
    });

    it('renders create as a separate page with only Basic, Documents, and Attributes tabs', async () => {
        renderPage(<VehicleCreatePage />, ['/vehicles/create']);

        expect(screen.getByRole('heading', { name: 'Create Vehicle' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Basic' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Documents' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Attributes' })).toBeInTheDocument();
        expect(screen.queryByRole('tab', { name: 'Ownership' })).not.toBeInTheDocument();
        expect(screen.queryByRole('tab', { name: 'Review' })).not.toBeInTheDocument();
    });

    it('prevents duplicate create submissions', async () => {
        const user = userEvent.setup();
        let resolveCreate: ((value: Vehicle) => void) | undefined;
        apiMocks.createVehicle.mockImplementationOnce(() => new Promise((resolve) => {
            resolveCreate = resolve;
        }));
        renderPage(<VehicleCreatePage />, ['/vehicles/create']);

        await user.click(screen.getByRole('button', { name: 'Create Vehicle' }));
        await user.click(screen.getByRole('button', { name: /Create Vehicle/ }));

        expect(apiMocks.createVehicle).toHaveBeenCalledOnce();
        resolveCreate?.(vehicle);
    });

    it('renders edit as a separate editable page without ownership or review tabs', async () => {
        renderPage(<RoutePage page={<VehicleEditPage />} path="/vehicles/:id/edit" />, ['/vehicles/10/edit']);

        expect(await screen.findByRole('heading', { name: 'Edit Vehicle' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Basic' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Documents' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Attributes' })).toBeInTheDocument();
        expect(screen.queryByRole('tab', { name: 'Ownership' })).not.toBeInTheDocument();
        expect(screen.queryByRole('tab', { name: 'Review' })).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save Changes' })).toBeInTheDocument();
    });

    it('renders detail read-only and removes quick actions', async () => {
        const user = userEvent.setup();
        renderPage(<RoutePage page={<VehicleDetailPage />} path="/vehicles/:id" />, ['/vehicles/10']);

        expect(await screen.findByRole('heading', { name: 'VEH-10' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Summary' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Documents' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Attributes' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Status History' })).toBeInTheDocument();
        expect(screen.queryByRole('tab', { name: 'Ownership' })).not.toBeInTheDocument();
        expect(screen.queryByText('Create service job')).not.toBeInTheDocument();
        expect(screen.queryByText('Review documents')).not.toBeInTheDocument();
        expect(screen.queryByText('View status history')).not.toBeInTheDocument();

        await user.click(screen.getByRole('tab', { name: 'Documents' }));
        expect(await screen.findAllByText('REG-1')).not.toHaveLength(0);
        expect(screen.getAllByRole('button', { name: 'Preview' })).not.toHaveLength(0);
        expect(screen.getAllByRole('button', { name: 'Download' })).not.toHaveLength(0);
        expect(screen.queryByRole('button', { name: 'Add Document' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Delete' })).not.toBeInTheDocument();
    });
});

function RoutePage({ page, path }: { page: ReactElement; path: string }) {
    return (
        <Routes>
            <Route path={path} element={page} />
        </Routes>
    );
}

function renderPage(page: ReactElement, initialEntries: string[]) {
    return render(
        <MemoryRouter initialEntries={initialEntries}>
            {page}
        </MemoryRouter>,
    );
}

function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, per_page: 25, to: data.length, total: data.length },
    };
}
