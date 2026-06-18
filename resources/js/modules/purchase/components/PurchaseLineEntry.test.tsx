import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { FastPurchaseLines, blankFastPurchaseLine } from './FastPurchaseLines';
import { PurchaseOrderLineEditor, type EditablePurchaseLine } from './PurchaseOrderLineEditor';
import type { FastPurchaseContext } from '../purchaseTypes';

vi.mock('./PurchaseLookups', () => ({
    ItemLookupSelect: ({ value }: { value: { name?: string } | null }) => (
        <label>
            Item
            <input aria-label="Item" readOnly value={value?.name ?? ''} />
        </label>
    ),
}));

vi.mock('../purchaseApi', () => ({
    getPurchaseItemContext: vi.fn().mockResolvedValue({
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
                        unit_cost: '10.000000',
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
});

function columnHeaders(): string[] {
    return screen.getAllByRole('columnheader').map((header) => header.textContent ?? '');
}

function purchaseOrderLine(): EditablePurchaseLine {
    return {
        item: { id: 1, code: 'ITEM-1', name: 'Brake pad' },
        item_variant: { id: 12, code: 'RED', name: 'Red' },
        item_variant_id: 12,
        uom: { id: 21, code: 'PCS', name: 'Pieces' },
        description: 'Brake pad',
        ordered_quantity: '2.000000',
        unit_price: '10.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '1.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '2.000000',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
    };
}
