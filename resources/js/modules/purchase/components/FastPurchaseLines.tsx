import { PurchaseLineEditor } from './PurchaseLineEditor';
import {
    emptyPurchaseLine,
    type EditablePurchaseLine,
    type PurchaseLineEditorConfig,
    type PurchaseLineField,
} from './purchaseLineModel';
import type { FastPurchaseContext, FastPurchaseLinePreview } from '../purchaseTypes';

export type FastPurchaseLineRow = EditablePurchaseLine;

interface FastPurchaseLinesProps {
    rows: FastPurchaseLineRow[];
    context: FastPurchaseContext | null;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    purchaseDate?: string;
    previewLines?: FastPurchaseLinePreview[];
    errorFor: (field: string) => string | undefined;
    errorIndexForLine?: (line: FastPurchaseLineRow, index: number) => number;
    onChange: (rows: FastPurchaseLineRow[]) => void;
}

const fastPurchaseFieldMap: Record<PurchaseLineField, string> = {
    item_id: 'item_id',
    item_variant_id: 'item_variant_id',
    uom_id: 'uom_id',
    description: 'description',
    quantity: 'quantity',
    unit_price: 'unit_cost',
    pricing_mode: 'pricing_mode',
    manual_price_confirmed: 'manual_price_confirmed',
    pricing_context_hash: 'pricing_context_hash',
    discount_calculation_type: 'discount_calculation_type',
    discount_rate: 'discount_rate',
    discount_amount: 'discount_amount',
    tax_calculation_type: 'tax_calculation_type',
    tax_rate: 'tax_rate',
    tax_amount: 'tax_amount',
    tax_group_id: 'tax_group_id',
    charge_calculation_type: 'charge_calculation_type',
    charge_rate: 'charge_rate',
    charge_amount: 'charge_amount',
};

export function blankFastPurchaseLine(): FastPurchaseLineRow {
    return emptyPurchaseLine({ unit_price: '' });
}

export function FastPurchaseLines({ rows, context, supplierId, currencyId, warehouseId, purchaseDate, previewLines = [], errorFor, errorIndexForLine, onChange }: FastPurchaseLinesProps) {
    const config: PurchaseLineEditorConfig = {
        unitLabel: 'Unit cost',
        taxMode: 'tax_group',
        taxGroupOptions: context?.tax_groups ?? [],
        defaultLine: { unit_price: '' },
        unitPriceMustBePositive: true,
        emptyMessage: 'No fast purchase lines added. Add the first item to this purchase.',
    };

    return (
        <PurchaseLineEditor
            lines={rows}
            onChange={onChange}
            config={config}
            supplierId={supplierId}
            currencyId={currencyId}
            warehouseId={warehouseId}
            purchaseDate={purchaseDate}
            amountForLine={(line, index) => previewForLine(previewLines, line, index)?.line_total}
            errorForLineField={(line, index, field) => errorFor(`lines.${errorIndexForLine?.(line, index) ?? indexOfError(previewLines, line, index)}.${fastPurchaseFieldMap[field]}`)}
        />
    );
}

function previewForLine(previewLines: FastPurchaseLinePreview[], line: FastPurchaseLineRow, index: number): FastPurchaseLinePreview | undefined {
    return previewLines.find((preview) => preview.client_line_key === line.client_key) ?? previewLines[index];
}

function indexOfError(previewLines: FastPurchaseLinePreview[], line: FastPurchaseLineRow, fallback: number): number {
    const previewIndex = previewLines.findIndex((preview) => preview.client_line_key === line.client_key);
    return previewIndex >= 0 ? previewIndex : fallback;
}
