import { Panel } from '@/shared/components/Panel';
import { SalesLineEditor } from './SalesLineEditor';
import type { EditableSalesLine } from './salesDocumentFormUtils';

interface Props {
    lines: EditableSalesLine[];
    onChange: (lines: EditableSalesLine[]) => void;
    errorFor: (name: string) => string | undefined;
    currencyId?: number;
    warehouseId?: number;
    salesDate?: string;
}

const salesOrderLineConfig = {
    unitLabel: 'Unit price',
    taxMode: 'manual' as const,
    emptyMessage: 'No sales lines added. Add the first item to this document.',
};

const salesOrderFieldMap = {
    item_id: 'item_id',
    item_variant_id: 'item_variant_id',
    uom_id: 'uom_id',
    description: 'description',
    quantity: 'quantity',
    unit_price: 'unit_price',
    pricing_mode: 'unit_price',
    manual_price_confirmed: 'unit_price',
    pricing_context_hash: 'unit_price',
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

export function SalesDocumentLinesSection({ lines, onChange, errorFor, currencyId, warehouseId, salesDate }: Props) {
    return (
        <Panel title="Lines">
            <SalesLineEditor
                lines={lines}
                onChange={onChange}
                config={salesOrderLineConfig}
                currencyId={currencyId}
                warehouseId={warehouseId}
                salesDate={salesDate}
                errorForLineField={(_, index, field) => errorFor(`lines.${index}.${salesOrderFieldMap[field]}`)}
            />
        </Panel>
    );
}
