import { FormEvent, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { itemApi } from '../services/itemApi';
import type { Item, ItemFormInput, ItemStatus, ItemType } from '../types/item.types';

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checkbox(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

export function ItemForm({ item, mode }: { item?: Item; mode: 'create' | 'edit' }) {
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
        const input: ItemFormInput = {
            allowPurchase: checkbox(formData, 'allow_purchase'),
            allowRentalUsage: checkbox(formData, 'allow_rental_usage'),
            allowSales: checkbox(formData, 'allow_sales'),
            allowServiceUsage: checkbox(formData, 'allow_service_usage'),
            baseUomId: String(formData.get('base_uom_id') ?? '1'),
            brand: String(formData.get('brand') ?? ''),
            category: String(formData.get('category') ?? ''),
            code: String(formData.get('sku') ?? ''),
            description: String(formData.get('description') ?? ''),
            displayName: String(formData.get('display_name') ?? ''),
            itemType: String(formData.get('type') ?? 'inventory_product') as ItemType,
            name: String(formData.get('name') ?? ''),
            status: String(formData.get('status') ?? 'active') as ItemStatus,
            stockable: checkbox(formData, 'is_stockable'),
            trackBatch: checkbox(formData, 'is_batch_tracked'),
            trackSerial: checkbox(formData, 'is_serial_tracked'),
        };

        try {
            const response = mode === 'edit' && item ? await itemApi.updateItem(item.id, input) : await itemApi.createItem(input);
            navigate(`/items/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save item.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    const isStockable = item?.stockBehavior === 'stock_tracked';

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection description="Items can be products, services, labour, bundles, rental charges, and non-stock references." title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <FieldLabel>Item code / SKU</FieldLabel>
                            <Input defaultValue={item?.code} name="sku" placeholder="ITM-0001" />
                            <FieldError message={errors.sku?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Item name</FieldLabel>
                            <Input defaultValue={item?.name} name="name" placeholder="Item, service, labour, or charge name" />
                            <FieldError message={errors.name?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Display name</FieldLabel>
                            <Input defaultValue={item?.displayName} name="display_name" placeholder="Display name" />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Item type</FieldLabel>
                            <Select defaultValue={item?.itemType ?? 'inventory_product'} name="type" options={[
                                { label: 'Inventory Product', value: 'inventory_product' },
                                { label: 'Service Item', value: 'service' },
                                { label: 'Labour Item', value: 'labour' },
                                { label: 'Combo / Bundle', value: 'combo' },
                                { label: 'Non-Inventory Item', value: 'non_inventory' },
                                { label: 'Customer-Supplied Reference', value: 'customer_supplied' },
                                { label: 'External Service', value: 'external_service' },
                                { label: 'Rental Charge', value: 'rental_charge' },
                                { label: 'Fee / Adjustment', value: 'fee_adjustment' },
                            ]} />
                            <FieldError message={errors.type?.[0]} />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Category</FieldLabel>
                            <Select defaultValue={item?.category} name="category" options={[
                                { label: 'Spare Parts', value: 'Spare Parts' },
                                { label: 'Services', value: 'Services' },
                                { label: 'Rental Charges', value: 'Rental Charges' },
                            ]} placeholder="Select category" />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Brand</FieldLabel>
                            <Input defaultValue={item?.brand} name="brand" placeholder="Optional brand" />
                        </div>
                        <div className="space-y-2">
                            <FieldLabel>Status</FieldLabel>
                            <Select defaultValue={item?.status ?? 'active'} name="status" options={[
                                { label: 'Active', value: 'active' },
                                { label: 'Draft', value: 'draft' },
                                { label: 'Inactive', value: 'inactive' },
                            ]} />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <FieldLabel>Description</FieldLabel>
                            <Textarea defaultValue={item?.description} name="description" placeholder="Operational notes and item usage guidance." />
                        </div>
                    </div>
                </FormSection>
                <FormSection description="Flags describe intended behavior. Backend remains authoritative for stock movements, UOM conversion, pricing, tax, cost, and accounts." title="Stock Behavior, Usage, and UOM">
                    <div className="grid gap-4 md:grid-cols-2">
                        {[
                            ['is_stockable', 'Stockable / affects inventory', isStockable],
                            ['allow_purchase', 'Allow purchase', true],
                            ['allow_sales', 'Allow sales', true],
                            ['allow_service_usage', 'Allow service usage', item?.itemType !== 'rental_charge'],
                            ['allow_rental_usage', 'Allow rental usage', item?.itemType === 'rental_charge'],
                            ['is_batch_tracked', 'Track batch', false],
                            ['is_serial_tracked', 'Track serial', false],
                        ].map(([name, label, defaultChecked]) => (
                            <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700" key={String(name)}>
                                <Checkbox defaultChecked={Boolean(defaultChecked)} name={String(name)} />
                                {label}
                            </label>
                        ))}
                        <div className="space-y-2">
                            <FieldLabel>Base UOM</FieldLabel>
                            <Select defaultValue="1" name="base_uom_id" options={[
                                { label: 'pcs', value: '1' },
                                { label: 'service', value: '2' },
                                { label: 'hour', value: '3' },
                                { label: 'bundle', value: '4' },
                                { label: 'day', value: '5' },
                            ]} />
                            <FieldError message={errors.base_uom_id?.[0]} />
                        </div>
                    </div>
                </FormSection>
                <FormSection description="Collected references only. Backend validates tax groups and finance accounts." title="Tax / Finance Defaults and Type-Specific Setup">
                    <div className="grid gap-4 md:grid-cols-2">
                        {['Tax Group', 'Income Account', 'Expense Account', 'Inventory Account', 'COGS Account', 'Combo Components / Variants / Identifiers'].map((label) => (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                <p className="text-sm font-semibold text-slate-900">{label}</p>
                                <p className="mt-1 text-sm text-slate-500">Backend validated after item save.</p>
                            </div>
                        ))}
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3">
                    <Link to="/items"><Button variant="secondary">Cancel</Button></Link>
                    <Button disabled={isSubmitting}>Save Draft</Button>
                    <Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Item' : 'Create Item'}</Button>
                </div>
            </div>
            <PreviewPanel rows={[
                { label: 'Stock availability', value: 'Backend-owned' },
                { label: 'UOM conversion', value: 'Backend-owned' },
                { label: 'Pricing / tax / cost', value: 'Backend-owned' },
                { label: 'Combo expansion', value: 'Backend-owned' },
            ]} title="Item validation preview" />
        </form>
    );
}

export function ItemCategoryForm() { return null; }
export function ItemAttributeForm() { return null; }
export function ItemVariantForm() { return null; }
export function ItemComboComponentForm() { return null; }
