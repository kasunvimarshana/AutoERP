import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { DataTable } from '@/shared/components/DataTable';
import { createVehicleServiceJobStore } from '../state/vehicleServiceJobStore';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import VehicleServiceLineEditor, {
    buildVehicleServiceLineDisplayRows,
    filterCollapsedComboChildren,
} from './VehicleServiceLineEditor';

const { createLine, jobLines, setData } = vi.hoisted(() => ({
    createLine: vi.fn(),
    setData: vi.fn(),
    jobLines: [
        {
            id: 100,
            parent_line_id: null,
            line_number: 1,
            line_source_type: 'combo_parent',
            item: { id: 10, code: 'PACK-1', name: 'Full service' },
            description: 'Full service',
            quantity: '1.000000',
            unit_cost: '0.000000',
            unit_price: '4500.000000',
            discount_rate: '0.000000',
            discount_amount: '0.000000',
            tax_rate: '0.000000',
            tax_amount: '0.000000',
            charge_rate: '0.000000',
            charge_amount: '0.000000',
            line_total: '4500.000000',
            is_inventory_tracked: false,
            is_customer_supplied: false,
            is_external: false,
            is_billable: true,
            is_employee_assignable: false,
            status: 'planned',
        },
        {
            id: 101,
            parent_line_id: 100,
            line_number: 2,
            line_source_type: 'combo_child',
            item: { id: 11, code: 'LAB-SUPERVISOR', name: 'Supervisor' },
            description: 'Supervisor',
            quantity: '1.000000',
            unit_cost: '300.000000',
            unit_price: '0.000000',
            discount_rate: '0.000000',
            discount_amount: '0.000000',
            tax_rate: '0.000000',
            tax_amount: '0.000000',
            charge_rate: '0.000000',
            charge_amount: '0.000000',
            line_total: '0.000000',
            is_inventory_tracked: false,
            is_customer_supplied: false,
            is_external: false,
            is_billable: false,
            is_employee_assignable: true,
            status: 'planned',
        },
        {
            id: 102,
            parent_line_id: 100,
            line_number: 3,
            line_source_type: 'combo_child',
            item: { id: 12, code: 'LAB-TECHNICIAN', name: 'Technician' },
            description: 'Technician',
            quantity: '1.000000',
            unit_cost: '400.000000',
            unit_price: '0.000000',
            discount_rate: '0.000000',
            discount_amount: '0.000000',
            tax_rate: '0.000000',
            tax_amount: '0.000000',
            charge_rate: '0.000000',
            charge_amount: '0.000000',
            line_total: '0.000000',
            is_inventory_tracked: false,
            is_customer_supplied: false,
            is_external: false,
            is_billable: false,
            is_employee_assignable: true,
            status: 'planned',
        },
    ] satisfies VehicleServiceJobLine[],
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        permissions: [vehicleServicePermissions.linesView, vehicleServicePermissions.linesManage],
    }),
}));

vi.mock('../vehicleServiceApi', () => ({
    createVehicleServiceLine: createLine,
    deleteVehicleServiceLine: vi.fn(),
    issueVehicleServiceInventory: vi.fn(),
    listInventoryIssueLines: vi.fn(),
    listVehicleServiceLines: vi.fn(),
    updateVehicleServiceLine: vi.fn(),
}));

vi.mock('@/shared/hooks/useApi', () => ({
    useApi: () => ({
        data: jobLines,
        loading: false,
        error: null,
        setData,
    }),
}));

describe('VehicleServiceLineEditor combo disclosure', () => {
    beforeEach(() => {
        setData.mockClear();
        createLine.mockReset();
    });

    it('hides resolved combo children until their parent is expanded', () => {
        const rows = buildVehicleServiceLineDisplayRows(jobLines);

        expect(filterCollapsedComboChildren(rows, new Set()).map((row) => row.line.id)).toEqual([100]);
        expect(filterCollapsedComboChildren(rows, new Set([100])).map((row) => row.line.id)).toEqual([100, 101, 102]);
    });

    it('keeps orphan combo children visible for inventory-only responses without parent rows', () => {
        const orphan = { ...jobLines[1], id: 201, parent_line_id: 999 };
        const rows = buildVehicleServiceLineDisplayRows([orphan]);

        expect(filterCollapsedComboChildren(rows, new Set()).map((row) => row.line.id)).toEqual([201]);
    });

    it('renders collapsed by default and toggles child rows from the accessible combo control', async () => {
        const user = userEvent.setup();
        render(
            <MemoryRouter>
                <VehicleServiceLineEditor
                    jobId={13}
                    expectedVersion={1}
                    onChanged={vi.fn()}
                    onVersionChanged={vi.fn()}
                    jobStore={createVehicleServiceJobStore(13)}
                />
            </MemoryRouter>,
        );

        expect(screen.queryAllByText('LAB-SUPERVISOR - Supervisor')).toHaveLength(0);
        const expandButtons = screen.getAllByRole('button', {
            name: 'Expand PACK-1 - Full service, 2 included items',
        });
        expect(expandButtons).toHaveLength(2);
        expect(expandButtons[0]).toHaveAttribute('aria-expanded', 'false');

        await user.click(expandButtons[0]);

        expect(screen.getAllByText('LAB-SUPERVISOR - Supervisor')).toHaveLength(2);
        const collapseButtons = screen.getAllByRole('button', {
            name: 'Collapse PACK-1 - Full service, 2 included items',
        });
        expect(collapseButtons[0]).toHaveAttribute('aria-expanded', 'true');

        await user.click(collapseButtons[0]);

        expect(screen.queryAllByText('LAB-SUPERVISOR - Supervisor')).toHaveLength(0);
    });

    it('does not trigger a row toggle when an action button is clicked', async () => {
        const user = userEvent.setup();
        const toggleRow = vi.fn();
        const editRow = vi.fn();

        render(
            <MemoryRouter>
                <DataTable
                    rows={[{ id: 1, expandable: true }]}
                    columns={[
                        { key: 'name', header: 'Item', render: () => 'Combo row' },
                        {
                            key: 'actions',
                            header: 'Actions',
                            render: () => <button type="button" onClick={editRow}>Edit row</button>,
                        },
                    ]}
                    rowKey={(row) => row.id}
                    onRowClick={toggleRow}
                    rowClickEnabled={(row) => row.expandable}
                />
            </MemoryRouter>,
        );

        await user.click(screen.getAllByRole('button', { name: 'Edit row' })[0]);
        expect(editRow).toHaveBeenCalledOnce();
        expect(toggleRow).not.toHaveBeenCalled();

        await user.click(screen.getAllByText('Combo row')[0]);
        expect(toggleRow).toHaveBeenCalledOnce();
    });

    it('keeps the create drawer open and resets and focuses the form after adding or clearing', async () => {
        const user = userEvent.setup();
        const onChanged = vi.fn();
        const createdLine = {
            ...jobLines[1],
            id: 200,
            parent_line_id: null,
            line_number: 4,
            line_source_type: 'external_item' as const,
            item: null,
            description: 'Customer supplied oil',
            is_external: true,
        };
        createLine.mockResolvedValueOnce({
            line: createdLine,
            rowVersion: 2,
            workforceLines: [],
            jobTotals: undefined,
        });

        render(
            <MemoryRouter>
                <VehicleServiceLineEditor
                    jobId={13}
                    expectedVersion={1}
                    onChanged={onChanged}
                    onVersionChanged={vi.fn()}
                    jobStore={createVehicleServiceJobStore(13)}
                />
            </MemoryRouter>,
        );

        await user.click(screen.getByRole('button', { name: 'Add line' }));
        await user.click(screen.getByRole('button', { name: 'Enter an external or customer-supplied item' }));
        await user.type(screen.getByLabelText('External item description'), 'Customer supplied oil');
        await user.click(screen.getAllByRole('button', { name: 'Add line' })
            .find((button) => button.getAttribute('type') === 'submit')!);

        await waitFor(() => expect(createLine).toHaveBeenCalledOnce());
        const resetItemInput = await screen.findByRole('combobox', { name: 'Item' });
        expect(resetItemInput).toHaveFocus();
        expect(screen.getByRole('heading', { name: 'Add line' })).toBeInTheDocument();
        expect(screen.queryByLabelText('External item description')).not.toBeInTheDocument();
        await waitFor(() => expect(screen.getAllByRole('button', { name: 'Add line' })
            .find((button) => button.getAttribute('type') === 'submit')).toBeEnabled());

        await user.type(resetItemInput, 'oil');
        await user.click(screen.getByRole('button', { name: 'Clear' }));
        expect(screen.getByRole('combobox', { name: 'Item' })).toHaveValue('');
        expect(screen.getByRole('combobox', { name: 'Item' })).toHaveFocus();
    });

    it('keeps the drawer and entered values when creating a line fails', async () => {
        const user = userEvent.setup();
        createLine.mockRejectedValueOnce(new Error('Could not add line.'));

        render(
            <MemoryRouter>
                <VehicleServiceLineEditor
                    jobId={13}
                    expectedVersion={1}
                    onChanged={vi.fn()}
                    onVersionChanged={vi.fn()}
                    jobStore={createVehicleServiceJobStore(13)}
                />
            </MemoryRouter>,
        );

        await user.click(screen.getByRole('button', { name: 'Add line' }));
        await user.click(screen.getByRole('button', { name: 'Enter an external or customer-supplied item' }));
        const descriptionInput = screen.getByLabelText('External item description');
        await user.type(descriptionInput, 'Keep this value');
        await user.click(screen.getAllByRole('button', { name: 'Add line' })
            .find((button) => button.getAttribute('type') === 'submit')!);

        await waitFor(() => expect(createLine).toHaveBeenCalledOnce());
        await waitFor(() => expect(screen.getAllByRole('button', { name: 'Add line' })
            .find((button) => button.getAttribute('type') === 'submit')).toBeEnabled());
        expect(descriptionInput).toHaveValue('Keep this value');
        expect(screen.getByRole('heading', { name: 'Add line' })).toBeInTheDocument();
    });
});
