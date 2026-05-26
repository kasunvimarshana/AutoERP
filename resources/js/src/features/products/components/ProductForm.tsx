import { useMemo } from 'react';
import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Textarea } from '../../../components/forms/Textarea';
import { Button } from '../../../components/ui/Button';
import type { ProductBrand, ProductCategory, UnitOfMeasure } from '../types';
import type { ProductFormInput, ProductFormValues } from '../schemas';

type ProductFormProps = {
    form: UseFormReturn<ProductFormInput, unknown, ProductFormValues>;
    onSubmit: (values: ProductFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    formError?: string | null;
    brands: ProductBrand[];
    categories: ProductCategory[];
    unitsOfMeasure: UnitOfMeasure[];
};

export function ProductForm({
    brands,
    categories,
    form,
    formError = null,
    isSubmitting,
    mode,
    onSubmit,
    unitsOfMeasure,
}: ProductFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
        watch,
    } = form;

    const valuationMethod = watch('valuation_method');
    const standardCost = Number(watch('standard_cost') ?? 0);
    const salesPrice = Number(watch('sales_price') ?? 0);
    const marginFromValues = useMemo(() => {
        if (!standardCost || !salesPrice || salesPrice <= standardCost) {
            return null;
        }

        return (((salesPrice - standardCost) / salesPrice) * 100).toFixed(2);
    }, [salesPrice, standardCost]);

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard
                    description="Keep the top section focused on the core product master: identity, grouping, and operational status."
                    title="Product setup"
                >
                    <FormGrid>
                        <FormField error={errors.name?.message} label="Product Name" required>
                            <Input placeholder="Enter product name" {...register('name')} error={errors.name?.message} />
                        </FormField>

                        <FormField error={errors.sku?.message} hint="Use the existing product code/SKU field from the backend contract." label="SKU / Product Code">
                            <Input placeholder="SKU-1001" {...register('sku')} error={errors.sku?.message} />
                        </FormField>

                        <FormField error={errors.type?.message} label="Product Type" required>
                            <Select {...register('type')} error={errors.type?.message}>
                                <option value="physical">Physical</option>
                                <option value="service">Service</option>
                                <option value="digital">Digital</option>
                                <option value="combo">Combo</option>
                                <option value="variable">Variable</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.slug?.message} hint="If left blank, the slug is generated from the product name." label="Slug">
                            <Input placeholder="auto-generated-product-slug" {...register('slug')} error={errors.slug?.message} />
                        </FormField>

                        <FormField
                            action={
                                <Link to="/products/brands/new">
                                    <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                        Add Brand
                                    </Button>
                                </Link>
                            }
                            error={errors.brand_id?.message}
                            label="Brand"
                        >
                            <Select {...register('brand_id')} error={errors.brand_id?.message}>
                                <option value="">Select brand</option>
                                {brands.map((brand) => (
                                    <option key={brand.id} value={brand.id}>
                                        {brand.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField
                            action={
                                <Link to="/products/categories/new">
                                    <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                        Add Category
                                    </Button>
                                </Link>
                            }
                            error={errors.category_id?.message}
                            label="Category"
                        >
                            <Select {...register('category_id')} error={errors.category_id?.message}>
                                <option value="">Select category</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField
                            error={errors.is_active?.message}
                            hint="Inactive products stay available historically but can be hidden from new operational flows."
                            label="Status"
                        >
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Keep this product available for current operations."
                                label="Product is active"
                                {...register('is_active')}
                            />
                        </FormField>

                        <FormField className="xl:col-span-3" error={errors.description?.message} label="Description">
                            <Textarea
                                placeholder="Short product summary, handling notes, internal setup context, or operational guidance."
                                {...register('description')}
                                error={errors.description?.message}
                                rows={5}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="Use the same 3-column rhythm to align product UOMs, conversion behavior, and inventory valuation choices."
                    title="Units and valuation"
                >
                    <FormGrid>
                        <FormField
                            action={
                                <Link to="/products/units/new">
                                    <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                        Add UOM
                                    </Button>
                                </Link>
                            }
                            error={errors.base_uom_id?.message}
                            label="Base UOM"
                            required
                        >
                            <Select {...register('base_uom_id')} error={errors.base_uom_id?.message}>
                                <option value="">Select base unit</option>
                                {unitsOfMeasure.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.name} ({unit.symbol})
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField error={errors.purchase_uom_id?.message} label="Purchase UOM">
                            <Select {...register('purchase_uom_id')} error={errors.purchase_uom_id?.message}>
                                <option value="">Select purchase unit</option>
                                {unitsOfMeasure.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.name} ({unit.symbol})
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField error={errors.sales_uom_id?.message} label="Sales UOM">
                            <Select {...register('sales_uom_id')} error={errors.sales_uom_id?.message}>
                                <option value="">Select sales unit</option>
                                {unitsOfMeasure.map((unit) => (
                                    <option key={unit.id} value={unit.id}>
                                        {unit.name} ({unit.symbol})
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField
                            error={errors.uom_conversion_factor?.message}
                            hint="Use when purchase or sales units differ from the base unit."
                            label="UOM Conversion Factor"
                        >
                            <Input placeholder="1" step="0.0001" type="number" {...register('uom_conversion_factor')} error={errors.uom_conversion_factor?.message} />
                        </FormField>

                        <FormField error={errors.valuation_method?.message} label="Valuation Method">
                            <Select {...register('valuation_method')} error={errors.valuation_method?.message}>
                                <option value="">Select valuation method</option>
                                <option value="fifo">FIFO</option>
                                <option value="lifo">LIFO</option>
                                <option value="weighted_average">Weighted Average</option>
                                <option value="standard">Standard</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.standard_cost?.message} hint="Required only when valuation method is Standard." label="Standard Cost">
                            <Input placeholder="0.00" step="0.01" type="number" {...register('standard_cost')} error={errors.standard_cost?.message} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="These commercial values are stored in product metadata until dedicated pricing modules are layered in."
                    title="Commercial settings"
                >
                    <FormGrid>
                        <FormField error={errors.purchase_price?.message} label="Reference Purchase Price">
                            <Input placeholder="0.00" step="0.01" type="number" {...register('purchase_price')} error={errors.purchase_price?.message} />
                        </FormField>

                        <FormField error={errors.sales_price?.message} label="Reference Sales Price">
                            <Input placeholder="0.00" step="0.01" type="number" {...register('sales_price')} error={errors.sales_price?.message} />
                        </FormField>

                        <FormField
                            error={errors.profit_margin?.message}
                            hint={marginFromValues ? `Computed margin from cost and price: ${marginFromValues}%` : 'Set a target margin or leave it blank.'}
                            label="Target Profit Margin %"
                        >
                            <Input placeholder="25" step="0.01" type="number" {...register('profit_margin')} error={errors.profit_margin?.message} />
                        </FormField>

                        <FormField error={errors.price_list_note?.message} label="Price Note">
                            <Input placeholder="Retail introductory price" {...register('price_list_note')} error={errors.price_list_note?.message} />
                        </FormField>

                        <FormField error={errors.supplier_reference?.message} label="Supplier Reference">
                            <Input placeholder="Preferred supplier / vendor reference" {...register('supplier_reference')} error={errors.supplier_reference?.message} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="Capture the primary identifier directly during product setup so the details tab opens with operational barcode data already in place."
                    title="Primary identifier"
                >
                    <FormGrid>
                        <FormField error={errors.identifier_technology?.message} label="Technology">
                            <Select {...register('identifier_technology')} error={errors.identifier_technology?.message}>
                                <option value="">Select technology</option>
                                <option value="barcode_1d">Barcode 1D</option>
                                <option value="barcode_2d">Barcode 2D</option>
                                <option value="qr_code">QR Code</option>
                                <option value="rfid_hf">RFID HF</option>
                                <option value="rfid_uhf">RFID UHF</option>
                                <option value="nfc">NFC</option>
                                <option value="gs1_epc">GS1 EPC</option>
                                <option value="custom">Custom</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.identifier_format?.message} label="Format">
                            <Select {...register('identifier_format')} error={errors.identifier_format?.message}>
                                <option value="">Select format</option>
                                <option value="ean13">EAN-13</option>
                                <option value="ean8">EAN-8</option>
                                <option value="upc_a">UPC-A</option>
                                <option value="code128">Code 128</option>
                                <option value="code39">Code 39</option>
                                <option value="qr">QR</option>
                                <option value="datamatrix">Data Matrix</option>
                                <option value="gs1_128">GS1-128</option>
                                <option value="epc_sgtin">EPC SGTIN</option>
                                <option value="other">Other</option>
                            </Select>
                        </FormField>

                        <FormField error={errors.identifier_value?.message} label="Identifier Value">
                            <Input placeholder="9551234567890" {...register('identifier_value')} error={errors.identifier_value?.message} />
                        </FormField>

                        <FormField error={errors.identifier_gs1_company_prefix?.message} label="GS1 Company Prefix">
                            <Input placeholder="1234567" {...register('identifier_gs1_company_prefix')} error={errors.identifier_gs1_company_prefix?.message} />
                        </FormField>

                        <FormField error={errors.identifier_is_primary?.message} label="Primary">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Use this identifier as the default scan target for the product."
                                label="Primary identifier"
                                {...register('identifier_is_primary')}
                            />
                        </FormField>

                        <FormField error={errors.identifier_is_active?.message} label="Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive identifiers remain in history but are not used in active workflows."
                                label="Identifier is active"
                                {...register('identifier_is_active')}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard
                    description="Tracking flags stay aligned with the backend validation rules and inventory-ready product structure."
                    title="Tracking controls"
                >
                    <FormGrid>
                        <FormField error={errors.is_batch_tracked?.message} label="Batch Tracking">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Track this item by production or receipt batch."
                                label="Enable batch tracking"
                                {...register('is_batch_tracked')}
                            />
                        </FormField>

                        <FormField error={errors.is_lot_tracked?.message} label="Lot Tracking">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Use lot-level tracking for traceability."
                                label="Enable lot tracking"
                                {...register('is_lot_tracked')}
                            />
                        </FormField>

                        <FormField
                            error={errors.is_serial_tracked?.message}
                            hint={valuationMethod === 'standard' ? 'Serial tracking cannot be combined with batch or lot tracking.' : undefined}
                            label="Serial Tracking"
                        >
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Assign and maintain unique serial numbers."
                                label="Enable serial tracking"
                                {...register('is_serial_tracked')}
                            />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar
                    leading={
                        <p className="text-sm text-stone-500">
                            Pricing summary, identifiers, and supplier notes are ready for reuse when Pricing, Sales, and Purchase modules are layered in.
                        </p>
                    }
                >
                    <Link to="/products">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Product' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
