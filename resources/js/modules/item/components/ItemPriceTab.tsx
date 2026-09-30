import { useCallback, useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { createItemPrice, listItemPrices, supersedeItemPrice } from '../itemApi';
import { itemPriceTypes, type ItemPrice, type ItemPricePayload, type ItemVariant } from '../itemTypes';
import { ItemCurrencySelect } from './ItemCurrencySelect';
import { ItemRelationHeader } from './ItemRelationHeader';
import { ItemUomSelect } from './ItemUomSelect';
import { ItemVariantSelect } from './ItemVariantSelect';
import { useItemRelationCrud } from './useItemRelationCrud';
import { createBatchPrice, listBatchPrices, searchInventoryBatches, supersedeBatchPrice } from '@/modules/inventory/inventoryApi';
import type { InventoryBatchPrice, InventoryBatchPricePayload } from '@/modules/inventory/inventoryTypes';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemPrices(itemId, { page, per_page: 20 }, signal);

export default function ItemPriceTab({ itemId, trackingType = 'none', defaultCurrency = null, defaultUom = null, readOnly = false, canViewBatchPrices = false, canManageBatchPrices = false }: {
    itemId: number;
    trackingType?: string;
    defaultCurrency?: NamedResource | null;
    defaultUom?: NamedResource | null;
    readOnly?: boolean;
    canViewBatchPrices?: boolean;
    canManageBatchPrices?: boolean;
}) {
    const crud = useItemRelationCrud({ itemId, list, create: createItemPrice, update: supersedeItemPrice });
    const columns: DataColumn<ItemPrice>[] = [
        { key: 'scope', header: 'Scope', render: (row) => row.organization_unit ? `Organization Unit: ${row.organization_unit.name}` : 'Tenant default' },
        { key: 'type', header: 'Price type', render: (row) => row.price_type },
        { key: 'amount', header: 'Amount', render: (row) => `${row.currency?.code ?? ''} ${row.amount}`.trim() },
        { key: 'uom', header: 'UOM', render: (row) => row.uom ? `${row.uom.code ?? ''} - ${row.uom.name}` : '-' },
        { key: 'effective', header: 'Effective', render: (row) => `${row.effective_from} to ${row.effective_to ?? 'Open'}` },
        { key: 'revision', header: 'Revision', render: (row) => `#${row.revision_no}` },
        { key: 'status', header: 'Record', render: (row) => <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${row.is_current_revision ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>{row.is_current_revision ? 'Current' : 'Historical'}</span> },
    ];
    if (!readOnly) {
        columns.push({
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => row.is_current_revision
                ? <button type="button" className="font-semibold text-sky-700" onClick={() => crud.startEdit(row)}>Supersede</button>
                : null,
        });
    }

    return <>
        <ItemRelationHeader
            title="Price revisions"
            description="Maintain immutable, effective-dated purchase, sales, service, and rental prices. Corrections supersede the current revision and preserve history."
            onAdd={readOnly ? undefined : crud.startCreate}
        />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable
            rows={crud.data?.data ?? []}
            columns={columns}
            rowKey={(row) => row.id}
            mobileSummary={(row) => `${row.price_type}: ${row.currency?.code ?? ''} ${row.amount}`.trim()}
            mobileDetails={(row) => <div className="grid grid-cols-2 gap-2 text-sm"><span>Scope: {row.organization_unit?.name ?? 'Tenant default'}</span><span>UOM: {row.uom?.code ?? '-'}</span><span>Revision #{row.revision_no}</span></div>}
        />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {!readOnly && <FormDrawer open={crud.open} title={crud.editing ? 'Supersede price revision' : 'Add price revision'} onClose={crud.close}>
            {crud.open && <PriceForm
                key={crud.editing?.id ?? 'new'}
                itemId={itemId}
                row={crud.editing}
                defaultCurrency={defaultCurrency}
                defaultUom={defaultUom}
                error={crud.actionError}
                submitting={crud.submitting}
                onCancel={crud.close}
                onSubmit={crud.submit}
            />}
        </FormDrawer>}
        {!readOnly && crud.confirmDialog}
        {canViewBatchPrices && ['batch', 'lot'].includes(trackingType) && <BatchPriceSection itemId={itemId} defaultCurrency={defaultCurrency} defaultUom={defaultUom} readOnly={!canManageBatchPrices} />}
    </>;
}

function PriceForm({ itemId, row, defaultCurrency, defaultUom, error, submitting, onCancel, onSubmit }: {
    itemId: number;
    row: ItemPrice | null;
    defaultCurrency: NamedResource | null;
    defaultUom: NamedResource | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemPricePayload) => Promise<void>;
}) {
    const [variant, setVariant] = useState<ItemVariant | null>(row?.variant ? { id: Number(row.variant.id), code: row.variant.code ?? '', name: row.variant.name, is_active: true } : null);
    const [currency, setCurrency] = useState<NamedResource | null>(row?.currency ?? defaultCurrency);
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? defaultUom);
    const [form, setForm] = useState<ItemPricePayload>({
        item_variant_id: row?.variant ? Number(row.variant.id) : null,
        price_type: row?.price_type ?? 'sales',
        currency_id: Number(row?.currency?.id ?? defaultCurrency?.id ?? 0),
        uom_id: Number(row?.uom?.id ?? defaultUom?.id ?? 0),
        amount: row?.amount ?? '0.000000',
        effective_from: row?.effective_from ?? '',
        effective_to: row?.effective_to ?? null,
        expected_version: row?.row_version,
        correction_reason: row ? '' : undefined,
    });

    return <form className="space-y-4" onSubmit={(event) => {
        event.preventDefault();
        if (!currency || !uom) return;
        void onSubmit({
            ...form,
            item_variant_id: variant ? Number(variant.id) : null,
            currency_id: Number(currency.id),
            uom_id: Number(uom.id),
            expected_version: row?.row_version,
            correction_reason: row ? form.correction_reason?.trim() : undefined,
        });
    }}>
        <ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2">
            <ItemVariantSelect itemId={itemId} value={variant} onChange={setVariant} error={fieldError(error, 'item_variant_id')} />
            <Select label="Price type" value={form.price_type} onChange={(event) => setForm({ ...form, price_type: event.target.value })} options={itemPriceTypes.map((value) => ({ value, label: value }))} error={fieldError(error, 'price_type')} />
            <DecimalInput label="Amount" value={form.amount} onChange={(event) => setForm({ ...form, amount: event.target.value })} error={fieldError(error, 'amount')} required />
            <ItemCurrencySelect value={currency} onChange={setCurrency} error={fieldError(error, 'currency_id')} />
            <ItemUomSelect value={uom} onChange={setUom} error={fieldError(error, 'uom_id')} />
            <Input label="Effective from" type="date" value={form.effective_from} onChange={(event) => setForm({ ...form, effective_from: event.target.value })} error={fieldError(error, 'effective_from')} required />
            <Input label="Effective to" type="date" value={form.effective_to ?? ''} onChange={(event) => setForm({ ...form, effective_to: event.target.value || null })} error={fieldError(error, 'effective_to')} />
        </div>
        {row && <div>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="price-correction-reason">Correction reason</label>
            <textarea
                id="price-correction-reason"
                className="min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                value={form.correction_reason ?? ''}
                onChange={(event) => setForm({ ...form, correction_reason: event.target.value })}
                required
            />
            {fieldError(error, 'correction_reason') && <p className="mt-1 text-sm text-rose-600">{fieldError(error, 'correction_reason')}</p>}
        </div>}
        <div className="flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
            <Button type="submit" loading={submitting} disabled={!currency || !uom || !form.effective_from || Boolean(row && !form.correction_reason?.trim())}>{row ? 'Supersede' : 'Add revision'}</Button>
        </div>
    </form>;
}

const batchPriceList = (itemId: number, page: number, signal: AbortSignal) =>
    listBatchPrices({ item_id: itemId, page, per_page: 20 }, signal);
const batchPriceCreate = (_itemId: number, payload: InventoryBatchPricePayload) => createBatchPrice(payload);
const batchPriceUpdate = (_itemId: number, id: number, payload: InventoryBatchPricePayload) => supersedeBatchPrice(id, payload);

function BatchPriceSection({ itemId, defaultCurrency, defaultUom, readOnly }: {
    itemId: number;
    defaultCurrency: NamedResource | null;
    defaultUom: NamedResource | null;
    readOnly: boolean;
}) {
    const crud = useItemRelationCrud({ itemId, list: batchPriceList, create: batchPriceCreate, update: batchPriceUpdate });
    const columns: DataColumn<InventoryBatchPrice>[] = [
        { key: 'batch', header: 'Batch / lot', render: (row) => `${row.batch.code ?? ''}${row.batch.name && row.batch.name !== row.batch.code ? ` / ${row.batch.name}` : ''}` },
        { key: 'type', header: 'Price type', render: (row) => row.price_type },
        { key: 'amount', header: 'Amount', render: (row) => `${row.currency?.code ?? ''} ${row.amount}`.trim() },
        { key: 'uom', header: 'UOM', render: (row) => row.uom?.code ?? '-' },
        { key: 'effective', header: 'Effective', render: (row) => `${row.effective_from} to ${row.effective_to ?? 'Open'}` },
        { key: 'revision', header: 'Revision', render: (row) => `#${row.revision_no}` },
    ];
    if (!readOnly) {
        columns.push({
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => row.is_current_revision
                ? <button type="button" className="font-semibold text-sky-700" onClick={() => crud.startEdit(row)}>Supersede</button>
                : null,
        });
    }

    return <div className="mt-8 border-t border-slate-200 pt-6">
        <ItemRelationHeader
            title="Batch / lot price revisions"
            description="Set independent sales or service prices for each stocked batch while preserving historical prices."
            onAdd={readOnly ? undefined : crud.startCreate}
        />
        <ErrorAlert error={crud.actionError ?? crud.error} />
        {crud.loading ? <LoadingState /> : <DataTable rows={crud.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No batch-specific prices have been recorded." />}
        <Pagination meta={crud.data?.meta} onPageChange={crud.setPage} />
        {!readOnly && <FormDrawer open={crud.open} title={crud.editing ? 'Supersede batch price' : 'Add batch price'} onClose={crud.close}>
            {crud.open && <BatchPriceForm itemId={itemId} row={crud.editing} defaultCurrency={defaultCurrency} defaultUom={defaultUom} error={crud.actionError} submitting={crud.submitting} onCancel={crud.close} onSubmit={crud.submit} />}
        </FormDrawer>}
        {!readOnly && crud.confirmDialog}
    </div>;
}

function BatchPriceForm({ itemId, row, defaultCurrency, defaultUom, error, submitting, onCancel, onSubmit }: {
    itemId: number;
    row: InventoryBatchPrice | null;
    defaultCurrency: NamedResource | null;
    defaultUom: NamedResource | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: InventoryBatchPricePayload) => Promise<void>;
}) {
    const [batch, setBatch] = useState<NamedResource | null>(row?.batch ?? null);
    const [currency, setCurrency] = useState<NamedResource | null>(row?.currency ?? defaultCurrency);
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? defaultUom);
    const [form, setForm] = useState<InventoryBatchPricePayload>({
        batch_id: row?.batch.id ?? 0,
        price_type: row?.price_type ?? 'service',
        currency_id: row?.currency.id ?? defaultCurrency?.id ?? 0,
        uom_id: row?.uom.id ?? defaultUom?.id ?? 0,
        amount: row?.amount ?? '0.000000',
        effective_from: row?.effective_from ?? '',
        effective_to: row?.effective_to ?? null,
        expected_version: row?.row_version,
        correction_reason: row ? '' : undefined,
    });
    const batchSearch = useCallback((params: Parameters<typeof searchInventoryBatches>[0]) => searchInventoryBatches(params, { itemId }), [itemId]);

    return <form className="space-y-4" onSubmit={(event) => {
        event.preventDefault();
        if (!batch || !currency || !uom) return;
        void onSubmit({
            ...form,
            batch_id: batch.id,
            currency_id: currency.id,
            uom_id: uom.id,
            expected_version: row?.row_version,
            correction_reason: row ? form.correction_reason?.trim() : undefined,
        });
    }}>
        <ErrorAlert error={error} />
        <div className="grid gap-4 sm:grid-cols-2">
            <LookupSelect label="Batch / lot" value={batch} onChange={setBatch} search={batchSearch} disabled={Boolean(row)} error={fieldError(error, 'batch_id')} loadOnOpen minSearchLength={0} required />
            <Select label="Price type" value={form.price_type} onChange={(event) => setForm({ ...form, price_type: event.target.value as 'sales' | 'service' })} options={[{ value: 'service', label: 'Service' }, { value: 'sales', label: 'Sales' }]} error={fieldError(error, 'price_type')} />
            <DecimalInput label="Amount" value={form.amount} onChange={(event) => setForm({ ...form, amount: event.target.value })} error={fieldError(error, 'amount')} required />
            <ItemCurrencySelect value={currency} onChange={setCurrency} error={fieldError(error, 'currency_id')} />
            <ItemUomSelect value={uom} onChange={setUom} error={fieldError(error, 'uom_id')} />
            <Input label="Effective from" type="date" value={form.effective_from} onChange={(event) => setForm({ ...form, effective_from: event.target.value })} error={fieldError(error, 'effective_from')} required />
            <Input label="Effective to" type="date" value={form.effective_to ?? ''} onChange={(event) => setForm({ ...form, effective_to: event.target.value || null })} error={fieldError(error, 'effective_to')} />
        </div>
        {row && <div>
            <label className="mb-1 block text-sm font-medium text-slate-700" htmlFor="batch-price-correction-reason">Correction reason</label>
            <textarea id="batch-price-correction-reason" className="min-h-24 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value={form.correction_reason ?? ''} onChange={(event) => setForm({ ...form, correction_reason: event.target.value })} required />
            {fieldError(error, 'correction_reason') && <p className="mt-1 text-sm text-rose-600">{fieldError(error, 'correction_reason')}</p>}
        </div>}
        <div className="flex justify-end gap-2">
            <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
            <Button type="submit" loading={submitting} disabled={!batch || !currency || !uom || !form.effective_from || Boolean(row && !form.correction_reason?.trim())}>{row ? 'Supersede' : 'Add batch price'}</Button>
        </div>
    </form>;
}
