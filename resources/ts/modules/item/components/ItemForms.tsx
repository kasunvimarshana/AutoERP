import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { itemApi } from '../services/itemApi';
import type { ComboComponentInput, Item, ItemBrand, ItemCategory, ItemFormInput, ItemLookupOption, ItemStatus, ItemType, ItemTypeOption, UomOption } from '../types/item.types';

type FormLookupState = {
    accounts: ItemLookupOption[];
    brands: ItemBrand[];
    categories: ItemCategory[];
    componentItems: Item[];
    itemTypes: ItemTypeOption[];
    taxGroups: ItemLookupOption[];
    uoms: UomOption[];
};

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checkbox(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

function stringValue(formData: FormData, name: string) {
    return String(formData.get(name) ?? '').trim();
}

function typeDefaults(type: ItemType, itemTypes: ItemTypeOption[]) {
    return itemTypes.find((option) => option.value === type);
}

function fieldMessage(errors: Record<string, string[]>, field: string) {
    return errors[field]?.[0];
}

export function ItemForm({ item, mode }: { item?: Item; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [lookups, setLookups] = useState<FormLookupState>({ accounts: [], brands: [], categories: [], componentItems: [], itemTypes: [], taxGroups: [], uoms: [] });
    const [lookupError, setLookupError] = useState('');
    const [lookupsLoading, setLookupsLoading] = useState(true);
    const [selectedType, setSelectedType] = useState<ItemType>(item?.itemType ?? 'inventory_product');
    const [comboItems, setComboItems] = useState<ComboComponentInput[]>([]);

    useEffect(() => {
        let mounted = true;
        Promise.all([
            itemApi.listCategories(),
            itemApi.listBrands(),
            itemApi.listItemTypes(),
            itemApi.listUoms(),
            itemApi.listFinanceAccounts(),
            itemApi.listTaxGroups(),
            itemApi.listItems({ perPage: 200 }),
        ])
            .then(([categories, brands, itemTypes, uoms, accounts, taxGroups, componentItems]) => {
                if (mounted) {
                    setLookups({
                        accounts: accounts.data,
                        brands: brands.data,
                        categories: categories.data,
                        componentItems: componentItems.data,
                        itemTypes: itemTypes.data,
                        taxGroups: taxGroups.data,
                        uoms: uoms.data,
                    });
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setLookupError(error instanceof Error ? error.message : 'Unable to load Item setup lookups.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setLookupsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, []);

    const selectedTypeDefaults = useMemo(() => typeDefaults(selectedType, lookups.itemTypes), [lookups.itemTypes, selectedType]);
    const stockableDefault = item?.isStockable ?? Boolean(selectedTypeDefaults?.isStockable);
    const serviceDefault = ['external_service', 'labour', 'service'].includes(selectedType);
    const chargeDefault = item?.allowChargeUsage ?? Boolean(selectedTypeDefaults?.isChargeable || serviceDefault || selectedType === 'rental_charge');

    function addComboItem() {
        setComboItems((rows) => [...rows, { componentItemId: '', quantity: '1', uomId: item?.baseUomId ?? lookups.uoms[0]?.id ?? '' }]);
    }

    function updateComboItem(index: number, field: keyof ComboComponentInput, value: string) {
        setComboItems((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: value } : row));
    }

    function removeComboItem(index: number) {
        setComboItems((rows) => rows.filter((_, rowIndex) => rowIndex !== index));
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: ItemFormInput = {
            allowChargeUsage: checkbox(formData, 'allow_charge_usage'),
            allowConsumptionUsage: checkbox(formData, 'allow_consumption_usage'),
            allowIssueUsage: checkbox(formData, 'allow_issue_usage'),
            allowReceiptUsage: checkbox(formData, 'allow_receipt_usage'),
            barcode: stringValue(formData, 'barcode'),
            baseUomId: stringValue(formData, 'base_uom_id'),
            brandId: stringValue(formData, 'brand_id'),
            categoryId: stringValue(formData, 'category_id'),
            cogsAccountId: stringValue(formData, 'cogs_account_id'),
            code: stringValue(formData, 'sku'),
            comboItems,
            defaultChargeUomId: stringValue(formData, 'default_charge_uom_id'),
            defaultConsumptionUomId: stringValue(formData, 'default_consumption_uom_id'),
            defaultIssueUomId: stringValue(formData, 'default_issue_uom_id'),
            defaultReceiptUomId: stringValue(formData, 'default_receipt_uom_id'),
            description: stringValue(formData, 'description'),
            displayName: stringValue(formData, 'display_name'),
            expenseAccountId: stringValue(formData, 'expense_account_id'),
            incomeAccountId: stringValue(formData, 'income_account_id'),
            inventoryAccountId: stringValue(formData, 'inventory_account_id'),
            itemType: stringValue(formData, 'type') as ItemType,
            itemTypeId: stringValue(formData, 'item_type_id'),
            name: stringValue(formData, 'name'),
            status: stringValue(formData, 'status') as ItemStatus,
            stockable: checkbox(formData, 'is_stockable'),
            taxGroupId: stringValue(formData, 'tax_group_id'),
            trackBatch: checkbox(formData, 'is_batch_tracked'),
            trackSerial: checkbox(formData, 'is_serial_tracked'),
        };

        try {
            const response = mode === 'edit' && item
                ? await itemApi.updateItem(item.id, input)
                : await itemApi.createItem(input);
            navigate(`/items/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError(error instanceof Error ? error.message : 'Unable to save item.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    if (lookupsLoading) {
        return <EmptyState description="Loading item categories, brands, item types, and units of measure..." title="Loading item setup" />;
    }

    if (lookupError) {
        return <EmptyState description={lookupError} title="Unable to load item setup" />;
    }

    if (!lookups.uoms.length) {
        return <EmptyState description="Create at least one unit of measure before creating items." title="No units of measure available" />;
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Define reusable item, service, labour, bundle, rental charge, and non-stock master data." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <FieldLabel>Item code / SKU</FieldLabel>
                            <Input defaultValue={item?.code} name="sku" placeholder="ITM-0001" />
                            <FieldError message={fieldMessage(errors, 'sku')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Item name</FieldLabel>
                            <Input defaultValue={item?.name} name="name" placeholder="Item, service, labour, or charge name" />
                            <FieldError message={fieldMessage(errors, 'name')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Display name</FieldLabel>
                            <Input defaultValue={item?.displayName} name="display_name" placeholder="Optional display name" />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Barcode / identifier</FieldLabel>
                            <Input defaultValue={item?.barcode} name="barcode" placeholder="Optional barcode" />
                            <FieldError message={fieldMessage(errors, 'barcode')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Item type</FieldLabel>
                            <Select
                                defaultValue={item?.itemType ?? 'inventory_product'}
                                name="type"
                                onChange={(event) => setSelectedType(event.target.value as ItemType)}
                                options={lookups.itemTypes.map((option) => ({ label: option.label, value: option.value }))}
                            />
                            <FieldError message={fieldMessage(errors, 'type')} />
                        </div>
                        <input name="item_type_id" readOnly type="hidden" value={lookups.itemTypes.find((option) => option.value === selectedType)?.id ?? item?.itemTypeId ?? ''} />
                        <div className="space-y-2">
                            <FieldLabel>Category</FieldLabel>
                            <Select
                                defaultValue={item?.categoryId ?? ''}
                                name="category_id"
                                options={lookups.categories.map((category) => ({ label: category.name, value: category.id }))}
                                placeholder="Select category"
                            />
                            <FieldError message={fieldMessage(errors, 'category_id')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Brand</FieldLabel>
                            <Select
                                defaultValue={item?.brandId ?? ''}
                                name="brand_id"
                                options={lookups.brands.map((brand) => ({ label: brand.name, value: brand.id }))}
                                placeholder="Select brand"
                            />
                            <FieldError message={fieldMessage(errors, 'brand_id')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Status</FieldLabel>
                            <Select defaultValue={item?.status ?? 'active'} name="status" options={[
                                { label: 'Active', value: 'active' },
                                { label: 'Draft', value: 'draft' },
                                { label: 'Inactive', value: 'inactive' },
                                { label: 'Discontinued', value: 'discontinued' },
                            ]} />
                            <FieldError message={fieldMessage(errors, 'status')} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <FieldLabel>Description</FieldLabel>
                            <Textarea defaultValue={item?.description} name="description" placeholder="Operational notes and reusable item setup guidance." />
                        </div>
                    </div>
                </FormSection>
                <FormSection description="These are item setup flags only. Backend services remain authoritative for stock effects, UOM conversion, pricing, tax, and accounting." title="Usage / Behavior">
                    <div className="grid gap-4 md:grid-cols-2">
                        {[
                            ['is_stockable', 'Stockable / affects inventory setup', stockableDefault],
                            ['allow_receipt_usage', 'Receipt usage allowed', item?.allowReceiptUsage ?? true],
                            ['allow_issue_usage', 'Issue / sale usage allowed', item?.allowIssueUsage ?? true],
                            ['allow_consumption_usage', 'Consumption usage allowed', item?.allowConsumptionUsage ?? stockableDefault],
                            ['allow_charge_usage', 'Charge / billing usage allowed', chargeDefault],
                            ['is_batch_tracked', 'Track batch', item?.isBatchTracked ?? false],
                            ['is_serial_tracked', 'Track serial', item?.isSerialTracked ?? false],
                        ].map(([name, label, defaultChecked]) => (
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={String(name)}>
                                <Checkbox defaultChecked={Boolean(defaultChecked)} name={String(name)} />
                                {label}
                            </label>
                        ))}
                    </div>
                </FormSection>
                <FormSection description="Select default units. Conversion validity is checked by the backend; the frontend does not convert quantities." title="UOM Defaults">
                    <div className="grid gap-4 md:grid-cols-2">
                        {[
                            ['base_uom_id', 'Base UOM', item?.baseUomId ?? lookups.uoms[0]?.id ?? '', true],
                            ['default_receipt_uom_id', 'Purchase / receipt UOM', item?.defaultReceiptUomId ?? '', false],
                            ['default_issue_uom_id', 'Sales / issue UOM', item?.defaultIssueUomId ?? '', false],
                            ['default_consumption_uom_id', 'Service / consumption UOM', item?.defaultConsumptionUomId ?? '', false],
                            ['default_charge_uom_id', 'Charge / rental UOM', item?.defaultChargeUomId ?? '', false],
                        ].map(([name, label, defaultValue, required]) => (
                            <div className="space-y-2" key={String(name)}>
                                <FieldLabel>{String(label)}</FieldLabel>
                                <Select
                                    defaultValue={String(defaultValue)}
                                    name={String(name)}
                                    options={lookups.uoms.map((uom) => ({ label: uom.label, value: uom.id }))}
                                    placeholder={required ? undefined : 'Backend default / not set'}
                                />
                                <FieldError message={fieldMessage(errors, String(name))} />
                            </div>
                        ))}
                    </div>
                </FormSection>
                <FormSection description="Optional defaults are stored on the Item record. Finance and Tax modules remain responsible for posting and tax calculation." title="Tax / Finance Defaults">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <FieldLabel>Tax group</FieldLabel>
                            <Select defaultValue={item?.taxGroupId ?? ''} name="tax_group_id" options={lookups.taxGroups.map((taxGroup) => ({ label: taxGroup.label, value: taxGroup.id }))} placeholder="No tax group" />
                            <FieldError message={fieldMessage(errors, 'tax_group_id')} />
                        </div>
                        {[
                            ['income_account_id', 'Income account', item?.incomeAccountId],
                            ['expense_account_id', 'Expense account', item?.expenseAccountId],
                            ['inventory_account_id', 'Inventory account', item?.inventoryAccountId],
                            ['cogs_account_id', 'COGS account', item?.cogsAccountId],
                        ].map(([name, label, defaultValue]) => (
                            <div className="space-y-2" key={String(name)}>
                                <FieldLabel>{String(label)}</FieldLabel>
                                <Select defaultValue={String(defaultValue ?? '')} name={String(name)} options={lookups.accounts.map((account) => ({ label: account.label, value: account.id }))} placeholder="No account default" />
                                <FieldError message={fieldMessage(errors, String(name))} />
                            </div>
                        ))}
                    </div>
                </FormSection>
                <FormSection description="Type-specific setup is captured here; backend modules decide actual workflow effects." title="Type-Specific Setup">
                    {selectedType === 'inventory_product' ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Product item: stock flags, batch/serial tracking, UOM defaults, and inventory accounts are saved as setup. Stock quantity and valuation are backend-owned.</div>
                    ) : null}
                    {['service', 'external_service'].includes(selectedType) ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Service item: saved as non-stock service setup. Scheduling, pricing, and billing are handled by source modules and backend services.</div>
                    ) : null}
                    {selectedType === 'labour' ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Labour item: use this for reusable labour definitions. Technician assignment and incentive rules are not calculated here.</div>
                    ) : null}
                    {selectedType === 'non_inventory' || selectedType === 'customer_supplied' ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Reference/non-inventory item: no stock impact is calculated by the frontend.</div>
                    ) : null}
                    {selectedType === 'rental_charge' ? (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Rental charge item: stored as chargeable/rentable setup. Rental billing and provider payable previews are backend-owned.</div>
                    ) : null}
                    {selectedType === 'combo' ? (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-slate-600">Add component references. Backend validates components, circular links, quantities, and UOMs.</p>
                                <Button onClick={addComboItem} variant="secondary">Add Component</Button>
                            </div>
                            {!comboItems.length ? <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No combo components added.</div> : null}
                            {comboItems.map((row, index) => (
                                <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_120px_160px_auto]" key={`${index}-${row.componentItemId}`}>
                                    <Select onChange={(event) => updateComboItem(index, 'componentItemId', event.target.value)} options={lookups.componentItems.filter((component) => component.id !== item?.id).map((component) => ({ label: `${component.code} - ${component.name}`, value: component.id }))} placeholder="Component item" value={row.componentItemId} />
                                    <Input min="0.0001" onChange={(event) => updateComboItem(index, 'quantity', event.target.value)} placeholder="Qty" step="0.0001" type="number" value={row.quantity} />
                                    <Select onChange={(event) => updateComboItem(index, 'uomId', event.target.value)} options={lookups.uoms.map((uom) => ({ label: uom.label, value: uom.id }))} value={row.uomId} />
                                    <Button onClick={() => removeComboItem(index)} variant="ghost">Remove</Button>
                                </div>
                            ))}
                            <FieldError message={fieldMessage(errors, 'combo_items')} />
                        </div>
                    ) : null}
                </FormSection>
                <div className="flex justify-end gap-3">
                    <Link to="/items"><Button variant="secondary">Cancel</Button></Link>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{isSubmitting ? 'Saving...' : mode === 'edit' ? 'Update Item' : 'Create Item'}</Button>
                </div>
            </div>
            <PreviewPanel rows={[
                { label: 'Stock availability', value: 'Backend-owned' },
                { label: 'UOM conversion', value: 'Backend-owned' },
                { label: 'Pricing / tax / cost', value: 'Backend-owned' },
                { label: 'Combo expansion', value: 'Backend-owned' },
            ]} title="Item responsibility boundary" />
        </form>
    );
}

export function ItemCategoryForm() { return null; }
export function ItemAttributeForm() { return null; }
export function ItemVariantForm() { return null; }
export function ItemComboComponentForm() { return null; }
