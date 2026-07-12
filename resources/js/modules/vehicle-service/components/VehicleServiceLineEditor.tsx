import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import {
    createVehicleServiceLine,
    deleteVehicleServiceLine,
    listVehicleServiceLines,
    updateVehicleServiceLine,
} from '../vehicleServiceApi';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import {
    emptyLineForm,
    formatLineItem,
    lineFormToPayload,
    lineToForm,
    type LineDialog,
    type VehicleServiceLineFormValue,
} from './line-editor/lineForm';
import { SummaryValue } from './line-editor/LineSummary';
import { VehicleServiceLineForm } from './line-editor/VehicleServiceLineForm';

export default function VehicleServiceLineEditor({
    jobId,
    expectedVersion,
    onChanged,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged?: (lines: VehicleServiceJobLine[], nextVersion: number) => void;
}) {
    const result = useApi((signal) => listVehicleServiceLines(jobId, signal), [jobId], true, false);
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const [removeTarget, setRemoveTarget] = useState<VehicleServiceJobLine | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [toast, setToast] = useState('');
    const [localExpectedVersion, setLocalExpectedVersion] = useState(expectedVersion);

    useEffect(() => {
        if (toast === '') return;

        const timeout = window.setTimeout(() => setToast(''), 2500);
        return () => window.clearTimeout(timeout);
    }, [toast]);

    useEffect(() => {
        setLocalExpectedVersion(expectedVersion);
    }, [expectedVersion]);

    const saveLine = async (value: VehicleServiceLineFormValue) => {
        if (!dialog || saving) return;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...lineFormToPayload(value), expected_version: localExpectedVersion };
            if (dialog.mode === 'edit') {
                const saved = await updateVehicleServiceLine(jobId, dialog.lineId, payload);
                const nextLines = replaceLine(result.data ?? [], saved);
                result.setData(nextLines);
                setToast('Job line updated.');
                onChanged?.(nextLines, localExpectedVersion + 1);
            } else {
                const saved = await createVehicleServiceLine(jobId, payload);
                const nextLines = appendLine(result.data ?? [], saved);
                result.setData(nextLines);
                setToast('Job line added.');
                onChanged?.(nextLines, localExpectedVersion + 1);
            }
            setDialog(null);
            setLocalExpectedVersion((current) => current + 1);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const removeLine = async (line: VehicleServiceJobLine) => {
        if (removing) return;
        setRemoving(true);
        setError(null);
        try {
            await deleteVehicleServiceLine(jobId, line.id, localExpectedVersion);
            const nextLines = removeLineFromList(result.data ?? [], line.id);
            result.setData(nextLines);
            setToast('Job line removed.');
            setRemoveTarget(null);
            onChanged?.(nextLines, localExpectedVersion + 1);
            setLocalExpectedVersion((current) => current + 1);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setRemoving(false);
        }
    };

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? result.error} />
            <ToastNotice message={toast} />
            <VehicleServiceLineTable
                lines={result.data ?? []}
                loading={result.loading}
                onAdd={() => {
                    setError(null);
                    setDialog({ mode: 'create', value: emptyLineForm() });
                }}
                onEdit={(line) => {
                    setError(null);
                    setDialog({ mode: 'edit', lineId: line.id, value: lineToForm(line) });
                }}
                onRemove={setRemoveTarget}
            />
            <FormDrawer
                open={Boolean(dialog)}
                title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'}
                onClose={() => !saving && setDialog(null)}
            >
                {dialog && (
                    <VehicleServiceLineForm
                        key={dialog.mode === 'edit' ? `edit-${dialog.lineId}` : 'create'}
                        value={dialog.value}
                        mode={dialog.mode}
                        error={error}
                        saving={saving}
                        onCancel={() => setDialog(null)}
                        onSave={(value) => void saveLine(value)}
                    />
                )}
            </FormDrawer>
            <ConfirmDialog
                open={Boolean(removeTarget)}
                title="Remove line"
                message="This service line will be removed from the job."
                confirmLabel="Remove line"
                loading={removing}
                onCancel={() => !removing && setRemoveTarget(null)}
                onConfirm={() => removeTarget && void removeLine(removeTarget)}
            />
        </div>
    );
}

function appendLine(lines: VehicleServiceJobLine[], line: VehicleServiceJobLine): VehicleServiceJobLine[] {
    const additions = [line, ...(line.children ?? [])];
    return [...lines, ...additions].sort((left, right) => left.line_number - right.line_number);
}

function replaceLine(lines: VehicleServiceJobLine[], line: VehicleServiceJobLine): VehicleServiceJobLine[] {
    return lines
        .map((current) => current.id === line.id ? line : current)
        .sort((left, right) => left.line_number - right.line_number);
}

function removeLineFromList(lines: VehicleServiceJobLine[], lineId: number): VehicleServiceJobLine[] {
    return lines.filter((line) => line.id !== lineId && line.parent_line_id !== lineId)
        .map((line, index) => ({ ...line, line_number: index + 1 }));
}

function VehicleServiceLineTable({ lines, loading, onAdd, onEdit, onRemove }: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    onAdd: () => void;
    onEdit: (line: VehicleServiceJobLine) => void;
    onRemove: (line: VehicleServiceJobLine) => void;
}) {
    const columns: DataColumn<VehicleServiceJobLine>[] = [
        { key: 'item', header: 'Item', render: formatLineItem },
        { key: 'quantity', header: 'Qty', render: (line) => line.quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.unit_price, className: 'tabular-nums' },
        { key: 'total', header: 'Total', render: (line) => line.line_total, className: 'tabular-nums font-semibold' },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (line) => (
                <LineActions onEdit={() => onEdit(line)} onRemove={() => onRemove(line)} />
            ),
        },
    ];

    return (
        <div className="space-y-3">
            <div className="flex justify-end">
                <Button type="button" onClick={onAdd}>Add line</Button>
            </div>
            {loading
                ? <LoadingState />
                : (
                    <DataTable
                        rows={lines}
                        columns={columns}
                        rowKey={(line) => line.id}
                        emptyMessage="No lines added yet. Click Add line to start."
                        mobileSummary={formatLineItem}
                        mobileDetails={(line) => <LineMobileDetails line={line} />}
                        mobileActions={(line) => (
                            <LineActions
                                onEdit={() => onEdit(line)}
                                onRemove={() => onRemove(line)}
                            />
                        )}
                    />
                )}
        </div>
    );
}

function LineActions({ onEdit, onRemove }: { onEdit: () => void; onRemove: () => void }) {
    return (
        <div className="flex justify-end gap-3">
            <button type="button" className="font-semibold text-sky-700" onClick={onEdit}>
                Edit line
            </button>
            <button type="button" className="font-semibold text-rose-600" onClick={onRemove}>
                Remove line
            </button>
        </div>
    );
}

function LineMobileDetails({ line }: { line: VehicleServiceJobLine }) {
    return (
        <div className="grid grid-cols-2 gap-2">
            <SummaryValue label="Qty" value={line.quantity} />
            <SummaryValue label="UOM" value={line.uom?.code ?? '-'} />
            <SummaryValue label="Price" value={line.unit_price} />
            <SummaryValue label="Total" value={line.line_total} />
        </div>
    );
}

function ToastNotice({ message }: { message: string }) {
    if (message === '') return null;

    return (
        <div className="fixed bottom-4 right-4 z-40 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-lg">
            {message}
        </div>
    );
}
