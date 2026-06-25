import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { getSalesAdjustmentCatalogue } from '../salesApi';
import type { SalesAdjustmentCatalogueEntry } from '../salesTypes';
import { SalesHeaderAdjustmentForm, type HeaderAdjustmentAllocationLine } from './SalesHeaderAdjustmentForm';
import {
    emptySalesAdjustment,
    formatAdjustmentAmount,
    formatAdjustmentCalculation,
    formatAdjustmentEffect,
    type EditableSalesAdjustment,
} from './salesHeaderAdjustmentModel';

type AdjustmentDialog =
    | { mode: 'create'; adjustment: EditableSalesAdjustment }
    | { mode: 'edit'; index: number; adjustment: EditableSalesAdjustment };

export function SalesHeaderAdjustmentEditor({ adjustments, allocationLines = [], onChange, errorFor }: {
    adjustments: EditableSalesAdjustment[];
    allocationLines?: HeaderAdjustmentAllocationLine[];
    onChange: (adjustments: EditableSalesAdjustment[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<AdjustmentDialog | null>(null);
    const [catalogue, setCatalogue] = useState<SalesAdjustmentCatalogueEntry[]>([]);
    const [catalogueError, setCatalogueError] = useState<ApiError | null>(null);
    const [catalogueLoading, setCatalogueLoading] = useState(true);
    const [catalogueReload, setCatalogueReload] = useState(0);
    const { confirm, confirmDialog } = useConfirmDialog();

    useEffect(() => {
        const controller = new AbortController();
        queueMicrotask(() => {
            setCatalogueLoading(true);
            setCatalogueError(null);
        });
        void getSalesAdjustmentCatalogue(controller.signal)
            .then((entries) => {
                if (!controller.signal.aborted) setCatalogue(entries);
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) {
                    setCatalogue([]);
                    setCatalogueError(toApiError(requestError));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setCatalogueLoading(false);
            });

        return () => controller.abort();
    }, [catalogueReload]);

    const saveAdjustment = (adjustment: EditableSalesAdjustment) => {
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
            message: 'This adjustment will be removed from the local sales draft.',
            confirmLabel: 'Remove adjustment',
        });
        if (confirmed) onChange(adjustments.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div className="space-y-4">
            {catalogueError && (
                <div className="space-y-3">
                    <ErrorAlert error={catalogueError} title="Adjustment catalogue unavailable" />
                    <Button type="button" variant="secondary" onClick={() => setCatalogueReload((current) => current + 1)}>Retry catalogue</Button>
                </div>
            )}
            <HeaderAdjustmentTable
                adjustments={adjustments}
                onAdd={() => setDialog({ mode: 'create', adjustment: emptySalesAdjustment() })}
                onEdit={(adjustment, index) => setDialog({ mode: 'edit', index, adjustment })}
                onRemove={(index) => void removeAdjustment(index)}
                hasError={(index) => adjustmentHasError(index, errorFor)}
                disabled={catalogueLoading || Boolean(catalogueError) || catalogue.length === 0}
            />
            <FormDrawer open={Boolean(dialog)} title={dialog?.mode === 'edit' ? 'Edit adjustment' : 'Add adjustment'} onClose={() => setDialog(null)}>
                {dialog && (
                    <SalesHeaderAdjustmentForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.index}` : 'create'}
                        adjustment={dialog.adjustment}
                        mode={dialog.mode}
                        catalogue={catalogue}
                        allocationLines={allocationLines}
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

function HeaderAdjustmentTable({ adjustments, onAdd, onEdit, onRemove, hasError, disabled }: {
    adjustments: EditableSalesAdjustment[];
    onAdd: () => void;
    onEdit: (adjustment: EditableSalesAdjustment, index: number) => void;
    onRemove: (index: number) => void;
    hasError: (index: number) => boolean;
    disabled: boolean;
}) {
    const rows = adjustments.map((adjustment, index) => ({ ...adjustment, rowIndex: index }));
    const columns: DataColumn<EditableSalesAdjustment & { rowIndex: number }>[] = [
        { key: 'name', header: 'Name', render: (row) => row.name || '-' },
        { key: 'type', header: 'Type', render: (row) => row.adjustment_type.replaceAll('_', ' ') },
        { key: 'effect', header: 'Effect', render: formatAdjustmentEffect },
        { key: 'calculation', header: 'Calculation', render: formatAdjustmentCalculation },
        { key: 'mapping', header: 'Finance Mapping', render: (row) => row.finance_mapping_label ?? row.revenue_treatment ?? '-' },
        { key: 'amount', header: 'Amount', render: formatAdjustmentAmount, className: 'tabular-nums' },
        { key: 'allocation', header: 'Allocation', render: (row) => row.allocation_method.replaceAll('_', ' ') },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (row) => <AdjustmentActions disabled={disabled} onEdit={() => onEdit(row, row.rowIndex)} onRemove={() => onRemove(row.rowIndex)} /> },
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
                mobileActions={(row) => <AdjustmentActions disabled={disabled} onEdit={() => onEdit(row, row.rowIndex)} onRemove={() => onRemove(row.rowIndex)} />}
                rowBadge={(row) => hasError(row.rowIndex) ? <ErrorBadge /> : null}
            />
            <Button type="button" variant="secondary" disabled={disabled} onClick={onAdd}>Add adjustment</Button>
        </div>
    );
}

function adjustmentHasError(index: number, errorFor: (field: string) => string | undefined): boolean {
    return ['name', 'adjustment_type', 'effect', 'calculation_type', 'calculation_base', 'rate', 'amount', 'allocation_method', 'allocations']
        .some((field) => Boolean(errorFor(`adjustments.${index}.${field}`)));
}

function AdjustmentActions({ disabled, onEdit, onRemove }: { disabled: boolean; onEdit: () => void; onRemove: () => void }) {
    return <div className="flex justify-end gap-3"><button type="button" disabled={disabled} className="font-semibold text-sky-700 disabled:cursor-not-allowed disabled:opacity-50" onClick={onEdit}>Edit adjustment</button><button type="button" disabled={disabled} className="font-semibold text-rose-600 disabled:cursor-not-allowed disabled:opacity-50" onClick={onRemove}>Remove adjustment</button></div>;
}

function AdjustmentMobileDetails({ adjustment }: { adjustment: EditableSalesAdjustment }) {
    return <div className="grid grid-cols-2 gap-2"><Preview label="Effect" value={formatAdjustmentEffect(adjustment)} /><Preview label="Calculation" value={formatAdjustmentAmount(adjustment)} /><Preview label="Type" value={adjustment.adjustment_type.replaceAll('_', ' ')} /><Preview label="Allocation" value={adjustment.allocation_method.replaceAll('_', ' ')} /></div>;
}

function Preview({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block text-slate-900">{value}</strong></div>;
}

function ErrorBadge() {
    return <span className="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Error</span>;
}
