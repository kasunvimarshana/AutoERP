import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { PurchaseHeaderAdjustmentForm } from './PurchaseHeaderAdjustmentForm';
import {
    emptyHeaderAdjustment,
    formatAdjustmentAmount,
    formatAdjustmentCalculation,
    formatAdjustmentEffect,
    type EditableHeaderAdjustment,
} from './purchaseHeaderAdjustmentModel';

export type { EditableHeaderAdjustment } from './purchaseHeaderAdjustmentModel';

type AdjustmentDialog =
    | { mode: 'create'; adjustment: EditableHeaderAdjustment }
    | { mode: 'edit'; index: number; adjustment: EditableHeaderAdjustment };

export function PurchaseHeaderAdjustmentEditor({ adjustments, onChange, errorFor }: {
    adjustments: EditableHeaderAdjustment[];
    onChange: (adjustments: EditableHeaderAdjustment[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<AdjustmentDialog | null>(null);
    const { confirm, confirmDialog } = useConfirmDialog();

    const saveAdjustment = (adjustment: EditableHeaderAdjustment) => {
        if (dialog?.mode === 'edit') {
            onChange(adjustments.map((current, index) => index === dialog.index ? adjustment : current));
        } else {
            onChange([...adjustments, adjustment]);
        }
        setDialog(null);
    };

    const removeAdjustment = async (index: number) => {
        const confirmed = await confirm({
            title: 'Remove adjustment',
            message: 'This adjustment will be removed from the local order draft.',
            confirmLabel: 'Remove adjustment',
        });
        if (confirmed) onChange(adjustments.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            <HeaderAdjustmentTable
                adjustments={adjustments}
                onAdd={() => setDialog({ mode: 'create', adjustment: emptyHeaderAdjustment() })}
                onEdit={(adjustment, index) => setDialog({ mode: 'edit', index, adjustment })}
                onRemove={(index) => void removeAdjustment(index)}
                hasError={(index) => adjustmentHasError(index, errorFor)}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit adjustment' : 'Add adjustment'} onClose={() => setDialog(null)}>
                {dialog && (
                    <PurchaseHeaderAdjustmentForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.index}` : 'create'}
                        adjustment={dialog.adjustment}
                        mode={dialog.mode}
                        errorFor={(field) => dialog.mode === 'edit' ? errorFor(`adjustments.${dialog.index}.${field}`) : undefined}
                        onCancel={() => setDialog(null)}
                        onSave={saveAdjustment}
                    />
                )}
            </FormDrawer>
            {confirmDialog}
        </div>
    );
}

function HeaderAdjustmentTable({ adjustments, onAdd, onEdit, onRemove, hasError }: {
    adjustments: EditableHeaderAdjustment[];
    onAdd: () => void;
    onEdit: (adjustment: EditableHeaderAdjustment, index: number) => void;
    onRemove: (index: number) => void;
    hasError: (index: number) => boolean;
}) {
    const rows = adjustments.map((adjustment, index) => ({ ...adjustment, rowIndex: index }));
    const columns: DataColumn<EditableHeaderAdjustment & { rowIndex: number }>[] = [
        { key: 'name', header: 'Name', render: (row) => row.name || '-' },
        { key: 'type', header: 'Type', render: (row) => row.adjustment_type.replaceAll('_', ' ') },
        { key: 'effect', header: 'Effect', render: formatAdjustmentEffect },
        { key: 'calculation', header: 'Calculation', render: formatAdjustmentCalculation },
        { key: 'amount', header: 'Amount', render: formatAdjustmentAmount, className: 'tabular-nums' },
        { key: 'allocation', header: 'Allocation', render: (row) => row.allocation_method.replaceAll('_', ' ') },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => <AdjustmentActions onEdit={() => onEdit(row, row.rowIndex)} onRemove={() => onRemove(row.rowIndex)} /> },
    ];

    return (
        <div className="space-y-3">
            <DataTable
                rows={rows}
                columns={columns}
                rowKey={(row) => row.rowIndex}
                emptyMessage="No adjustments added yet."
                mobileSummary={(row) => row.name || row.adjustment_type.replaceAll('_', ' ')}
                mobileDetails={(row) => <AdjustmentMobileDetails adjustment={row} />}
                mobileActions={(row) => <AdjustmentActions onEdit={() => onEdit(row, row.rowIndex)} onRemove={() => onRemove(row.rowIndex)} />}
                rowBadge={(row) => hasError(row.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" onClick={onAdd}>Add adjustment</Button>
        </div>
    );
}

function adjustmentHasError(index: number, errorFor: (field: string) => string | undefined): boolean {
    return ['name', 'adjustment_type', 'effect', 'calculation_type', 'calculation_base', 'rate', 'amount', 'allocation_method']
        .some((field) => Boolean(errorFor(`adjustments.${index}.${field}`)));
}

function AdjustmentActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" className="font-semibold text-sky-700" onClick={onEdit}>Edit adjustment</button><button type="button" className="font-semibold text-rose-600" onClick={onRemove}>Remove adjustment</button></div>;
}

function AdjustmentMobileDetails({ adjustment }: { adjustment: EditableHeaderAdjustment }) {
    return <div className="grid grid-cols-2 gap-2"><Preview label="Effect" value={formatAdjustmentEffect(adjustment)} /><Preview label="Calculation" value={formatAdjustmentAmount(adjustment)} /><Preview label="Type" value={adjustment.adjustment_type.replaceAll('_', ' ')} /><Preview label="Allocation" value={adjustment.allocation_method.replaceAll('_', ' ')} /></div>;
}

function Preview({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block text-slate-900">{value}</strong></div>;
}

function ErrorBadge() {
    return <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Error</span>;
}
