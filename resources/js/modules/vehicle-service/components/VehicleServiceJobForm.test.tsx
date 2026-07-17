import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NamedResource } from '@/shared/types/common';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { VehicleServiceJobForm } from './VehicleServiceJobForm';

const apiMocks = vi.hoisted(() => ({
    createVehicleServiceJob: vi.fn(),
    getVehicleServiceJobCreateDefaults: vi.fn(),
    updateVehicleServiceJob: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

vi.mock('./VehicleServiceQuickVehicleModal', () => ({
    VehicleServiceQuickVehicleModal: () => null,
}));

vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({ label, value, onChange }: {
        label: string;
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => {
        const choice = lookupChoice(label);

        return (
            <div>
                <span>{label}: {value?.name ?? 'None'}</span>
                <button type="button" onClick={() => onChange(choice)}>
                    Choose {label}
                </button>
            </div>
        );
    },
}));

const existingJob: VehicleServiceJob = {
    id: 92,
    row_version: 3,
    job_number: 'VSJ-92',
    job_date: '2026-07-15',
    customer_id: 5,
    customer: { id: 5, code: 'CUS-5', name: 'Vehicle Owner' },
    bill_to_customer_id: 5,
    bill_to_customer: { id: 5, code: 'CUS-5', name: 'Vehicle Owner' },
    vehicle_id: 14,
    vehicle: lookupChoice('Vehicle') as VehicleServiceJob['vehicle'],
    supervisor_employee_id: 21,
    supervisor: { id: 21, code: 'EMP-21', name: 'Supervisor' },
    supervisor_commission_type: 'percentage',
    supervisor_commission_value: '5.000000',
    supervisor_commission_amount: '0.000000',
    status: 'draft',
    subtotal: '0.000000',
    discount_total: '0.000000',
    tax_total: '0.000000',
    charge_total: '0.000000',
    grand_total: '0.000000',
};

const organizationDefault = {
    commission_type: 'percentage' as const,
    commission_value: '7.500000',
};

describe('VehicleServiceJobForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.createVehicleServiceJob.mockResolvedValue({
            id: 91,
            job_number: 'VSJ-91',
        });
        apiMocks.getVehicleServiceJobCreateDefaults.mockResolvedValue(organizationDefault);
        apiMocks.updateVehicleServiceJob.mockResolvedValue(existingJob);
    });

    it('prefills actual organization values and submits the exact visible snapshot', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await waitFor(() => expect(screen.getByLabelText('Supervisor commission')).toHaveValue('percentage'));
        expect(screen.queryByRole('option', { name: 'Use organization default' })).not.toBeInTheDocument();
        expect(screen.queryByText('Organization commission default')).not.toBeInTheDocument();
        expect(screen.getByLabelText('Commission value')).toHaveValue('7.500000');
        expect(screen.getByLabelText('Commission value')).toBeEnabled();
        expect(screen.getByText('Loaded from organization default.')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Choose Vehicle' }));
        await user.click(screen.getByRole('button', { name: 'Choose Bill-to customer' }));
        await user.click(screen.getByRole('button', { name: 'Choose Supervisor' }));
        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceJob).toHaveBeenCalledWith(
            expect.objectContaining({
                customer_id: 5,
                bill_to_customer_id: 8,
                vehicle_id: 14,
                supervisor_employee_id: 21,
                supervisor_commission_type: 'percentage',
                supervisor_commission_value: '7.500000',
            }),
        ));
    });

    it('allows an explicit supervisor commission override while creating a job', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await waitFor(() => expect(screen.getByLabelText('Supervisor commission')).toHaveValue('percentage'));
        await user.click(screen.getByRole('button', { name: 'Choose Vehicle' }));
        await user.click(screen.getByRole('button', { name: 'Choose Supervisor' }));
        await user.selectOptions(screen.getByLabelText('Supervisor commission'), 'fixed');
        expect(screen.getByText('Custom value for this job.')).toBeInTheDocument();
        expect(screen.getByLabelText('Commission value')).toHaveValue('0.000000');
        await user.clear(screen.getByLabelText('Commission value'));
        await user.type(screen.getByLabelText('Commission value'), '2500');
        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceJob).toHaveBeenCalledWith(
            expect.objectContaining({
                supervisor_employee_id: 21,
                supervisor_commission_type: 'fixed',
                supervisor_commission_value: '2500',
            }),
        ));
    });

    it('stores an edited default value with its visible commission type', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await waitFor(() => expect(screen.getByLabelText('Commission value')).toHaveValue('7.500000'));
        await user.click(screen.getByRole('button', { name: 'Choose Vehicle' }));
        await user.click(screen.getByRole('button', { name: 'Choose Supervisor' }));
        await user.clear(screen.getByLabelText('Commission value'));
        await user.type(screen.getByLabelText('Commission value'), '9');
        expect(screen.getByText('Custom value for this job.')).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceJob).toHaveBeenCalledWith(
            expect.objectContaining({
                supervisor_commission_type: 'percentage',
                supervisor_commission_value: '9',
            }),
        ));
    });

    it('blocks a supervised job after a default load failure until an actual type is selected', async () => {
        const user = userEvent.setup();
        apiMocks.getVehicleServiceJobCreateDefaults.mockRejectedValueOnce(new Error('Network failure'));

        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        expect(await screen.findByText(/Unable to load the organization default/)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Retry organization default' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save draft' })).toBeEnabled();

        await user.click(screen.getByRole('button', { name: 'Choose Supervisor' }));
        expect(screen.getByRole('button', { name: 'Save draft' })).toBeDisabled();

        await user.selectOptions(screen.getByLabelText('Supervisor commission'), 'fixed');
        expect(screen.getByRole('button', { name: 'Save draft' })).toBeEnabled();
        expect(screen.getByLabelText('Commission value')).toBeEnabled();
    });

    it('shows the stored supervisor commission snapshot while editing without loading current defaults', () => {
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/92/edit']}>
                <VehicleServiceJobForm job={existingJob} />
            </TestRouter>,
        );

        expect(screen.getByLabelText('Supervisor commission')).toHaveValue('percentage');
        expect(screen.getByLabelText('Commission value')).toHaveValue('5.000000');
        expect(screen.getByText('Stored job commission snapshot.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Save job' })).toBeInTheDocument();
        expect(apiMocks.getVehicleServiceJobCreateDefaults).not.toHaveBeenCalled();
    });
});

function lookupChoice(label: string): NamedResource | null {
    if (label === 'Vehicle') {
        return {
            id: 14,
            code: 'VH-14',
            name: 'CAB-1414',
            current_customer: {
                id: 5,
                code: 'CUS-5',
                name: 'Vehicle Owner',
            },
            odometer_reading: '1200.000000',
            odometer_unit: 'km',
        } as NamedResource;
    }

    if (label === 'Bill-to customer') {
        return {
            id: 8,
            code: 'CUS-8',
            name: 'Billing Customer',
        };
    }

    if (label === 'Supervisor') {
        return {
            id: 21,
            code: 'EMP-21',
            name: 'Supervisor',
        };
    }

    return null;
}
