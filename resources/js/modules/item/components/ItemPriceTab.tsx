import { useState } from 'react';
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
import type { NamedResource } from '@/shared/types/common';
import { createItemPrice, listItemPrices, supersedeItemPrice } from '../itemApi';
import { itemPriceTypes, type ItemPrice, type ItemPricePayload, type ItemVariant } from '../itemTypes';
import { ItemCurrencySelect } from './ItemCurrencySelect';
import { ItemRelationHeader } from './ItemRelationHeader';
import { ItemUomSelect } from './ItemUomSelect';
import { ItemVariantSelect } from './ItemVariantSelect';
import { useItemRelationCrud } from './useItemRelationCrud';

const list = (itemId: number, page: number, signal: AbortSignal) => listItemPrices(itemId, { page, per_page: 20 }, signal);

export default function ItemPriceTab({ itemId, readOnly = false }: { itemId: number; readOnly?: boolean }) {
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
                error={crud.actionError}
                submitting={crud.submitting}
                onCancel={crud.close}
                onSubmit={crud.submit}
            />}
        </FormDrawer>}
        {!readOnly && crud.confirmDialog}
    </>;
}

function PriceForm({ itemId, row, error, submitting, onCancel, onSubmit }: {
    itemId: number;
    row: ItemPrice | null;
    error: ApiError | null;
    submitting: boolean;
    onCancel: () => void;
    onSubmit: (payload: ItemPricePayload) => Promise<void>;
}) {
    const [variant, setVariant] = useState<ItemVariant | null>(row?.variant ? { id: Number(row.variant.id), code: row.variant.code ?? '', name: row.variant.name, is_active: true } : null);
    const [currency, setCurrency] = useState<NamedResource | null>(row?.currency ?? null);
    const [uom, setUom] = useState<NamedResource | null>(row?.uom ?? null);
    const [form, setForm] = useState<ItemPricePayload>({
        item_variant_id: row?.variant ? Number(row.variant.id) : null,
        price_type: row?.price_type ?? 'sales',
        currency_id: row?.currency ? Number(row.currency.id) : 0,
        uom_id: row?.uom ? Number(row.uom.id) : 0,
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
