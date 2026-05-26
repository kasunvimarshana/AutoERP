import { useEffect, useMemo, useState, type CSSProperties } from 'react';
import { useFieldArray, useWatch, type UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import { ProductAutocomplete } from '../../products/components/ProductAutocomplete';
import type { ProductRecord, ProductVariant, UnitOfMeasure } from '../../products/types';
import type { SupplierRecord } from '../../suppliers/types';
import type { TaxGroupRecord } from '../../tax/types';
import type { WarehouseRecord } from '../../warehouse/types';
import type { PurchaseOrderFormInput, PurchaseOrderFormValues } from '../schemas';

type PurchaseOrderFormProps = {
    form: UseFormReturn<PurchaseOrderFormInput, unknown, PurchaseOrderFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    onSubmit: (values: PurchaseOrderFormValues) => void | Promise<void>;
    products: ProductRecord[];
    variants: ProductVariant[];
    suppliers: SupplierRecord[];
    taxGroups: TaxGroupRecord[];
    unitsOfMeasure: UnitOfMeasure[];
    warehouses: WarehouseRecord[];
    submitLabel?: string;
};

const lineGridStyle: CSSProperties = {
    gridTemplateColumns: 'minmax(280px,2fr) minmax(96px,0.7fr) minmax(220px,1.5fr) 108px 128px 130px 120px 120px 92px',
};

function toNumber(value: unknown) {
    const numeric = Number(value ?? 0);

    return Number.isFinite(numeric) ? numeric : 0;
}

function money(value: number) {
    return Math.round(value * 10000) / 10000;
}

function purchasePrice(product: ProductRecord) {
    return Number(product.metadata?.purchase_price ?? product.standard_cost ?? 0);
}

function lineAmounts(line: Partial<PurchaseOrderFormInput['lines'][number]> | undefined) {
    const gross = toNumber(line?.ordered_qty) * toNumber(line?.unit_price);
    const discountValue = toNumber(line?.discount_value);
    const discountAmount = line?.discount_type === 'fixed' ? discountValue : gross * (discountValue / 100);
    const lineTotal = Math.max(gross - discountAmount, 0);
    const taxAmount = toNumber(line?.tax_amount);

    return {
        gross: money(gross),
        discount: money(Math.min(discountAmount, gross)),
        net: money(lineTotal),
        tax: money(taxAmount),
        total: money(lineTotal + taxAmount),
    };
}

function headerDiscountAmount(type: unknown, value: unknown, base: number) {
    const numeric = toNumber(value);

    if (type === 'fixed') {
        return Math.min(numeric, base);
    }

    if (type === 'percentage') {
        return Math.min(base * (numeric / 100), base);
    }

    return 0;
}

function makeLineFromProduct(product: ProductRecord) {
    return {
        id: '',
        item_id: product.id,
        variant_id: '',
        description: product.description ?? '',
        uom_id: product.purchase_uom_id ?? product.base_uom_id ?? '',
        ordered_qty: '1',
        received_qty: '0',
        rejected_qty: '0',
        invoiced_qty: '0',
        unit_price: String(purchasePrice(product)),
        discount_type: '',
        discount_value: '0',
        tax_group_id: product.tax_group_id ?? '',
        tax_amount: '0',
        account_id: product.expense_account_id ?? product.inventory_account_id ?? '',
    };
}

function productName(product: ProductRecord | undefined, itemId: unknown) {
    if (product) {
        return product.sku ? `${product.name} (${product.sku})` : product.name;
    }

    return itemId ? `Item #${itemId}` : 'Item';
}

export function PurchaseOrderForm({
    form,
    formError = null,
    isSubmitting,
    onSubmit,
    products,
    variants,
    suppliers,
    taxGroups,
    warehouses,
    submitLabel = 'Save Purchase Order',
}: PurchaseOrderFormProps) {
    const [selectedProduct, setSelectedProduct] = useState<ProductRecord | null>(null);
    const [selectionMessage, setSelectionMessage] = useState<string | null>(null);
    const {
        control,
        formState: { errors },
        handleSubmit,
        register,
        setValue,
    } = form;
    const { append, fields, remove } = useFieldArray({ control, name: 'lines' });
    const watchedLines = useWatch({ control, name: 'lines' }) ?? [];
    const headerDiscountType = useWatch({ control, name: 'header_discount_type' });
    const headerDiscountValue = useWatch({ control, name: 'header_discount_value' });
    const lineTotals = useMemo(() => watchedLines.map((line) => lineAmounts(line)), [watchedLines]);
    const totals = useMemo(() => {
        const subtotal = money(lineTotals.reduce((total, line) => total + line.gross, 0));
        const lineDiscountTotal = money(lineTotals.reduce((total, line) => total + line.discount, 0));
        const lineTaxTotal = money(lineTotals.reduce((total, line) => total + line.tax, 0));
        const headerDiscount = money(headerDiscountAmount(headerDiscountType, headerDiscountValue, subtotal - lineDiscountTotal));
        const discountTotal = money(lineDiscountTotal + headerDiscount);
        const grandTotal = money(subtotal - discountTotal + lineTaxTotal);

        return { subtotal, lineDiscountTotal, lineTaxTotal, headerDiscount, discountTotal, grandTotal };
    }, [headerDiscountType, headerDiscountValue, lineTotals]);

    useEffect(() => {
        watchedLines.forEach((line, index) => {
            const amounts = lineAmounts(line);
            setValue(`lines.${index}.tax_amount`, amounts.tax, { shouldDirty: false, shouldValidate: false });
        });
    }, [setValue, watchedLines]);

    function addProductLine() {
        if (!selectedProduct) {
            setSelectionMessage('Select an item before adding it to the order.');
            return;
        }

        const existingIndex = watchedLines.findIndex((line) => Number(line?.item_id ?? 0) === selectedProduct.id);
        if (existingIndex >= 0) {
            setValue(`lines.${existingIndex}.ordered_qty`, String(toNumber(watchedLines[existingIndex]?.ordered_qty) + 1), {
                shouldDirty: true,
                shouldValidate: true,
            });
            setSelectedProduct(null);
            setSelectionMessage('Item already exists in this order, so the ordered quantity was increased.');
            return;
        }

        append(makeLineFromProduct(selectedProduct));
        setSelectedProduct(null);
        setSelectionMessage(null);
    }

    const lineArrayError = typeof errors.lines?.message === 'string' ? errors.lines.message : null;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Supplier, receiving warehouse, currency, dates, and header adjustments." title="Purchase order">
                    <FormGrid>
                        <FormField error={errors.supplier_id?.message} label="Supplier" required>
                            <Select error={errors.supplier_id?.message} {...register('supplier_id')}>
                                <option value="">Select supplier</option>
                                {suppliers.map((supplier) => (
                                    <option key={supplier.id} value={supplier.id}>{supplier.name ?? `Supplier #${supplier.id}`}</option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.warehouse_id?.message} label="Warehouse" required>
                            <Select error={errors.warehouse_id?.message} {...register('warehouse_id')}>
                                <option value="">Select warehouse</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>{warehouse.name}</option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.currency_id?.message} label="Currency ID">
                            <Input error={errors.currency_id?.message} placeholder="Optional" {...register('currency_id')} />
                        </FormField>
                        <FormField error={errors.po_number?.message} label="PO Number">
                            <Input error={errors.po_number?.message} placeholder="Auto if backend sequence is enabled" {...register('po_number')} />
                        </FormField>
                        <FormField error={errors.order_date?.message} label="Order Date" required>
                            <Input error={errors.order_date?.message} type="date" {...register('order_date')} />
                        </FormField>
                        <FormField error={errors.expected_date?.message} label="Expected Date">
                            <Input error={errors.expected_date?.message} type="date" {...register('expected_date')} />
                        </FormField>
                        <FormField error={errors.exchange_rate?.message} label="Exchange Rate">
                            <Input error={errors.exchange_rate?.message} placeholder="1.0000" {...register('exchange_rate')} />
                        </FormField>
                        <FormField error={errors.header_discount_type?.message} label="Header Discount">
                            <Select error={errors.header_discount_type?.message} {...register('header_discount_type')}>
                                <option value="">None</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.header_discount_value?.message} label="Header Discount Value">
                            <Input error={errors.header_discount_value?.message} min="0" step="0.01" type="number" {...register('header_discount_value')} />
                        </FormField>
                        <FormField error={errors.header_tax_group_id?.message} label="Header Tax Group">
                            <Select error={errors.header_tax_group_id?.message} {...register('header_tax_group_id')}>
                                <option value="">None</option>
                                {taxGroups.map((taxGroup) => (
                                    <option key={taxGroup.id} value={taxGroup.id}>{taxGroup.name}</option>
                                ))}
                            </Select>
                        </FormField>
                    </FormGrid>
                    <div className="mt-4">
                        <FormField error={errors.notes?.message} label="Notes">
                            <Input error={errors.notes?.message} placeholder="Supplier instructions or receiving notes" {...register('notes')} />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard description="Dynamic item lines with quantity, price, discount, tax group, and calculated totals." title="Line items">
                    {lineArrayError ? <p className="mb-4 text-sm text-red-600">{lineArrayError}</p> : null}
                    <FormField error={selectionMessage && !selectedProduct ? selectionMessage : undefined} label="Item Search">
                        <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                            <ProductAutocomplete
                                error={selectionMessage && !selectedProduct ? selectionMessage : undefined}
                                onChange={(product) => {
                                    setSelectedProduct(product);
                                    setSelectionMessage(null);
                                }}
                                products={products}
                                value={selectedProduct}
                            />
                            <Button disabled={!selectedProduct} onClick={addProductLine} type="button" variant="secondary">
                                Add Item
                            </Button>
                        </div>
                    </FormField>

                    <div className="mt-5 overflow-x-auto rounded-xl border border-stone-200">
                        <div className="min-w-[82rem]">
                            <div className="grid items-center gap-2 bg-stone-50 px-3 py-3 text-xs font-semibold uppercase text-stone-500" style={lineGridStyle}>
                                <span>Item</span>
                                <span>Variant</span>
                                <span>Description</span>
                                <span>Qty</span>
                                <span>Unit Price</span>
                                <span>Discount</span>
                                <span>Tax Group</span>
                                <span>Total</span>
                                <span>Remove</span>
                            </div>
                            {fields.map((field, index) => {
                                const itemId = Number(watchedLines[index]?.item_id ?? 0);
                                const product = products.find((item) => item.id === itemId);
                                const productVariants = variants.filter((variant) => variant.product_id === itemId);
                                const lineErrors = errors.lines?.[index];

                                return (
                                    <div key={field.id} className="grid items-start gap-2 border-t border-stone-200 bg-white px-3 py-3" style={lineGridStyle}>
                                        <div>
                                            <input type="hidden" {...register(`lines.${index}.item_id`)} />
                                            <div className="flex h-11 items-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-sm font-medium text-stone-900">
                                                <span className="truncate">{productName(product, watchedLines[index]?.item_id)}</span>
                                            </div>
                                            {lineErrors?.item_id?.message ? <p className="mt-1 text-xs text-red-600">{lineErrors.item_id.message}</p> : null}
                                        </div>
                                        <Select disabled={productVariants.length === 0} error={lineErrors?.variant_id?.message} {...register(`lines.${index}.variant_id`)}>
                                            <option value="">None</option>
                                            {productVariants.map((variant) => (
                                                <option key={variant.id} value={variant.id}>{variant.name}</option>
                                            ))}
                                        </Select>
                                        <Input error={lineErrors?.description?.message} {...register(`lines.${index}.description`)} />
                                        <input type="hidden" {...register(`lines.${index}.uom_id`)} />
                                        <Input error={lineErrors?.ordered_qty?.message} min="0.0001" step="0.0001" type="number" {...register(`lines.${index}.ordered_qty`)} />
                                        <Input error={lineErrors?.unit_price?.message} min="0" step="0.01" type="number" {...register(`lines.${index}.unit_price`)} />
                                        <div className="grid grid-cols-[1fr_0.8fr] gap-2">
                                            <Select {...register(`lines.${index}.discount_type`)}>
                                                <option value="">None</option>
                                                <option value="percentage">%</option>
                                                <option value="fixed">Fixed</option>
                                            </Select>
                                            <Input min="0" step="0.01" type="number" {...register(`lines.${index}.discount_value`)} />
                                        </div>
                                        <Select error={lineErrors?.tax_group_id?.message} {...register(`lines.${index}.tax_group_id`)}>
                                            <option value="">None</option>
                                            {taxGroups.map((taxGroup) => (
                                                <option key={taxGroup.id} value={taxGroup.id}>{taxGroup.name}</option>
                                            ))}
                                        </Select>
                                        <div className="flex h-11 items-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-sm font-semibold text-stone-950">
                                            {lineTotals[index]?.total.toFixed(2) ?? '0.00'}
                                        </div>
                                        <Button className="h-11 w-full px-3" onClick={() => remove(index)} type="button" variant="secondary">Remove</Button>
                                    </div>
                                );
                            })}
                            {fields.length === 0 ? <div className="border-t border-stone-200 bg-white px-4 py-8 text-sm text-stone-500">Search and add an item to start the order.</div> : null}
                        </div>
                    </div>
                </SectionCard>

                <SectionCard description="Calculated client-side for review before submission; backend remains authoritative." title="Totals">
                    <FormGrid>
                        <ReadOnlyTotal label="Subtotal" value={totals.subtotal} />
                        <ReadOnlyTotal label="Line Discount" value={totals.lineDiscountTotal} />
                        <ReadOnlyTotal label="Header Discount" value={totals.headerDiscount} />
                        <ReadOnlyTotal label="Tax Total" value={totals.lineTaxTotal} />
                        <ReadOnlyTotal label="Grand Total" value={totals.grandTotal} strong />
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Link to="/purchase/orders">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : submitLabel}</Button>
                </ActionBar>
            </div>
        </form>
    );
}

function ReadOnlyTotal({ label, value, strong = false }: { label: string; value: number; strong?: boolean }) {
    return (
        <div>
            <span className="text-sm font-medium text-stone-700">{label}</span>
            <div className={`mt-2 flex h-11 items-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-sm ${strong ? 'font-semibold text-stone-950' : 'text-stone-700'}`}>
                {value.toFixed(2)}
            </div>
        </div>
    );
}
