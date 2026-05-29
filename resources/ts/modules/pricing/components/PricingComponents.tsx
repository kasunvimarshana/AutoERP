import { FormEvent, MouseEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { priceLists } from '../mock/pricingMock';
import { pricingApi } from '../services/pricingApi';
import type {
    Discount,
    DiscountRule,
    PriceBreakdown,
    PriceHistory,
    PriceList,
    PriceListFormInput,
    PriceListItem,
    PriceListType,
    PriceResolveRequest,
    PriceResolveResult,
    PricingAuditEntry,
    PricingModuleScope,
    PricingRule,
    PricingRuleCondition,
    PricingRuleFormInput,
    PricingTier,
    PricingUsageSummary,
} from '../types/pricing.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checked(formData: FormData, name: string) {
    return formData.get(name) === 'on';
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

export function PriceListSummaryCard({ priceList }: { priceList: PriceList }) {
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
                    <p className="mt-1 max-w-3xl text-sm text-slate-500">{priceList.description}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Currency</p><p className="mt-1 font-semibold text-slate-800">{priceList.currency}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Priority</p><p className="mt-1 font-semibold text-slate-800">{priceList.priority}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Valid</p><p className="mt-1 font-semibold text-slate-800">{priceList.validFrom} to {priceList.validTo}</p></div>
                </div>
            </div>
        </Card>
    );
}

export function PriceListItemsTable({ items }: { items: PriceListItem[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Item', key: 'itemName', render: (row) => <div><p className="font-semibold text-slate-900">{row.itemName}</p><p className="text-xs text-slate-400">{row.itemCode}</p></div> },
                { header: 'UOM', key: 'uom' },
                { header: 'Min Qty', key: 'minQuantity' },
                { header: 'Unit Price', key: 'unitPrice' },
                { header: 'Effective', key: 'effectiveFrom', render: (row) => `${row.effectiveFrom} to ${row.effectiveTo}` },
                { header: 'Status', key: 'active', render: (row) => <StatusBadge status={row.active ? 'Active' : 'Inactive'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={items}
        />
    );
}

export function PricingRuleConditionsTable({ conditions }: { conditions: PricingRuleCondition[] }) {
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
    return (
        <DataTable
            columns={[
                { header: 'Tier', key: 'tierName' },
                { header: 'Item', key: 'itemName' },
                { header: 'Min', key: 'minQuantity' },
                { header: 'Max', key: 'maxQuantity' },
                { header: 'Unit Price', key: 'unitPrice' },
                { header: 'Status', key: 'active', render: (row) => <StatusBadge status={row.active ? 'Active' : 'Inactive'} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={tiers}
        />
    );
}

export function PricingHistoryTable({ history }: { history: PriceHistory[] }) {
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
            subtitle="Readonly backend/mock explanation. Frontend does not resolve pricing priority."
            title="Price Breakdown"
        />
    );
}

export function PriceResolverResultPanel({ result }: { result?: PriceResolveResult }) {
    if (!result) {
        return <PreviewPanel rows={[{ label: 'Result', value: 'Run resolver preview to request backend price resolution.' }]} title="Resolved Price" />;
    }

    return (
        <div className="space-y-5">
            <PreviewPanel
                rows={[
                    { label: 'Resolved unit price', value: result.resolvedUnitPrice },
                    { label: 'Selected price list', value: result.selectedPriceList },
                    { label: 'Applied rule', value: result.appliedRule },
                    { label: 'Applied discount', value: result.appliedDiscount },
                    { label: 'Tier info', value: result.tierInfo },
                    { label: 'Net unit price', value: result.netUnitPrice },
                    { label: 'Warnings', value: result.warnings.length ? result.warnings.join(', ') : 'None' },
                    { label: 'Errors', value: result.errors.length ? result.errors.join(', ') : 'None' },
                ]}
                subtitle="Backend/mock readonly result. No price or discount calculation happens in the frontend."
                title="Resolved Price"
            />
            <PriceBreakdownPanel breakdown={result.breakdown} />
        </div>
    );
}

export function PricingUsagePanel({ usage }: { usage: PricingUsageSummary }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Modules', value: usage.modules },
                { label: 'Item coverage', value: usage.itemCoverage },
                { label: 'Customer lists', value: usage.customerLinks },
                { label: 'Supplier lists', value: usage.supplierLinks },
                { label: 'Transaction usage', value: usage.transactionUsage },
            ]}
            title="Usage / Activity"
        />
    );
}

export function PricingActivityTimeline({ entries }: { entries: PricingAuditEntry[] }) {
    return <AuditTimeline events={entries.map((entry) => ({ actor: entry.actor, description: entry.description, time: entry.time }))} />;
}

export function PriceListItemForm() {
    return (
        <FormSection description="Captured item price setup. Backend validates UOM, dates, currency, and final price resolving." title="Price List Item">
            <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2"><FieldLabel>Item</FieldLabel><Input placeholder="Select item placeholder" /></div>
                <div className="space-y-2"><FieldLabel>UOM</FieldLabel><Input placeholder="Select UOM placeholder" /></div>
                <div className="space-y-2"><FieldLabel>Unit price</FieldLabel><Input placeholder="Backend validated price input" /></div>
            </div>
        </FormSection>
    );
}

export function PricingRuleConditionForm() {
    return (
        <FormSection description="Rule conditions are submitted to backend for evaluation during price resolving." title="Condition Builder">
            <div className="grid gap-4 md:grid-cols-4">
                <div className="space-y-2"><FieldLabel>Field</FieldLabel><Input placeholder="customer.category" /></div>
                <div className="space-y-2"><FieldLabel>Operator</FieldLabel><Input placeholder="equals" /></div>
                <div className="space-y-2"><FieldLabel>Value</FieldLabel><Input placeholder="Fleet" /></div>
                <div className="space-y-2"><FieldLabel>Logical operator</FieldLabel><Select options={[{ label: 'And', value: 'and' }, { label: 'Or', value: 'or' }]} /></div>
            </div>
        </FormSection>
    );
}

export function DiscountForm() {
    return (
        <FormSection description="Frontend captures discount setup only. Final discount amount must come from backend preview/posting." title="Discount">
            <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2"><FieldLabel>Discount code</FieldLabel><Input placeholder="DISC-FLEET-10" /></div>
                <div className="space-y-2"><FieldLabel>Type</FieldLabel><Select options={[{ label: 'Percentage', value: 'percentage' }, { label: 'Fixed amount', value: 'fixed' }]} /></div>
                <div className="space-y-2"><FieldLabel>Value</FieldLabel><Input placeholder="Backend validates value" /></div>
            </div>
        </FormSection>
    );
}

export function PricingTierForm() {
    return (
        <FormSection description="Tier data is saved for backend resolver evaluation. Frontend does not apply tiers." title="Pricing Tier">
            <div className="grid gap-4 md:grid-cols-4">
                <div className="space-y-2"><FieldLabel>Item</FieldLabel><Input placeholder="Select item placeholder" /></div>
                <div className="space-y-2"><FieldLabel>Min quantity</FieldLabel><Input placeholder="10" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Max quantity</FieldLabel><Input placeholder="24" type="number" /></div>
                <div className="space-y-2"><FieldLabel>Unit price</FieldLabel><Input placeholder="Backend validated price" /></div>
            </div>
        </FormSection>
    );
}

export function PriceListForm({ mode, priceList }: { mode: 'create' | 'edit'; priceList?: PriceList }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: PriceListFormInput = {
            code: String(formData.get('code') ?? ''),
            currency: String(formData.get('currency') ?? 'LKR'),
            description: String(formData.get('description') ?? ''),
            isActive: checked(formData, 'is_active'),
            isCustomerSpecific: checked(formData, 'is_customer_specific'),
            isSupplierSpecific: checked(formData, 'is_supplier_specific'),
            moduleUsage: moduleOptions.filter((option) => checked(formData, option.name)).map((option) => option.name),
            name: String(formData.get('name') ?? ''),
            priority: String(formData.get('priority') ?? ''),
            type: String(formData.get('type') ?? 'sales') as PriceListType,
            validFrom: String(formData.get('valid_from') ?? ''),
            validTo: String(formData.get('valid_to') ?? ''),
        };

        try {
            const response = mode === 'edit' && priceList ? await pricingApi.updatePriceList(priceList.id, input) : await pricingApi.createPriceList(input);
            navigate(`/pricing/price-lists/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save price list.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Define the price list identity, type, and currency. Backend owns price selection priority." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Price list code</FieldLabel><Input defaultValue={priceList?.code} name="code" placeholder="SPL-STD-2026" /><FieldError message={errors.code?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Price list name</FieldLabel><Input defaultValue={priceList?.name} name="name" placeholder="Standard Sales Price List" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Type</FieldLabel><Select defaultValue={priceList?.type ?? 'sales'} name="type" options={priceListTypeOptions} /></div>
                        <div className="space-y-2"><FieldLabel>Currency</FieldLabel><Input defaultValue={priceList?.currency ?? 'LKR'} name="currency" placeholder="LKR" /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={priceList?.description} name="description" placeholder="Where and how this price list is used." /></div>
                    </div>
                </FormSection>
                <FormSection description="Validity and priority are submitted for backend resolver evaluation." title="Validity">
                    <div className="grid gap-4 md:grid-cols-4">
                        <div className="space-y-2"><FieldLabel>Valid from</FieldLabel><Input defaultValue={priceList?.validFrom} name="valid_from" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Valid to</FieldLabel><Input defaultValue={priceList?.validTo} name="valid_to" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={priceList?.priority} name="priority" placeholder="100" type="number" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={priceList?.status !== 'inactive'} name="is_active" />Active</label>
                    </div>
                </FormSection>
                <FormSection description="Scope controls where the backend resolver may consider this price list." title="Scope">
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
                <PriceListItemForm />
                <div className="flex justify-end gap-3"><Link to="/pricing/price-lists"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Price List' : 'Create Price List'}</Button></div>
            </div>
            <PreviewPanel rows={[{ label: 'Price resolving', value: 'Backend-owned' }, { label: 'Discount priority', value: 'Backend-owned' }, { label: 'UOM price normalization', value: 'Backend-owned' }]} title="Pricing backend ownership" />
        </form>
    );
}

export function PricingRuleForm({ mode, rule }: { mode: 'create' | 'edit'; rule?: PricingRule }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);
        const input: PricingRuleFormInput = {
            actionType: String(formData.get('action_type') ?? 'price_list') as PricingRule['actionType'],
            actionValue: String(formData.get('action_value') ?? ''),
            conditionsNote: String(formData.get('conditions_note') ?? ''),
            description: String(formData.get('description') ?? ''),
            isActive: checked(formData, 'is_active'),
            isExclusive: checked(formData, 'is_exclusive'),
            isStackable: checked(formData, 'is_stackable'),
            name: String(formData.get('name') ?? ''),
            priority: String(formData.get('priority') ?? '0'),
            ruleCode: String(formData.get('code') ?? ''),
            ruleType: String(formData.get('rule_type') ?? 'price_resolve') as PricingRule['ruleType'],
            sourceType: String(formData.get('source_type') ?? 'all') as PricingRule['sourceType'],
            validFrom: String(formData.get('valid_from') ?? ''),
            validTo: String(formData.get('valid_to') ?? ''),
        };

        try {
            const response = mode === 'edit' && rule ? await pricingApi.updatePricingRule(rule.id, input) : await pricingApi.createPricingRule(input);
            navigate(`/pricing/rules/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save pricing rule.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Rules describe resolver inputs and outcomes. Backend evaluates priority and conditions." title="Basic Rule">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Rule code</FieldLabel><Input defaultValue={rule?.code} name="code" placeholder="RULE-FLEET-DISC" /></div>
                        <div className="space-y-2"><FieldLabel>Rule name</FieldLabel><Input defaultValue={rule?.name} name="name" placeholder="Fleet Customer Discount Rule" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Rule type</FieldLabel><Select defaultValue={rule?.ruleType ?? 'price_resolve'} name="rule_type" options={[{ label: 'Price resolve', value: 'price_resolve' }, { label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Module-specific', value: 'module_specific' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Source module</FieldLabel><Select defaultValue={rule?.sourceType ?? 'all'} name="source_type" options={[{ label: 'All modules', value: 'all' }, { label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Priority</FieldLabel><Input defaultValue={rule?.priority} name="priority" placeholder="100" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Action type</FieldLabel><Select defaultValue={rule?.actionType ?? 'price_list'} name="action_type" options={[{ label: 'Price list', value: 'price_list' }, { label: 'Discount', value: 'discount' }, { label: 'Tier', value: 'tier' }, { label: 'Override', value: 'override' }]} /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={rule?.description} name="description" placeholder="Rule intent and business context." /></div>
                    </div>
                </FormSection>
                <FormSection description="Backend owns condition evaluation and price/discount result calculation." title="Conditions and Result">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Conditions note</FieldLabel><Textarea name="conditions_note" placeholder="Example: customer category = Fleet and item category = Labour" /></div>
                        <div className="space-y-2"><FieldLabel>Action value</FieldLabel><Textarea defaultValue={rule?.actionValue} name="action_value" placeholder="Backend applies the configured price list, discount, or tier." /></div>
                        <div className="space-y-2"><FieldLabel>Valid from</FieldLabel><Input defaultValue={rule?.validFrom} name="valid_from" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Valid to</FieldLabel><Input defaultValue={rule?.validTo} name="valid_to" type="date" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.isStackable} name="is_stackable" />Stackable</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.isExclusive} name="is_exclusive" />Exclusive</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={rule?.status !== 'inactive'} name="is_active" />Active</label>
                    </div>
                </FormSection>
                <PricingRuleConditionForm />
                <div className="flex justify-end gap-3"><Link to="/pricing/rules"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Rule' : 'Create Rule'}</Button></div>
            </div>
            <PreviewPanel rows={[{ label: 'Rule priority', value: 'Backend-owned' }, { label: 'Discount calculation', value: 'Backend-owned' }, { label: 'Tier application', value: 'Backend-owned' }]} title="Rule backend ownership" />
        </form>
    );
}

export function PriceResolverForm() {
    const [result, setResult] = useState<PriceResolveResult>();
    const [isLoading, setIsLoading] = useState(false);
    const [formError, setFormError] = useState('');

    function buildInput(form: HTMLFormElement): PriceResolveRequest {
        const formData = new FormData(form);
        return {
            currency: String(formData.get('currency') ?? 'LKR'),
            customerId: String(formData.get('customer_id') ?? '') || undefined,
            date: String(formData.get('date') ?? ''),
            itemId: String(formData.get('item_id') ?? ''),
            moduleSource: String(formData.get('module_source') ?? 'sales') as PricingModuleScope,
            priceListId: String(formData.get('price_list_id') ?? '') || undefined,
            quantity: String(formData.get('quantity') ?? '1'),
            supplierId: String(formData.get('supplier_id') ?? '') || undefined,
            uomId: String(formData.get('uom_id') ?? ''),
        };
    }

    async function handlePreview(event: MouseEvent<HTMLButtonElement>) {
        event.preventDefault();
        const form = event.currentTarget.closest('form');
        if (!form) {
            return;
        }

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
                <FormSection description="Submit transaction context to backend/mock resolver. The result panel is readonly." title="Price Resolver Inputs">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2"><FieldLabel>Module/source</FieldLabel><Select name="module_source" options={[{ label: 'Sales', value: 'sales' }, { label: 'Purchase', value: 'purchase' }, { label: 'Vehicle Service', value: 'vehicle_service' }, { label: 'Vehicle Rental', value: 'vehicle_rental' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Item</FieldLabel><Input name="item_id" placeholder="Item id / selector placeholder" /></div>
                        <div className="space-y-2"><FieldLabel>UOM</FieldLabel><Input name="uom_id" placeholder="UOM id / selector placeholder" /></div>
                        <div className="space-y-2"><FieldLabel>Customer optional</FieldLabel><Input name="customer_id" placeholder="Customer id" /></div>
                        <div className="space-y-2"><FieldLabel>Supplier optional</FieldLabel><Input name="supplier_id" placeholder="Supplier id" /></div>
                        <div className="space-y-2"><FieldLabel>Quantity</FieldLabel><Input defaultValue="1" name="quantity" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Date</FieldLabel><Input defaultValue="2026-05-29" name="date" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Currency</FieldLabel><Input defaultValue="LKR" name="currency" /></div>
                        <div className="space-y-2"><FieldLabel>Price list optional</FieldLabel><Select name="price_list_id" options={priceLists.map((list) => ({ label: list.name, value: list.id }))} placeholder="Backend can choose" /></div>
                    </div>
                </FormSection>
                <div className="flex justify-end"><Button disabled={isLoading} onClick={handlePreview} type="button" variant="blue">{isLoading ? 'Resolving...' : 'Resolve Price Preview'}</Button></div>
            </div>
            <PriceResolverResultPanel result={result} />
        </form>
    );
}

export function PriceListRowActions({ priceList }: { priceList: PriceList }) {
    return (
        <div className="flex items-center gap-3 text-sm font-semibold">
            <Link className="text-slate-950" to={`/pricing/price-lists/${priceList.id}`}>View</Link>
            <Link className="text-slate-600" to={`/pricing/price-lists/${priceList.id}/edit`}>Edit</Link>
        </div>
    );
}

export function PricingRuleRowActions({ rule }: { rule: PricingRule }) {
    return (
        <div className="flex items-center gap-3 text-sm font-semibold">
            <Link className="text-slate-950" to={`/pricing/rules/${rule.id}`}>View</Link>
            <Link className="text-slate-600" to={`/pricing/rules/${rule.id}/edit`}>Edit</Link>
        </div>
    );
}

export function DiscountCardGrid({ rows }: { rows: Discount[] }) {
    return (
        <div className="grid gap-4 lg:grid-cols-2">
            {rows.map((discount) => (
                <Card className="p-5" key={discount.id}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{discount.code}</p>
                            <h3 className="mt-1 font-bold text-slate-950">{discount.name}</h3>
                            <p className="mt-2 text-sm text-slate-500">{discount.discountType} discount, value stored for backend calculation.</p>
                        </div>
                        <StatusBadge status={discount.status} />
                    </div>
                    <PreviewPanel
                        rows={[
                            { label: 'Value', value: discount.discountValue },
                            { label: 'Priority', value: discount.priority },
                            { label: 'Stacking', value: discount.isExclusive ? 'Exclusive' : discount.isStackable ? 'Stackable' : 'Single' },
                        ]}
                        title="Discount setup"
                    />
                </Card>
            ))}
        </div>
    );
}
