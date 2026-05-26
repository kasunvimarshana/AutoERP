import { useMemo } from 'react';
import { useFieldArray, useWatch, type UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { ProductRecord, ProductVariant, UnitOfMeasure } from '../../products/types';
import type { SupplierRecord } from '../../suppliers/types';
import type { WarehouseLocationRecord, WarehouseRecord } from '../../warehouse/types';
import type { GrnFormInput, GrnFormValues } from '../schemas';
import type { PurchaseOrderRecord } from '../types';

type GrnFormProps = {
    form: UseFormReturn<GrnFormInput, unknown, GrnFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    onSubmit: (values: GrnFormValues) => void | Promise<void>;
    products: ProductRecord[];
    purchaseOrders: PurchaseOrderRecord[];
    variants: ProductVariant[];
    suppliers: SupplierRecord[];
    unitsOfMeasure: UnitOfMeasure[];
    warehouseLocations: WarehouseLocationRecord[];
    warehouses: WarehouseRecord[];
    submitLabel?: string;
};

const emptyLine = {
    id: '',
    purchase_order_line_id: '',
    item_id: '',
    variant_id: '',
    description: '',
    location_id: '',
    uom_id: '',
    expected_qty: '0',
    received_qty: '1',
    rejected_qty: '0',
    unit_price: '0',
};

function toNumber(value: unknown) {
    const numeric = Number(value ?? 0);

    return Number.isFinite(numeric) ? numeric : 0;
}

function lineTotal(line: Partial<GrnFormInput['lines'][number]> | undefined) {
    return Math.max(toNumber(line?.received_qty) * toNumber(line?.unit_price), 0);
}

export function GrnForm({ form, formError = null, isSubmitting, onSubmit, products, purchaseOrders, variants, suppliers, unitsOfMeasure, warehouseLocations, warehouses, submitLabel = 'Create GRN' }: GrnFormProps) {
    const {
        control,
        formState: { errors },
        handleSubmit,
        register,
        setValue,
    } = form;
    const { append, fields, remove } = useFieldArray({ control, name: 'lines' });
    const watchedLines = useWatch({ control, name: 'lines' }) ?? [];
    const lineTotals = useMemo(() => watchedLines.map((line) => lineTotal(line)), [watchedLines]);
    const grandTotal = useMemo(() => lineTotals.reduce((total, value) => total + value, 0), [lineTotals]);
    const lineArrayError = typeof errors.lines?.message === 'string' ? errors.lines.message : null;

    function handleProductChange(index: number, rawProductId: string) {
        const product = products.find((item) => item.id === Number(rawProductId));
        if (!product) {
            return;
        }

        setValue(`lines.${index}.description`, product.description ?? '', { shouldDirty: true });
        setValue(`lines.${index}.uom_id`, product.purchase_uom_id ?? product.base_uom_id ?? '', { shouldDirty: true, shouldValidate: true });
        setValue(`lines.${index}.unit_price`, Number(product.standard_cost ?? product.metadata?.purchase_price ?? 0), { shouldDirty: true, shouldValidate: true });
        setValue(`lines.${index}.variant_id`, '', { shouldDirty: true });
    }

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Capture receipt header details and optionally link this GRN to a purchase order." title="Goods receipt note">
                    <FormGrid>
                        <FormField error={errors.supplier_id?.message} label="Supplier" required>
                            <Select error={errors.supplier_id?.message} {...register('supplier_id')}>
                                <option value="">Select supplier</option>
                                {suppliers.map((supplier) => (
                                    <option key={supplier.id} value={supplier.id}>{supplier.name}</option>
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
                        <FormField error={errors.purchase_order_id?.message} label="Purchase Order">
                            <Select error={errors.purchase_order_id?.message} {...register('purchase_order_id')}>
                                <option value="">No purchase order</option>
                                {purchaseOrders.map((order) => (
                                    <option key={order.id} value={order.id}>{order.po_number}</option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.currency_id?.message} label="Currency ID" required>
                            <Input error={errors.currency_id?.message} placeholder="1" {...register('currency_id')} />
                        </FormField>
                        <FormField error={errors.grn_number?.message} label="GRN Number" required>
                            <Input error={errors.grn_number?.message} placeholder="GRN-1001" {...register('grn_number')} />
                        </FormField>
                        <FormField error={errors.received_date?.message} label="Received Date" required>
                            <Input error={errors.received_date?.message} type="date" {...register('received_date')} />
                        </FormField>
                        <FormField error={errors.exchange_rate?.message} label="Exchange Rate">
                            <Input error={errors.exchange_rate?.message} placeholder="1.00" {...register('exchange_rate')} />
                        </FormField>
                    </FormGrid>
                    <div className="mt-4">
                        <FormField error={errors.notes?.message} label="Notes">
                            <Input error={errors.notes?.message} placeholder="Receiving notes or supplier delivery reference" {...register('notes')} />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard action={<Button onClick={() => append(emptyLine)} type="button" variant="secondary">Add Row</Button>} description="Record the products and quantities received into warehouse locations." title="Received line items">
                    {lineArrayError ? <p className="mb-4 text-sm text-red-600">{lineArrayError}</p> : null}
                    <div className="overflow-x-auto">
                        <div className="min-w-[82rem] space-y-3">
                            <div className="grid grid-cols-[1.25fr_0.9fr_1.1fr_1fr_0.75fr_0.75fr_0.75fr_0.75fr_0.75fr_0.8fr_auto] gap-3 text-xs font-semibold uppercase text-stone-500">
                                <span>Item</span>
                                <span>Variant</span>
                                <span>Description</span>
                                <span>Location</span>
                                <span>UOM</span>
                                <span>Ordered</span>
                                <span>Received</span>
                                <span>Rejected</span>
                                <span>Unit Cost</span>
                                <span>Total</span>
                                <span>Remove</span>
                            </div>
                            {fields.map((field, index) => {
                                const selectedProductId = Number(watchedLines[index]?.item_id ?? 0);
                                const productVariants = variants.filter((variant) => variant.product_id === selectedProductId);
                                const lineErrors = errors.lines?.[index];

                                return (
                                    <div key={field.id} className="grid grid-cols-[1.25fr_0.9fr_1.1fr_1fr_0.75fr_0.75fr_0.75fr_0.75fr_0.75fr_0.8fr_auto] gap-3 rounded-2xl border border-stone-200 bg-white p-3">
                                        <FormField error={lineErrors?.item_id?.message} label="Item" required>
                                            <Select error={lineErrors?.item_id?.message} {...register(`lines.${index}.item_id`, { onChange: (event) => handleProductChange(index, event.target.value) })}>
                                                <option value="">Select</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>{product.name}</option>
                                                ))}
                                            </Select>
                                        </FormField>
                                        <FormField error={lineErrors?.variant_id?.message} label="Variant">
                                            <Select disabled={productVariants.length === 0} error={lineErrors?.variant_id?.message} {...register(`lines.${index}.variant_id`)}>
                                                <option value="">None</option>
                                                {productVariants.map((variant) => (
                                                    <option key={variant.id} value={variant.id}>{variant.name}</option>
                                                ))}
                                            </Select>
                                        </FormField>
                                        <FormField error={lineErrors?.description?.message} label="Description">
                                            <Input error={lineErrors?.description?.message} {...register(`lines.${index}.description`)} />
                                        </FormField>
                                        <FormField error={lineErrors?.location_id?.message} label="Location" required>
                                            <Select error={lineErrors?.location_id?.message} {...register(`lines.${index}.location_id`)}>
                                                <option value="">Select</option>
                                                {warehouseLocations.map((location) => (
                                                    <option key={location.id} value={location.id}>{location.path ?? location.name}</option>
                                                ))}
                                            </Select>
                                        </FormField>
                                        <FormField error={lineErrors?.uom_id?.message} label="UOM" required>
                                            <Select error={lineErrors?.uom_id?.message} {...register(`lines.${index}.uom_id`)}>
                                                <option value="">Select</option>
                                                {unitsOfMeasure.map((uom) => (
                                                    <option key={uom.id} value={uom.id}>{uom.symbol || uom.name}</option>
                                                ))}
                                            </Select>
                                        </FormField>
                                        <FormField error={lineErrors?.expected_qty?.message} label="Ordered">
                                            <Input error={lineErrors?.expected_qty?.message} min="0" step="0.000001" type="number" {...register(`lines.${index}.expected_qty`)} />
                                        </FormField>
                                        <FormField error={lineErrors?.received_qty?.message} label="Received" required>
                                            <Input error={lineErrors?.received_qty?.message} min="0.000001" step="0.000001" type="number" {...register(`lines.${index}.received_qty`)} />
                                        </FormField>
                                        <FormField error={lineErrors?.rejected_qty?.message} label="Rejected">
                                            <Input error={lineErrors?.rejected_qty?.message} min="0" step="0.000001" type="number" {...register(`lines.${index}.rejected_qty`)} />
                                        </FormField>
                                        <FormField error={lineErrors?.unit_price?.message} label="Unit Cost" required>
                                            <Input error={lineErrors?.unit_price?.message} min="0" step="0.01" type="number" {...register(`lines.${index}.unit_price`)} />
                                        </FormField>
                                        <div className="space-y-2">
                                            <span className="text-sm font-medium text-stone-800">Line total</span>
                                            <div className="flex h-11 items-center rounded-xl border border-stone-200 bg-stone-50 px-3 text-sm font-semibold text-stone-950">{lineTotals[index]?.toFixed(2) ?? '0.00'}</div>
                                        </div>
                                        <div className="flex items-end">
                                            <Button className="h-11 px-3" disabled={fields.length === 1} onClick={() => remove(index)} type="button" variant="secondary">Remove</Button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                    <div className="mt-4 flex items-center justify-end gap-3 text-sm">
                        <span className="text-stone-500">Received value</span>
                        <span className="font-semibold text-stone-950">{grandTotal.toFixed(2)}</span>
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Link to="/purchase/grns">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : submitLabel}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
