import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import type { NamedResource } from '@/shared/types/common';
import { addDecimal, isNonNegativeDecimal, isPositiveDecimal, multiplyDecimal, nonNegativeDecimal, percentageOfDecimal, subtractDecimal } from '@/shared/utils/decimal';
import { ItemLookupSelect, UomLookupSelect } from './PurchaseLookups';

export interface EditablePurchaseLine {
    item: NamedResource | null;
    uom: NamedResource | null;
    description: string;
    ordered_quantity: string;
    unit_price: string;
    discount_calculation_type: 'fixed' | 'percentage';
    discount_rate: string;
    discount_amount: string;
    tax_calculation_type: 'fixed' | 'percentage';
    tax_rate: string;
    tax_amount: string;
    charge_calculation_type: 'fixed' | 'percentage';
    charge_rate: string;
    charge_amount: string;
}

export interface PurchaseLinePreview {
    subtotal: string;
    discount: string;
    tax: string;
    charge: string;
    total: string;
}

type LineDialog = { mode: 'create'; line: EditablePurchaseLine } | { mode: 'edit'; index: number; line: EditablePurchaseLine };
type LineFormErrors = Partial<Record<'item' | 'uom' | 'ordered_quantity' | 'unit_price', string>>;

const calculationOptions = [{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }];

export function previewLineAmounts(line: EditablePurchaseLine): PurchaseLinePreview {
    const subtotal = multiplyDecimal(line.ordered_quantity, line.unit_price);
    const discount = line.discount_calculation_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.discount_rate)
        : line.discount_amount;
    const taxBase = nonNegativeDecimal(subtractDecimal(subtotal, discount));
    const tax = line.tax_calculation_type === 'percentage'
        ? percentageOfDecimal(taxBase, line.tax_rate)
        : line.tax_amount;
    const charge = line.charge_calculation_type === 'percentage'
        ? percentageOfDecimal(subtotal, line.charge_rate)
        : line.charge_amount;

    return {
        subtotal,
        discount,
        tax,
        charge,
        total: nonNegativeDecimal(addDecimal(addDecimal(subtractDecimal(subtotal, discount), tax), charge)),
    };
}

export function previewLineTotal(line: EditablePurchaseLine): string {
    return previewLineAmounts(line).total;
}

export function emptyPurchaseLine(): EditablePurchaseLine {
    return {
        item: null,
        uom: null,
        description: '',
        ordered_quantity: '1.000000',
        unit_price: '0.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
    };
}

export function PurchaseOrderLineEditor({ lines, onChange, errorFor }: {
    lines: EditablePurchaseLine[];
    onChange: (lines: EditablePurchaseLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    const addLine = (line: EditablePurchaseLine) => {
        onChange([...lines, line]);
        setDialog(null);
    };
    const updateLine = (index: number, line: EditablePurchaseLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
        setDialog(null);
    };
    const removeLine = async (index: number) => {
        const confirmed = await confirm({
            title: 'Remove line',
            message: 'This line will be removed from the local order draft.',
            confirmLabel: 'Remove line',
        });
        if (!confirmed) return;
        onChange(lines.filter((_, currentIndex) => currentIndex !== index));
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
                    <PurchaseLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.index}` : 'create'}
                        line={dialog.line}
                        mode={dialog.mode}
                        errorFor={(field) => dialog.mode === 'edit' ? errorFor(`lines.${dialog.index}.${field}`) : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={(line) => dialog.mode === 'edit' ? updateLine(dialog.index, line) : addLine(line)}
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
                emptyMessage="No lines added yet. Click Add line to start."
                mobileSummary={formatItemLabel}
                mobileDetails={(line) => <LineMobileDetails line={line} />}
                mobileActions={(line) => <LineActions onEdit={() => onEdit(line, line.rowIndex)} onRemove={() => onRemove(line.rowIndex)} />}
                rowBadge={(line) => hasError(line.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" onClick={onAdd}>Add line</Button>
        </div>
    );
}

function PurchaseLineForm({ line, mode, errorFor, onSave, onCancel }: {
    line: EditablePurchaseLine;
    mode: 'create' | 'edit';
    errorFor: (field: string) => string | undefined;
    onSave: (line: EditablePurchaseLine) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(line);
    const [errors, setErrors] = useState<LineFormErrors>({});
    const set = <K extends keyof EditablePurchaseLine>(key: K, value: EditablePurchaseLine[K]) => {
        setDraft((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };
    const fieldError = (field: keyof LineFormErrors) => errors[field] ?? errorFor(field);
    const preview = previewLineAmounts(draft);

    return (
        <form className="space-y-5" onSubmit={(event) => {
            event.preventDefault();
            const nextErrors = validateLineForm(draft);
            setErrors(nextErrors);
            if (Object.keys(nextErrors).length > 0) return;
            onSave(draft);
        }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Item, quantity, UOM, and price are enough for most lines.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <ItemLookupSelect value={draft.item} onChange={(item) => set('item', item)} error={fieldError('item') ?? errorFor('item_id')} />
                    <DecimalInput label="Quantity" value={draft.ordered_quantity} error={fieldError('ordered_quantity')} onChange={(event) => set('ordered_quantity', event.target.value)} />
                    <UomLookupSelect value={draft.uom} onChange={(uom) => set('uom', uom)} error={fieldError('uom') ?? errorFor('uom_id')} />
                    <DecimalInput label="Unit price" value={draft.unit_price} error={fieldError('unit_price')} onChange={(event) => set('unit_price', event.target.value)} />
                    <Input className="sm:col-span-2" label="Description" value={draft.description} onChange={(event) => set('description', event.target.value)} />
                </div>
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced pricing</summary>
                <p className="mt-1 text-sm text-slate-500">Advanced pricing is optional.</p>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <Select label="Discount type" value={draft.discount_calculation_type} options={calculationOptions} error={errorFor('discount_calculation_type')} onChange={(event) => set('discount_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.discount_calculation_type === 'percentage' ? 'Discount value (%)' : 'Discount value'} value={draft.discount_calculation_type === 'percentage' ? draft.discount_rate : draft.discount_amount} error={errorFor(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount')} onChange={(event) => set(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount', event.target.value)} />
                    <Select label="Tax type" value={draft.tax_calculation_type} options={calculationOptions} error={errorFor('tax_calculation_type')} onChange={(event) => set('tax_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.tax_calculation_type === 'percentage' ? 'Tax value (%)' : 'Tax value'} value={draft.tax_calculation_type === 'percentage' ? draft.tax_rate : draft.tax_amount} error={errorFor(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount')} onChange={(event) => set(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount', event.target.value)} />
                    <Select label="Charge type" value={draft.charge_calculation_type} options={calculationOptions} error={errorFor('charge_calculation_type')} onChange={(event) => set('charge_calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    <DecimalInput label={draft.charge_calculation_type === 'percentage' ? 'Charge value (%)' : 'Charge value'} value={draft.charge_calculation_type === 'percentage' ? draft.charge_rate : draft.charge_amount} error={errorFor(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount')} onChange={(event) => set(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount', event.target.value)} />
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Line Preview</h3>
                <div className="mt-3 grid gap-2 sm:grid-cols-5">
                    <PreviewValue label="Subtotal" value={preview.subtotal} />
                    <PreviewValue label="Discount" value={preview.discount} />
                    <PreviewValue label="Tax" value={preview.tax} />
                    <PreviewValue label="Charge" value={preview.charge} />
                    <PreviewValue label="Total" value={preview.total} />
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">{mode === 'edit' ? 'Save line' : 'Add line'}</Button>
            </div>
        </form>
    );
}

function validateLineForm(line: EditablePurchaseLine): LineFormErrors {
    const errors: LineFormErrors = {};
    if (!line.item) errors.item = 'Select an item.';
    if (!line.uom) errors.uom = 'Select a UOM.';
    if (!isPositiveDecimal(line.ordered_quantity)) errors.ordered_quantity = 'Quantity must be greater than zero.';
    if (!isNonNegativeDecimal(line.unit_price)) errors.unit_price = 'Unit price cannot be negative.';
    return errors;
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
