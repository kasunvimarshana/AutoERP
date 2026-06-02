import { FormEvent, MouseEvent, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { itemUomApi } from '../../../services/api/itemUomApi';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { ConfirmDialog } from '../../../shared/components/ui/ConfirmDialog';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { pricingApi } from '../services/pricingApi';
import type {
    Discount,
    DiscountInput,
    DiscountRule,
    LookupOption,
    PriceBreakdown,
    PriceHistory,
    PriceList,
    PriceListFormInput,
    PriceListItem,
    PriceListItemInput,
    PriceListType,
    PriceResolveRequest,
    PriceResolveResult,
    PricingAuditEntry,
    PricingModuleScope,
    PricingRule,
    PricingRuleCondition,
    PricingRuleFormInput,
    PricingTier,
    PricingTierInput,
    PricingUsageSummary,
} from '../types/pricing.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checked(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

function fieldValue(formData: FormData, name: string) {
    return String(formData.get(name) ?? '');
}

const priceListTypeOptions = [
    { label: 'Sales', value: 'sales' },
    { label: 'Purchase', value: 'purchase' },
    { label: 'Customer-specific', value: 'customer' },
    { label: 'Supplier-specific', value: 'supplier' },
    { label: 'Vehicle service', value: 'service' },
    { label: 'Vehicle rental', value: 'rental' },
];

const moduleOptions: Array<{ label: string; name: PricingModuleScope }> = [
    { label: 'Sales', name: 'sales' },
    { label: 'Purchase', name: 'purchase' },
    { label: 'Vehicle Service', name: 'vehicle_service' },
    { label: 'Vehicle Rental', name: 'vehicle_rental' },
];

type FormErrorState = {
    errors: Record<string, string[]>;
    message: string;
};

function errorState(error: unknown, fallback: string): FormErrorState {
    if (error instanceof ApiError) {
        return { errors: error.errors, message: error.message };
    }

    return { errors: {}, message: fallback };
}

function lookupOptions(rows: LookupOption[]) {
    return rows.map((row) => ({ label: row.label, value: row.id }));
}

function useItemUomLookup(context: string, initialItemId = '', initialUomId = '') {
    const [itemId, setItemId] = useState(initialItemId);
    const [uomId, setUomId] = useState(initialUomId);
    const [uoms, setUoms] = useState<LookupOption[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [message, setMessage] = useState('');

    useEffect(() => {
        setItemId(initialItemId);
        setUomId(initialUomId);
    }, [initialItemId, initialUomId]);

    function selectItem(nextItemId: string): void {
        setItemId(nextItemId);
        setUomId('');
        setUoms([]);
        setMessage('');
    }

    useEffect(() => {
        let mounted = true;

        if (!itemId) {
            setUoms([]);
            setMessage('');
            return () => { mounted = false; };
        }

        setIsLoading(true);
        itemUomApi.listForItem(itemId, context)
            .then((options) => {
                if (!mounted) return;

                const rows = options.map((option) => ({
                    id: option.id,
                    label: option.label,
                    name: option.name || option.label,
                    secondary: option.name && option.name !== option.label ? option.name : option.category,
                }));
                const defaultOption = options.find((option) => option.isDefaultForContext) ?? options[0];
                const existingStillValid = initialUomId && rows.some((row) => row.id === initialUomId);

                setUoms(rows);
                setUomId(existingStillValid ? initialUomId : (defaultOption?.id ?? ''));
                setMessage(rows.length === 0 ? 'No UOM configured for this item.' : '');
            })
            .catch((error: unknown) => {
                if (!mounted) return;
                setUoms([]);
                setUomId('');
                setMessage(error instanceof Error ? error.message : 'Unable to load UOMs for this item.');
            })
            .finally(() => {
                if (mounted) setIsLoading(false);
            });

        return () => { mounted = false; };
    }, [context, initialUomId, itemId]);

    return {
        hint: !itemId ? 'Select an item first.' : isLoading ? 'Loading UOMs for selected item...' : message,
        isLoading,
        itemId,
        selectItem,
        setUomId,
        uomId,
        uoms,
    };
}

export function PriceListSummaryCard({ onStatusChange, priceList }: { onStatusChange?: (priceList: PriceList) => void; priceList: PriceList }) {
    const [confirm, setConfirm] = useState<'activate' | 'deactivate' | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    async function applyStatus() {
        if (!confirm) return;
        setIsSaving(true);
        try {
            const response = confirm === 'activate'
                ? await pricingApi.activatePriceList(priceList.id)
                : await pricingApi.deactivatePriceList(priceList.id);
            onStatusChange?.(response.data);
        } finally {
            setConfirm(null);
            setIsSaving(false);
        }
    }

    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{priceList.code}</p>
                        <StatusBadge status={priceList.status} />
                        <StatusBadge status={priceList.type} />
                    </div>
                    <h2 className="mt-2 text-xl font-bold text-slate-950">{priceList.name}</h2>
                    <p className="mt-1 max-w-3xl text-sm text-slate-500">{priceList.description || 'No description provided.'}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Currency</p><p className="mt-1 font-semibold text-slate-800">{priceList.currency || 'Default'}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Priority</p><p className="mt-1 font-semibold text-slate-800">{priceList.priority}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Valid</p><p className="mt-1 font-semibold text-slate-800">{priceList.validFrom || 'Any'} to {priceList.validTo || 'Open'}</p></div>
                </div>
            </div>
            <div className="mt-4 flex justify-end gap-2">
                {priceList.status === 'active'
                    ? <Button disabled={isSaving} onClick={() => setConfirm('deactivate')} variant="secondary">Deactivate</Button>
                    : <Button disabled={isSaving} onClick={() => setConfirm('activate')}>Activate</Button>}
            </div>
            <ConfirmDialog
                message={`This will ${confirm ?? 'update'} the price list status in the backend.`}
                onCancel={() => setConfirm(null)}
                onConfirm={applyStatus}
                open={confirm !== null}
                title="Confirm price list status"
            />
        </Card>
    );
}

function PriceListItemEditor({ existing, onSaved, priceListId }: { existing?: PriceListItem; onSaved: (item: PriceListItem) => void; priceListId: string }) {
    const [items, setItems] = useState<LookupOption[]>([]);
    const itemUoms = useItemUomLookup('pricing', existing?.itemId, existing?.uomId);
    const [state, setState] = useState<FormErrorState>({ errors: {}, message: '' });
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        let mounted = true;
        pricingApi.listItems().then((itemResponse) => {
            if (mounted) {
                setItems(itemResponse.data);
            }
        }).catch((error: unknown) => {
            if (mounted) setState(errorState(error, 'Unable to load item/UOM lookups.'));
        });
        return () => { mounted = false; };
    }, []);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const input: PriceListItemInput = {
            discountType: fieldValue(formData, 'discount_type') as PriceListItemInput['discountType'],
            discountValue: fieldValue(formData, 'discount_value'),
            effectiveFrom: fieldValue(formData, 'valid_from'),
            effectiveTo: fieldValue(formData, 'valid_to'),
            id: existing?.id,
            isActive: checked(formData, 'is_active'),
            itemId: fieldValue(formData, 'item_id'),
            maxQuantity: fieldValue(formData, 'max_quantity'),
            minQuantity: fieldValue(formData, 'min_quantity') || '1',
            priceListId,
            priority: fieldValue(formData, 'priority') || '0',
            uomId: fieldValue(formData, 'uom_id'),
            unitPrice: fieldValue(formData, 'price'),
        };

        setIsSaving(true);
        setState({ errors: {}, message: '' });
        try {
            const response = await pricingApi.upsertPriceListItem(input);
            onSaved(response.data);
            event.currentTarget.reset();
        } catch (error) {
            setState(errorState(error, 'Unable to save price list item.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-4" onSubmit={handleSubmit}>
            {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{state.message}</div> : null}
            <div className="grid gap-4 md:grid-cols-4">
                <div className="space-y-2"><FieldLabel>Item</FieldLabel><Select name="item_id" onChange={(event) => itemUoms.selectItem(event.currentTarget.value)} options={lookupOptions(items)} placeholder="Select item" value={itemUoms.itemId} /><FieldError message={state.errors.item_id?.[0]} /></div>
                <div className="space-y-2"><FieldLabel>UOM</FieldLabel><Select disabled={!itemUoms.itemId || itemUoms.isLoading || itemUoms.uoms.length === 0} name="uom_id" onChange={(event) => itemUoms.setUomId(event.currentTarget.value)} options={lookupOptions(itemUoms.uoms)} placeholder="Select UOM" value={itemUoms.uomId} />{itemUoms.hint ? <p className="text-xs text-slate-500">{itemUoms.hint}</p> : null}<FieldError message={state.errors.uom_id?.[0]} /></div>
                <div className="space-y-2"><FieldLabel>Unit price</FieldLabel><Input defaultValue={existing?.unitPrice} name="price" type="number" /><FieldError message={state.errors.price?.[0]} /></div>
                <div className="space-y-2"><FieldLabel>Min quantity</FieldLabel><Input defaultValue={existing?.minQuantity ?? '1'} name="min_quantity" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Max quantity</FieldLabel><Input defaultValue={existing?.maxQuantity} name="max_quantity" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Discount type</FieldLabel><Select defaultValue={existing?.discountType ?? 'percentage'} name="discount_type" options={[{ label: 'Percentage', value: 'percentage' }, { label: 'Fixed', value: 'fixed' }]} /></div>
                <div className="space-y-2"><FieldLabel>Discount value</FieldLabel><Input defaultValue={existing?.discountValue ?? '0'} name="discount_value" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={existing?.priority ?? '0'} name="priority" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Effective from</FieldLabel><Input defaultValue={existing?.effectiveFrom} name="valid_from" type="date" /></div>
                <div className="space-y-2"><FieldLabel>Effective to</FieldLabel><Input defaultValue={existing?.effectiveTo} name="valid_to" type="date" /></div>
                <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={existing?.active ?? true} name="is_active" />Active</label>
            </div>
            <div className="flex justify-end"><Button disabled={isSaving} type="submit">{existing ? 'Update Price Item' : 'Add Price Item'}</Button></div>
        </form>
    );
}

export function PriceListItemsManager({ items, onChanged, priceListId }: { items: PriceListItem[]; onChanged: () => void; priceListId: string }) {
    const [editing, setEditing] = useState<PriceListItem | undefined>();
    const [deleteTarget, setDeleteTarget] = useState<PriceListItem | null>(null);

    async function deleteItem() {
        if (!deleteTarget) return;
        await pricingApi.deletePriceListItem(deleteTarget.id);
        setDeleteTarget(null);
        onChanged();
    }

    return (
        <div className="space-y-5">
            <FormSection description="Persist item/UOM-specific prices used by the backend resolver." title={editing ? 'Edit Price List Item' : 'Add Price List Item'}>
                <PriceListItemEditor existing={editing} onSaved={() => { setEditing(undefined); onChanged(); }} priceListId={priceListId} />
            </FormSection>
            {items.length ? (
                <DataTable
                    columns={[
                        { header: 'Item', key: 'itemName', render: (row) => <div><p className="font-semibold text-slate-900">{row.itemName}</p><p className="text-xs text-slate-400">{row.itemCode}</p></div> },
                        { header: 'UOM', key: 'uom' },
                        { header: 'Min Qty', key: 'minQuantity' },
                        { header: 'Max Qty', key: 'maxQuantity' },
                        { header: 'Unit Price', key: 'unitPrice' },
                        { header: 'Effective', key: 'effectiveFrom', render: (row) => `${row.effectiveFrom || 'Any'} to ${row.effectiveTo || 'Open'}` },
                        { header: 'Status', key: 'active', render: (row) => <StatusBadge status={row.active ? 'Active' : 'Inactive'} /> },
                        { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-3 text-sm font-semibold"><button className="text-slate-950" onClick={() => setEditing(row)} type="button">Edit</button><button className="text-red-600" onClick={() => setDeleteTarget(row)} type="button">Delete</button></div> },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={items}
                />
            ) : <EmptyState description="Add the first backend-persisted price item for this list." title="No price list items" />}
            <ConfirmDialog message="Delete this price list item from the backend?" onCancel={() => setDeleteTarget(null)} onConfirm={deleteItem} open={deleteTarget !== null} title="Delete price item" />
        </div>
    );
}

export function PricingRuleConditionsTable({ conditions }: { conditions: PricingRuleCondition[] }) {
    if (!conditions.length) {
        return <EmptyState description="No conditions are linked to this rule." title="No rule conditions" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Seq', key: 'sequence' },
                { header: 'Type', key: 'conditionType' },
                { header: 'Field', key: 'field' },
                { header: 'Operator', key: 'operator' },
                { header: 'Value', key: 'value' },
            ]}
            getRowKey={(row) => row.id}
            rows={conditions}
        />
    );
}

export function DiscountRulesTable({ rules }: { rules: DiscountRule[] }) {
    if (!rules.length) {
        return <EmptyState description="No discount-rule links are configured." title="No discount rules" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Scope', key: 'scope' },
                { header: 'Discount', key: 'discountId' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rules}
        />
    );
}

export function PricingTierTable({ tiers }: { tiers: PricingTier[] }) {
    if (!tiers.length) {
        return <EmptyState description="No quantity tiers are configured." title="No pricing tiers" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Tier', key: 'tierName' },
                { header: 'Min', key: 'minQuantity' },
                { header: 'Max', key: 'maxQuantity' },
                { header: 'Unit Price', key: 'unitPrice' },
                { header: 'Adjustment', key: 'adjustmentType', render: (row) => row.adjustmentType ? `${row.adjustmentType} ${row.adjustmentValue}` : 'None' },
                { header: 'Status', key: 'active', render: (row) => <StatusBadge status={row.active ? 'Active' : 'Inactive'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={tiers}
        />
    );
}

export function PricingHistoryTable({ history }: { history: PriceHistory[] }) {
    if (!history.length) {
        return <EmptyState description="No price changes have been recorded yet." title="No price history" />;
    }

    return (
        <DataTable
            columns={[
                { header: 'Effective', key: 'effectiveDate' },
                { header: 'Price List', key: 'priceListName' },
                { header: 'Item', key: 'itemName' },
                { header: 'Old Price', key: 'oldPrice' },
                { header: 'New Price', key: 'newPrice' },
                { header: 'Change', key: 'change' },
                { header: 'Actor', key: 'actor' },
            ]}
            getRowKey={(row) => row.id}
            rows={history}
        />
    );
}

export function PriceBreakdownPanel({ breakdown }: { breakdown: PriceBreakdown[] }) {
    return (
        <PreviewPanel
            rows={breakdown.map((row) => ({ label: row.label, value: row.value }))}
            subtitle="Readonly backend resolver explanation."
            title="Price Breakdown"
        />
    );
}

export function PriceResolverResultPanel({ result }: { result?: PriceResolveResult }) {
    if (!result) {
        return <PreviewPanel rows={[{ label: 'Result', value: 'Submit resolver inputs to request a backend price result.' }]} subtitle="No resolver request has been submitted." title="Resolved Price" />;
    }

    return (
        <div className="space-y-5">
            <PreviewPanel
                rows={[
                    { label: 'Resolved unit price', value: result.resolvedUnitPrice },
                    { label: 'Selected price list', value: result.selectedPriceList || 'None' },
                    { label: 'Applied rule', value: result.appliedRule || 'None' },
                    { label: 'Applied discount', value: result.appliedDiscount },
                    { label: 'Tier info', value: result.tierInfo || 'None' },
                    { label: 'Net amount', value: result.netUnitPrice },
                    { label: 'Warnings', value: result.warnings.length ? result.warnings.join(', ') : 'None' },
                    { label: 'Errors', value: result.errors.length ? result.errors.join(', ') : 'None' },
                ]}
                subtitle="Readonly backend result. The frontend does not resolve price, tier, or discount values."
                title="Resolved Price"
            />
            <PriceBreakdownPanel breakdown={result.breakdown} />
        </div>
    );
}

export function PricingUsagePanel({ usage }: { usage: PricingUsageSummary }) {
    const counts = usage.counts;

    return (
        <PreviewPanel
            rows={[
                { label: 'Price items', value: counts.priceListItems ?? 0 },
                { label: 'Customer links', value: counts.customerLinks ?? 0 },
                { label: 'Supplier links', value: counts.supplierLinks ?? 0 },
                { label: 'Conditions', value: counts.conditions ?? 0 },
                { label: 'Tiers', value: counts.tiers ?? 0 },
                { label: 'Purchase references', value: counts.purchaseReferences ?? 0 },
                { label: 'Sales references', value: counts.salesReferences ?? 0 },
                { label: 'Service references', value: counts.serviceReferences ?? 0 },
                { label: 'Rental references', value: counts.rentalReferences ?? 0 },
                { label: 'History entries', value: counts.historyEntries ?? 0 },
            ]}
            subtitle="Counts are returned by Pricing backend usage endpoints."
            title="Usage Summary"
        />
    );
}

export function PricingActivityTimeline({ entries }: { entries: PricingAuditEntry[] }) {
    return entries.length
        ? <AuditTimeline events={entries.map((entry) => ({ actor: entry.actor, description: entry.description, id: entry.id, time: entry.time }))} />
        : <EmptyState description="No audit activity was returned for this pricing record." title="No audit activity" />;
}

export function PriceListForm({ mode, priceList }: { mode: 'create' | 'edit'; priceList?: PriceList }) {
    const navigate = useNavigate();
    const [currencies, setCurrencies] = useState<LookupOption[]>([]);
    const [state, setState] = useState<FormErrorState>({ errors: {}, message: '' });
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        let mounted = true;
        pricingApi.listCurrencies()
            .then((response) => { if (mounted) setCurrencies(response.data); })
            .catch((error: unknown) => { if (mounted) setState(errorState(error, 'Unable to load currencies.')); });
        return () => { mounted = false; };
    }, []);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setState({ errors: {}, message: '' });
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const type = fieldValue(formData, 'type') as PriceListType;
        const input: PriceListFormInput = {
            code: fieldValue(formData, 'code'),
            currencyId: fieldValue(formData, 'currency_id'),
            description: fieldValue(formData, 'description'),
            isActive: checked(formData, 'is_active'),
            isCustomerSpecific: type === 'customer' || checked(formData, 'is_customer_specific'),
            isDefault: checked(formData, 'is_default'),
            isExclusive: checked(formData, 'is_exclusive'),
            isStackable: checked(formData, 'is_stackable'),
            isSupplierSpecific: type === 'supplier' || checked(formData, 'is_supplier_specific'),
            moduleUsage: moduleOptions.filter((option) => checked(formData, option.name)).map((option) => option.name),
            name: fieldValue(formData, 'name'),
            priority: fieldValue(formData, 'priority'),
            type,
            validFrom: fieldValue(formData, 'valid_from'),
            validTo: fieldValue(formData, 'valid_to'),
        };

        try {
            const response = mode === 'edit' && priceList ? await pricingApi.updatePriceList(priceList.id, input) : await pricingApi.createPriceList(input);
            navigate(`/pricing/price-lists/${response.data.id}`);
        } catch (error) {
            setState(errorState(error, 'Unable to save price list.'));
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={handleSubmit}>
            {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{state.message}</div> : null}
            <FormSection description="Define generic price list identity, scope, and resolver priority." title="Basic Information">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2"><FieldLabel>Price list code</FieldLabel><Input defaultValue={priceList?.code} name="code" /><FieldError message={state.errors.code?.[0]} /></div>
                    <div className="space-y-2"><FieldLabel>Price list name</FieldLabel><Input defaultValue={priceList?.name} name="name" /><FieldError message={state.errors.name?.[0]} /></div>
                    <div className="space-y-2"><FieldLabel>Type</FieldLabel><Select defaultValue={priceList?.type ?? 'sales'} name="type" options={priceListTypeOptions} /></div>
                    <div className="space-y-2"><FieldLabel>Currency</FieldLabel><Select defaultValue={priceList?.currencyId} name="currency_id" options={lookupOptions(currencies)} /></div>
                    <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={priceList?.description} name="description" /></div>
                </div>
            </FormSection>
            <FormSection description="Validity and priority are submitted for backend resolver evaluation." title="Validity and Behavior">
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="space-y-2"><FieldLabel>Valid from</FieldLabel><Input defaultValue={priceList?.validFrom} name="valid_from" type="date" /></div>
                    <div className="space-y-2"><FieldLabel>Valid to</FieldLabel><Input defaultValue={priceList?.validTo} name="valid_to" type="date" /></div>
                    <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={priceList?.priority ?? '0'} name="priority" type="number" /></div>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.status !== 'inactive'} name="is_active" />Active</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.isDefault} name="is_default" />Default</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.isStackable ?? true} name="is_stackable" />Stackable</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.isExclusive} name="is_exclusive" />Exclusive</label>
                </div>
            </FormSection>
            <FormSection description="Scope controls where the backend resolver may consider this list." title="Scope">
                <div className="grid gap-3 md:grid-cols-3">
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.isCustomerSpecific} name="is_customer_specific" />Customer-specific</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.isSupplierSpecific} name="is_supplier_specific" />Supplier-specific</label>
                    {moduleOptions.map((option) => (
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={option.name}>
                            <Checkbox defaultChecked={priceList?.moduleUsage.includes(option.name)} name={option.name} />{option.label}
                        </label>
                    ))}
                </div>
            </FormSection>
            <div className="flex justify-end gap-3"><Link to="/pricing/price-lists"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Price List' : 'Create Price List'}</Button></div>
        </form>
    );
}

export function PricingRuleForm({ mode, rule }: { mode: 'create' | 'edit'; rule?: PricingRule }) {
    const navigate = useNavigate();
    const [state, setState] = useState<FormErrorState>({ errors: {}, message: '' });
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setState({ errors: {}, message: '' });
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);
        const input: PricingRuleFormInput = {
            actionType: fieldValue(formData, 'action_type') as PricingRule['actionType'],
            actionValue: fieldValue(formData, 'action_value'),
            conditionField: fieldValue(formData, 'condition_field'),
            conditionOperator: fieldValue(formData, 'condition_operator'),
            conditionValue: fieldValue(formData, 'condition_value'),
            description: fieldValue(formData, 'description'),
            isActive: checked(formData, 'is_active'),
            isExclusive: checked(formData, 'is_exclusive'),
            isStackable: checked(formData, 'is_stackable'),
            name: fieldValue(formData, 'name'),
            priority: fieldValue(formData, 'priority') || '0',
            ruleCode: fieldValue(formData, 'code'),
            ruleType: fieldValue(formData, 'rule_type') as PricingRule['ruleType'],
            sourceType: fieldValue(formData, 'source_type') as PricingRule['sourceType'],
            validFrom: fieldValue(formData, 'valid_from'),
            validTo: fieldValue(formData, 'valid_to'),
        };

        try {
            const response = mode === 'edit' && rule ? await pricingApi.updatePricingRule(rule.id, input) : await pricingApi.createPricingRule(input);
            navigate(`/pricing/rules/${response.data.id}`);
        } catch (error) {
            setState(errorState(error, 'Unable to save pricing rule.'));
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={handleSubmit}>
            {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{state.message}</div> : null}
            <FormSection description="Rules describe resolver inputs and outcomes. Backend evaluates priority and conditions." title="Basic Rule">
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2"><FieldLabel>Rule code</FieldLabel><Input defaultValue={rule?.code} name="code" /></div>
                    <div className="space-y-2"><FieldLabel>Rule name</FieldLabel><Input defaultValue={rule?.name} name="name" /><FieldError message={state.errors.name?.[0]} /></div>
                    <div className="space-y-2"><FieldLabel>Rule type</FieldLabel><Select defaultValue={rule?.ruleType ?? 'price_resolve'} name="rule_type" options={[{ label: 'Price resolve', value: 'price_resolve' }, { label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Generic', value: 'generic' }]} /></div>
                    <div className="space-y-2"><FieldLabel>Source module</FieldLabel><Select defaultValue={rule?.sourceType ?? 'all'} name="source_type" options={[{ label: 'All modules', value: 'all' }, { label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }]} /></div>
                    <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={rule?.priority ?? '0'} name="priority" type="number" /></div>
                    <div className="space-y-2"><FieldLabel>Action type</FieldLabel><Select defaultValue={rule?.actionType ?? 'adjust_price'} name="action_type" options={[{ label: 'Adjust price', value: 'adjust_price' }, { label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Override', value: 'override' }]} /></div>
                    <div className="space-y-2"><FieldLabel>Action value</FieldLabel><Input defaultValue={rule?.actionValue} name="action_value" type="number" /></div>
                    <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={rule?.description} name="description" /></div>
                </div>
            </FormSection>
            <FormSection description="A real condition row can be saved with the rule. Additional condition rows can be managed from the detail endpoint later." title="Condition">
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-2"><FieldLabel>Field</FieldLabel><Input name="condition_field" /></div>
                    <div className="space-y-2"><FieldLabel>Operator</FieldLabel><Select name="condition_operator" options={[{ label: 'Equals', value: 'equals' }, { label: 'Greater than or equal', value: 'gte' }, { label: 'Less than or equal', value: 'lte' }, { label: 'Contains', value: 'contains' }]} /></div>
                    <div className="space-y-2"><FieldLabel>Value</FieldLabel><Input name="condition_value" /></div>
                </div>
            </FormSection>
            <FormSection description="Backend owns rule execution and conflict behavior." title="Validity and Status">
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="space-y-2"><FieldLabel>Valid from</FieldLabel><Input defaultValue={rule?.validFrom} name="valid_from" type="date" /></div>
                    <div className="space-y-2"><FieldLabel>Valid to</FieldLabel><Input defaultValue={rule?.validTo} name="valid_to" type="date" /></div>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.isStackable ?? true} name="is_stackable" />Stackable</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.isExclusive} name="is_exclusive" />Exclusive</label>
                    <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.status !== 'inactive'} name="is_active" />Active</label>
                </div>
            </FormSection>
            <div className="flex justify-end gap-3"><Link to="/pricing/rules"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Rule' : 'Create Rule'}</Button></div>
        </form>
    );
}

export function PriceResolverForm() {
    const [result, setResult] = useState<PriceResolveResult>();
    const [moduleSource, setModuleSource] = useState<PricingModuleScope>('sales');
    const [lookups, setLookups] = useState<{ currencies: LookupOption[]; items: LookupOption[]; priceLists: PriceList[] }>({ currencies: [], items: [], priceLists: [] });
    const itemUoms = useItemUomLookup(moduleSource);
    const [isLoading, setIsLoading] = useState(false);
    const [formError, setFormError] = useState('');

    useEffect(() => {
        let mounted = true;
        Promise.all([pricingApi.listItems(), pricingApi.listCurrencies(), pricingApi.listPriceLists()])
            .then(([items, currencies, priceLists]) => {
                if (mounted) setLookups({ currencies: currencies.data, items: items.data, priceLists: priceLists.data });
            })
            .catch((error: unknown) => { if (mounted) setFormError(error instanceof Error ? error.message : 'Unable to load resolver lookups.'); });
        return () => { mounted = false; };
    }, []);

    function buildInput(form: HTMLFormElement): PriceResolveRequest {
        const formData = new FormData(form);
        return {
            currencyId: fieldValue(formData, 'currency_id') || undefined,
            customerId: fieldValue(formData, 'customer_id') || undefined,
            date: fieldValue(formData, 'date'),
            itemId: fieldValue(formData, 'item_id'),
            moduleSource: fieldValue(formData, 'module_source') as PricingModuleScope,
            priceListId: fieldValue(formData, 'price_list_id') || undefined,
            quantity: fieldValue(formData, 'quantity') || '1',
            supplierId: fieldValue(formData, 'supplier_id') || undefined,
            uomId: fieldValue(formData, 'uom_id'),
        };
    }

    async function handlePreview(event: MouseEvent<HTMLButtonElement>) {
        event.preventDefault();
        const form = event.currentTarget.closest('form');
        if (!form) return;

        setIsLoading(true);
        setFormError('');
        try {
            setResult(await pricingApi.resolvePrice(buildInput(form)));
        } catch (error) {
            setFormError(error instanceof ApiError ? error.message : 'Unable to resolve price preview.');
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_420px]">
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Submit transaction context to the backend resolver. The result panel is readonly." title="Price Resolver Inputs">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Module/source</FieldLabel><Select name="module_source" onChange={(event) => setModuleSource(event.currentTarget.value as PricingModuleScope)} options={[{ label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }]} value={moduleSource} /></div>
                        <div className="space-y-2"><FieldLabel>Item</FieldLabel><Select name="item_id" onChange={(event) => itemUoms.selectItem(event.currentTarget.value)} options={lookupOptions(lookups.items)} placeholder="Select item" value={itemUoms.itemId} /></div>
                        <div className="space-y-2"><FieldLabel>UOM</FieldLabel><Select disabled={!itemUoms.itemId || itemUoms.isLoading || itemUoms.uoms.length === 0} name="uom_id" onChange={(event) => itemUoms.setUomId(event.currentTarget.value)} options={lookupOptions(itemUoms.uoms)} placeholder="Select UOM" value={itemUoms.uomId} />{itemUoms.hint ? <p className="text-xs text-slate-500">{itemUoms.hint}</p> : null}</div>
                        <div className="space-y-2"><FieldLabel>Customer optional</FieldLabel><Input name="customer_id" /></div>
                        <div className="space-y-2"><FieldLabel>Supplier optional</FieldLabel><Input name="supplier_id" /></div>
                        <div className="space-y-2"><FieldLabel>Quantity</FieldLabel><Input defaultValue="1" name="quantity" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Date</FieldLabel><Input name="date" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Currency</FieldLabel><Select name="currency_id" options={lookupOptions(lookups.currencies)} /></div>
                        <div className="space-y-2"><FieldLabel>Price list optional</FieldLabel><Select name="price_list_id" options={lookups.priceLists.map((list) => ({ label: `${list.code} - ${list.name}`, value: list.id }))} /></div>
                    </div>
                </FormSection>
                <div className="flex justify-end"><Button disabled={isLoading} onClick={handlePreview} type="button" variant="blue">{isLoading ? 'Resolving...' : 'Resolve Price'}</Button></div>
            </div>
            <PriceResolverResultPanel result={result} />
        </form>
    );
}

export function PriceListRowActions({ onChanged, priceList }: { onChanged?: () => void; priceList: PriceList }) {
    const [confirm, setConfirm] = useState<'activate' | 'deactivate' | null>(null);

    async function apply() {
        if (!confirm) return;
        if (confirm === 'activate') {
            await pricingApi.activatePriceList(priceList.id);
        } else {
            await pricingApi.deactivatePriceList(priceList.id);
        }
        setConfirm(null);
        onChanged?.();
    }

    return (
        <div className="flex items-center gap-3 text-sm font-semibold">
            <Link className="text-slate-950" to={`/pricing/price-lists/${priceList.id}`}>View</Link>
            <Link className="text-slate-600" to={`/pricing/price-lists/${priceList.id}/edit`}>Edit</Link>
            <button className="text-slate-600" onClick={() => setConfirm(priceList.status === 'active' ? 'deactivate' : 'activate')} type="button">{priceList.status === 'active' ? 'Deactivate' : 'Activate'}</button>
            <ConfirmDialog message="Update price list active status?" onCancel={() => setConfirm(null)} onConfirm={apply} open={confirm !== null} title="Confirm status change" />
        </div>
    );
}

export function PricingRuleRowActions({ onChanged, rule }: { onChanged?: () => void; rule: PricingRule }) {
    const [confirm, setConfirm] = useState<'activate' | 'deactivate' | null>(null);

    async function apply() {
        if (!confirm) return;
        if (confirm === 'activate') {
            await pricingApi.activatePricingRule(rule.id);
        } else {
            await pricingApi.deactivatePricingRule(rule.id);
        }
        setConfirm(null);
        onChanged?.();
    }

    return (
        <div className="flex items-center gap-3 text-sm font-semibold">
            <Link className="text-slate-950" to={`/pricing/rules/${rule.id}`}>View</Link>
            <Link className="text-slate-600" to={`/pricing/rules/${rule.id}/edit`}>Edit</Link>
            <button className="text-slate-600" onClick={() => setConfirm(rule.status === 'active' ? 'deactivate' : 'activate')} type="button">{rule.status === 'active' ? 'Deactivate' : 'Activate'}</button>
            <ConfirmDialog message="Update pricing rule active status?" onCancel={() => setConfirm(null)} onConfirm={apply} open={confirm !== null} title="Confirm rule status" />
        </div>
    );
}

export function DiscountManager({ onChanged, rows }: { onChanged: () => void; rows: Discount[] }) {
    const [editing, setEditing] = useState<Discount | undefined>();
    const [state, setState] = useState<FormErrorState>({ errors: {}, message: '' });
    const [isSaving, setIsSaving] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const input: DiscountInput = {
            code: fieldValue(formData, 'code'),
            discountType: fieldValue(formData, 'discount_type') as DiscountInput['discountType'],
            discountValue: fieldValue(formData, 'discount_value'),
            id: editing?.id,
            isActive: checked(formData, 'is_active'),
            isExclusive: checked(formData, 'is_exclusive'),
            isStackable: checked(formData, 'is_stackable'),
            name: fieldValue(formData, 'name'),
            priority: fieldValue(formData, 'priority') || '0',
            validFrom: fieldValue(formData, 'valid_from'),
            validTo: fieldValue(formData, 'valid_to'),
        };
        setIsSaving(true);
        setState({ errors: {}, message: '' });
        try {
            if (editing) {
                await pricingApi.updateDiscount(editing.id, input);
            } else {
                await pricingApi.createDiscount(input);
            }
            setEditing(undefined);
            event.currentTarget.reset();
            onChanged();
        } catch (error) {
            setState(errorState(error, 'Unable to save discount.'));
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <div className="space-y-5">
            <FormSection description="Persist reusable discount definitions. Backend calculates amounts during preview and posting." title={editing ? 'Edit Discount' : 'Create Discount'}>
                <form className="space-y-4" onSubmit={handleSubmit}>
                    {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{state.message}</div> : null}
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-2"><FieldLabel>Code</FieldLabel><Input defaultValue={editing?.code} name="code" /></div>
                        <div className="space-y-2"><FieldLabel>Name</FieldLabel><Input defaultValue={editing?.name} name="name" /><FieldError message={state.errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Type</FieldLabel><Select defaultValue={editing?.discountType ?? 'percentage'} name="discount_type" options={[{ label: 'Percentage', value: 'percentage' }, { label: 'Fixed', value: 'fixed' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Value</FieldLabel><Input defaultValue={editing?.discountValue} name="discount_value" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={editing?.priority ?? '0'} name="priority" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Valid from</FieldLabel><Input defaultValue={editing?.validFrom} name="valid_from" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Valid to</FieldLabel><Input defaultValue={editing?.validTo} name="valid_to" type="date" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={editing?.status !== 'inactive'} name="is_active" />Active</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={editing?.isStackable ?? true} name="is_stackable" />Stackable</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={editing?.isExclusive} name="is_exclusive" />Exclusive</label>
                    </div>
                    <div className="flex justify-end gap-2">{editing ? <Button onClick={() => setEditing(undefined)} type="button" variant="secondary">Cancel Edit</Button> : null}<Button disabled={isSaving} type="submit">{editing ? 'Update Discount' : 'Create Discount'}</Button></div>
                </form>
            </FormSection>
            {rows.length ? (
                <div className="grid gap-4 lg:grid-cols-2">
                    {rows.map((discount) => (
                        <Card className="p-5" key={discount.id}>
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{discount.code}</p>
                                    <h3 className="mt-1 font-bold text-slate-950">{discount.name}</h3>
                                    <p className="mt-2 text-sm text-slate-500">{discount.discountType} discount.</p>
                                </div>
                                <StatusBadge status={discount.status} />
                            </div>
                            <PreviewPanel rows={[{ label: 'Value', value: discount.discountValue }, { label: 'Priority', value: discount.priority }, { label: 'Stacking', value: discount.isExclusive ? 'Exclusive' : discount.isStackable ? 'Stackable' : 'Single' }]} subtitle="Persisted discount definition." title="Discount setup" />
                            <div className="mt-4 flex justify-end"><Button onClick={() => setEditing(discount)} variant="secondary">Edit Discount</Button></div>
                        </Card>
                    ))}
                </div>
            ) : <EmptyState description="Create a discount definition to make it available to the backend resolver." title="No discounts" />}
        </div>
    );
}

export function PricingTierManager({ onChanged, priceListItems, rows }: { onChanged: () => void; priceListItems: PriceListItem[]; rows: PricingTier[] }) {
    const [editing, setEditing] = useState<PricingTier | undefined>();
    const [deleteTarget, setDeleteTarget] = useState<PricingTier | null>(null);
    const [state, setState] = useState<FormErrorState>({ errors: {}, message: '' });
    const editingPriceItem = priceListItems.find((item) => item.id === editing?.priceListItemId);
    const itemUoms = useItemUomLookup('pricing', editingPriceItem?.itemId, editing?.uomId);
    const priceItemOptions = useMemo(() => priceListItems.map((item) => ({ label: `${item.itemName} - ${item.unitPrice}`, value: item.id })), [priceListItems]);

    function selectPriceItem(priceListItemId: string): void {
        const priceItem = priceListItems.find((item) => item.id === priceListItemId);
        itemUoms.selectItem(priceItem?.itemId ?? '');
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const input: PricingTierInput = {
            adjustmentType: fieldValue(formData, 'adjustment_type') as PricingTierInput['adjustmentType'],
            adjustmentValue: fieldValue(formData, 'adjustment_value'),
            id: editing?.id,
            isActive: checked(formData, 'is_active'),
            maxQuantity: fieldValue(formData, 'max_quantity'),
            minQuantity: fieldValue(formData, 'min_quantity') || '1',
            priceListItemId: fieldValue(formData, 'price_list_item_id'),
            pricingRuleId: '',
            sequence: fieldValue(formData, 'sequence') || '1',
            unitPrice: fieldValue(formData, 'price'),
            uomId: fieldValue(formData, 'uom_id'),
        };
        setState({ errors: {}, message: '' });
        try {
            await pricingApi.upsertPricingTier(input);
            setEditing(undefined);
            event.currentTarget.reset();
            onChanged();
        } catch (error) {
            setState(errorState(error, 'Unable to save pricing tier.'));
        }
    }

    async function deleteTier() {
        if (!deleteTarget) return;
        await pricingApi.deletePricingTier(deleteTarget.id);
        setDeleteTarget(null);
        onChanged();
    }

    return (
        <div className="space-y-5">
            <FormSection description="Persist quantity tiers. Backend selects and applies matching tiers during price resolution." title={editing ? 'Edit Pricing Tier' : 'Create Pricing Tier'}>
                <form className="space-y-4" onSubmit={handleSubmit}>
                    {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{state.message}</div> : null}
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-2"><FieldLabel>Price item</FieldLabel><Select defaultValue={editing?.priceListItemId} name="price_list_item_id" onChange={(event) => selectPriceItem(event.currentTarget.value)} options={priceItemOptions} /></div>
                        <div className="space-y-2"><FieldLabel>Sequence</FieldLabel><Input defaultValue={editing?.sequence ?? '1'} name="sequence" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Min quantity</FieldLabel><Input defaultValue={editing?.minQuantity ?? '1'} name="min_quantity" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Max quantity</FieldLabel><Input defaultValue={editing?.maxQuantity} name="max_quantity" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>UOM</FieldLabel><Select disabled={!itemUoms.itemId || itemUoms.isLoading || itemUoms.uoms.length === 0} name="uom_id" onChange={(event) => itemUoms.setUomId(event.currentTarget.value)} options={lookupOptions(itemUoms.uoms)} placeholder="Select UOM" value={itemUoms.uomId} />{itemUoms.hint ? <p className="text-xs text-slate-500">{itemUoms.hint}</p> : null}</div>
                        <div className="space-y-2"><FieldLabel>Override price</FieldLabel><Input defaultValue={editing?.unitPrice} name="price" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Adjustment type</FieldLabel><Select defaultValue={editing?.adjustmentType} name="adjustment_type" options={[{ label: 'None', value: '' }, { label: 'Percentage', value: 'percentage' }, { label: 'Fixed', value: 'fixed' }, { label: 'Override', value: 'override' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Adjustment value</FieldLabel><Input defaultValue={editing?.adjustmentValue} name="adjustment_value" type="number" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={editing?.active ?? true} name="is_active" />Active</label>
                    </div>
                    <div className="flex justify-end gap-2">{editing ? <Button onClick={() => setEditing(undefined)} type="button" variant="secondary">Cancel Edit</Button> : null}<Button type="submit">{editing ? 'Update Tier' : 'Create Tier'}</Button></div>
                </form>
            </FormSection>
            <PricingTierTable tiers={rows} />
            {rows.length ? <div className="flex flex-wrap gap-2">{rows.map((tier) => <div className="flex gap-2" key={tier.id}><Button onClick={() => setEditing(tier)} variant="secondary">Edit {tier.tierName}</Button><Button onClick={() => setDeleteTarget(tier)} variant="secondary">Delete</Button></div>)}</div> : null}
            <ConfirmDialog message="Delete this tier from the backend?" onCancel={() => setDeleteTarget(null)} onConfirm={deleteTier} open={deleteTarget !== null} title="Delete pricing tier" />
        </div>
    );
}
