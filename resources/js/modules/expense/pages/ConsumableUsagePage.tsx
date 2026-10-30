import { useCallback, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { emptyInventoryDimensions, InventoryDimensionFields } from '@/modules/inventory/components/InventoryDimensionFields';
import { inventoryPermissions } from '@/modules/inventory/inventoryPermissions';
import { reportingPermissions } from '@/modules/reporting/reportingPermissions';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { getConsumableAvailability, listConsumableUsage, postConsumableUsage, reverseConsumableUsage, type ConsumableUsage } from '../consumableUsageApi';

export default function ConsumableUsagePage() {
    const auth = useAuth();
    const today = businessDateInputValue();
    const [item, setItem] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [dimensions, setDimensions] = useState(emptyInventoryDimensions);
    const [usageDate, setUsageDate] = useState(today);
    const [quantity, setQuantity] = useState('');
    const [notes, setNotes] = useState('');
    const [from, setFrom] = useState(`${today.slice(0, 7)}-01`);
    const [to, setTo] = useState(today);
    const [page, setPage] = useState(1);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [reverseTarget, setReverseTarget] = useState<ConsumableUsage | null>(null);
    const [reverseReason, setReverseReason] = useState('');
    const locationSearch = useCallback((params: LookupLoadParams) => searchWarehouseLocations(params, warehouse?.id), [warehouse?.id]);
    const history = useApi((signal) => listConsumableUsage({ from, to, page, per_page: 25 }, signal), [from, to, page], from !== '' && to !== '' && from <= to);
    const availability = useApi((signal) => getConsumableAvailability({
        item_id: item!.id,
        warehouse_id: warehouse!.id,
        warehouse_location_id: dimensions.warehouseLocation?.id,
        item_variant_id: dimensions.itemVariant?.id,
        batch_id: dimensions.batch?.id,
    }, signal), [item?.id, warehouse?.id, dimensions.warehouseLocation?.id, dimensions.itemVariant?.id, dimensions.batch?.id], item !== null && warehouse !== null);
    const canPost = hasPermission(auth, inventoryPermissions.consumableUsagePost);
    const canReverse = hasPermission(auth, inventoryPermissions.consumableUsageReverse);
    const canViewReport = hasPermission(auth, reportingPermissions.view) && auth.enabledModules?.includes('reporting');

    async function post(event: FormEvent) {
        event.preventDefault();
        if (!item || !warehouse || !isPositiveDecimal(quantity) || busy) return;
        setBusy(true);
        setError(null);
        setSuccess(null);
        try {
            const posted = await postConsumableUsage({
                usage_date: usageDate,
                item_id: item.id,
                warehouse_id: warehouse.id,
                warehouse_location_id: dimensions.warehouseLocation?.id,
                item_variant_id: dimensions.itemVariant?.id,
                batch_id: dimensions.batch?.id,
                uom_id: dimensions.uom?.id,
                quantity,
                notes: notes.trim() || undefined,
            });
            setSuccess(`${posted.number} posted. Stock and operating expense updated.`);
            setQuantity('');
            setNotes('');
            setPage(1);
            history.reload();
            availability.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    async function reverse() {
        if (!reverseTarget || !reverseReason.trim() || busy) return;
        setBusy(true);
        setError(null);
        setSuccess(null);
        try {
            await reverseConsumableUsage(reverseTarget.id, reverseReason.trim());
            setSuccess(`${reverseTarget.number} reversed. Stock and Finance were restored.`);
            setReverseTarget(null);
            setReverseReason('');
            history.reload();
            availability.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    const columns: DataColumn<ConsumableUsage>[] = [
        { key: 'date', header: 'Date', render: (row) => row.date },
        { key: 'item', header: 'Item', render: (row) => <div><strong>{row.item.name}</strong><div className="text-xs text-slate-500">{row.number}</div></div> },
        { key: 'place', header: 'Used from', render: (row) => `${row.warehouse.name}${row.location ? ` / ${row.location.name}` : ''}` },
        { key: 'quantity', header: 'Quantity', render: (row) => `${row.quantity} ${row.uom?.name ?? ''}` },
        { key: 'cost', header: 'Expense cost', render: (row) => row.total_cost },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'action', header: '', render: (row) => row.status === 'posted' && canReverse ? <Button variant="danger" onClick={() => { setReverseTarget(row); setReverseReason(''); }}>Reverse</Button> : null },
    ];

    return <>
        <ContentHeader title="Consumable Usage" description="Record the amount actually used. Posting reduces stock and recognizes its inventory cost as an operating expense." actions={canViewReport ? <LinkButton to="/reports/consumable-usage" variant="secondary">View usage report</LinkButton> : undefined} />
        <ErrorAlert error={error ?? history.error ?? availability.error} />
        <SuccessAlert message={success} inline onDismiss={() => setSuccess(null)} />

        {canPost && <Panel title="Record usage" className="mb-6">
            <form onSubmit={(event) => void post(event)} className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <LookupSelect label="Consumable item" value={item} onChange={(value) => { setItem(value); setDimensions(emptyInventoryDimensions()); }} search={lookupApi.consumableItems} required placeholder="Search shampoo, liquid, or another consumable" error={fieldError(error, 'item_id')} />
                    <LookupSelect label="Warehouse" value={warehouse} onChange={(value) => { setWarehouse(value); setDimensions((current) => ({ ...current, warehouseLocation: null, batch: null })); }} search={searchWarehouses} required loadOnOpen minSearchLength={0} error={fieldError(error, 'warehouse_id')} />
                    <LookupSelect label="Location" value={dimensions.warehouseLocation} onChange={(value) => setDimensions((current) => ({ ...current, warehouseLocation: value, batch: null }))} search={locationSearch} disabled={!warehouse} loadOnOpen minSearchLength={0} placeholder="Select if stock is stored by location" error={fieldError(error, 'warehouse_location_id')} />
                    <Input label="Usage date" type="date" max={today} value={usageDate} onChange={(event) => setUsageDate(event.target.value)} required error={fieldError(error, 'usage_date')} />
                    <DecimalInput label="Quantity used" value={quantity} onChange={(event) => setQuantity(event.target.value)} required error={fieldError(error, 'quantity')} hint="Enter the actual amount consumed." />
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 md:col-span-1 xl:col-span-2">
                        <strong className="block text-slate-900">Available stock in selected scope</strong>
                        {availability.loading ? 'Checking stock…' : availability.data ? `${availability.data.quantityAvailable} base units` : 'Select an item and warehouse.'}
                        <span className="mt-1 block text-xs text-slate-500">Select the location and batch when stock is tracked there. Posting checks the exact balance and values the expense.</span>
                    </div>
                </div>
                <InventoryDimensionFields item={item} warehouse={warehouse} value={dimensions} onChange={setDimensions} includeLocation={false} includeSerial={false} errors={{ itemVariant: fieldError(error, 'item_variant_id'), batch: fieldError(error, 'batch_id'), uom: fieldError(error, 'uom_id') }} />
                <Textarea label="Notes (optional)" value={notes} onChange={(event) => setNotes(event.target.value)} rows={2} error={fieldError(error, 'notes')} />
                <Button type="submit" loading={busy} disabled={!item || !warehouse || !isPositiveDecimal(quantity) || !usageDate}>Post usage</Button>
            </form>
        </Panel>}

        <Panel title="Usage history and cost">
            <div className="mb-4 grid gap-3 sm:grid-cols-2 max-w-xl">
                <Input label="From" type="date" value={from} onChange={(event) => { setFrom(event.target.value); setPage(1); }} />
                <Input label="To" type="date" value={to} onChange={(event) => { setTo(event.target.value); setPage(1); }} error={from > to ? 'To must be on or after From.' : undefined} />
            </div>
            <p className="mb-3 text-sm text-slate-600">Totals include posted usage in the selected period. Reversed entries remain in history.</p>
            {history.data?.summary.items.length ? <div className="mb-5 overflow-x-auto rounded-lg bg-blue-50 p-4"><table className="w-full text-sm"><thead><tr className="text-left text-slate-600"><th className="pb-2">Item</th><th className="pb-2">Used quantity</th><th className="pb-2">Expense cost (base currency)</th></tr></thead><tbody>{history.data.summary.items.map((row) => <tr key={row.item.id} className="border-t border-blue-100"><td className="py-2 font-medium">{row.item.name}</td><td>{row.quantity} {row.uom ?? ''}</td><td>{row.cost}</td></tr>)}</tbody></table></div> : null}
            {history.loading ? <LoadingState /> : <DataTable rows={history.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No consumable usage in this period." />}
            <Pagination meta={history.data?.meta} onPageChange={setPage} />
        </Panel>

        <Modal open={reverseTarget !== null} title={`Reverse ${reverseTarget?.number ?? 'usage'}`} onClose={() => setReverseTarget(null)} closeDisabled={busy}>
            <p className="text-sm text-slate-600">This restores the issued stock and reverses its operating expense. The original entry remains in history.</p>
            <div className="mt-4"><Textarea label="Reason" value={reverseReason} onChange={(event) => setReverseReason(event.target.value)} required rows={3} /></div>
            <div className="mt-5 flex justify-end gap-2"><Button variant="secondary" onClick={() => setReverseTarget(null)} disabled={busy}>Cancel</Button><Button variant="danger" onClick={() => void reverse()} disabled={!reverseReason.trim()} loading={busy}>Reverse usage</Button></div>
        </Modal>
    </>;
}
