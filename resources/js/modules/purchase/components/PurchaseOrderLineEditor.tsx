import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { PurchaseOrderLineForm } from './PurchaseOrderLineForm';
import {
    emptyPurchaseLine,
    previewLineTotal,
    type EditablePurchaseLine,
} from './purchaseOrderLineModel';

export type { EditablePurchaseLine } from './purchaseOrderLineModel';
export { previewLineAmounts } from './purchaseOrderLineModel';

type LineDialog =
    | { mode: 'create'; line: EditablePurchaseLine }
    | { mode: 'edit'; index: number; line: EditablePurchaseLine };

export function PurchaseOrderLineEditor({ lines, onChange, errorFor, supplierId, currencyId, warehouseId }: {
    lines: EditablePurchaseLine[];
    onChange: (lines: EditablePurchaseLine[]) => void;
    errorFor: (field: string) => string | undefined;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
}) {
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    const saveLine = (line: EditablePurchaseLine) => {
        if (dialog?.mode === 'edit') {
            onChange(lines.map((current, index) => index === dialog.index ? line : current));
        } else {
            onChange([...lines, line]);
        }
        setDialog(null);
    };

    const removeLine = async (index: number) => {
        const confirmed = await confirm({
            title: 'Remove line',
            message: 'This line will be removed from the local order draft.',
            confirmLabel: 'Remove line',
        });
        if (confirmed) onChange(lines.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            <PurchaseLineTable
                lines={lines}
                onAdd={() => setDialog({ mode: 'create', line: emptyPurchaseLine() })}
                onEdit={(line, index) => setDialog({ mode: 'edit', index, line })}
                onRemove={(index) => void removeLine(index)}
                hasError={(index) => lineHasError(index, errorFor)}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'} onClose={() => setDialog(null)}>
                {dialog && (
                    <PurchaseOrderLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.index}` : 'create'}
                        line={dialog.line}
                        mode={dialog.mode}
                        supplierId={supplierId}
                        currencyId={currencyId}
                        warehouseId={warehouseId}
                        errorFor={(field) => dialog.mode === 'edit' ? errorFor(`lines.${dialog.index}.${field}`) : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={saveLine}
                    />
                )}
            </FormDrawer>
            {confirmDialog}
        </div>
    );
}

function PurchaseLineTable({ lines, onAdd, onEdit, onRemove, hasError }: {
    lines: EditablePurchaseLine[];
    onAdd: () => void;
    onEdit: (line: EditablePurchaseLine, index: number) => void;
    onRemove: (index: number) => void;
    hasError: (index: number) => boolean;
}) {
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));
    const columns: DataColumn<EditablePurchaseLine & { rowIndex: number }>[] = [
        { key: 'item', header: 'Item', render: formatItemLabel },
        { key: 'variant', header: 'Variant', render: (line) => line.item_variant?.code ?? line.item_variant?.name ?? '-' },
        { key: 'quantity', header: 'Qty', render: (line) => line.ordered_quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? line.uom?.name ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.unit_price, className: 'tabular-nums' },
        { key: 'discount', header: 'Discount', render: formatDiscountSummary },
        { key: 'tax', header: 'Tax', render: formatTaxSummary },
        { key: 'total', header: 'Total', render: previewLineTotal, className: 'tabular-nums font-semibold' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} /> },
    ];

    return (
        <div className="space-y-3">
            <DataTable
                rows={rows}
                columns={columns}
                rowKey={(line) => line.rowIndex}
                emptyMessage="No purchase lines added. Add the first item to this order."
                mobileSummary={formatItemLabel}
                mobileDetails={(line) => <LineMobileDetails line={line} />}
                mobileActions={(line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} />}
                rowBadge={(line) => hasError(line.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" onClick={onAdd}>Add line</Button>
        </div>
    );
}

function lineHasError(index: number, errorFor: (field: string) => string | undefined): boolean {
    return ['item_id', 'uom_id', 'ordered_quantity', 'unit_price', 'discount_rate', 'discount_amount', 'tax_rate', 'tax_amount', 'charge_rate', 'charge_amount']
        .some((field) => Boolean(errorFor(`lines.${index}.${field}`)));
}

function formatItemLabel(line: EditablePurchaseLine): string {
    if (!line.item) return '-';
    return [line.item.code, line.item.name].filter(Boolean).join(' - ');
}

function formatDiscountSummary(line: EditablePurchaseLine): string {
    return line.discount_calculation_type === 'percentage' ? `${line.discount_rate}%` : line.discount_amount;
}

function formatTaxSummary(line: EditablePurchaseLine): string {
    return line.tax_calculation_type === 'percentage' ? `${line.tax_rate}%` : line.tax_amount;
}

function LineActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit line</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove line</button></div>;
}

function PreviewValue({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}

function LineMobileDetails({ line }: { line: EditablePurchaseLine }) {
    return <div className="grid grid-cols-2 gap-2"><PreviewValue label="Qty" value={line.ordered_quantity} /><PreviewValue label="UOM" value={line.uom?.code ?? '-'} /><PreviewValue label="Price" value={line.unit_price} /><PreviewValue label="Total" value={previewLineTotal(line)} /></div>;
}

function ErrorBadge() {
    return <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Error</span>;
}
