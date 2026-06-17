import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import VehicleMasterDataPage from './VehicleMasterDataPage';
import type { VehicleMasterKind } from './VehicleMasterDataPage';

const apiMocks = vi.hoisted(() => ({
    createVehicleCategory: vi.fn(),
    createVehicleMake: vi.fn(),
    createVehicleModel: vi.fn(),
    createVehicleType: vi.fn(),
    listVehicleCategories: vi.fn(),
    listVehicleMakes: vi.fn(),
    listVehicleModels: vi.fn(),
    listVehicleTypes: vi.fn(),
    searchVehicleCategories: vi.fn(),
    searchVehicleMakes: vi.fn(),
    updateVehicleCategory: vi.fn(),
    updateVehicleMake: vi.fn(),
    updateVehicleModel: vi.fn(),
    updateVehicleType: vi.fn(),
}));

vi.mock('./vehicleApi', () => apiMocks);

const toyota = { id: 1, code: 'TOYOTA', name: 'Toyota', description: 'Default make', is_active: true };

describe('VehicleMasterDataPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listVehicleMakes.mockResolvedValue(collection([toyota]));
        apiMocks.listVehicleTypes.mockResolvedValue(collection([]));
        apiMocks.listVehicleCategories.mockResolvedValue(collection([]));
        apiMocks.listVehicleModels.mockResolvedValue(collection([]));
        apiMocks.createVehicleMake.mockResolvedValue(toyota);
        apiMocks.createVehicleModel.mockResolvedValue({ id: 2, code: 'COROLLA', name: 'Corolla', make: toyota, is_active: true });
        apiMocks.updateVehicleMake.mockResolvedValue(toyota);
        apiMocks.searchVehicleMakes.mockResolvedValue(collection([toyota]));
        apiMocks.searchVehicleCategories.mockResolvedValue(collection([]));
    });

    it('loads lists with search, active filtering, and server pagination', async () => {
        const user = userEvent.setup();
        renderPage('makes');

        expect(await screen.findAllByText('Toyota')).not.toHaveLength(0);
        expect(apiMocks.listVehicleMakes).toHaveBeenLastCalledWith(
            expect.objectContaining({ page: 1, per_page: 25 }),
            expect.any(AbortSignal),
        );

        await user.type(screen.getByLabelText('Search'), 'toy');
        await waitFor(() => expect(apiMocks.listVehicleMakes).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: 'toy', page: 1 }),
            expect.any(AbortSignal),
        ));

        await user.selectOptions(screen.getByLabelText('Status'), 'true');
        await waitFor(() => expect(apiMocks.listVehicleMakes).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: 'toy', is_active: true, page: 1 }),
            expect.any(AbortSignal),
        ));

        await user.click(screen.getByRole('button', { name: 'Next' }));
        await waitFor(() => expect(apiMocks.listVehicleMakes).toHaveBeenLastCalledWith(
            expect.objectContaining({ search: 'toy', is_active: true, page: 2 }),
            expect.any(AbortSignal),
        ));
    });

    it('prevents duplicate create submissions', async () => {
        const user = userEvent.setup();
        let resolveCreate: ((value: typeof toyota) => void) | undefined;
        apiMocks.createVehicleMake.mockImplementationOnce(() => new Promise((resolve) => {
            resolveCreate = resolve;
        }));
        renderPage('makes');

        await screen.findAllByText('Toyota');
        await user.click(screen.getByRole('button', { name: 'Add Make' }));
        await user.type(screen.getByLabelText('Code *'), 'NISSAN');
        await user.type(screen.getByLabelText('Name *'), 'Nissan');
        await user.click(screen.getByRole('button', { name: 'Create Make' }));
        await user.click(screen.getByRole('button', { name: /Create Make/ }));

        expect(apiMocks.createVehicleMake).toHaveBeenCalledOnce();
        resolveCreate?.(toyota);
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    });

    it('shows backend validation beside fields', async () => {
        const user = userEvent.setup();
        apiMocks.createVehicleMake.mockRejectedValueOnce(new ApiError(
            'Please correct the highlighted fields and try again.',
            422,
            null,
            null,
            { code: ['Vehicle make code already exists for this tenant.'] },
        ));
        renderPage('makes');

        await screen.findAllByText('Toyota');
        await user.click(screen.getByRole('button', { name: 'Add Make' }));
        await user.type(screen.getByLabelText('Code *'), 'TOYOTA');
        await user.type(screen.getByLabelText('Name *'), 'Toyota');
        await user.click(screen.getByRole('button', { name: 'Create Make' }));

        expect(await screen.findByText('Vehicle make code already exists for this tenant.')).toBeInTheDocument();
    });

    it('warns before discarding an unsaved form', async () => {
        const user = userEvent.setup();
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
        renderPage('makes');

        await screen.findAllByText('Toyota');
        await user.click(screen.getByRole('button', { name: 'Add Make' }));
        await user.type(screen.getByLabelText('Code *'), 'NISSAN');
        await user.click(screen.getByRole('button', { name: 'Close modal' }));

        expect(confirm).toHaveBeenCalledWith('You have unsaved changes. Leave this form and discard them?');
        expect(screen.getByRole('dialog', { name: 'Add Make' })).toBeInTheDocument();

        confirm.mockRestore();
    });

    it('surfaces relationship validation for vehicle models', async () => {
        const user = userEvent.setup();
        apiMocks.createVehicleModel.mockRejectedValueOnce(new ApiError(
            'Please correct the highlighted fields and try again.',
            422,
            null,
            null,
            { vehicle_make_id: ['The vehicle make field is required.'] },
        ));
        renderPage('models');

        expect(await screen.findByRole('heading', { name: 'Vehicle Models' })).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Add Model' }));
        await user.type(screen.getByLabelText('Code *'), 'COROLLA');
        await user.type(screen.getByLabelText('Name *'), 'Corolla');
        await user.click(screen.getByRole('button', { name: 'Create Model' }));

        expect(apiMocks.createVehicleModel).toHaveBeenCalledWith(expect.objectContaining({
            code: 'COROLLA',
            name: 'Corolla',
            vehicle_make_id: null,
        }));
        expect(await screen.findByText('The vehicle make field is required.')).toBeInTheDocument();
    });
});

function renderPage(kind: VehicleMasterKind) {
    return render(
        <MemoryRouter>
            <VehicleMasterDataPage kind={kind} />
        </MemoryRouter>,
    );
}

function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 2, per_page: 25, to: data.length, total: 26 },
    };
}
