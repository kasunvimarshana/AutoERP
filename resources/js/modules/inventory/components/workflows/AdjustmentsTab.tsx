import { useCallback, useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { getDefaultWarehouse, getDefaultWarehouseLocation } from '@/modules/warehouse/warehouseApi';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Modal } from '@/shared/components/Modal';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { compactObject } from '@/shared/utils/object';
import { formatDate } from '@/shared/utils/formatDate';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { createAdjustment, postAdjustment } from '../../inventoryApi';
import type { AdjustmentPayload, InventoryRecord } from '../../inventoryTypes';
import { emptyInventoryDimensions, InventoryDimensionFields } from '../InventoryDimensionFields';
import { OpeningStockImportPanel } from './OpeningStockImportPanel';
import {
    label,
    localToday,
    quantity,
    RecordList,
    relation,
    runFormAction,
    subtractDecimals,
    useRecordAction,
    type WorkflowProps,
    WorkflowPanel,
} from '../inventoryUi';

export function AdjustmentsTab({ data, loading, error, reload, canManage, canPost }: WorkflowProps & { canManage: boolean; canPost: boolean }) {
    const [viewingAdjustment, setViewingAdjustment] = useState<InventoryRecord | null>(null);
    const [form, setForm] = useState({
        adjustment_date: localToday(),
        adjustment_type: 'recount',
        system_quantity: '0.000000',
        counted_quantity: '0.000000',
        unit_cost: '0.000000',
        reason: '',
    });
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [dimensions, setDimensions] = useState(emptyInventoryDimensions);
    const warehouseTouched = useRef(false);
    const locationTouched = useRef(false);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const recordAction = useRecordAction(reload, setActionError);
    const locationSearch = useCallback(
        (params: LookupLoadParams) => searchWarehouseLocations(params, warehouse?.id),
        [warehouse?.id],
    );
    useEffect(() => {
        if (warehouse !== null || warehouseTouched.current) return;

        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then((defaultWarehouse) => {
                if (!controller.signal.aborted && !warehouseTouched.current && defaultWarehouse) {
                    setWarehouse(defaultWarehouse);
                }
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setActionError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [warehouse]);
    useEffect(() => {
        if (!warehouse || dimensions.warehouseLocation || locationTouched.current) return;

        const controller = new AbortController();
        void getDefaultWarehouseLocation(warehouse.id, controller.signal)
            .then((defaultLocation) => {
                if (controller.signal.aborted || locationTouched.current || !defaultLocation) return;
                setDimensions((current) => ({ ...current, warehouseLocation: defaultLocation }));
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setActionError(toApiError(requestError));
            });

        return () => controller.abort();
    }, [warehouse, dimensions.warehouseLocation]);
    const submit = (event: FormEvent) => void runFormAction(event, setBusy, setActionError, async () => {
        await createAdjustment(compactObject({
            adjustment_date: form.adjustment_date,
            adjustment_type: form.adjustment_type,
            warehouse_id: warehouse?.id ?? 0,
            warehouse_location_id: dimensions.warehouseLocation?.id,
            reason: form.reason,
            lines: [{
                item_id: item?.id ?? 0,
                system_quantity: form.system_quantity,
                counted_quantity: form.counted_quantity,
                adjustment_quantity: subtractDecimals(form.counted_quantity, form.system_quantity),
                unit_cost: form.unit_cost,
                item_variant_id: dimensions.itemVariant?.id,
                batch_id: dimensions.batch?.id,
                serial_number_id: dimensions.serial?.id,
                uom_id: dimensions.uom?.id,
                reason: form.reason,
            }],
        }) as AdjustmentPayload);
        reload();
    });

    return (
        <WorkflowPanel title="Stock adjustment workflow" loading={loading} error={error} actionError={actionError}>
            {canManage && (
                <div className="space-y-4">
                    <OpeningStockImportPanel reload={reload} />
                    <form className="space-y-4" onSubmit={submit}>
                        <div className="grid gap-4 md:grid-cols-3">
                            <LookupSelect label="Item" value={item} onChange={(value) => { setItem(value); setDimensions(emptyInventoryDimensions()); }} search={lookupApi.stockableItems} error={fieldError(actionError, 'lines.0.item_id')} />
                            <LookupSelect label="Warehouse" value={warehouse} onChange={(value) => { warehouseTouched.current = true; locationTouched.current = false; setActionError(null); setWarehouse(value); setDimensions((current) => ({ ...current, warehouseLocation: null, serial: null })); }} search={searchWarehouses} error={fieldError(actionError, 'warehouse_id')} loadOnOpen minSearchLength={0} />
                            <LookupSelect
                                label="Location"
                                value={dimensions.warehouseLocation}
                                onChange={(warehouseLocation) => { locationTouched.current = true; setActionError(null); setDimensions((current) => ({ ...current, warehouseLocation, serial: null })); }}
                                search={locationSearch}
                                placeholder="Search locations..."
                                error={fieldError(actionError, 'warehouse_location_id')}
                                disabled={!warehouse}
                                loadOnOpen
                                minSearchLength={0}
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1.5fr_1fr_1fr_auto]">
                            <Select
                                label="Type"
                                value={form.adjustment_type}
                                options={['recount', 'increase', 'decrease', 'damage', 'expiry', 'opening_balance'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                                onChange={(event) => setForm({ ...form, adjustment_type: event.target.value })}
                            />
                            <DecimalInput label="System (base)" value={form.system_quantity} error={fieldError(actionError, 'lines.0.system_quantity')} onChange={(event) => setForm({ ...form, system_quantity: event.target.value })} />
                            <DecimalInput label="Counted (base)" value={form.counted_quantity} error={fieldError(actionError, 'lines.0.counted_quantity')} onChange={(event) => setForm({ ...form, counted_quantity: event.target.value })} />
                            <DecimalInput label="Cost/base" value={form.unit_cost} error={fieldError(actionError, 'lines.0.unit_cost')} onChange={(event) => setForm({ ...form, unit_cost: event.target.value })} />
                            <Input label="Reason" value={form.reason} error={fieldError(actionError, 'reason')} onChange={(event) => setForm({ ...form, reason: event.target.value })} />
                            <Input label="Date" type="date" value={form.adjustment_date} error={fieldError(actionError, 'adjustment_date')} onChange={(event) => setForm({ ...form, adjustment_date: event.target.value })} />
                            <div className="flex items-end"><Button type="submit" loading={busy} disabled={!item || !warehouse}>Create</Button></div>
                        </div>
                        <InventoryDimensionFields
                            item={item}
                            warehouse={warehouse}
                            value={dimensions}
                            onChange={setDimensions}
                            includeLocation={false}
                            includeSerial
                            errors={{
                                itemVariant: fieldError(actionError, 'lines.0.item_variant_id'),
                                batch: fieldError(actionError, 'lines.0.batch_id'),
                                serial: fieldError(actionError, 'lines.0.serial_number_id'),
                                uom: fieldError(actionError, 'lines.0.uom_id'),
                            }}
                        />
                    </form>
                </div>
            )}
            <RecordList rows={data} columns={columns((row) => (
                <div className="flex gap-2">
                    <Button variant="secondary" onClick={() => setViewingAdjustment(row)}>View</Button>
                    {canPost && (
                        <Button
                            variant="secondary"
                            loading={recordAction.pendingKey === `post-adjustment:${row.id}`}
                            disabled={!['draft', 'approved'].includes(String(row.status ?? '')) || recordAction.pendingKey !== null}
                            onClick={() => void recordAction.run(`post-adjustment:${row.id}`, () => postAdjustment(row.id))}
                        >
                            Post
                        </Button>
                    )}
                </div>
            ))} />
            <AdjustmentDetails adjustment={viewingAdjustment} onClose={() => setViewingAdjustment(null)} />
        </WorkflowPanel>
    );
}

function AdjustmentDetails({ adjustment, onClose }: { adjustment: InventoryRecord | null; onClose: () => void }) {
    if (!adjustment) return null;

    const lines = Array.isArray(adjustment.lines) ? adjustment.lines as InventoryRecord[] : [];

    return (
        <Modal open title={`Adjustment ${label(adjustment, 'adjustment_number')}`} onClose={onClose}>
            <dl className="grid gap-4 sm:grid-cols-2">
                <Detail label="Type" value={String(adjustment.adjustment_type ?? '-').replaceAll('_', ' ')} />
                <Detail label="Status" value={<StatusBadge status={String(adjustment.status ?? '')} />} />
                <Detail label="Warehouse" value={relation(adjustment.warehouse)} />
                <Detail label="Location" value={relation(adjustment.warehouse_location)} />
                <Detail label="Date" value={formatDate(String(adjustment.adjustment_date ?? ''))} />
                <Detail label="Reason" value={String(adjustment.reason ?? '-')} />
            </dl>
            <div className="mt-6">
                <h3 className="mb-3 text-sm font-semibold text-slate-900">Item details</h3>
                <div className="overflow-x-auto rounded-lg border border-slate-200">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead className="bg-slate-50 text-xs text-slate-500">
                            <tr><th className="px-3 py-2">Item</th><th className="px-3 py-2">System stock</th><th className="px-3 py-2">Counted stock</th><th className="px-3 py-2">Stock change</th><th className="px-3 py-2">Reason</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {lines.map((line, index) => (
                                <tr key={Number(line.id ?? index)}>
                                    <td className="px-3 py-2 font-medium text-slate-800">{relation(line.item)}</td>
                                    <td className="px-3 py-2">{quantity(line.system_quantity)}</td>
                                    <td className="px-3 py-2">{quantity(line.counted_quantity)}</td>
                                    <td className="px-3 py-2"><StockChange system={line.system_quantity} counted={line.counted_quantity} /></td>
                                    <td className="px-3 py-2">{String(line.reason ?? '-')}</td>
                                </tr>
                            ))}
                            {lines.length === 0 && <tr><td className="px-3 py-4 text-slate-500" colSpan={5}>No item details available.</td></tr>}
                        </tbody>
                    </table>
                </div>
            </div>
        </Modal>
    );
}

function StockChange({ system, counted }: { system: unknown; counted: unknown }) {
    const difference = subtractDecimals(String(counted ?? '0'), String(system ?? '0'));
    if (/^-?0(?:\.0*)?$/.test(difference)) {
        return <span className="font-medium text-slate-500">No change</span>;
    }

    if (difference.startsWith('-')) {
        return <span className="font-semibold text-rose-700">↓ Decreased by {quantity(difference.slice(1))}</span>;
    }

    return <span className="font-semibold text-emerald-700">↑ Increased by {quantity(difference)}</span>;
}

function Detail({ label: detailLabel, value }: { label: string; value: ReactNode }) {
    return <div><dt className="text-xs font-medium text-slate-500">{detailLabel}</dt><dd className="mt-1 text-sm text-slate-900">{value}</dd></div>;
}

function columns(actions: (row: InventoryRecord) => ReactNode) {
    return [
        { key: 'number', header: 'Adjustment', render: (row: InventoryRecord) => label(row, 'adjustment_number') },
        { key: 'type', header: 'Type', render: (row: InventoryRecord) => String(row.adjustment_type ?? '-') },
        { key: 'warehouse', header: 'Warehouse', render: (row: InventoryRecord) => relation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row: InventoryRecord) => <StatusBadge status={String(row.status ?? '')} /> },
        { key: 'actions', header: '', render: actions },
    ];
}
