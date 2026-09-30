import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { PurchaseLineForm } from './PurchaseLineForm';
import {
    emptyPurchaseLine,
    formatPurchaseLineCharge,
    formatPurchaseLineDiscount,
    formatPurchaseLineItem,
    formatPurchaseLineTax,
    previewLineTotal,
    type EditablePurchaseLine,
    type PurchaseLineEditorConfig,
    type PurchaseLineField,
} from './purchaseLineModel';

type LineDialog =
    | { mode: 'create'; line: EditablePurchaseLine }
    | { mode: 'edit'; index: number; line: EditablePurchaseLine };

export function PurchaseLineEditor({ lines, onChange, config, errorForLineField, amountForLine, supplierId, currencyId, warehouseId, purchaseDate }: {
    lines: EditablePurchaseLine[];
    onChange: (lines: EditablePurchaseLine[]) => void;
    config: PurchaseLineEditorConfig;
    errorForLineField: (line: EditablePurchaseLine, index: number, field: PurchaseLineField) => string | undefined;
    amountForLine?: (line: EditablePurchaseLine, index: number) => string | undefined;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    purchaseDate?: string;
}) {
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const [createFormRevision, setCreateFormRevision] = useState(0);
    const { confirm, confirmDialog } = useConfirmDialog();

    const saveLine = (line: EditablePurchaseLine) => {
        if (dialog?.mode === 'edit') {
            onChange(lines.map((current, index) => index === dialog.index ? line : current));
            setDialog(null);
        } else {
            onChange([...lines, line]);
            if (config.continuousCreate) {
                setDialog({ mode: 'create', line: emptyPurchaseLine(config.defaultLine) });
                setCreateFormRevision((current) => current + 1);
            } else {
                setDialog(null);
            }
        }
    };

    const removeLine = async (index: number) => {
        const confirmed = await confirm({
            title: 'Remove line',
            message: 'This line will be removed from the local purchase draft.',
            confirmLabel: 'Remove line',
        });
        if (confirmed) onChange(lines.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            <PurchaseLineTable
                lines={lines}
                config={config}
                onAdd={() => setDialog({ mode: 'create', line: emptyPurchaseLine(config.defaultLine) })}
                onEdit={(line, index) => setDialog({ mode: 'edit', index, line: { ...line } })}
                onRemove={(index) => void removeLine(index)}
                hasError={(line, index) => lineHasError(line, index, errorForLineField, config)}
                amountForLine={amountForLine}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'} onClose={() => setDialog(null)}>
                {dialog && (
                    <PurchaseLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.line.client_key}` : `create-${createFormRevision}`}
                        line={dialog.line}
                        mode={dialog.mode}
                        config={config}
                        supplierId={supplierId}
                        currencyId={currencyId}
                        warehouseId={warehouseId}
                        purchaseDate={purchaseDate}
                        errorFor={(field) => dialog.mode === 'edit' ? errorForLineField(dialog.line, dialog.index, field) : undefined}
                        onClear={config.continuousCreate && dialog.mode === 'create' ? () => {
                            setDialog({ mode: 'create', line: emptyPurchaseLine(config.defaultLine) });
                            setCreateFormRevision((current) => current + 1);
                        } : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={saveLine}
                    />
                )}
            </FormDrawer>
            {confirmDialog}
        </div>
    );
}

function PurchaseLineTable({ lines, config, onAdd, onEdit, onRemove, hasError, amountForLine }: {
    lines: EditablePurchaseLine[];
    config: PurchaseLineEditorConfig;
    onAdd: () => void;
    onEdit: (line: EditablePurchaseLine, index: number) => void;
    onRemove: (index: number) => void;
    hasError: (line: EditablePurchaseLine, index: number) => boolean;
    amountForLine?: (line: EditablePurchaseLine, index: number) => string | undefined;
}) {
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));
    const columns: DataColumn<EditablePurchaseLine & { rowIndex: number }>[] = [
        { key: 'item', header: 'Item', render: formatPurchaseLineItem },
        { key: 'variant', header: 'Variant', render: (line) => line.item_variant?.code ?? line.item_variant?.name ?? '-' },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? line.uom?.name ?? '-' },
        { key: 'price', header: config.unitLabel, render: (line) => line.unit_price || '0.000000', className: 'tabular-nums' },
        { key: 'discount', header: 'Discount', render: formatPurchaseLineDiscount },
        { key: 'tax', header: 'Tax', render: (line) => formatPurchaseLineTax(line, config) },
        { key: 'charge', header: 'Charge', render: formatPurchaseLineCharge },
        { key: 'total', header: 'Amount', render: (line) => amountForLine?.(line, line.rowIndex) ?? (config.taxMode === 'manual' ? previewLineTotal(line) : previewLineTotal(line)), className: 'tabular-nums font-semibold' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} /> },
    ];

    return (
        <div className="space-y-3">
            <DataTable
                rows={rows}
                columns={columns}
                rowKey={(line) => line.client_key}
                emptyMessage={config.emptyMessage}
                mobileSummary={formatPurchaseLineItem}
                mobileDetails={(line) => <LineMobileDetails line={line} config={config} amount={amountForLine?.(line, line.rowIndex)} />}
                mobileActions={(line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} />}
                rowBadge={(line) => hasError(line, line.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" onClick={onAdd}>Add line</Button>
        </div>
    );
}

function lineHasError(
    line: EditablePurchaseLine,
    index: number,
    errorForLineField: (line: EditablePurchaseLine, index: number, field: PurchaseLineField) => string | undefined,
    config: PurchaseLineEditorConfig,
): boolean {
    const fields: PurchaseLineField[] = [
        'item_id',
        'item_variant_id',
        'uom_id',
        'quantity',
        'unit_price',
        'pricing_mode',
        'manual_price_confirmed',
        'pricing_context_hash',
        'discount_calculation_type',
        'discount_rate',
        'discount_amount',
        'charge_calculation_type',
        'charge_rate',
        'charge_amount',
    ];
    if (config.taxMode === 'manual') {
        fields.push('tax_calculation_type', 'tax_rate', 'tax_amount');
    } else {
        fields.push('tax_group_id');
    }

    return fields.some((field) => Boolean(errorForLineField(line, index, field)));
}

function LineActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit line</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove line</button></div>;
}

function PreviewValue({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}

function LineMobileDetails({ line, config, amount }: { line: EditablePurchaseLine; config: PurchaseLineEditorConfig; amount?: string }) {
    return (
        <div className="grid grid-cols-2 gap-2">
            <PreviewValue label="Variant" value={line.item_variant?.code ?? line.item_variant?.name ?? '-'} />
            <PreviewValue label="Quantity" value={line.quantity} />
            <PreviewValue label="UOM" value={line.uom?.code ?? '-'} />
            <PreviewValue label={config.unitLabel} value={line.unit_price || '0.000000'} />
            <PreviewValue label="Discount" value={formatPurchaseLineDiscount(line)} />
            <PreviewValue label="Tax" value={formatPurchaseLineTax(line, config)} />
            <PreviewValue label="Charge" value={formatPurchaseLineCharge(line)} />
            <PreviewValue label="Amount" value={amount ?? (config.taxMode === 'manual' ? previewLineTotal(line) : previewLineTotal(line))} />
        </div>
    );
}

function ErrorBadge() {
    return <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Error</span>;
}
