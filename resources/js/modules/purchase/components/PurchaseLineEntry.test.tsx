import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { FastPurchaseLines, blankFastPurchaseLine } from './FastPurchaseLines';
import { PurchaseOrderLineEditor, type EditablePurchaseLine } from './PurchaseOrderLineEditor';
import { fastPurchaseLineToPayload, purchaseOrderLineFromResource, purchaseOrderLineToPayload } from './purchaseLineAdapters';
import type { FastPurchaseContext } from '../purchaseTypes';

vi.mock('./PurchaseLookups', () => ({
    ItemLookupSelect: ({ value, onChange, error }: {
        value: { id: number; code?: string; name?: string } | null;
        onChange: (value: { id: number; code?: string; name?: string } | null) => void;
        error?: string;
    }) => (
        <div>
            <label>
                Item
                <input aria-label="Item" readOnly value={value?.name ?? ''} aria-invalid={Boolean(error)} />
            </label>
            <button type="button" onClick={() => onChange({ id: 1, code: 'ITEM-1', name: 'Brake pad' })}>Choose brake pad</button>
            {error && <span>{error}</span>}
        </div>
    ),
}));

vi.mock('../purchaseApi', () => ({
    getPurchaseItemContext: vi.fn().mockResolvedValue({
        item: { id: 1, code: 'ITEM-1', name: 'Brake pad' },
        variants: [{ id: 12, code: 'RED', name: 'Red' }],
        allowed_purchase_uoms: [{ id: 21, uom: { id: 21, code: 'PCS', name: 'Pieces' } }],
        default_purchase_uom_id: 21,
        description: 'Brake pad',
        unit_price: '10.000000',
        tax_defaults: {},
    }),
}));

describe('Purchase line entry', () => {
    it('uses the expected PO line columns including separate Variant and UOM labels', () => {
        render(
            <MemoryRouter>
                <PurchaseOrderLineEditor
                    lines={[purchaseOrderLine()]}
                    onChange={vi.fn()}
                    errorFor={() => undefined}
                />
            </MemoryRouter>,
        );

        expect(columnHeaders()).toEqual(expect.arrayContaining([
            'Item',
            'Variant',
            'Quantity',
            'UOM',
            'Unit price',
            'Discount',
            'Tax',
            'Amount',
            'Actions',
        ]));
    });

    it('uses the same visible Fast Purchase line columns and keeps Variant separate from UOM', () => {
        render(
            <MemoryRouter>
                <FastPurchaseLines
                    rows={[{
                        ...blankFastPurchaseLine(),
                        item: null,
                        item_variant: { id: 12, code: 'RED', name: 'Red' },
                        uom: { id: 21, code: 'PCS', name: 'Pieces' },
                        quantity: '2.000000',
                        unit_price: '10.000000',
                    }]}
                    context={{ tax_groups: [{ id: 3, code: 'VAT', name: 'VAT' }] } as unknown as FastPurchaseContext}
                    previewLines={[{ line_total: '23.000000' } as never]}
                    errorFor={() => undefined}
                    onChange={vi.fn()}
                />
            </MemoryRouter>,
        );

        expect(columnHeaders()).toEqual(expect.arrayContaining([
            'Item',
            'Variant',
            'UOM',
            'Quantity',
            'Unit cost',
            'Discount',
            'Tax',
            'Amount',
        ]));
        expect(screen.getAllByText('Variant').length).toBeGreaterThan(0);
        expect(screen.getAllByText('UOM').length).toBeGreaterThan(0);
        expect(screen.getAllByText('23.000000').length).toBeGreaterThan(0);
    });

    it('keeps an invalid new line in the drawer until it can be saved', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderEditor([], onChange);

        await user.click(screen.getByRole('button', { name: 'Add line' }));
        const dialog = await screen.findByRole('dialog', { name: 'Add line' });
        await user.click(within(dialog).getByRole('button', { name: 'Add line' }));

        expect(await screen.findByText('Select an item.')).toBeInTheDocument();
        expect(onChange).not.toHaveBeenCalled();
        expect(screen.getByRole('dialog', { name: 'Add line' })).toBeInTheDocument();
    });

    it('adds a valid line only after drawer save', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderEditor([], onChange);

        await user.click(screen.getByRole('button', { name: 'Add line' }));
        const dialog = await screen.findByRole('dialog', { name: 'Add line' });
        await user.click(within(dialog).getByRole('button', { name: 'Choose brake pad' }));
        await waitFor(() => expect(within(dialog).getByLabelText('UOM')).toHaveValue('21'));
        await user.click(within(dialog).getByRole('button', { name: 'Add line' }));

        expect(onChange).toHaveBeenCalledTimes(1);
        expect(onChange.mock.calls[0][0]).toHaveLength(1);
        expect(onChange.mock.calls[0][0][0].item?.name).toBe('Brake pad');
    });

    it('edit cancel preserves the original row', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderEditor([purchaseOrderLine()], onChange);

        await user.click(screen.getAllByRole('button', { name: 'Edit line' })[0]);
        const dialog = await screen.findByRole('dialog', { name: 'Edit line' });
        fireEvent.change(within(dialog).getByLabelText('Quantity'), { target: { value: '3.000000' } });
        await user.click(within(dialog).getByRole('button', { name: 'Cancel' }));

        expect(onChange).not.toHaveBeenCalled();
        expect(screen.getAllByText('2.000000').length).toBeGreaterThan(0);
    });

    it('edit save replaces the intended row', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderEditor([purchaseOrderLine()], onChange);

        await user.click(screen.getAllByRole('button', { name: 'Edit line' })[0]);
        const dialog = await screen.findByRole('dialog', { name: 'Edit line' });
        fireEvent.change(within(dialog).getByLabelText('Quantity'), { target: { value: '3.000000' } });
        await user.click(within(dialog).getByRole('button', { name: 'Save line' }));

        expect(onChange).toHaveBeenCalledTimes(1);
        expect(onChange.mock.calls[0][0][0].quantity).toBe('3.000000');
        expect(onChange.mock.calls[0][0][0].client_key).toBe('po-line-1');
    });

    it('saves an unchanged persisted PO line without manual price confirmation', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        const persisted = purchaseOrderLineFromResource({
            id: 7,
            item_id: 1,
            item: { id: 1, code: 'ITEM-1', name: 'Brake pad' },
            item_variant_id: 12,
            item_variant: { id: 12, code: 'RED', name: 'Red' },
            uom_id: 21,
            uom: { id: 21, code: 'PCS', name: 'Pieces' },
            description: 'Brake pad',
            ordered_quantity: '2.000000',
            unit_price: '10.000000',
            discount_amount: '1.000000',
            tax_amount: '2.000000',
            charge_amount: '0.000000',
        });

        expect(persisted.pricing_state).toBe('persisted');
        expect(persisted.manual_price_confirmed).toBe(true);

        renderEditor([persisted], onChange);
        await user.click(screen.getAllByRole('button', { name: 'Edit line' })[0]);
        const dialog = await screen.findByRole('dialog', { name: 'Edit line' });
        await user.click(within(dialog).getByRole('button', { name: 'Save line' }));

        expect(screen.queryByText('Manual price must be confirmed for the current line context.')).not.toBeInTheDocument();
        expect(onChange).toHaveBeenCalledTimes(1);
        expect(purchaseOrderLineToPayload(onChange.mock.calls[0][0][0])).toMatchObject({
            item_id: 1,
            unit_price: '10.000000',
        });
    });

    it('removes a line after confirmation', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderEditor([purchaseOrderLine()], onChange);

        await user.click(screen.getAllByRole('button', { name: 'Remove line' })[0]);
        const dialog = await screen.findByRole('dialog', { name: 'Remove line' });
        await user.click(within(dialog).getByRole('button', { name: 'Remove line' }));

        expect(onChange).toHaveBeenCalledWith([]);
    });

    it('maps shared editable lines to PO and Fast Purchase payload fields explicitly', () => {
        const line = {
            ...purchaseOrderLine(),
            auto_price: false,
            manual_price_confirmed: true,
            pricing_context_hash: 'a'.repeat(64),
        };

        expect(purchaseOrderLineToPayload(line)).toMatchObject({
            item_id: 1,
            item_variant_id: 12,
            ordered_quantity: '2.000000',
            uom_id: 21,
            unit_price: '10.000000',
            tax_amount: '2.000000',
        });

        const fastPurchasePayload = fastPurchaseLineToPayload(line);
        expect(fastPurchasePayload).toMatchObject({
            client_line_key: 'po-line-1',
            item_id: 1,
            item_variant_id: 12,
            quantity: '2.000000',
            uom_id: 21,
            unit_cost: '10.000000',
            pricing_mode: 'manual',
            manual_price_confirmed: true,
            pricing_context_hash: 'a'.repeat(64),
            charge_amount: '0.000000',
        });
        expect(fastPurchasePayload).not.toHaveProperty('tax_amount');
        expect(fastPurchasePayload).not.toHaveProperty('tax_calculation_type');
    });
});

function columnHeaders(): string[] {
    return screen.getAllByRole('columnheader').map((header) => header.textContent ?? '');
}

function renderEditor(lines: EditablePurchaseLine[], onChange = vi.fn()) {
    return render(
        <MemoryRouter>
            <PurchaseOrderLineEditor
                lines={lines}
                onChange={onChange}
                errorFor={() => undefined}
            />
        </MemoryRouter>,
    );
}

function purchaseOrderLine(): EditablePurchaseLine {
    return {
        client_key: 'po-line-1',
        item: { id: 1, code: 'ITEM-1', name: 'Brake pad' },
        item_variant: { id: 12, code: 'RED', name: 'Red' },
        item_variant_id: 12,
        uom: { id: 21, code: 'PCS', name: 'Pieces' },
        description: 'Brake pad',
        quantity: '2.000000',
        unit_price: '10.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '1.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '2.000000',
        tax_group_id: '',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
    };
}
