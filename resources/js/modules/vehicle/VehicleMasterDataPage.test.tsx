import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { TestRouter } from '@/test/TestRouter';
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

vi.mock('./vehicleApi', () => Object.fromEntries(
    Object.entries(apiMocks).map(([name, mock]) => [
        name,
        (...args: unknown[]) => Reflect.apply(mock, undefined, args),
    ]),
));

const toyota = { id: 1, code: 'TOYOTA', name: 'Toyota', description: 'Default make', is_active: true };

describe('VehicleMasterDataPage', () => {
    beforeEach(() => {
        Object.values(apiMocks).forEach((mock) => mock.mockReset());
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

        await user.click(await screen.findByRole('button', { name: 'Go to page 2' }));
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
        fireEvent.change(screen.getByLabelText('Code *'), { target: { value: 'NISSAN' } });
        fireEvent.change(screen.getByLabelText('Name *'), { target: { value: 'Nissan' } });
        await user.click(screen.getByRole('button', { name: 'Create Make' }));
        await user.click(screen.getByRole('button', { name: /Create Make/ }));

        expect(apiMocks.createVehicleMake).toHaveBeenCalledOnce();
        resolveCreate?.(toyota);
        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        await waitFor(() => expect(apiMocks.listVehicleMakes).toHaveBeenCalledTimes(2));
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
        fireEvent.change(screen.getByLabelText('Code *'), { target: { value: 'TOYOTA' } });
        fireEvent.change(screen.getByLabelText('Name *'), { target: { value: 'Toyota' } });
        await user.click(screen.getByRole('button', { name: 'Create Make' }));

        await waitFor(() => expect(apiMocks.createVehicleMake).toHaveBeenCalledTimes(1));
        expect(await screen.findByText('Vehicle make code already exists for this tenant.')).toBeInTheDocument();
    });

    it('warns before discarding an unsaved form', async () => {
        const user = userEvent.setup();
        renderPage('makes');

        await screen.findAllByText('Toyota');
        await user.click(screen.getByRole('button', { name: 'Add Make' }));
        fireEvent.change(screen.getByLabelText('Code *'), { target: { value: 'NISSAN' } });
        await user.click(screen.getByRole('button', { name: 'Close modal' }));

        const discardDialog = screen.getByRole('dialog', { name: 'Discard unsaved changes?' });
        expect(discardDialog).toBeInTheDocument();
        await user.click(within(discardDialog).getByRole('button', { name: 'Cancel' }));
        expect(screen.getByRole('dialog', { name: 'Add Make' })).toBeInTheDocument();

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
        fireEvent.change(screen.getByLabelText('Code *'), { target: { value: 'COROLLA' } });
        fireEvent.change(screen.getByLabelText('Name *'), { target: { value: 'Corolla' } });
        await user.click(screen.getByRole('button', { name: 'Create Model' }));

        expect(apiMocks.createVehicleModel).not.toHaveBeenCalled();
        expect(await screen.findByText('Select a valid make from the list.')).toBeInTheDocument();
    });
});

function renderPage(kind: VehicleMasterKind) {
    return render(
        <TestRouter>
            <VehicleMasterDataPage kind={kind} />
        </TestRouter>,
    );
}

function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 2, per_page: 25, to: data.length, total: 26 },
    };
}
