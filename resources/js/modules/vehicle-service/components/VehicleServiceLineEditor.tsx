import { useEffect, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import {
    createVehicleServiceLine,
    deleteVehicleServiceLine,
    issueVehicleServiceInventory,
    listInventoryIssueLines,
    listVehicleServiceLines,
    updateVehicleServiceLine,
} from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJobStore } from '../state/vehicleServiceJobStore';
import type { VehicleServiceJobLine, VehicleServiceJobTotals } from '../vehicleServiceTypes';
import { VehicleServiceInventoryIssueDrawer } from './VehicleServiceInventoryIssueDrawer';
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

const TOAST_DURATION_MS = 2500;
const CHILD_LINE_INDENT_CLASS = 'pl-7 md:pl-9';

interface VehicleServiceLineDisplayRow {
    line: VehicleServiceJobLine;
    depth: number;
    parent: VehicleServiceJobLine | null;
    isComboParent: boolean;
    isComboChild: boolean;
    childCount: number;
}

export default function VehicleServiceLineEditor({
    jobId,
    expectedVersion,
    onChanged,
    onVersionChanged,
    jobStore,
}: {
    jobId: number;
    expectedVersion: number;
    onChanged: (lines: VehicleServiceJobLine[], nextVersion: number, totals?: VehicleServiceJobTotals) => void;
    onVersionChanged: (nextVersion: number) => void;
    jobStore: VehicleServiceJobStore;
}) {
    const { permissions } = useAuth();
    const canViewLines = permissions.includes(vehicleServicePermissions.linesView);
    const canManageLines = canViewLines && permissions.includes(vehicleServicePermissions.linesManage);
    const canViewInventory = permissions.includes(vehicleServicePermissions.inventoryView);
    const canIssueInventory = canViewInventory
        && permissions.includes(vehicleServicePermissions.inventoryIssue);
    const linesResult = useApi(
        (signal) => listVehicleServiceLines(jobId, signal),
        [jobId],
        canViewLines,
        false,
    );
    const inventoryOnlyResult = useApi(
        (signal) => listInventoryIssueLines(jobId, {}, signal),
        [jobId],
        !canViewLines && canViewInventory,
        false,
    );
    const visibleLines = canViewLines
        ? (linesResult.data ?? [])
        : (inventoryOnlyResult.data ?? []);
    const loading = canViewLines ? linesResult.loading : inventoryOnlyResult.loading;
    const loadError = canViewLines ? linesResult.error : inventoryOnlyResult.error;
    const [dialog, setDialog] = useState<LineDialog | null>(null);
    const [createFormRevision, setCreateFormRevision] = useState(0);
    const [removeTarget, setRemoveTarget] = useState<VehicleServiceJobLine | null>(null);
    const [issueTarget, setIssueTarget] = useState<VehicleServiceJobLine | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [toast, setToast] = useState('');

    useEffect(() => {
        if (toast === '') return;

        const timeout = window.setTimeout(() => setToast(''), TOAST_DURATION_MS);
        return () => window.clearTimeout(timeout);
    }, [toast]);

    const saveLine = async (value: VehicleServiceLineFormValue, issueStock: boolean) => {
        if (!canManageLines || !dialog || saving) return;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...lineFormToPayload(value), expected_version: expectedVersion };
            if (dialog.mode === 'edit') {
                const mutation = await updateVehicleServiceLine(jobId, dialog.lineId, payload);
                const nextLines = replaceLine(linesResult.data ?? [], mutation.line);
                jobStore.getState().replaceWorkforceLines({
                    lines: mutation.workforceLines,
                    rowVersion: mutation.rowVersion,
                });
                linesResult.setData(nextLines);
                setToast('Job line updated.');
                onChanged(nextLines, mutation.rowVersion, mutation.jobTotals);
                setDialog(null);
            } else {
                const mutation = await createVehicleServiceLine(jobId, payload);
                const saved = mutation.line;
                const nextLines = appendLine(linesResult.data ?? [], saved);
                const lineVersion = mutation.rowVersion;
                jobStore.getState().replaceWorkforceLines({
                    lines: mutation.workforceLines,
                    rowVersion: mutation.rowVersion,
                });

                if (issueStock && canIssueInventory && value.issueWarehouse && value.issueLocation) {
                    try {
                        const movements = await issueVehicleServiceInventory(jobId, {
                            expected_version: lineVersion,
                            warehouse_id: value.issueWarehouse.id,
                            warehouse_location_id: value.issueLocation.id,
                            line_ids: [saved.id],
                        });
                        const movement = movements.find((candidate) => candidate.source_line_id === saved.id);
                        const issuedLine = {
                            ...saved,
                            inventory_movement_id: movement?.id ?? saved.inventory_movement_id ?? null,
                            issue_eligible: false,
                            inventory_warning: null,
                            status: 'issued',
                        };
                        const issuedLines = replaceLine(nextLines, issuedLine);
                        linesResult.setData(issuedLines);
                        setToast('Job line added and stock issued.');
                        onChanged(issuedLines, lineVersion + 1, mutation.jobTotals);
                    } catch (requestError) {
                        linesResult.setData(nextLines);
                        setToast('Job line added. Stock issue is still pending.');
                        setError(toApiError(requestError));
                        onChanged(nextLines, lineVersion, mutation.jobTotals);
                    }
                } else {
                    linesResult.setData(nextLines);
                    setToast('Job line added.');
                    onChanged(nextLines, lineVersion, mutation.jobTotals);
                }
                setDialog({ mode: 'create', value: emptyLineForm() });
                setCreateFormRevision((current) => current + 1);
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const removeLine = async (line: VehicleServiceJobLine) => {
        if (!canManageLines || removing) return;
        setRemoving(true);
        setError(null);
        try {
            const mutation = await deleteVehicleServiceLine(jobId, line.id, expectedVersion);
            const nextLines = removeLineFromList(linesResult.data ?? [], line.id);
            jobStore.getState().replaceWorkforceLines({
                lines: mutation.workforceLines,
                rowVersion: mutation.rowVersion,
            });
            linesResult.setData(nextLines);
            setToast('Job line removed.');
            setRemoveTarget(null);
            onChanged(nextLines, mutation.rowVersion, mutation.jobTotals);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setRemoving(false);
        }
    };

    const handleStockIssued = async (nextVersion: number) => {
        onVersionChanged(nextVersion);
        setToast('Stock issued successfully.');
        setError(null);
        try {
            if (canViewLines) {
                const freshLines = await listVehicleServiceLines(jobId);
                linesResult.setData(freshLines);
                onChanged(freshLines, nextVersion);
            } else {
                const pendingLines = await listInventoryIssueLines(jobId);
                inventoryOnlyResult.setData(pendingLines);
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    };

    if (!canViewLines && !canViewInventory) {
        return (
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">
                You do not have permission to view job lines or job inventory usage.
            </div>
        );
    }

    return (
        <div className="space-y-5">
            <ErrorAlert error={error ?? loadError} />
            <ToastNotice message={toast} />
            {!canViewLines && canViewInventory && (
                <div className="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                    Showing inventory lines that are still pending stock issue.
                </div>
            )}
            <VehicleServiceLineTable
                lines={visibleLines}
                loading={loading}
                canManageLines={canManageLines}
                canViewInventory={canViewInventory}
                canIssueInventory={canIssueInventory}
                inventoryOnly={!canViewLines}
                onAdd={() => {
                    setError(null);
                    setDialog({ mode: 'create', value: emptyLineForm() });
                }}
                onEdit={(line) => {
                    setError(null);
                    setDialog({ mode: 'edit', lineId: line.id, value: lineToForm(line) });
                }}
                onRemove={setRemoveTarget}
                onIssue={(line) => {
                    setError(null);
                    setIssueTarget(line);
                }}
            />
            {canManageLines && (
                <FormDrawer
                    open={Boolean(dialog)}
                    title={dialog?.mode === 'edit' ? 'Edit line' : 'Add line'}
                    onClose={() => !saving && setDialog(null)}
                    closeDisabled={saving}
                >
                    {dialog && (
                        <VehicleServiceLineForm
                            key={dialog.mode === 'edit' ? `edit-${dialog.lineId}` : `create-${createFormRevision}`}
                            value={dialog.value}
                            mode={dialog.mode}
                            error={error}
                            saving={saving}
                            canIssueInventory={canIssueInventory}
                            onClear={() => {
                                setError(null);
                                setDialog({ mode: 'create', value: emptyLineForm() });
                                setCreateFormRevision((current) => current + 1);
                            }}
                            onCancel={() => setDialog(null)}
                            onSave={(value, issueStock) => void saveLine(value, issueStock)}
                        />
                    )}
                </FormDrawer>
            )}
            {canIssueInventory && (
                <VehicleServiceInventoryIssueDrawer
                    open={Boolean(issueTarget)}
                    jobId={jobId}
                    line={issueTarget}
                    expectedVersion={expectedVersion}
                    onClose={() => setIssueTarget(null)}
                    onIssued={(nextVersion) => void handleStockIssued(nextVersion)}
                />
            )}
            {canManageLines && (
                <ConfirmDialog
                    open={Boolean(removeTarget)}
                    title="Remove line"
                    message="This service line will be removed from the job."
                    confirmLabel="Remove line"
                    loading={removing}
                    onCancel={() => !removing && setRemoveTarget(null)}
                    onConfirm={() => removeTarget && void removeLine(removeTarget)}
                />
            )}
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

function VehicleServiceLineTable({
    lines,
    loading,
    canManageLines,
    canViewInventory,
    canIssueInventory,
    inventoryOnly,
    onAdd,
    onEdit,
    onRemove,
    onIssue,
}: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    canManageLines: boolean;
    canViewInventory: boolean;
    canIssueInventory: boolean;
    inventoryOnly: boolean;
    onAdd: () => void;
    onEdit: (line: VehicleServiceJobLine) => void;
    onRemove: (line: VehicleServiceJobLine) => void;
    onIssue: (line: VehicleServiceJobLine) => void;
}) {
    const [expandedComboIds, setExpandedComboIds] = useState<Set<number>>(() => new Set());
    const allRows = buildVehicleServiceLineDisplayRows(lines);
    const rows = filterCollapsedComboChildren(allRows, expandedComboIds);
    const toggleCombo = (comboId: number) => {
        setExpandedComboIds((current) => {
            const next = new Set(current);
            if (next.has(comboId)) next.delete(comboId);
            else next.add(comboId);
            return next;
        });
    };
    const columns: DataColumn<VehicleServiceLineDisplayRow>[] = [
        {
            key: 'item',
            header: 'Item',
            render: (row) => renderLineItemCell(
                row,
                expandedComboIds.has(row.line.id),
                () => toggleCombo(row.line.id),
            ),
        },
        { key: 'quantity', header: 'Qty', render: (row) => renderLineMetric(row, row.line.quantity), className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (row) => renderLineMetric(row, row.line.uom?.code ?? '-') },
        ...(canViewInventory ? [{ key: 'stock', header: 'Stock', render: (row: VehicleServiceLineDisplayRow) => renderStockState(row.line) }] : []),
        ...(!inventoryOnly ? [
            { key: 'price', header: 'Unit price', render: renderLineUnitPrice, className: 'tabular-nums' },
            { key: 'total', header: 'Total', render: renderLineTotal, className: 'tabular-nums font-semibold' },
        ] : []),
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <LineActions
                    onEdit={canManageLines && !row.isComboChild ? () => onEdit(row.line) : undefined}
                    onRemove={canManageLines && !row.isComboChild ? () => onRemove(row.line) : undefined}
                    onIssue={canIssueInventory && canIssueLine(row.line) ? () => onIssue(row.line) : undefined}
                />
            ),
        },
    ];

    return (
        <div className="space-y-3">
            {canManageLines && (
                <div className="flex justify-end">
                    <Button type="button" onClick={onAdd}>Add line</Button>
                </div>
            )}
            {loading
                ? <LoadingState />
                : (
                    <DataTable
                        rows={rows}
                        columns={columns}
                        rowKey={(row) => row.line.id}
                        emptyMessage={inventoryOnly
                            ? 'No inventory lines remain to issue.'
                            : 'No lines added yet. Click Add line to start.'}
                        mobileSummary={(row) => renderMobileSummary(
                            row,
                            expandedComboIds.has(row.line.id),
                            () => toggleCombo(row.line.id),
                        )}
                        mobileDetails={(row) => <LineMobileDetails row={row} showStock={canViewInventory} showPricing={!inventoryOnly} />}
                        mobileActions={(row) => (
                            <LineActions
                                onEdit={canManageLines && !row.isComboChild ? () => onEdit(row.line) : undefined}
                                onRemove={canManageLines && !row.isComboChild ? () => onRemove(row.line) : undefined}
                                onIssue={canIssueInventory && canIssueLine(row.line) ? () => onIssue(row.line) : undefined}
                            />
                        )}
                        onRowClick={(row) => toggleCombo(row.line.id)}
                        rowClickEnabled={(row) => row.isComboParent}
                        rowClassName={lineRowClassName}
                    />
                )}
        </div>
    );
}

function LineActions({ onEdit, onRemove, onIssue }: {
    onEdit?: () => void;
    onRemove?: () => void;
    onIssue?: () => void;
}) {
    if (!onEdit && !onRemove && !onIssue) return null;

    return (
        <div className="flex justify-end gap-2">
            {onIssue && (
                <button
                    type="button"
                    className="inline-flex h-10 items-center justify-center rounded-xl border border-emerald-200 px-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                    onClick={onIssue}
                    aria-label="Issue stock"
                    title="Issue stock"
                >
                    Issue stock
                </button>
            )}
            {onEdit && (
                <button
                    type="button"
                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-sky-700 transition hover:border-sky-200 hover:bg-sky-50"
                    onClick={onEdit}
                    aria-label="Edit line"
                    title="Edit line"
                >
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.9" className="h-5 w-5" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.75v2.5h2.5L14.5 8l-2.5-2.5-8.25 8.25Z" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="m10.75 4.75 2.5 2.5" />
                    </svg>
                </button>
            )}
            {onRemove && (
                <button
                    type="button"
                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-rose-600 transition hover:border-rose-200 hover:bg-rose-50"
                    onClick={onRemove}
                    aria-label="Remove line"
                    title="Remove line"
                >
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.9" className="h-5 w-5" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5.75 7.25h8.5" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M8 7.25V5.75a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1.5" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M7 7.25v6a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-6" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M8.75 9.25v3" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 9.25v3" />
                    </svg>
                </button>
            )}
        </div>
    );
}

function LineMobileDetails({
    row,
    showStock,
    showPricing,
}: {
    row: VehicleServiceLineDisplayRow;
    showStock: boolean;
    showPricing: boolean;
}) {
    const { line } = row;
    return (
        <div className={`grid grid-cols-2 gap-2 ${row.isComboChild ? CHILD_LINE_INDENT_CLASS : ''}`}>
            <SummaryValue label="Qty" value={line.quantity} />
            <SummaryValue label="UOM" value={line.uom?.code ?? '-'} />
            {showStock && <SummaryValue label="Stock" value={stockStateLabel(line)} />}
            {showPricing && <SummaryValue label="Price" value={row.isComboChild && !line.is_billable ? 'Included in pack' : line.unit_price} />}
            {showPricing && <SummaryValue label="Total" value={line.line_total} />}
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

export function buildVehicleServiceLineDisplayRows(lines: VehicleServiceJobLine[]): VehicleServiceLineDisplayRow[] {
    const byId = new Map(lines.map((line) => [line.id, line]));
    const childCountByParentId = new Map<number, number>();

    lines.forEach((line) => {
        if (typeof line.parent_line_id === 'number') {
            childCountByParentId.set(line.parent_line_id, (childCountByParentId.get(line.parent_line_id) ?? 0) + 1);
        }
    });

    return lines.map((line) => {
        const parent = typeof line.parent_line_id === 'number' ? (byId.get(line.parent_line_id) ?? null) : null;
        const depth = parent ? (typeof parent.parent_line_id === 'number' ? 2 : 1) : 0;

        return {
            line,
            depth,
            parent,
            isComboParent: line.line_source_type === 'combo_parent',
            isComboChild: line.line_source_type === 'combo_child',
            childCount: childCountByParentId.get(line.id) ?? 0,
        };
    });
}

export function filterCollapsedComboChildren(
    rows: VehicleServiceLineDisplayRow[],
    expandedComboIds: ReadonlySet<number>,
): VehicleServiceLineDisplayRow[] {
    return rows.filter((row) => !row.isComboChild
        || row.parent === null
        || expandedComboIds.has(row.parent.id));
}

function renderLineItemCell(
    row: VehicleServiceLineDisplayRow,
    expanded: boolean,
    onToggle: () => void,
) {
    const itemLabel = formatLineItem(row.line);

    if (row.isComboParent) {
        return <ComboDisclosure row={row} expanded={expanded} onToggle={onToggle} showDescription />;
    }

    if (row.isComboChild) {
        return (
            <div className={`${CHILD_LINE_INDENT_CLASS} relative space-y-1`}>
                <span className="pointer-events-none absolute left-2 top-1 h-5 w-4 border-b border-l border-slate-300" aria-hidden="true" />
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-slate-700">{itemLabel}</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-600">
                        Included
                    </span>
                </div>
                <p className="text-xs text-slate-500">
                    Under {formatLineItem(row.parent ?? row.line)}
                </p>
            </div>
        );
    }

    return <span className="text-slate-900">{itemLabel}</span>;
}

function renderLineMetric(row: VehicleServiceLineDisplayRow, value: string) {
    return <span className={row.isComboChild ? 'text-slate-500' : ''}>{value}</span>;
}

function renderLineUnitPrice(row: VehicleServiceLineDisplayRow) {
    if (row.isComboChild && !row.line.is_billable) {
        return (
            <div className="space-y-0.5">
                <div className="text-xs font-medium uppercase tracking-wide text-slate-500">Included</div>
                <div className="text-xs text-slate-400">{row.line.unit_price}</div>
            </div>
        );
    }

    return <span className={row.isComboChild ? 'text-slate-500' : ''}>{row.line.unit_price}</span>;
}

function renderLineTotal(row: VehicleServiceLineDisplayRow) {
    if (row.isComboChild && !row.line.is_billable) {
        return (
            <div className="space-y-0.5">
                <div className="text-xs font-semibold uppercase tracking-wide text-emerald-700">Included</div>
                <div className="text-xs font-medium text-slate-400">{row.line.line_total}</div>
            </div>
        );
    }

    return <span className={row.isComboChild ? 'text-slate-600' : 'text-slate-900'}>{row.line.line_total}</span>;
}

function renderMobileSummary(
    row: VehicleServiceLineDisplayRow,
    expanded: boolean,
    onToggle: () => void,
) {
    if (row.isComboParent) {
        return <ComboDisclosure row={row} expanded={expanded} onToggle={onToggle} showDescription={false} />;
    }

    if (row.isComboChild) {
        return (
            <div className={`${CHILD_LINE_INDENT_CLASS} relative`}>
                <span className="pointer-events-none absolute left-2 top-1 h-5 w-4 border-b border-l border-slate-300" aria-hidden="true" />
                <span className="font-medium text-slate-700">{formatLineItem(row.line)}</span>
            </div>
        );
    }

    return formatLineItem(row.line);
}

function ComboDisclosure({
    row,
    expanded,
    onToggle,
    showDescription,
}: {
    row: VehicleServiceLineDisplayRow;
    expanded: boolean;
    onToggle: () => void;
    showDescription: boolean;
}) {
    const itemLabel = formatLineItem(row.line);
    const childLabel = `${row.childCount} included item${row.childCount === 1 ? '' : 's'}`;

    return (
        <button
            type="button"
            className="group -m-2 flex max-w-full items-start gap-2 rounded-lg p-2 text-left outline-none transition hover:bg-sky-100/70 focus-visible:ring-2 focus-visible:ring-sky-500"
            aria-expanded={expanded}
            aria-label={`${expanded ? 'Collapse' : 'Expand'} ${itemLabel}, ${childLabel}`}
            onClick={onToggle}
        >
            <svg
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                className={`mt-0.5 h-4 w-4 shrink-0 text-sky-700 transition-transform ${expanded ? 'rotate-90' : ''}`}
                aria-hidden="true"
            >
                <path strokeLinecap="round" strokeLinejoin="round" d="m7.5 5 5 5-5 5" />
            </svg>
            <span className="min-w-0 space-y-1">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="font-semibold text-slate-900">{itemLabel}</span>
                    <span className="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                        Combo pack
                    </span>
                </span>
                {showDescription && (
                    <span className="block text-xs font-normal text-slate-500">
                        Bundle price covers {childLabel}.
                    </span>
                )}
            </span>
        </button>
    );
}

function lineRowClassName(row: VehicleServiceLineDisplayRow): string | undefined {
    if (row.isComboParent) return 'bg-sky-50/60';
    if (row.isComboChild) return 'bg-slate-50/70';
    return undefined;
}

function canIssueLine(line: VehicleServiceJobLine): boolean {
    return line.is_inventory_tracked
        && !line.is_customer_supplied
        && line.inventory_movement_id == null
        && line.status !== 'cancelled';
}

function stockStateLabel(line: VehicleServiceJobLine): string {
    if (!line.is_inventory_tracked || line.is_customer_supplied) return '-';
    return line.inventory_movement_id == null ? 'Pending issue' : 'Issued';
}

function renderStockState(line: VehicleServiceJobLine) {
    const label = stockStateLabel(line);
    if (label === '-') return <span className="text-slate-400">-</span>;

    return (
        <span className={`rounded-full px-2 py-1 text-xs font-semibold ${label === 'Issued' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'}`}>
            {label}
        </span>
    );
}
