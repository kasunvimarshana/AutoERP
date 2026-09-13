import { useState } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { VehicleServiceEmployeeAssignment, VehicleServiceJob, VehicleServiceJobLine } from '../vehicleServiceTypes';
import { createVehicleServiceJobStore } from '../state/vehicleServiceJobStore';
import VehicleServiceEmployeeAssignmentTab from './VehicleServiceEmployeeAssignmentTab';

const apiMocks = vi.hoisted(() => ({
    createVehicleServiceEmployeeBatch: vi.fn(),
    deleteVehicleServiceEmployee: vi.fn(),
    getVehicleServiceJob: vi.fn(),
    listEmployeeAssignableLines: vi.fn(),
    updateVehicleServiceEmployee: vi.fn(),
}));

vi.mock('../vehicleServiceApi', () => apiMocks);

vi.mock('./employee-assignment/EmployeePickerPanel', () => ({
    EmployeePickerPanel: ({ onToggle }: { onToggle: (employee: NamedResource) => void }) => (
        <aside aria-label="Employee contacts">
            <button type="button" onClick={() => onToggle({ id: 22, code: 'EMP-22', name: 'Second technician' })}>Choose Second technician</button>
            <button type="button" onClick={() => onToggle({ id: 23, code: 'EMP-23', name: 'Third technician' })}>Choose Third technician</button>
        </aside>
    ),
}));

vi.mock('@/shared/components/GenericLookupSelect', () => ({
    GenericLookupSelect: ({ label, value, disabled, onChange }: {
        label: string;
        value: NamedResource | null;
        disabled?: boolean;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button type="button" disabled={disabled} onClick={() => onChange({ id: 22, code: 'EMP-22', name: 'Second technician' })}>
            {value ? `${value.code} - ${value.name}` : `Choose ${label}`}
        </button>
    ),
}));

const firstAssignment = assignment(1, 21, 'First technician', '100.000000');
const secondAssignment = assignment(2, 22, 'Second technician', '50.000000');
const initialLines = [line([firstAssignment])];
const refreshedLines = [line([{ ...firstAssignment, commission_amount: '50.000000' }, secondAssignment])];

function assignment(id: number, employeeId: number, employeeName: string, commissionAmount: string): VehicleServiceEmployeeAssignment {
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

function line(employeeAssignments: VehicleServiceEmployeeAssignment[], usesJobSupervisor = false): VehicleServiceJobLine {
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
        line_discount_total: '0.000000',
        job_discount_base: '1000.000000',
        job_discount_amount: '0.000000',
        discount_total: '0.000000',
        tax_total: '0.000000',
        charge_total: '0.000000',
        grand_total: '1000.000000',
        commission_cost_total: '0.000000',
        net_after_commission: '1000.000000',
    };
}

async function chooseAndAssignEmployee() {
    const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Select one or more employees' }));
    await user.click(screen.getByRole('button', { name: 'Choose Second technician' }));
    await user.click(screen.getByRole('button', { name: 'Assign selected employees (1)' }));
}

function WorkforceHarness({ onVersionChanged }: { onVersionChanged: (nextVersion: number) => void }) {
    const [expectedVersion, setExpectedVersion] = useState(7);
    const [jobStore] = useState(() => createVehicleServiceJobStore(7));
    return <VehicleServiceEmployeeAssignmentTab jobId={7} expectedVersion={expectedVersion} active jobStore={jobStore} onChanged={(nextVersion) => { setExpectedVersion(nextVersion); onVersionChanged(nextVersion); }} />;
}

function renderTab(onVersionChanged: (nextVersion: number) => void = vi.fn()) {
    return render(<TestRouter><WorkforceHarness onVersionChanged={onVersionChanged} /></TestRouter>);
}

describe('VehicleServiceEmployeeAssignmentTab', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listEmployeeAssignableLines.mockResolvedValueOnce(initialLines).mockResolvedValue(refreshedLines);
        apiMocks.getVehicleServiceJob.mockResolvedValueOnce(refreshedJob(9)).mockResolvedValue(refreshedJob(10));
        apiMocks.createVehicleServiceEmployeeBatch.mockResolvedValue([secondAssignment]);
        apiMocks.updateVehicleServiceEmployee.mockResolvedValue(secondAssignment);
        apiMocks.deleteVehicleServiceEmployee.mockResolvedValue(undefined);
    });

    it('uses the refreshed version and one header action for all pending assignments', async () => {
        const onVersionChanged = vi.fn();
        renderTab(onVersionChanged);
        await chooseAndAssignEmployee();

        await waitFor(() => expect(apiMocks.createVehicleServiceEmployeeBatch).toHaveBeenCalledWith(7, {
            expected_version: 9,
            lines: [{ line_id: 11, employee_ids: [22] }],
        }));
        expect(screen.queryByRole('button', { name: 'Add' })).not.toBeInTheDocument();
        expect(onVersionChanged).toHaveBeenNthCalledWith(1, 9);
        await waitFor(() => expect(onVersionChanged).toHaveBeenLastCalledWith(10));
    });

    it('assigns multiple pending employees to the same labour line', async () => {
        const user = userEvent.setup();
        renderTab();

        await user.click(await screen.findByRole('button', { name: 'Select one or more employees' }));
        await user.click(screen.getByRole('button', { name: 'Choose Second technician' }));
        await user.click(screen.getByRole('button', { name: 'Choose Third technician' }));

        expect(screen.getByRole('button', { name: '2 employees selected' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Remove EMP-22 - Second technician' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Remove EMP-23 - Third technician' })).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Assign selected employees (2)' }));

        await waitFor(() => expect(apiMocks.createVehicleServiceEmployeeBatch).toHaveBeenCalledWith(7, {
            expected_version: 9,
            lines: [{ line_id: 11, employee_ids: [22, 23] }],
        }));
    });

    it('reloads stale data and preserves the pending choice for retry', async () => {
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue(initialLines);
        apiMocks.getVehicleServiceJob.mockReset().mockResolvedValueOnce(refreshedJob(9)).mockResolvedValue(refreshedJob(12));
        apiMocks.createVehicleServiceEmployeeBatch.mockRejectedValueOnce(new ApiError('Validation failed', 422, null, null, { expected_version: ['Vehicle service job was changed by another request.'] }));
        renderTab();
        await chooseAndAssignEmployee();

        expect((await screen.findAllByText(/Latest job and workforce data has been loaded/)).length).toBeGreaterThanOrEqual(1);
        expect(screen.getByRole('button', { name: 'Assign selected employees (1)' })).toBeEnabled();
    });

    it('groups combo labour lines under the human-readable combo name', async () => {
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue([{ ...line([]), parent_line_id: 90, parent_line: { id: 90, line_number: 1, description: 'ACID RAIN REMOVER L' }, commission_default: { commission_type: 'fixed', commission_value: '400.000000', locked: true } }]);
        renderTab();
        expect(await screen.findByRole('heading', { name: 'ACID RAIN REMOVER L' })).toBeInTheDocument();
        expect(screen.getByText('Commission pool: 400.000000')).toBeInTheDocument();
    });

    it('shows supervisor assignment first, locks it, and includes it automatically', async () => {
        const regularLine = { ...line([]), id: 12, line_number: 1, description: 'Technician line' };
        const supervisorLine = { ...line([], true), id: 11, line_number: 2, description: 'Supervisor line' };
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValueOnce([regularLine, supervisorLine]).mockResolvedValue([regularLine, supervisorLine]);
        renderTab();

        const lineHeadings = await screen.findAllByRole('heading', { level: 4 });
        expect(lineHeadings[0]).toHaveTextContent('Supervisor line');
        expect(screen.getByText('EMP-31 - Service Supervisor')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'EMP-31 - Service Supervisor' })).not.toBeInTheDocument();
        await userEvent.click(screen.getByRole('button', { name: 'Assign selected employees (1)' }));
        await waitFor(() => expect(apiMocks.createVehicleServiceEmployeeBatch).toHaveBeenCalledWith(7, expect.objectContaining({ lines: [{ line_id: 11, employee_ids: [31] }] })));
    });

    it('blocks an automatic supervisor assignment when the Job Card has no supervisor', async () => {
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue([line([], true)]);
        apiMocks.getVehicleServiceJob.mockReset().mockResolvedValue({ ...refreshedJob(9), supervisor_employee_id: null, supervisor: null });
        renderTab();
        expect(await screen.findByText('Select a Job Card supervisor first.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Assign selected employees' })).toBeDisabled();
    });

    it('keeps advanced details in the edit drawer', async () => {
        renderTab();
        await userEvent.click(await screen.findByRole('button', { name: 'Edit' }));
        expect(screen.getByRole('heading', { name: 'Edit assignment' })).toBeInTheDocument();
        expect(screen.getByLabelText('Assigned hours')).toHaveValue('1.000000');
        expect(screen.getByLabelText('Service / labour line')).toBeDisabled();
    });

    it('keeps an existing supervisor assignment locked to the Job Card supervisor', async () => {
        const supervisorAssignment = { ...firstAssignment, employee_id: 31, employee: { id: 31, code: 'EMP-31', name: 'Service Supervisor' }, role_type: 'supervisor' };
        apiMocks.listEmployeeAssignableLines.mockReset().mockResolvedValue([line([supervisorAssignment], true)]);
        renderTab();
        await userEvent.click(await screen.findByRole('button', { name: 'Edit' }));
        expect(screen.getByText('This line always uses the Job Card supervisor.')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'EMP-31 - Service Supervisor' })).not.toBeInTheDocument();
    });
});
