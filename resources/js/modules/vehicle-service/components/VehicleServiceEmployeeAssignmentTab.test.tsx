import { useState } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    VehicleServiceEmployeeAssignment,
    VehicleServiceJob,
    VehicleServiceJobLine,
} from '../vehicleServiceTypes';
import VehicleServiceEmployeeAssignmentTab from './VehicleServiceEmployeeAssignmentTab';

const apiMocks = vi.hoisted(() => ({
    createVehicleServiceEmployee: vi.fn(),
    deleteVehicleServiceEmployee: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    listEmployeeAssignableLines: vi.fn(),
    updateVehicleServiceEmployee: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({ label, onChange }: {
        label: string;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button
            type="button"
            onClick={() => onChange({ id: 22, code: 'EMP-22', name: 'Second technician' })}
        >
            Choose {label}
        </button>
    ),
}));

const firstAssignment = assignment(1, 21, 'First technician', '100.000000');
const secondAssignment = assignment(2, 22, 'Second technician', '50.000000');
const initialLines = [line([firstAssignment])];
const refreshedLines = [line([
    { ...firstAssignment, commission_amount: '50.000000' },
    secondAssignment,
])];

function assignment(
    id: number,
    employeeId: number,
    employeeName: string,
    commissionAmount: string,
): VehicleServiceEmployeeAssignment {
    return {
        id,
        vehicle_service_job_line_id: 11,
        employee_id: employeeId,
        employee: { id: employeeId, code: `EMP-${employeeId}`, name: employeeName },
        role_type: 'technician',
        assigned_hours: '1.000000',
        rate: '0.000000',
        commission_type: 'fixed',
        commission_value: '100.000000',
        commission_amount: commissionAmount,
        status: 'assigned',
    };
}

function line(employeeAssignments: VehicleServiceEmployeeAssignment[]): VehicleServiceJobLine {
    return {
        id: 11,
        line_number: 1,
        line_source_type: 'labour_item',
        description: 'Shared labour commission',
        quantity: '1.000000',
        unit_cost: '0.000000',
        unit_price: '1000.000000',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
        line_total: '1000.000000',
        is_inventory_tracked: false,
        is_customer_supplied: false,
        is_external: false,
        is_billable: true,
        is_employee_assignable: true,
        status: 'pending',
        employee_assignments: employeeAssignments,
    };
}

function refreshedJob(rowVersion: number): VehicleServiceJob {
    return {
        id: 7,
        row_version: rowVersion,
        job_number: 'VSJ-7',
        job_date: '2026-07-17',
        customer_id: 5,
        vehicle_id: 9,
        supervisor_commission_type: 'none',
        supervisor_commission_value: '0.000000',
        supervisor_commission_amount: '0.000000',
        status: 'draft',
        subtotal: '1000.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '1000.000000',
    };
}

async function submitNewAssignment() {
    const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Assign employee' }));
    await user.selectOptions(screen.getByLabelText('Service / labour line'), '11');
    await user.click(screen.getByRole('button', { name: 'Choose Employee' }));
    await user.click(screen.getByRole('button', { name: 'Save assignment' }));
}

function WorkforceHarness({ onVersionChanged }: { onVersionChanged: (nextVersion: number) => void }) {
    const [expectedVersion, setExpectedVersion] = useState(7);

    return (
        <VehicleServiceEmployeeAssignmentTab
            jobId={7}
            expectedVersion={expectedVersion}
            onChanged={(nextVersion) => {
                setExpectedVersion(nextVersion);
                onVersionChanged(nextVersion);
            }}
        />
    );
}

function renderTab(onVersionChanged: (nextVersion: number) => void) {
    return render(
        <TestRouter>
            <WorkforceHarness onVersionChanged={onVersionChanged} />
        </TestRouter>,
    );
}

describe('VehicleServiceEmployeeAssignmentTab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listEmployeeAssignableLines
            .mockResolvedValueOnce(initialLines)
            .mockResolvedValue(refreshedLines);
        apiMocks.getVehicleServiceJob
            .mockResolvedValueOnce(refreshedJob(9))
            .mockResolvedValue(refreshedJob(10));
        apiMocks.createVehicleServiceEmployee.mockResolvedValue(secondAssignment);
        apiMocks.updateVehicleServiceEmployee.mockResolvedValue(secondAssignment);
        apiMocks.deleteVehicleServiceEmployee.mockResolvedValue(undefined);
    });

    it('synchronizes the job version before the first assignment mutation', async () => {
        const onVersionChanged = vi.fn();
        renderTab(onVersionChanged);

        await submitNewAssignment();

        await waitFor(() => expect(apiMocks.createVehicleServiceEmployee).toHaveBeenCalledWith(
            7,
            11,
            expect.objectContaining({ expected_version: 9, employee_id: 22 }),
        ));
        expect(onVersionChanged).toHaveBeenNthCalledWith(1, 9);
        await waitFor(() => expect(onVersionChanged).toHaveBeenLastCalledWith(10));
        expect(apiMocks.listEmployeeAssignableLines).toHaveBeenCalledTimes(2);
        expect(apiMocks.getVehicleServiceJob).toHaveBeenCalledTimes(2);
        expect((await screen.findAllByText('fixed: 50.000000')).length).toBeGreaterThanOrEqual(2);
    });

    it('reloads the latest state after a genuine stale-version rejection and keeps the form retryable', async () => {
        const onVersionChanged = vi.fn();
        apiMocks.getVehicleServiceJob
            .mockReset()
            .mockResolvedValueOnce(refreshedJob(9))
            .mockResolvedValue(refreshedJob(12));
        apiMocks.createVehicleServiceEmployee.mockRejectedValueOnce(new ApiError(
            'Validation failed',
            422,
            null,
            null,
            { expected_version: ['Vehicle service job was changed by another request.'] },
        ));

        renderTab(onVersionChanged);
        await submitNewAssignment();

        expect((await screen.findAllByText(/Latest job and workforce data has been loaded/)).length).toBeGreaterThanOrEqual(1);
        expect(onVersionChanged).toHaveBeenNthCalledWith(1, 9);
        expect(onVersionChanged).toHaveBeenLastCalledWith(12);
        expect(apiMocks.createVehicleServiceEmployee).toHaveBeenCalledWith(
            7,
            11,
            expect.objectContaining({ expected_version: 9 }),
        );
        expect(screen.getByRole('button', { name: 'Save assignment' })).toBeInTheDocument();
    });
});
