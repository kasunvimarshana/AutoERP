import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TestRouter } from '@/test/TestRouter';
import type { NamedResource } from '@/shared/types/common';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { VehicleServiceJobForm } from './VehicleServiceJobForm';

const apiMocks = vi.hoisted(() => ({
    createVehicleServiceJob: vi.fn(),
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

describe('VehicleServiceJobForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.createVehicleServiceJob.mockResolvedValue({
            id: 91,
            job_number: 'VSJ-91',
        });
    });

    it('submits the vehicle owner and selected bill-to customer', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/vehicle-service/jobs/create']}>
                <VehicleServiceJobForm />
            </TestRouter>,
        );

        await user.click(screen.getByRole('button', { name: 'Choose Vehicle' }));
        await user.click(screen.getByRole('button', { name: 'Choose Bill-to customer' }));
        await user.click(screen.getByRole('button', { name: 'Save draft' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceJob).toHaveBeenCalledWith(
            expect.objectContaining({
                customer_id: 5,
                bill_to_customer_id: 8,
                vehicle_id: 14,
            }),
        ));
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
