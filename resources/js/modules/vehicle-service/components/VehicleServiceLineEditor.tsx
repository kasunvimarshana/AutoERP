import { useEffect, useRef, useState, type ReactNode } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { useApi } from '@/shared/hooks/useApi';
import { addDecimal, compareDecimalStrings, subtractDecimal, sumDecimals } from '@/shared/utils/decimal';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { useAuth } from '@/modules/auth/AuthProvider';
import {
    createVehicleServiceLine,
    deleteVehicleServiceLine,
    listInventoryIssueLines,
    listVehicleServiceLines,
    updateVehicleServiceLine,
} from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJobStore } from '../state/vehicleServiceJobStore';
import type { VehicleServiceJobLine, VehicleServiceJobTotals } from '../vehicleServiceTypes';
import { VehicleServiceInventoryIssueDrawer } from './VehicleServiceInventoryIssueDrawer';
import {
    lineValueWithItem,
    VehicleServiceLineItemLookup,
} from './line-editor/LineItemFields';
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
const DEFAULT_QUANTITY = '1.000000';
const MINIMUM_QUANTITY = '0.000001';

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
    const [quickAddItem, setQuickAddItem] = useState<ItemLookupResource | null>(null);
    const [removeTarget, setRemoveTarget] = useState<VehicleServiceJobLine | null>(null);
    const [issueTarget, setIssueTarget] = useState<VehicleServiceJobLine | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [toast, setToast] = useState('');
    const mutationInFlight = useRef(false);

    useEffect(() => {
        if (toast === '') return;

        const timeout = window.setTimeout(() => setToast(''), TOAST_DURATION_MS);
        return () => window.clearTimeout(timeout);
    }, [toast]);

    const addSelectedItem = async (item: ItemLookupResource | null) => {
        setQuickAddItem(item);
        if (!item || !canManageLines || mutationInFlight.current) return;

        mutationInFlight.current = true;
        setSaving(true);
        setError(null);
        try {
            const value = lineValueWithItem(emptyLineForm(), item);
            const mutation = await createVehicleServiceLine(jobId, {
                ...lineFormToPayload(value),
                expected_version: expectedVersion,
            });
            const nextLines = appendLine(linesResult.data ?? [], mutation.line);
            jobStore.getState().replaceWorkforceLines({
                lines: mutation.workforceLines,
                rowVersion: mutation.rowVersion,
            });
            linesResult.setData(nextLines);
            onChanged(nextLines, mutation.rowVersion, mutation.jobTotals);
            setQuickAddItem(null);
            setToast('Job line added.');
        } catch (requestError) {
            setQuickAddItem(null);
            setError(toApiError(requestError));
        } finally {
            mutationInFlight.current = false;
            setSaving(false);
        }
    };

    const saveLine = async (value: VehicleServiceLineFormValue) => {
        if (!canManageLines || !dialog || mutationInFlight.current) return;
        mutationInFlight.current = true;
        setSaving(true);
        setError(null);
        try {
            const payload = { ...lineFormToPayload(value), expected_version: expectedVersion };
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
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            mutationInFlight.current = false;
            setSaving(false);
        }
    };

    const updateQuantity = async (line: VehicleServiceJobLine, quantity: string) => {
        if (!canManageLines || mutationInFlight.current || compareDecimalStrings(quantity, MINIMUM_QUANTITY) < 0) return;
        mutationInFlight.current = true;
        setSaving(true);
        setError(null);
        try {
            const value = { ...lineToForm(line), quantity };
            const mutation = await updateVehicleServiceLine(jobId, line.id, {
                ...lineFormToPayload(value),
                expected_version: expectedVersion,
            });
            const nextLines = replaceLine(linesResult.data ?? [], mutation.line);
            jobStore.getState().replaceWorkforceLines({
                lines: mutation.workforceLines,
                rowVersion: mutation.rowVersion,
            });
            linesResult.setData(nextLines);
            onChanged(nextLines, mutation.rowVersion, mutation.jobTotals);
            setToast('Quantity updated.');
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            mutationInFlight.current = false;
            setSaving(false);
        }
    };

    const removeLine = async (line: VehicleServiceJobLine) => {
        if (!canManageLines || mutationInFlight.current) return;
        mutationInFlight.current = true;
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
            mutationInFlight.current = false;
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
            {canManageLines && (
                <VehicleServiceLineItemLookup
                    value={quickAddItem}
                    disabled={saving || removing}
                    onChange={(item) => void addSelectedItem(item)}
                />
            )}
            <VehicleServiceLineTable
                lines={visibleLines}
                loading={loading}
                canManageLines={canManageLines}
                canViewInventory={canViewInventory}
                canIssueInventory={canIssueInventory}
                inventoryOnly={!canViewLines}
                mutationDisabled={saving || removing}
                onEdit={(line) => {
                    setError(null);
                    setDialog({ lineId: line.id, value: lineToForm(line) });
                }}
                onQuantityChange={(line, quantity) => void updateQuantity(line, quantity)}
                onRemove={setRemoveTarget}
                onIssue={(line) => {
                    setError(null);
                    setIssueTarget(line);
                }}
            />
            {canManageLines && (
                <FormDrawer
                    open={Boolean(dialog)}
                    title="Edit line"
                    onClose={() => !saving && setDialog(null)}
                    closeDisabled={saving}
                >
                    {dialog && (
                        <VehicleServiceLineForm
                            key={`edit-${dialog.lineId}`}
                            value={dialog.value}
                            error={error}
                            saving={saving}
                            onCancel={() => setDialog(null)}
                            onSave={(value) => void saveLine(value)}
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
    mutationDisabled,
    onEdit,
    onQuantityChange,
    onRemove,
    onIssue,
}: {
    lines: VehicleServiceJobLine[];
    loading: boolean;
    canManageLines: boolean;
    canViewInventory: boolean;
    canIssueInventory: boolean;
    inventoryOnly: boolean;
    mutationDisabled: boolean;
    onEdit: (line: VehicleServiceJobLine) => void;
    onQuantityChange: (line: VehicleServiceJobLine, quantity: string) => void;
    onRemove: (line: VehicleServiceJobLine) => void;
    onIssue: (line: VehicleServiceJobLine) => void;
}) {
    const [expandedComboIds, setExpandedComboIds] = useState<Set<number>>(() => new Set());
    const allRows = buildVehicleServiceLineDisplayRows(lines);
    const rows = filterCollapsedComboChildren(allRows, expandedComboIds);
    const totalLines = allRows
        .map((row) => row.line)
        .filter((line) => line.parent_line_id == null && line.status !== 'cancelled');
    const billableTotalLines = totalLines.filter((line) => line.is_billable);
    const quantityTotal = sumDecimals(totalLines.map((line) => line.quantity));
    const discountTotal = sumDecimals(billableTotalLines.map((line) => line.discount_amount));
    const subtotalTotal = sumDecimals(billableTotalLines.map((line) => line.line_total));
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
            footer: 'Total',
            render: (row) => renderLineItemCell(
                row,
                expandedComboIds.has(row.line.id),
                () => toggleCombo(row.line.id),
                canViewInventory,
            ),
        },
        {
            key: 'quantity',
            header: 'Quantity',
            footer: <QuantityDisplay value={quantityTotal} precision={6} minimumPrecision={1} />,
            render: (row) => canManageLines && !row.isComboChild
                ? <QuantityControl key={`${row.line.id}-${row.line.quantity}`} line={row.line} disabled={mutationDisabled} onChange={(quantity) => onQuantityChange(row.line, quantity)} />
                : renderLineMetric(row, `${formatQuantity(row.line.quantity, 6, 1)} ${row.line.uom?.code ?? ''}`.trim()),
        },
        ...(!inventoryOnly ? [
            { key: 'price', header: 'Unit price', render: renderLineUnitPrice, className: 'tabular-nums' },
            {
                key: 'discount',
                header: 'Discount',
                footer: <MoneyDisplay value={discountTotal} />,
                render: renderLineDiscount,
                className: 'tabular-nums',
            },
            {
                key: 'total',
                header: 'Subtotal',
                footer: <MoneyDisplay value={subtotalTotal} />,
                render: renderLineTotal,
                className: 'tabular-nums font-semibold',
            },
        ] : []),
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <LineActions
                    onEdit={canManageLines && !row.isComboChild && !mutationDisabled ? () => onEdit(row.line) : undefined}
                    onRemove={canManageLines && !row.isComboChild && !mutationDisabled ? () => onRemove(row.line) : undefined}
                    onIssue={canIssueInventory && canIssueLine(row.line) && !mutationDisabled ? () => onIssue(row.line) : undefined}
                />
            ),
        },
    ];

    return (
        <div className="space-y-3">
            {loading
                ? <LoadingState />
                : (
                    <DataTable
                        rows={rows}
                        columns={columns}
                        rowKey={(row) => row.line.id}
                        emptyMessage={inventoryOnly
                            ? 'No inventory lines remain to issue.'
                            : 'No lines added yet. Search for an item above to start.'}
                        mobileSummary={(row) => renderMobileSummary(
                            row,
                            expandedComboIds.has(row.line.id),
                            () => toggleCombo(row.line.id),
                        )}
                        mobileDetails={(row) => (
                            <LineMobileDetails
                                row={row}
                                showStock={canViewInventory}
                                showPricing={!inventoryOnly}
                                quantityControl={canManageLines && !row.isComboChild
                                    ? <QuantityControl key={`${row.line.id}-${row.line.quantity}`} line={row.line} disabled={mutationDisabled} onChange={(quantity) => onQuantityChange(row.line, quantity)} />
                                    : undefined}
                            />
                        )}
                        mobileActions={(row) => (
                            <LineActions
                                onEdit={canManageLines && !row.isComboChild && !mutationDisabled ? () => onEdit(row.line) : undefined}
                                onRemove={canManageLines && !row.isComboChild && !mutationDisabled ? () => onRemove(row.line) : undefined}
                                onIssue={canIssueInventory && canIssueLine(row.line) && !mutationDisabled ? () => onIssue(row.line) : undefined}
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

function QuantityControl({ line, disabled, onChange }: {
    line: VehicleServiceJobLine;
    disabled: boolean;
    onChange: (quantity: string) => void;
}) {
    const [draft, setDraft] = useState(() => formatQuantity(line.quantity, 6, 1));

    const commit = () => {
        if (compareDecimalStrings(draft, MINIMUM_QUANTITY) < 0) {
            setDraft(formatQuantity(line.quantity, 6, 1));
            return;
        }
        if (compareDecimalStrings(draft, line.quantity) !== 0) onChange(draft);
    };
    const decrease = () => {
        const next = subtractDecimal(line.quantity, DEFAULT_QUANTITY);
        if (compareDecimalStrings(next, MINIMUM_QUANTITY) < 0) return;
        setDraft(formatQuantity(next, 6, 1));
        onChange(next);
    };
    const increase = () => {
        const next = addDecimal(line.quantity, DEFAULT_QUANTITY);
        setDraft(formatQuantity(next, 6, 1));
        onChange(next);
    };

    return (
        <div className="flex items-center gap-1.5">
            <button
                type="button"
                className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 text-lg font-semibold text-sky-700 transition hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={disabled || compareDecimalStrings(line.quantity, DEFAULT_QUANTITY) <= 0}
                aria-label={`Decrease quantity for ${formatLineItem(line)}`}
                onClick={decrease}
            >
                −
            </button>
            <input
                type="text"
                inputMode="decimal"
                className="h-9 w-20 rounded-lg border border-slate-300 bg-white px-2 text-center tabular-nums outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100 disabled:bg-slate-100"
                value={draft}
                disabled={disabled}
                aria-label={`Quantity for ${formatLineItem(line)}`}
                onChange={(event) => setDraft(event.target.value.replace(/[^\d.]/g, ''))}
                onBlur={commit}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        commit();
                    }
                    if (event.key === 'Escape') setDraft(formatQuantity(line.quantity, 6, 1));
                }}
            />
            <button
                type="button"
                className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-100 text-lg font-semibold text-sky-700 transition hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-50"
                disabled={disabled}
                aria-label={`Increase quantity for ${formatLineItem(line)}`}
                onClick={increase}
            >
                +
            </button>
            <span className="ml-1 text-xs font-medium text-slate-500">{line.uom?.code ?? ''}</span>
        </div>
    );
}

function LineMobileDetails({
    row,
    showStock,
    showPricing,
    quantityControl,
}: {
    row: VehicleServiceLineDisplayRow;
    showStock: boolean;
    showPricing: boolean;
    quantityControl?: ReactNode;
}) {
    const { line } = row;
    return (
        <div className={`grid grid-cols-2 gap-2 ${row.isComboChild ? CHILD_LINE_INDENT_CLASS : ''}`}>
            <SummaryValue
                label="Quantity"
                value={quantityControl ?? <QuantityDisplay value={line.quantity} precision={6} minimumPrecision={1} />}
            />
            <SummaryValue label="UOM" value={line.uom?.code ?? '-'} />
            {showStock && <SummaryValue label="Stock" value={stockStateLabel(line)} />}
            {showPricing && <SummaryValue label="Price" value={row.isComboChild && !line.is_billable ? 'Included in pack' : line.unit_price} />}
            {showPricing && <SummaryValue label="Discount" value={line.discount_amount} />}
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
    showStock: boolean,
) {
    if (row.isComboParent) {
        return <ComboDisclosure row={row} expanded={expanded} onToggle={onToggle} showDescription />;
    }

    if (row.isComboChild) {
        return (
            <div className={`${CHILD_LINE_INDENT_CLASS} relative space-y-1`}>
                <span className="pointer-events-none absolute left-2 top-1 h-5 w-4 border-b border-l border-slate-300" aria-hidden="true" />
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-slate-700">{lineItemName(row.line)}</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-600">
                        Included
                    </span>
                </div>
                {lineItemMetadata(row.line) && (
                    <p className="text-xs text-slate-500">{lineItemMetadata(row.line)}</p>
                )}
                <p className="text-xs text-slate-500">
                    Under {formatLineItem(row.parent ?? row.line)}
                </p>
                {showStock && row.line.is_inventory_tracked && (
                    <p className="text-xs text-slate-500">{stockStateLabel(row.line)}</p>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-1">
            <span className="font-medium text-slate-900">{lineItemName(row.line)}</span>
            {lineItemMetadata(row.line) && (
                <p className="text-xs text-slate-500">{lineItemMetadata(row.line)}</p>
            )}
            {showStock && row.line.is_inventory_tracked && (
                <p className="text-xs text-slate-500">{stockStateLabel(row.line)}</p>
            )}
        </div>
    );
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

    return <span className={row.isComboChild ? 'text-slate-500' : ''}><MoneyDisplay value={row.line.unit_price} /></span>;
}

function renderLineDiscount(row: VehicleServiceLineDisplayRow) {
    if (row.isComboChild && !row.line.is_billable) {
        return <span className="text-slate-400">-</span>;
    }

    return <span className={row.isComboChild ? 'text-slate-500' : ''}><MoneyDisplay value={row.line.discount_amount} /></span>;
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

    return <span className={row.isComboChild ? 'text-slate-600' : 'text-slate-900'}><MoneyDisplay value={row.line.line_total} /></span>;
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
            <div className={`${CHILD_LINE_INDENT_CLASS} relative space-y-1`}>
                <span className="pointer-events-none absolute left-2 top-1 h-5 w-4 border-b border-l border-slate-300" aria-hidden="true" />
                <span className="font-medium text-slate-700">{lineItemName(row.line)}</span>
                {lineItemMetadata(row.line) && (
                    <span className="block text-xs font-normal text-slate-500">{lineItemMetadata(row.line)}</span>
                )}
            </div>
        );
    }

    return (
        <div className="space-y-1">
            <span>{lineItemName(row.line)}</span>
            {lineItemMetadata(row.line) && (
                <span className="block text-xs font-normal text-slate-500">{lineItemMetadata(row.line)}</span>
            )}
        </div>
    );
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
    const itemName = lineItemName(row.line);
    const itemMetadata = lineItemMetadata(row.line);
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
                    <span className="font-semibold text-slate-900">{itemName}</span>
                    <span className="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                        Combo pack
                    </span>
                </span>
                {itemMetadata && (
                    <span className="block text-xs font-normal text-slate-500">{itemMetadata}</span>
                )}
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

function lineItemName(line: VehicleServiceJobLine): string {
    return line.item?.name?.trim() || line.description;
}

function lineItemMetadata(line: VehicleServiceJobLine): string {
    const details = [line.item?.code?.trim()].filter(Boolean) as string[];
    if (line.is_inventory_tracked) {
        const quantity = line.available_stock_quantity == null
            ? '-'
            : formatQuantity(line.available_stock_quantity, 6, 1);
        const uom = line.uom?.code ?? line.uom?.name ?? '';
        details.push(`In stock: ${quantity}${uom ? ` ${uom}` : ''}`);
    }

    return details.join(' | ');
}

function stockStateLabel(line: VehicleServiceJobLine): string {
    if (!line.is_inventory_tracked || line.is_customer_supplied) return '-';
    if (line.inventory_movement?.status === 'reversed') return 'Returned';
    return line.inventory_movement_id == null ? 'Pending issue' : 'Issued';
}
