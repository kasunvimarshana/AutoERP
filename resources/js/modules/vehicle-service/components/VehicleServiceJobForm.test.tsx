import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NamedResource } from '@/shared/types/common';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import { VehicleServiceJobForm } from './VehicleServiceJobForm';
import { VehicleServiceSummaryPanel } from './VehicleServiceSummaryPanel';

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
    type: 'body_wash',
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
    commission_cost_total: '0.000000',
    net_after_commission: '0.000000',
};

describe('VehicleServiceJobForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.createVehicleServiceJob.mockResolvedValue({
            id: 91,
            job_number: 'VSJ-91',
        });
        apiMocks.updateVehicleServiceJob.mockResolvedValue(existingJob);
    });

    it('suggests the next service mileage for a full service job and preserves an edited value', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        [
            'Bill-to customer',
            'Job date',
            'Expected delivery',
            'Fuel level',
            'Priority',
            'Supervisor commission',
            'Commission value',
        ].forEach((label) => {
            expect(screen.queryByLabelText(label)).not.toBeInTheDocument();
        });
        expect(screen.getByLabelText('Odometer')).toBeRequired();
        expect(screen.getByLabelText('Odometer')).toBeEnabled();
        expect(screen.getByLabelText('Next Service Mileage')).toBeEnabled();
        expect(screen.getByLabelText('Next Service Mileage')).not.toBeRequired();
        expect(screen.getByLabelText('Manual Job Card')).not.toBeRequired();
        expect(screen.getByRole('option', { name: 'Oil Change' })).toHaveValue('oil_change');
        expect(screen.getByRole('option', { name: 'Accessories' })).toHaveValue('accessories');
        expect(apiMocks.getVehicleServiceJobCreateDefaults).not.toHaveBeenCalled();

        await user.click(screen.getByRole('button', { name: 'Choose Vehicle' }));
        await user.click(screen.getByRole('button', { name: 'Choose Supervisor' }));
        expect(screen.getByLabelText('Odometer')).toHaveValue('1200.000000');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('6200.000000');
        await user.clear(screen.getByLabelText('Next Service Mileage'));
        await user.type(screen.getByLabelText('Next Service Mileage'), '15000');
        await user.type(screen.getByLabelText('Manual Job Card'), 'MJC-1042');
        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceJob).toHaveBeenCalledWith(
            expect.objectContaining({
                customer_id: 5,
                bill_to_customer_id: 5,
                vehicle_id: 14,
                type: 'full_service',
                supervisor_employee_id: 21,
                odometer_reading: '1200.000000',
                next_service_mileage: '15000',
                manual_job_card: 'MJC-1042',
            }),
        ));
        const submittedPayload = apiMocks.createVehicleServiceJob.mock.calls[0]?.[0];
        expect(submittedPayload).not.toHaveProperty('supervisor_commission_type');
        expect(submittedPayload).not.toHaveProperty('supervisor_commission_value');
    });

    it('clears and disables mileage fields for body wash, then starts blank when changed to full service', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await user.type(screen.getByLabelText('Odometer'), '3000');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('8000.000000');

        await user.selectOptions(screen.getByLabelText('Type'), 'body_wash');
        expect(screen.getByLabelText('Odometer')).toBeDisabled();
        expect(screen.getByLabelText('Odometer')).toHaveValue('');
        expect(screen.getByLabelText('Next Service Mileage')).toBeDisabled();
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('');

        await user.selectOptions(screen.getByLabelText('Type'), 'accessories');
        expect(screen.getByLabelText('Odometer')).toBeDisabled();
        expect(screen.getByLabelText('Next Service Mileage')).toBeDisabled();
        expect(screen.getAllByText('Not applicable to Accessories.')).toHaveLength(2);

        await user.selectOptions(screen.getByLabelText('Type'), 'full_service');
        expect(screen.getByLabelText('Odometer')).toBeEnabled();
        expect(screen.getByLabelText('Odometer')).toBeRequired();
        expect(screen.getByLabelText('Odometer')).toHaveValue('');
        expect(screen.getByLabelText('Next Service Mileage')).toBeEnabled();
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('');
    });

    it('applies the editable mileage suggestion to oil change jobs', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await user.selectOptions(screen.getByLabelText('Type'), 'oil_change');
        expect(screen.getByLabelText('Odometer')).toBeEnabled();
        expect(screen.getByLabelText('Odometer')).toBeRequired();
        await user.type(screen.getByLabelText('Odometer'), '7000');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('12000.000000');
        await user.clear(screen.getByLabelText('Next Service Mileage'));
        await user.type(screen.getByLabelText('Next Service Mileage'), '12500');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('12500');
    });

    it('keeps hidden fields out of edit mode and clearly styles disabled body wash mileage fields', () => {
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/92/edit']}>
                <VehicleServiceJobForm job={existingJob} />
            </TestRouter>,
        );

        expect(screen.getByLabelText('Type')).toHaveValue('body_wash');
        [
            'Job date',
            'Expected delivery',
            'Fuel level',
            'Priority',
            'Supervisor commission',
            'Commission value',
        ].forEach((label) => {
            expect(screen.queryByLabelText(label)).not.toBeInTheDocument();
        });
        expect(screen.queryByRole('button', { name: 'Choose Bill-to customer' })).not.toBeInTheDocument();
        expect(screen.getByLabelText('Odometer')).toBeDisabled();
        expect(screen.getByLabelText('Odometer')).toHaveValue('');
        expect(screen.getByLabelText('Odometer')).toHaveClass('disabled:bg-slate-100', 'disabled:text-slate-400');
        expect(screen.getByLabelText('Next Service Mileage')).toBeDisabled();
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveClass('disabled:bg-slate-100', 'disabled:text-slate-400');
        expect(screen.getAllByText('Not applicable to Body Wash.')).toHaveLength(2);
        expect(screen.getByRole('button', { name: 'Save job' })).toBeInTheDocument();
        expect(apiMocks.getVehicleServiceJobCreateDefaults).not.toHaveBeenCalled();
    });

    it('enables blank mileage fields when an existing body wash job changes to full service', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/92/edit']}>
                <VehicleServiceJobForm job={existingJob} />
            </TestRouter>,
        );

        await user.selectOptions(screen.getByLabelText('Type'), 'full_service');
        expect(screen.getByLabelText('Odometer')).toBeEnabled();
        expect(screen.getByLabelText('Odometer')).toBeRequired();
        expect(screen.getByLabelText('Odometer')).toHaveValue('');

        await user.type(screen.getByLabelText('Odometer'), '4000');
        expect(screen.getByLabelText('Next Service Mileage')).toHaveValue('9000.000000');
        await user.clear(screen.getByLabelText('Next Service Mileage'));
        await user.type(screen.getByLabelText('Next Service Mileage'), '9500');
        await user.click(screen.getByRole('button', { name: 'Save job' }));

        await waitFor(() => expect(apiMocks.updateVehicleServiceJob).toHaveBeenCalledWith(
            92,
            expect.objectContaining({
                type: 'full_service',
                odometer_reading: '4000',
                next_service_mileage: '9500',
            }),
        ));
    });

    it('hides nonessential and inapplicable fields from a body wash overview', () => {
        render(<VehicleServiceSummaryPanel job={existingJob} />);

        [
            'Job date',
            'Expected delivery',
            'Odometer',
            'Next service mileage',
            'Fuel level',
            'Priority',
            'Supervisor commission',
        ].forEach((label) => {
            expect(screen.queryByText(label)).not.toBeInTheDocument();
        });
        expect(screen.getByText('Manual job card')).toBeInTheDocument();
        expect(screen.getByText('Type')).toBeInTheDocument();
    });

    it('shows mileage values in an oil change overview', () => {
        render(<VehicleServiceSummaryPanel job={{
            ...existingJob,
            type: 'oil_change',
            type_label: 'Oil Change',
            odometer_reading: '4000.000000',
            next_service_mileage: '9500.000000',
        }} />);

        expect(screen.getByText('Odometer')).toBeInTheDocument();
        expect(screen.getByText('4000.000000')).toBeInTheDocument();
        expect(screen.getByText('Next service mileage')).toBeInTheDocument();
        expect(screen.getByText('9500.000000')).toBeInTheDocument();
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
