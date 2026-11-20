import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { TestRouter } from '@/test/TestRouter';
import { VehicleServiceQuickVehicleModal } from './VehicleServiceQuickVehicleModal';

const mocks = vi.hoisted(() => ({
    createCustomer: vi.fn(),
    createCustomerWithRelations: vi.fn(),
    createVehicle: vi.fn(),
    createMake: vi.fn(),
    createModel: vi.fn(),
    createType: vi.fn(),
}));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => true }));
vi.mock('@/modules/customer/customerApi', () => ({
    createCustomer: mocks.createCustomer,
    createCustomerWithRelations: mocks.createCustomerWithRelations,
    generateCustomerCode: async () => 'CUS-NEW',
    searchCurrencies: async () => ({ data: [{ id: 1, code: 'LKR', name: 'Rupee' }] }),
}));
vi.mock('@/modules/vehicle/vehicleApi', () => ({
    createVehicleWithRelations: mocks.createVehicle,
    createVehicleMake: mocks.createMake,
    createVehicleModel: mocks.createModel,
    createVehicleType: mocks.createType,
    generateVehicleCode: async () => 'VEH-NEW',
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({ CustomerLookupSelect: () => null }));
vi.mock('@/modules/vehicle/components/VehicleMakeSelect', () => ({ VehicleMakeSelect: ({ value }: { value: { name: string } | null }) => <span>Selected make: {value?.name ?? 'none'}</span> }));
vi.mock('@/modules/vehicle/components/VehicleModelSelect', () => ({ VehicleModelSelect: ({ value }: { value: { name: string } | null }) => <span>Selected model: {value?.name ?? 'none'}</span> }));
vi.mock('@/modules/vehicle/components/VehicleTypeSelect', () => ({ VehicleTypeSelect: ({ value }: { value: { name: string } | null }) => <span>Selected type: {value?.name ?? 'none'}</span> }));

describe('Service job new customer duplicate handling', () => {
    beforeEach(() => vi.clearAllMocks());

    it('saves a general address with the new customer before creating the vehicle', async () => {
        mocks.createCustomerWithRelations.mockResolvedValue({ id: 21, code: 'CUS-NEW', name: 'Nimal' });
        mocks.createVehicle.mockResolvedValue({ id: 31, code: 'VEH-NEW', registration_number: 'ABC-1234' });
        const user = userEvent.setup();

        render(<TestRouter><VehicleServiceQuickVehicleModal open initialVehicleNumber="ABC-1234" onClose={vi.fn()} onCreated={vi.fn()} /></TestRouter>);
        await user.click(screen.getByRole('button', { name: 'New customer' }));
        await user.type(await screen.findByLabelText('Customer Name'), 'Nimal');
        await user.type(screen.getByLabelText('WhatsApp Number'), '0776580914');
        await user.type(screen.getByLabelText('Customer address'), '123 Main Street');
        await waitFor(() => expect(screen.getByRole('button', { name: 'Save vehicle and continue' })).toBeEnabled());
        await user.click(screen.getByRole('button', { name: 'Save vehicle and continue' }));

        await waitFor(() => expect(mocks.createCustomerWithRelations).toHaveBeenCalledWith(expect.objectContaining({
            addresses: [{ address_type: 'general', address_line_1: '123 Main Street', is_primary: true, is_active: true }],
        })));
        expect(mocks.createVehicle).toHaveBeenCalled();
    });

    it('keeps entered data, shows the existing customer, and does not create a vehicle after a number conflict', async () => {
        const message = 'This number already belongs to Nimal (CUS-OLD). Use the existing customer or enter a different number.';
        mocks.createCustomer.mockRejectedValue(new ApiError(message, 422, 'CUSTOMER_PHONE_IN_USE', 'validation', { mobile: [message] }, {
            existing_customer: { id: 21, name: 'Nimal', code: 'CUS-OLD', archived: false },
        }));
        const onCreated = vi.fn();
        const user = userEvent.setup();
        render(<TestRouter><VehicleServiceQuickVehicleModal open initialVehicleNumber="ABC-1234" onClose={vi.fn()} onCreated={onCreated} /></TestRouter>);
        await user.click(screen.getByRole('button', { name: 'New customer' }));
        await user.type(await screen.findByLabelText('Customer Name'), 'Different name');
        await user.type(screen.getByLabelText('WhatsApp Number'), '0776580914');
        await waitFor(() => expect(screen.getByRole('button', { name: 'Save vehicle and continue' })).toBeEnabled());
        await user.click(screen.getByRole('button', { name: 'Save vehicle and continue' }));
        expect(await screen.findByRole('link', { name: 'View existing customer' })).toHaveAttribute('href', '/customers/21');
        expect(screen.getByLabelText('WhatsApp Number')).toHaveValue('0776580914');
        expect(mocks.createVehicle).not.toHaveBeenCalled();
        expect(onCreated).not.toHaveBeenCalled();
    });
});

describe('Service job vehicle master quick add', () => {
    beforeEach(() => vi.clearAllMocks());

    it('creates and selects a make, then creates a model under that make without losing vehicle input', async () => {
        mocks.createMake.mockResolvedValue({ id: 1, code: 'TOYOTA', name: 'Toyota', is_active: true });
        mocks.createModel.mockResolvedValue({ id: 2, code: 'COROLLA', name: 'Corolla', make: { id: 1, name: 'Toyota' }, is_active: true });
        const user = userEvent.setup();
        render(<TestRouter><VehicleServiceQuickVehicleModal open initialVehicleNumber="ABC-1234" onClose={vi.fn()} onCreated={vi.fn()} /></TestRouter>);

        await user.type(screen.getByLabelText('Registration Number'), 'XYZ');
        await user.click(screen.getByRole('button', { name: 'Add Model' }));
        expect(screen.getByText('Select or add a Make before saving this Model.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save Model' })).toBeDisabled();
        await user.click(screen.getByRole('button', { name: 'Add Make' }));
        await user.type(screen.getByLabelText('Make name'), 'Toyota');
        await user.type(within(screen.getByRole('group', { name: 'New Make details' })).getByLabelText('Code'), 'TOYOTA');
        await user.click(screen.getByRole('button', { name: 'Save Make' }));
        expect(await screen.findByText('Selected make: Toyota')).toBeInTheDocument();
        expect(screen.getByLabelText('Registration Number')).toHaveValue('ABC-1234XYZ');

        await user.click(screen.getByRole('button', { name: 'Add Model' }));
        expect(screen.getByText('Toyota', { selector: 'strong' })).toBeInTheDocument();
        await user.type(screen.getByLabelText('Model name'), 'Corolla');
        await user.type(within(screen.getByRole('group', { name: 'New Model details' })).getByLabelText('Code'), 'COROLLA');
        await user.click(screen.getByRole('button', { name: 'Save Model' }));
        await waitFor(() => expect(mocks.createModel).toHaveBeenCalledWith({ code: 'COROLLA', name: 'Corolla', vehicle_make_id: 1 }));
        expect(await screen.findByText('Selected model: Corolla')).toBeInTheDocument();
    });

    it('shows backend validation and keeps the quick form open', async () => {
        mocks.createType.mockRejectedValue(new ApiError('Validation failed', 422, null, null, { code: ['Code already exists.'] }));
        const user = userEvent.setup();
        render(<TestRouter><VehicleServiceQuickVehicleModal open initialVehicleNumber="ABC-1234" onClose={vi.fn()} onCreated={vi.fn()} /></TestRouter>);
        await user.click(screen.getByRole('button', { name: 'Add Type' }));
        await user.type(screen.getByLabelText('Type name'), 'Car');
        await user.type(within(screen.getByRole('group', { name: 'New Type details' })).getByLabelText('Code'), 'CAR');
        await user.click(screen.getByRole('button', { name: 'Save Type' }));
        expect(await screen.findByText('Code already exists.')).toBeInTheDocument();
        expect(screen.getByLabelText('Type name')).toHaveValue('Car');
        expect(screen.getByRole('button', { name: 'Save vehicle and continue' })).toBeDisabled();
    });
});
