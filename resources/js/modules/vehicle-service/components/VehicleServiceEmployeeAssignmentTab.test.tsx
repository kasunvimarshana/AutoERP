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
import { createVehicleServiceJobStore } from '../state/vehicleServiceJobStore';

const apiMocks = vi.hoisted(() => ({
    createVehicleServiceEmployee: vi.fn(),
    deleteVehicleServiceEmployee: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    listEmployeeAssignableLines: vi.fn(),
    updateVehicleServiceEmployee: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({ label, value, disabled, placeholder, onChange }: {
        label: string;
        value: NamedResource | null;
        disabled?: boolean;
        placeholder?: string;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button
            type="button"
            disabled={disabled}
            onClick={() => onChange(placeholder?.startsWith('Search supervisors')
                ? { id: 32, code: 'EMP-32', name: 'Alternate Supervisor' }
                : { id: 22, code: 'EMP-22', name: 'Second technician' })}
        >
            {value ? `${value.code} - ${value.name}` : `Choose ${label}`}
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

function line(
    employeeAssignments: VehicleServiceEmployeeAssignment[],
    usesJobSupervisor = false,
): VehicleServiceJobLine {
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
        uses_job_supervisor: usesJobSupervisor,
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
        type: 'full_service',
        customer_id: 5,
        vehicle_id: 9,
        supervisor_employee_id: 31,
        supervisor: { id: 31, code: 'EMP-31', name: 'Service Supervisor' },
        supervisor_commission_type: 'none',
        supervisor_commission_value: '0.000000',
        supervisor_commission_amount: '0.000000',
        status: 'draft',
        subtotal: '1000.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '1000.000000',
        commission_cost_total: '0.000000',
        net_after_commission: '1000.000000',
    };
}

async function submitNewAssignment() {
    const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Choose Employee' }));
    await user.click(screen.getByRole('button', { name: 'Add' }));
}

function WorkforceHarness({
    onVersionChanged,
    active = true,
}: {
    onVersionChanged: (nextVersion: number) => void;
    active?: boolean;
}) {
    const [expectedVersion, setExpectedVersion] = useState(7);
    const [jobStore] = useState(() => createVehicleServiceJobStore(7));

    return (
        <VehicleServiceEmployeeAssignmentTab
            jobId={7}
            expectedVersion={expectedVersion}
            active={active}
            jobStore={jobStore}
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
        expect(apiMocks.createVehicleServiceEmployee.mock.calls[0]?.[2]).not.toHaveProperty('role_type');
        expect(onVersionChanged).toHaveBeenNthCalledWith(1, 9);
        await waitFor(() => expect(onVersionChanged).toHaveBeenLastCalledWith(10));
        expect(apiMocks.listEmployeeAssignableLines).toHaveBeenCalledTimes(2);
        expect(apiMocks.getVehicleServiceJob).toHaveBeenCalledTimes(2);
        expect((await screen.findAllByText('fixed: 50.000000')).length).toBeGreaterThanOrEqual(2);
    });

    it('keeps cached workforce visible and revalidates when the tab becomes active again', async () => {
        const onVersionChanged = vi.fn();
        const view = render(
            <TestRouter>
                <WorkforceHarness active onVersionChanged={onVersionChanged} />
            </TestRouter>,
        );
        expect((await screen.findAllByText('fixed: 100.000000')).length).toBeGreaterThanOrEqual(1);

        view.rerender(
            <TestRouter>
                <WorkforceHarness active={false} onVersionChanged={onVersionChanged} />
            </TestRouter>,
        );
        view.rerender(
            <TestRouter>
                <WorkforceHarness active onVersionChanged={onVersionChanged} />
            </TestRouter>,
        );

        await waitFor(() => expect(apiMocks.listEmployeeAssignableLines).toHaveBeenCalledTimes(2));
        expect((await screen.findAllByText('fixed: 50.000000')).length).toBeGreaterThanOrEqual(1);
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
        expect(screen.getByRole('button', { name: 'Add' })).toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Assign employee' })).not.toBeInTheDocument();
    });

    it('groups combo labour lines under the human-readable combo name', async () => {
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue([
            {
                ...line([]),
                parent_line_id: 90,
                parent_line: { id: 90, line_number: 1, description: 'ACID RAIN REMOVER L' },
                commission_default: {
                    commission_type: 'fixed',
                    commission_value: '400.000000',
                    locked: true,
                },
            },
        ]);

        renderTab(vi.fn());

        expect(await screen.findByRole('heading', { name: 'ACID RAIN REMOVER L' })).toBeInTheDocument();
        expect(screen.getByText('Combo line 1')).toBeInTheDocument();
        expect(screen.getByText('Commission pool: 400.000000')).toBeInTheDocument();
        expect(screen.getByText('Not assigned')).toBeInTheDocument();
    });

    it('defaults every supervisor labour line to the Job Card supervisor', async () => {
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue([
            {
                ...line([], true),
                parent_line_id: 90,
                parent_line: { id: 90, line_number: 1, description: 'First combo' },
            },
            {
                ...line([], true),
                id: 12,
                line_number: 3,
                parent_line_id: 91,
                parent_line: { id: 91, line_number: 2, description: 'Second combo' },
            },
        ]);

        renderTab(vi.fn());

        expect(await screen.findAllByRole('button', {
            name: 'EMP-31 - Service Supervisor',
        })).toHaveLength(2);
    });

    it('allows an alternate Supervisor employee to be assigned to a supervisor labour line', async () => {
        apiMocks.listEmployeeAssignableLines
            .mockReset()
            .mockResolvedValueOnce([line([], true)])
            .mockResolvedValue([line([], true)]);
        const user = userEvent.setup();
        renderTab(vi.fn());

        const supervisorSelect = await screen.findByRole('button', {
            name: 'EMP-31 - Service Supervisor',
        });
        expect(supervisorSelect).toBeEnabled();
        await user.click(supervisorSelect);
        await user.click(screen.getByRole('button', { name: 'Add' }));
        expect(screen.queryByRole('heading', { name: 'Assign job supervisor' })).not.toBeInTheDocument();

        await waitFor(() => expect(apiMocks.createVehicleServiceEmployee).toHaveBeenCalledWith(
            7,
            11,
            expect.objectContaining({ employee_id: 32, expected_version: 9 }),
        ));
    });

    it('allows a Supervisor employee to be selected when the Job Card has no default supervisor', async () => {
        apiMocks.listEmployeeAssignableLines
            .mockReset()
            .mockResolvedValueOnce([line([], true)])
            .mockResolvedValue([line([], true)]);
        apiMocks.getVehicleServiceJob.mockReset().mockResolvedValue({
            ...refreshedJob(9),
            supervisor_employee_id: null,
            supervisor: null,
        });
        const user = userEvent.setup();

        renderTab(vi.fn());

        const supervisorSelect = await screen.findByRole('button', { name: 'Choose Employee' });
        expect(supervisorSelect).toBeEnabled();
        await user.click(supervisorSelect);
        await user.click(screen.getByRole('button', { name: 'Add' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceEmployee).toHaveBeenCalledWith(
            7,
            11,
            expect.objectContaining({ employee_id: 32, expected_version: 9 }),
        ));
    });

    it('keeps the drawer only for editing advanced assignment details', async () => {
        const user = userEvent.setup();
        renderTab(vi.fn());

        await user.click(await screen.findByRole('button', { name: 'Edit' }));

        expect(screen.getByRole('heading', { name: 'Edit assignment' })).toBeInTheDocument();
        expect(screen.getByLabelText('Assigned hours')).toHaveValue('1.000000');
        expect(screen.getByLabelText('Service / labour line')).toBeDisabled();
    });

    it('allows an existing supervisor assignment employee to be changed', async () => {
        const supervisorAssignment = {
            ...firstAssignment,
            employee_id: 31,
            employee: { id: 31, code: 'EMP-31', name: 'Service Supervisor' },
            role_type: 'supervisor',
        };
        apiMocks.listEmployeeAssignableLines
            .mockReset()
            .mockResolvedValueOnce([line([supervisorAssignment], true)])
            .mockResolvedValue([line([supervisorAssignment], true)]);
        const user = userEvent.setup();
        renderTab(vi.fn());

        await user.click(await screen.findByRole('button', { name: 'Edit' }));
        const employeeSelect = screen.getByRole('button', {
            name: 'EMP-31 - Service Supervisor',
        });
        expect(employeeSelect).toBeEnabled();
        await user.click(employeeSelect);
        await user.click(screen.getByRole('button', { name: 'Save assignment' }));

        await waitFor(() => expect(apiMocks.updateVehicleServiceEmployee).toHaveBeenCalledWith(
            7,
            11,
            1,
            expect.objectContaining({ employee_id: 32, expected_version: 9 }),
        ));
    });

});
