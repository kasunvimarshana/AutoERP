import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import { DataTable } from '@/shared/components/DataTable';
import { createVehicleServiceJobStore } from '../state/vehicleServiceJobStore';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import VehicleServiceLineEditor, {
    buildVehicleServiceLineDisplayRows,
    filterCollapsedComboChildren,
} from './VehicleServiceLineEditor';

const { createLine, updateLine, jobLines, quickItem, setData } = vi.hoisted(() => ({
    createLine: vi.fn(),
    updateLine: vi.fn(),
    setData: vi.fn(),
    quickItem: {
        id: 25,
        code: 'OIL-25',
        name: 'Engine oil',
        item_type: 'stock',
        is_stockable: true,
        base_uom: { id: 5, code: 'PCS', name: 'Pieces' },
        resolved_purchase_unit_price: '800.000000',
        resolved_service_unit_price: '1200.000000',
    } satisfies ItemLookupResource,
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
        {
            id: 103,
            parent_line_id: null,
            line_number: 4,
            line_source_type: 'inventory_item',
            item: { id: 13, code: 'FILTER-1', name: 'Oil filter' },
            uom: { id: 5, code: 'PCS', name: 'Pieces' },
            description: 'Oil filter',
            quantity: '1.000000',
            unit_cost: '800.000000',
            unit_price: '1200.000000',
            discount_rate: '0.000000',
            discount_amount: '100.000000',
            tax_rate: '0.000000',
            tax_amount: '0.000000',
            charge_rate: '0.000000',
            charge_amount: '0.000000',
            line_total: '1100.000000',
            is_inventory_tracked: true,
            is_customer_supplied: false,
            is_external: false,
            is_billable: true,
            is_employee_assignable: false,
            available_stock_quantity: '7.000000',
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
    updateVehicleServiceLine: updateLine,
}));

vi.mock('./line-editor/LineItemFields', async (importOriginal) => {
    const actual = await importOriginal<typeof import('./line-editor/LineItemFields')>();

    return {
        ...actual,
        VehicleServiceLineItemLookup: ({ value, disabled, onChange }: {
            value: ItemLookupResource | null;
            disabled?: boolean;
            onChange: (item: ItemLookupResource | null) => void;
        }) => (
            <button type="button" disabled={disabled} onClick={() => onChange(quickItem)}>
                {value ? value.name : 'Search item'}
            </button>
        ),
    };
});

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
        updateLine.mockReset();
    });

    it('hides resolved combo children until their parent is expanded', () => {
        const rows = buildVehicleServiceLineDisplayRows(jobLines);

        expect(filterCollapsedComboChildren(rows, new Set()).map((row) => row.line.id)).toEqual([100, 103]);
        expect(filterCollapsedComboChildren(rows, new Set([100])).map((row) => row.line.id)).toEqual([100, 101, 102, 103]);
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

        expect(screen.queryAllByText('LAB-SUPERVISOR')).toHaveLength(0);
        const expandButtons = screen.getAllByRole('button', {
            name: 'Expand PACK-1 - Full service, 2 included items',
        });
        expect(expandButtons).toHaveLength(2);
        expect(expandButtons[0]).toHaveAttribute('aria-expanded', 'false');

        await user.click(expandButtons[0]);

        expect(screen.getAllByText('LAB-SUPERVISOR')).toHaveLength(2);
        const collapseButtons = screen.getAllByRole('button', {
            name: 'Collapse PACK-1 - Full service, 2 included items',
        });
        expect(collapseButtons[0]).toHaveAttribute('aria-expanded', 'true');

        await user.click(collapseButtons[0]);

        expect(screen.queryAllByText('LAB-SUPERVISOR')).toHaveLength(0);
    });

    it('shows item code, available stock, compact quantities, and table totals', () => {
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

        expect(screen.getAllByText('FILTER-1 | In stock: 7.0 PCS')).toHaveLength(2);
        expect(screen.getAllByRole('textbox', { name: /Quantity for/ })[0]).toHaveValue('1.0');
        const footer = screen.getByRole('table').querySelector('tfoot');
        expect(footer).toHaveTextContent('Total');
        expect(footer).toHaveTextContent('2.0');
        expect(footer).toHaveTextContent('LKR 100.00');
        expect(footer).toHaveTextContent('LKR 5,600.00');
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

    it('adds a selected item immediately with lookup defaults and clears the quick search', async () => {
        const user = userEvent.setup();
        const onChanged = vi.fn();
        const createdLine = {
            ...jobLines[0],
            id: 200,
            parent_line_id: null,
            line_number: 4,
            line_source_type: 'inventory_item' as const,
            item: quickItem,
            uom: quickItem.base_uom,
            description: quickItem.name,
            unit_cost: '800.000000',
            unit_price: '1200.000000',
            line_total: '1200.000000',
            is_inventory_tracked: true,
            is_external: false,
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

        await user.click(screen.getByRole('button', { name: 'Search item' }));

        await waitFor(() => expect(createLine).toHaveBeenCalledOnce());
        expect(createLine).toHaveBeenCalledWith(13, expect.objectContaining({
            expected_version: 1,
            line_source_type: 'inventory_item',
            item_id: quickItem.id,
            uom_id: quickItem.base_uom.id,
            quantity: '1.000000',
            unit_cost: '800.000000',
            unit_price: '1200.000000',
        }));
        expect(onChanged).toHaveBeenCalledWith(expect.arrayContaining([createdLine]), 2, undefined);
        expect(screen.getByRole('button', { name: 'Search item' })).toBeEnabled();
        expect(screen.queryByRole('heading', { name: 'Add line' })).not.toBeInTheDocument();
    });

    it('clears the quick search and allows retry when creating a selected item fails', async () => {
        const user = userEvent.setup();
        createLine.mockRejectedValueOnce(new ApiError(
            'Could not add line.',
            422,
            'VEHICLE_SERVICE_LINE_INVALID',
            'validation',
        ));

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

        await user.click(screen.getByRole('button', { name: 'Search item' }));

        await waitFor(() => expect(createLine).toHaveBeenCalledOnce());
        expect(screen.getByRole('button', { name: 'Search item' })).toBeEnabled();
    });

    it('updates quantity from the inline controls with the current row version', async () => {
        const user = userEvent.setup();
        const updatedLine = { ...jobLines[0], quantity: '2.000000', line_total: '9000.000000' };
        updateLine.mockResolvedValueOnce({
            line: updatedLine,
            rowVersion: 2,
            workforceLines: [],
            jobTotals: undefined,
        });

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

        await user.click(screen.getAllByRole('button', { name: 'Increase quantity for PACK-1 - Full service' })[0]);

        await waitFor(() => expect(updateLine).toHaveBeenCalledWith(13, 100, expect.objectContaining({
            expected_version: 1,
            quantity: '2.000000',
        })));
    });
});
