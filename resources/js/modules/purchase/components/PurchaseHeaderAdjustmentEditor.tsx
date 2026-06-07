import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';

export interface EditableHeaderAdjustment {
    name: string;
    adjustment_type: string;
    effect: 'increase' | 'decrease';
    calculation_type: 'fixed' | 'percentage';
    calculation_base: 'subtotal' | 'subtotal_after_line_discount' | 'subtotal_after_line_adjustments';
    rate: string;
    amount: string;
    allocation_method: string;
    description: string;
}

type AdjustmentDialog =
    | { mode: 'create'; adjustment: EditableHeaderAdjustment }
    | { mode: 'edit'; index: number; adjustment: EditableHeaderAdjustment };
type AdjustmentFormErrors = Partial<Record<'name' | 'amount' | 'rate', string>>;

const adjustmentTypes = [
    'discount',
    'tax',
    'freight',
    'charge',
    'credit_note',
    'debit_note',
    'withholding',
    'rounding',
    'other',
].map((value) => ({ value, label: value.replaceAll('_', ' ') }));

const calculationOptions = [{ value: 'fixed', label: 'Fixed' }, { value: 'percentage', label: 'Percentage' }];
const effectOptions = [{ value: 'increase', label: 'Increase' }, { value: 'decrease', label: 'Decrease' }];
const allocationOptions = [
    { value: 'proportional', label: 'Proportional' },
    { value: 'manual', label: 'Manual' },
    { value: 'first_invoice', label: 'First invoice' },
    { value: 'last_invoice', label: 'Last invoice' },
];
const calculationBases = [
    { value: 'subtotal', label: 'Subtotal' },
    { value: 'subtotal_after_line_discount', label: 'After line discount' },
    { value: 'subtotal_after_line_adjustments', label: 'After line adjustments' },
];

export function emptyHeaderAdjustment(): EditableHeaderAdjustment {
    return {
        name: '',
        adjustment_type: 'freight',
        effect: 'increase',
        calculation_type: 'fixed',
        calculation_base: 'subtotal',
        rate: '0.000000',
        amount: '0.000000',
        allocation_method: 'proportional',
        description: '',
    };
}

export function PurchaseHeaderAdjustmentEditor({ adjustments, onChange, errorFor }: {
    adjustments: EditableHeaderAdjustment[];
    onChange: (adjustments: EditableHeaderAdjustment[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<AdjustmentDialog | null>(null);
    const addAdjustment = (adjustment: EditableHeaderAdjustment) => {
        onChange([...adjustments, adjustment]);
        setDialog(null);
    };
    const updateAdjustment = (index: number, adjustment: EditableHeaderAdjustment) => {
        onChange(adjustments.map((current, currentIndex) => currentIndex === index ? adjustment : current));
        setDialog(null);
    };
    const removeAdjustment = (index: number) => {
        if (!window.confirm('Remove this adjustment?')) return;
        onChange(adjustments.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            <HeaderAdjustmentTable
                adjustments={adjustments}
                onAdd={() => setDialog({ mode: 'create', adjustment: emptyHeaderAdjustment() })}
                onEdit={(adjustment, index) => setDialog({ mode: 'edit', index, adjustment })}
                onRemove={removeAdjustment}
            />
            <Modal open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit adjustment' : 'Add adjustment'} onClose={() => setDialog(null)}>
                {dialog && (
                    <HeaderAdjustmentForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.index}` : 'create'}
                        adjustment={dialog.adjustment}
                        mode={dialog.mode}
                        errorFor={(field) => dialog.mode === 'edit' ? errorFor(`adjustments.${dialog.index}.${field}`) : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={(adjustment) => dialog.mode === 'edit' ? updateAdjustment(dialog.index, adjustment) : addAdjustment(adjustment)}
                    />
                )}
            </Modal>
        </div>
    );
}

function HeaderAdjustmentTable({ adjustments, onAdd, onEdit, onRemove }: {
    adjustments: EditableHeaderAdjustment[];
    onAdd: () => void;
    onEdit: (adjustment: EditableHeaderAdjustment, index: number) => void;
    onRemove: (index: number) => void;
}) {
    const rows = adjustments.map((adjustment, index) => ({ ...adjustment, rowIndex: index }));
    const columns: DataColumn<EditableHeaderAdjustment & { rowIndex: number }>[] = [
        { key: 'name', header: 'Name', render: (row) => row.name || '-' },
        { key: 'type', header: 'Type', render: (row) => row.adjustment_type.replaceAll('_', ' ') },
        { key: 'effect', header: 'Effect', render: formatEffect },
        { key: 'calculation', header: 'Calculation', render: formatAdjustmentCalculation },
        { key: 'amount', header: 'Amount', render: formatAdjustmentAmount, className: 'tabular-nums' },
        { key: 'allocation', header: 'Allocation', render: (row) => row.allocation_method.replaceAll('_', ' ') },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => <AdjustmentActions onEdit={() => onEdit(row, row.rowIndex)} onRemove={() => onRemove(row.rowIndex)} /> },
    ];

    return (
        <div className="space-y-3">
            <DataTable rows={rows} columns={columns} rowKey={(row) => row.rowIndex} emptyMessage="No adjustments added yet. Click Add adjustment to start." />
            <Button type="button" variant="secondary" onClick={onAdd}>Add adjustment</Button>
        </div>
    );
}

function HeaderAdjustmentForm({ adjustment, mode, errorFor, onSave, onCancel }: {
    adjustment: EditableHeaderAdjustment;
    mode: 'create' | 'edit';
    errorFor: (field: string) => string | undefined;
    onSave: (adjustment: EditableHeaderAdjustment) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(adjustment);
    const [errors, setErrors] = useState<AdjustmentFormErrors>({});
    const set = <K extends keyof EditableHeaderAdjustment>(key: K, value: EditableHeaderAdjustment[K]) => {
        setDraft((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };
    const fieldError = (field: keyof AdjustmentFormErrors) => errors[field] ?? errorFor(field);

    return (
        <form className="space-y-5" onSubmit={(event) => {
            event.preventDefault();
            const nextErrors = validateAdjustmentForm(draft);
            setErrors(nextErrors);
            if (Object.keys(nextErrors).length > 0) return;
            onSave(draft);
        }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Define the adjustment name, type, effect, and calculation.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Input label="Name" value={draft.name} error={fieldError('name') ?? errorFor('name')} onChange={(event) => set('name', event.target.value)} />
                    <Select label="Type" value={draft.adjustment_type} options={adjustmentTypes} error={errorFor('adjustment_type')} onChange={(event) => set('adjustment_type', event.target.value)} />
                    <Select label="Effect" value={draft.effect} options={effectOptions} error={errorFor('effect')} onChange={(event) => set('effect', event.target.value as 'increase' | 'decrease')} />
                    <Select label="Calculation" value={draft.calculation_type} options={calculationOptions} error={errorFor('calculation_type')} onChange={(event) => set('calculation_type', event.target.value as 'fixed' | 'percentage')} />
                    {draft.calculation_type === 'percentage' && <Select label="Base" value={draft.calculation_base} options={calculationBases} error={errorFor('calculation_base')} onChange={(event) => set('calculation_base', event.target.value as EditableHeaderAdjustment['calculation_base'])} />}
                    {draft.calculation_type === 'percentage'
                        ? <Input label="Rate (%)" type="number" min="0" max="100" step="0.000001" value={draft.rate} error={fieldError('rate') ?? errorFor('rate')} onChange={(event) => set('rate', event.target.value)} />
                        : <Input label="Amount" type="number" min="0" step="0.000001" value={draft.amount} error={fieldError('amount') ?? errorFor('amount')} onChange={(event) => set('amount', event.target.value)} />}
                    <Select label="Allocation" value={draft.allocation_method} options={allocationOptions} error={errorFor('allocation_method')} onChange={(event) => set('allocation_method', event.target.value)} />
                </div>
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced</summary>
                <div className="mt-4">
                    <Input label="Description" value={draft.description} onChange={(event) => set('description', event.target.value)} />
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Adjustment Preview</h3>
                <div className="mt-2 text-slate-700">{formatAdjustmentSummary(draft)}</div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">{mode === 'edit' ? 'Save adjustment' : 'Add adjustment'}</Button>
            </div>
        </form>
    );
}

function validateAdjustmentForm(adjustment: EditableHeaderAdjustment): AdjustmentFormErrors {
    const errors: AdjustmentFormErrors = {};
    if (!adjustment.name.trim()) errors.name = 'Enter an adjustment name.';
    if (adjustment.calculation_type === 'percentage' && Number(adjustment.rate) < 0) errors.rate = 'Rate cannot be negative.';
    if (adjustment.calculation_type === 'fixed' && Number(adjustment.amount) < 0) errors.amount = 'Amount cannot be negative.';
    return errors;
}

function formatEffect(adjustment: EditableHeaderAdjustment): string {
    return adjustment.effect === 'increase' ? 'Increase' : 'Decrease';
}

function formatAdjustmentCalculation(adjustment: EditableHeaderAdjustment): string {
    if (adjustment.calculation_type === 'fixed') return 'Fixed';
    return `Percentage of ${adjustment.calculation_base.replaceAll('_', ' ')}`;
}

function formatAdjustmentAmount(adjustment: EditableHeaderAdjustment): string {
    return adjustment.calculation_type === 'percentage' ? `${adjustment.rate}%` : adjustment.amount;
}

function formatAdjustmentSummary(adjustment: EditableHeaderAdjustment): string {
    return `${formatEffect(adjustment)} ${formatAdjustmentAmount(adjustment)} as ${adjustment.adjustment_type.replaceAll('_', ' ')} using ${adjustment.allocation_method.replaceAll('_', ' ')} allocation.`;
}

function AdjustmentActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit adjustment</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove adjustment</button></div>;
}
