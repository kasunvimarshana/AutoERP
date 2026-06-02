import { FormEvent, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { itemApi } from '../services/itemApi';
import type { ComboComponentInput, Item, ItemBrand, ItemCategory, ItemFormInput, ItemLookupOption, ItemStatus, ItemType, ItemTypeOption, ItemTypeSetupPreview, UomOption } from '../types/item.types';
import { ItemCapabilityPanel } from './ItemPanels';

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

function clientKey() {
    return globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`;
}

export function ItemForm({ item, mode }: { item?: Item; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [lookups, setLookups] = useState<FormLookupState>({ accounts: [], brands: [], categories: [], componentItems: [], itemTypes: [], taxGroups: [], uoms: [] });
    const [lookupError, setLookupError] = useState('');
    const [lookupsLoading, setLookupsLoading] = useState(true);
    const [lookupLoading, setLookupLoading] = useState('');
    const [selectedType, setSelectedType] = useState<ItemType>(item?.itemType ?? 'inventory_product');
    const [comboItems, setComboItems] = useState<ComboComponentInput[]>([]);
    const [baseUomId, setBaseUomId] = useState(item?.baseUomId ?? '');
    const [behavior, setBehavior] = useState({
        chargeable: item?.allowChargeUsage ?? false,
        consumable: item?.allowConsumptionUsage ?? false,
        issuable: item?.allowIssueUsage ?? true,
        purchasable: item?.allowReceiptUsage ?? true,
        stockable: item?.isStockable ?? false,
        trackBatch: item?.isBatchTracked ?? false,
        trackSerial: item?.isSerialTracked ?? false,
    });
    const [setupPreview, setSetupPreview] = useState<ItemTypeSetupPreview | null>(null);
    const [setupPreviewError, setSetupPreviewError] = useState('');

    useEffect(() => {
        let mounted = true;
        Promise.all([
            itemApi.listItemTypes(),
            itemApi.listUoms(),
        ])
            .then(([itemTypes, uoms]) => {
                if (mounted) {
                    setLookups({
                        accounts: Array.from(new Map([
                            ...(item?.incomeAccountId ? [{ id: item.incomeAccountId, label: item.incomeAccount ?? 'Current income account', name: item.incomeAccount ?? 'Current income account' }] : []),
                            ...(item?.expenseAccountId ? [{ id: item.expenseAccountId, label: item.expenseAccount ?? 'Current expense account', name: item.expenseAccount ?? 'Current expense account' }] : []),
                            ...(item?.inventoryAccountId ? [{ id: item.inventoryAccountId, label: item.inventoryAccount ?? 'Current inventory account', name: item.inventoryAccount ?? 'Current inventory account' }] : []),
                            ...(item?.cogsAccountId ? [{ id: item.cogsAccountId, label: item.cogsAccount ?? 'Current COGS account', name: item.cogsAccount ?? 'Current COGS account' }] : []),
                        ].map((account) => [account.id, account] as const)).values()),
                        brands: item?.brandId ? [{ id: item.brandId, isActive: true, name: item.brand ?? 'Current brand' }] : [],
                        categories: item?.categoryId ? [{ code: '', id: item.categoryId, isActive: true, name: item.category, status: 'active' }] : [],
                        componentItems: [],
                        itemTypes: itemTypes.data,
                        taxGroups: item?.taxGroupId ? [{ id: item.taxGroupId, label: 'Current tax group', name: 'Current tax group' }] : [],
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
    }, [item]);

    async function loadLookup(kind: keyof Pick<FormLookupState, 'accounts' | 'brands' | 'categories' | 'componentItems' | 'taxGroups'>) {
        if (lookups[kind].length > 0 && kind !== 'componentItems') {
            return;
        }

        setLookupLoading(kind);
        setLookupError('');

        try {
            const response = await ({
                accounts: () => itemApi.listFinanceAccounts(),
                brands: () => itemApi.listBrands(),
                categories: () => itemApi.listCategories(),
                componentItems: () => itemApi.lookupItems({ perPage: 25, status: 'active' }),
                taxGroups: () => itemApi.listTaxGroups(),
            }[kind])();

            setLookups((current) => ({ ...current, [kind]: response.data }));
        } catch (error) {
            setLookupError(error instanceof Error ? error.message : `Unable to load ${kind}.`);
        } finally {
            setLookupLoading('');
        }
    }

    useEffect(() => {
        if (!item || item.itemType !== 'combo') {
            return;
        }

        let mounted = true;
        itemApi.listComboComponents(item.id)
            .then((response) => {
                if (mounted) {
                    setComboItems(response.data.map((component) => ({
                        clientKey: component.id || clientKey(),
                        componentItemId: component.componentItemId ?? '',
                        quantity: component.quantity,
                        uomId: component.uomId ?? item.baseUomId ?? '',
                    })));
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setFormError(error instanceof Error ? error.message : 'Unable to load combo components.');
                    setComboItems([]);
                }
            });

        return () => {
            mounted = false;
        };
    }, [item]);

    const selectedTypeDefaults = useMemo(() => typeDefaults(selectedType, lookups.itemTypes), [lookups.itemTypes, selectedType]);
    const serviceDefault = ['external_service', 'labour', 'service'].includes(selectedType);
    const chargeDefault = Boolean(selectedTypeDefaults?.isChargeable || serviceDefault || selectedType === 'rental_charge');

    useEffect(() => {
        if (item) {
            return;
        }

        setBehavior((current) => ({
            ...current,
            chargeable: chargeDefault,
            consumable: Boolean(selectedTypeDefaults?.isStockable),
            stockable: Boolean(selectedTypeDefaults?.isStockable),
        }));
    }, [chargeDefault, item, selectedTypeDefaults]);

    useEffect(() => {
        if (!baseUomId && lookups.uoms[0]?.id) {
            setBaseUomId(lookups.uoms[0].id);
        }
    }, [baseUomId, lookups.uoms]);

    useEffect(() => {
        if (!lookups.itemTypes.length) {
            return;
        }

        let mounted = true;
        const timeout = window.setTimeout(() => {
        setSetupPreviewError('');

        itemApi.getTypeSetupPreview({
            allowChargeUsage: behavior.chargeable,
            allowConsumptionUsage: behavior.consumable,
            allowIssueUsage: behavior.issuable,
            allowReceiptUsage: behavior.purchasable,
            baseUomId,
            comboItems,
            itemType: selectedType,
            itemTypeId: lookups.itemTypes.find((option) => option.value === selectedType)?.id ?? item?.itemTypeId ?? '',
            stockable: behavior.stockable,
            trackBatch: behavior.trackBatch,
            trackSerial: behavior.trackSerial,
        })
            .then((response) => {
                if (mounted) {
                    setSetupPreview(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setSetupPreviewError(error instanceof Error ? error.message : 'Unable to preview item setup.');
                }
            });
        }, 300);

        return () => {
            mounted = false;
            window.clearTimeout(timeout);
        };
    }, [baseUomId, behavior, comboItems, item?.itemTypeId, lookups.itemTypes, selectedType]);

    function addComboItem() {
        if (!lookups.componentItems.length) {
            void loadLookup('componentItems');
        }

        setComboItems((rows) => [...rows, { clientKey: clientKey(), componentItemId: '', quantity: '1', uomId: item?.baseUomId ?? lookups.uoms[0]?.id ?? '' }]);
    }

    function updateComboItem(index: number, field: keyof ComboComponentInput, value: string) {
        setComboItems((rows) => rows.map((row, rowIndex) => rowIndex === index ? { ...row, [field]: value } : row));
    }

    function removeComboItem(index: number) {
        setComboItems((rows) => rows.filter((_, rowIndex) => rowIndex !== index));
    }

    function updateBehavior(field: keyof typeof behavior, value: boolean) {
        setBehavior((current) => ({ ...current, [field]: value }));
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
            leadTimeDays: stringValue(formData, 'lead_time_days'),
            maximumStock: stringValue(formData, 'maximum_stock'),
            minimumStock: stringValue(formData, 'minimum_stock'),
            reorderPoint: stringValue(formData, 'reorder_point'),
            reorderQuantity: stringValue(formData, 'reorder_quantity'),
            safetyStock: stringValue(formData, 'safety_stock'),
            standardCost: stringValue(formData, 'standard_cost'),
            status: stringValue(formData, 'status') as ItemStatus,
            stockable: checkbox(formData, 'is_stockable'),
            taxGroupId: stringValue(formData, 'tax_group_id'),
            trackBatch: checkbox(formData, 'is_batch_tracked'),
            trackSerial: checkbox(formData, 'is_serial_tracked'),
            valuationMethod: stringValue(formData, 'valuation_method'),
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
                                onFocus={() => void loadLookup('categories')}
                                onMouseDown={() => void loadLookup('categories')}
                                options={lookups.categories.map((category) => ({ label: category.name, value: category.id }))}
                                placeholder={lookupLoading === 'categories' ? 'Loading categories...' : 'Select category'}
                            />
                            <FieldError message={fieldMessage(errors, 'category_id')} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Brand</FieldLabel>
                            <Select
                                defaultValue={item?.brandId ?? ''}
                                name="brand_id"
                                onFocus={() => void loadLookup('brands')}
                                onMouseDown={() => void loadLookup('brands')}
                                options={lookups.brands.map((brand) => ({ label: brand.name, value: brand.id }))}
                                placeholder={lookupLoading === 'brands' ? 'Loading brands...' : 'Select brand'}
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
                            ['is_stockable', 'Stockable / affects inventory setup', 'stockable'],
                            ['allow_receipt_usage', 'Receipt usage allowed', 'purchasable'],
                            ['allow_issue_usage', 'Issue / sale usage allowed', 'issuable'],
                            ['allow_consumption_usage', 'Consumption usage allowed', 'consumable'],
                            ['allow_charge_usage', 'Charge / billing usage allowed', 'chargeable'],
                            ['is_batch_tracked', 'Track batch', 'trackBatch'],
                            ['is_serial_tracked', 'Track serial', 'trackSerial'],
                        ].map(([name, label, behaviorField]) => (
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={String(name)}>
                                <Checkbox
                                    checked={behavior[behaviorField as keyof typeof behavior]}
                                    name={String(name)}
                                    onChange={(event) => updateBehavior(behaviorField as keyof typeof behavior, event.target.checked)}
                                />
                                {label}
                            </label>
                        ))}
                    </div>
                </FormSection>
                <FormSection description="Select default units. Conversion validity is checked by the backend; the frontend does not convert quantities." title="UOM Defaults">
                    <div className="grid gap-4 md:grid-cols-2">
                        {[
                            ['base_uom_id', 'Base UOM', baseUomId || lookups.uoms[0]?.id || '', true],
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
                                    onChange={name === 'base_uom_id' ? (event) => setBaseUomId(event.target.value) : undefined}
                                    options={lookups.uoms.map((uom) => ({ label: uom.label, value: uom.id }))}
                                    placeholder={required ? undefined : 'Not configured'}
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
                            <Select defaultValue={item?.taxGroupId ?? ''} name="tax_group_id" onFocus={() => void loadLookup('taxGroups')} onMouseDown={() => void loadLookup('taxGroups')} options={lookups.taxGroups.map((taxGroup) => ({ label: taxGroup.label, value: taxGroup.id }))} placeholder={lookupLoading === 'taxGroups' ? 'Loading tax groups...' : 'No tax group'} />
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
                                <Select defaultValue={String(defaultValue ?? '')} name={String(name)} onFocus={() => void loadLookup('accounts')} onMouseDown={() => void loadLookup('accounts')} options={lookups.accounts.map((account) => ({ label: account.label, value: account.id }))} placeholder={lookupLoading === 'accounts' ? 'Loading accounts...' : 'No account default'} />
                                <FieldError message={fieldMessage(errors, String(name))} />
                            </div>
                        ))}
                    </div>
                </FormSection>
                <FormSection description="Type-specific setup is validated by the Item API and saved on the Item record." title="Type-Specific Setup">
                    {selectedType === 'inventory_product' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <FieldLabel>Valuation method</FieldLabel>
                                <Select defaultValue={item?.valuationMethod ?? 'weighted_average'} name="valuation_method" options={[
                                    { label: 'Weighted Average', value: 'weighted_average' },
                                    { label: 'FIFO', value: 'fifo' },
                                    { label: 'Standard Cost', value: 'standard_cost' },
                                    { label: 'Specific Identification', value: 'specific_identification' },
                                ]} />
                            </div>
                            <div className="space-y-2">
                                <FieldLabel>Standard cost</FieldLabel>
                                <Input defaultValue={item?.standardCost ?? ''} min="0" name="standard_cost" step="0.0001" type="number" />
                            </div>
                            {[
                                ['minimum_stock', 'Minimum stock', item?.minimumStock ?? '0'],
                                ['maximum_stock', 'Maximum stock', item?.maximumStock ?? ''],
                                ['reorder_point', 'Reorder point', item?.reorderPoint ?? '0'],
                                ['reorder_quantity', 'Reorder quantity', item?.reorderQuantity ?? ''],
                                ['safety_stock', 'Safety stock', item?.safetyStock ?? '0'],
                                ['lead_time_days', 'Lead time days', item?.leadTimeDays ?? '0'],
                            ].map(([name, label, defaultValue]) => (
                                <div className="space-y-2" key={String(name)}>
                                    <FieldLabel>{String(label)}</FieldLabel>
                                    <Input defaultValue={String(defaultValue)} min="0" name={String(name)} step={name === 'lead_time_days' ? '1' : '0.0001'} type="number" />
                                    <FieldError message={fieldMessage(errors, String(name))} />
                                </div>
                            ))}
                        </div>
                    ) : null}
                    {['service', 'external_service'].includes(selectedType) ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.chargeable} name="service_chargeable_display" onChange={(event) => updateBehavior('chargeable', event.target.checked)} />
                                Chargeable service setup
                            </label>
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.issuable} name="service_sellable_display" onChange={(event) => updateBehavior('issuable', event.target.checked)} />
                                Available for sales/service documents
                            </label>
                        </div>
                    ) : null}
                    {selectedType === 'labour' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.chargeable} name="labour_chargeable_display" onChange={(event) => updateBehavior('chargeable', event.target.checked)} />
                                Chargeable labour setup
                            </label>
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.consumable} name="labour_consumption_display" onChange={(event) => updateBehavior('consumable', event.target.checked)} />
                                Usable on service consumption lines
                            </label>
                        </div>
                    ) : null}
                    {selectedType === 'non_inventory' || selectedType === 'customer_supplied' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.purchasable} name="non_inventory_purchasable_display" onChange={(event) => updateBehavior('purchasable', event.target.checked)} />
                                Purchasable non-stock setup
                            </label>
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.issuable} name="non_inventory_sellable_display" onChange={(event) => updateBehavior('issuable', event.target.checked)} />
                                Sellable non-stock setup
                            </label>
                        </div>
                    ) : null}
                    {selectedType === 'rental_charge' ? (
                        <div className="grid gap-4 md:grid-cols-2">
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                <Checkbox checked={behavior.chargeable} name="rental_chargeable_display" onChange={(event) => updateBehavior('chargeable', event.target.checked)} />
                                Rental charge enabled
                            </label>
                            <div className="space-y-2">
                                <FieldLabel>Rental billing unit</FieldLabel>
                                <Select
                                    defaultValue={item?.defaultChargeUomId ?? baseUomId}
                                    name="rental_charge_uom_display"
                                    onChange={(event) => {
                                        const hidden = document.querySelector<HTMLSelectElement>('select[name="default_charge_uom_id"]');
                                        if (hidden) hidden.value = event.target.value;
                                    }}
                                    options={lookups.uoms.map((uom) => ({ label: uom.label, value: uom.id }))}
                                />
                            </div>
                        </div>
                    ) : null}
                    {selectedType === 'combo' ? (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-slate-600">Add component references. Backend validates components, circular links, quantities, and UOMs.</p>
                                <Button onClick={addComboItem} variant="secondary">Add Component</Button>
                            </div>
                            {!comboItems.length ? <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No combo components added.</div> : null}
                            {comboItems.map((row, index) => (
                                <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_120px_160px_auto]" key={row.clientKey}>
                                    <Select onChange={(event) => updateComboItem(index, 'componentItemId', event.target.value)} onFocus={() => void loadLookup('componentItems')} onMouseDown={() => void loadLookup('componentItems')} options={lookups.componentItems.filter((component) => component.id !== item?.id).map((component) => ({ label: `${component.code} - ${component.name}`, value: component.id }))} placeholder={lookupLoading === 'componentItems' ? 'Loading items...' : 'Component item'} value={row.componentItemId} />
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
            <div className="space-y-4">
                {setupPreview ? <ItemCapabilityPanel capabilities={setupPreview.capabilities} /> : null}
                {setupPreviewError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{setupPreviewError}</div> : null}
                {setupPreview?.warnings.length ? (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h3 className="text-sm font-bold text-amber-900">Setup warnings</h3>
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
                            {setupPreview.warnings.map((warning) => <li key={warning}>{warning}</li>)}
                        </ul>
                    </div>
                ) : null}
            </div>
        </form>
    );
}
