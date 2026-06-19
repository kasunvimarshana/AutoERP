import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { SalesLineForm } from './SalesLineForm';
import {
    emptySalesLine,
    formatSalesLineCharge,
    formatSalesLineDiscount,
    formatSalesLineItem,
    formatSalesLineTax,
    previewLineTotal,
    type EditableSalesLine,
    type SalesLineEditorConfig,
    type SalesLineField,
} from './salesLineModel';

type LineDialog =
    | { mode: 'create'; line: EditableSalesLine }
    | { mode: 'edit'; index: number; line: EditableSalesLine };

export function SalesLineEditor({ lines, onChange, config, errorForLineField, amountForLine, currencyId, warehouseId, salesDate }: {
    lines: EditableSalesLine[];
    onChange: (lines: EditableSalesLine[]) => void;
    config: SalesLineEditorConfig;
    errorForLineField: (line: EditableSalesLine, index: number, field: SalesLineField) => string | undefined;
    amountForLine?: (line: EditableSalesLine, index: number) => string | undefined;
    currencyId?: number;
    warehouseId?: number;
    salesDate?: string;
}) {
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    const saveLine = (line: EditableSalesLine) => {
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
            message: 'This line will be removed from the local sales draft.',
            confirmLabel: 'Remove line',
        });
        if (confirmed) onChange(lines.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            <SalesLineTable
                lines={lines}
                config={config}
                onAdd={() => setDialog({ mode: 'create', line: emptySalesLine(config.defaultLine) })}
                onEdit={(line, index) => setDialog({ mode: 'edit', index, line: { ...line } })}
                onRemove={(index) => void removeLine(index)}
                hasError={(line, index) => lineHasError(line, index, errorForLineField, config)}
                amountForLine={amountForLine}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'} onClose={() => setDialog(null)}>
                {dialog && (
                    <SalesLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.line.client_key}` : 'create'}
                        line={dialog.line}
                        mode={dialog.mode}
                        config={config}
                        currencyId={currencyId}
                        warehouseId={warehouseId}
                        salesDate={salesDate}
                        errorFor={(field) => dialog.mode === 'edit' ? errorForLineField(dialog.line, dialog.index, field) : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={saveLine}
                    />
                )}
            </FormDrawer>
            {confirmDialog}
        </div>
    );
}

function SalesLineTable({ lines, config, onAdd, onEdit, onRemove, hasError, amountForLine }: {
    lines: EditableSalesLine[];
    config: SalesLineEditorConfig;
    onAdd: () => void;
    onEdit: (line: EditableSalesLine, index: number) => void;
    onRemove: (index: number) => void;
    hasError: (line: EditableSalesLine, index: number) => boolean;
    amountForLine?: (line: EditableSalesLine, index: number) => string | undefined;
}) {
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));
    const columns: DataColumn<EditableSalesLine & { rowIndex: number }>[] = [
        { key: 'item', header: 'Item', render: formatSalesLineItem },
        { key: 'variant', header: 'Variant', render: (line) => line.item_variant?.code ?? line.item_variant?.name ?? '-' },
        { key: 'quantity', header: 'Quantity', render: (line) => line.quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? line.uom?.name ?? '-' },
        { key: 'price', header: config.unitLabel, render: (line) => line.unit_price || '0.000000', className: 'tabular-nums' },
        { key: 'discount', header: 'Discount', render: formatSalesLineDiscount },
        { key: 'tax', header: 'Tax', render: (line) => formatSalesLineTax(line, config) },
        { key: 'charge', header: 'Charge', render: formatSalesLineCharge },
        { key: 'total', header: 'Amount', render: (line) => amountForLine?.(line, line.rowIndex) ?? previewLineTotal(line), className: 'tabular-nums font-semibold' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} /> },
    ];

    return (
        <div className="space-y-3">
            <DataTable
                rows={rows}
                columns={columns}
                rowKey={(line) => line.client_key}
                emptyMessage={config.emptyMessage}
                mobileSummary={formatSalesLineItem}
                mobileDetails={(line) => <LineMobileDetails line={line} config={config} amount={amountForLine?.(line, line.rowIndex)} />}
                mobileActions={(line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} />}
                rowBadge={(line) => hasError(line, line.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" onClick={onAdd}>Add line</Button>
        </div>
    );
}

function lineHasError(
    line: EditableSalesLine,
    index: number,
    errorForLineField: (line: EditableSalesLine, index: number, field: SalesLineField) => string | undefined,
    config: SalesLineEditorConfig,
): boolean {
    const fields: SalesLineField[] = [
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

function LineMobileDetails({ line, config, amount }: { line: EditableSalesLine; config: SalesLineEditorConfig; amount?: string }) {
    return (
        <div className="grid grid-cols-2 gap-2">
            <PreviewValue label="Variant" value={line.item_variant?.code ?? line.item_variant?.name ?? '-'} />
            <PreviewValue label="Quantity" value={line.quantity} />
            <PreviewValue label="UOM" value={line.uom?.code ?? '-'} />
            <PreviewValue label={config.unitLabel} value={line.unit_price || '0.000000'} />
            <PreviewValue label="Discount" value={formatSalesLineDiscount(line)} />
            <PreviewValue label="Tax" value={formatSalesLineTax(line, config)} />
            <PreviewValue label="Charge" value={formatSalesLineCharge(line)} />
            <PreviewValue label="Amount" value={amount ?? previewLineTotal(line)} />
        </div>
    );
}

function ErrorBadge() {
    return <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Error</span>;
}
