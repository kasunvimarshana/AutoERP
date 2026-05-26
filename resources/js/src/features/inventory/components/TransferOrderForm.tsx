import { useFieldArray, type UseFormReturn } from 'react-hook-form';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { ProductRecord } from '../../products/types';
import type { WarehouseRecord } from '../../warehouse/types';
import type { UnitOfMeasure } from '../../products/types';
import type { TransferOrderFormInput, TransferOrderFormValues } from '../schemas';

type TransferOrderFormProps = {
    form: UseFormReturn<TransferOrderFormInput, unknown, TransferOrderFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    onSubmit: (values: TransferOrderFormValues) => void | Promise<void>;
    products: ProductRecord[];
    units: UnitOfMeasure[];
    warehouses: WarehouseRecord[];
};

export function TransferOrderForm({ form, formError = null, isSubmitting, onSubmit, products, units, warehouses }: TransferOrderFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
        control,
    } = form;
    const { fields, append, remove } = useFieldArray({ control, name: 'lines' });

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Transfer orders move stock between warehouses while preserving the backend workflow states for approval and receipt." title="Transfer order">
                    <FormGrid>
                        <FormField error={errors.transfer_number?.message} label="Transfer Number" required>
                            <Input error={errors.transfer_number?.message} placeholder="TO-10025" {...register('transfer_number')} />
                        </FormField>
                        <FormField error={errors.request_date?.message} label="Request Date" required>
                            <Input error={errors.request_date?.message} type="date" {...register('request_date')} />
                        </FormField>
                        <FormField error={errors.expected_date?.message} label="Expected Date">
                            <Input error={errors.expected_date?.message} type="date" {...register('expected_date')} />
                        </FormField>
                        <FormField error={errors.from_warehouse_id?.message} label="From Warehouse" required>
                            <Select error={errors.from_warehouse_id?.message} {...register('from_warehouse_id')}>
                                <option value="">Select source</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.to_warehouse_id?.message} label="To Warehouse" required>
                            <Select error={errors.to_warehouse_id?.message} {...register('to_warehouse_id')}>
                                <option value="">Select destination</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.status?.message} label="Initial Status">
                            <Select error={errors.status?.message} {...register('status')}>
                                <option value="draft">Draft</option>
                                <option value="approved">Approved</option>
                                <option value="in_transit">In Transit</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Cancelled</option>
                            </Select>
                        </FormField>
                    </FormGrid>
                    <div className="mt-4">
                        <FormField error={errors.notes?.message} label="Notes">
                            <Input error={errors.notes?.message} placeholder="Operational notes for receiving and transport teams" {...register('notes')} />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard description="Each transfer line maps directly to the backend payload contract using product, unit, and requested quantity." title="Transfer lines">
                    <div className="space-y-4">
                        {fields.map((field, index) => (
                            <div key={field.id} className="rounded-2xl border border-stone-200/80 bg-stone-50/80 p-4">
                                <div className="grid gap-4 xl:grid-cols-5">
                                    <FormField error={errors.lines?.[index]?.product_id?.message} label="Product" required>
                                        <Select error={errors.lines?.[index]?.product_id?.message} {...register(`lines.${index}.product_id`)}>
                                            <option value="">Select product</option>
                                            {products.map((product) => (
                                                <option key={product.id} value={product.id}>
                                                    {product.name}
                                                </option>
                                            ))}
                                        </Select>
                                    </FormField>
                                    <FormField error={errors.lines?.[index]?.uom_id?.message} label="UOM" required>
                                        <Select error={errors.lines?.[index]?.uom_id?.message} {...register(`lines.${index}.uom_id`)}>
                                            <option value="">Select UOM</option>
                                            {units.map((unit) => (
                                                <option key={unit.id} value={unit.id}>
                                                    {unit.name}
                                                </option>
                                            ))}
                                        </Select>
                                    </FormField>
                                    <FormField error={errors.lines?.[index]?.requested_qty?.message} label="Requested Qty" required>
                                        <Input error={errors.lines?.[index]?.requested_qty?.message} placeholder="12" {...register(`lines.${index}.requested_qty`)} />
                                    </FormField>
                                    <FormField error={errors.lines?.[index]?.unit_cost?.message} label="Unit Cost">
                                        <Input error={errors.lines?.[index]?.unit_cost?.message} placeholder="24.50" {...register(`lines.${index}.unit_cost`)} />
                                    </FormField>
                                    <div className="flex items-end">
                                        <Button className="w-full" onClick={() => remove(index)} type="button" variant="secondary">
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ))}
                        <Button
                            onClick={() =>
                                append({
                                    product_id: 0,
                                    uom_id: 0,
                                    requested_qty: 1,
                                    unit_cost: '',
                                    from_location_id: '',
                                    to_location_id: '',
                                })
                            }
                            type="button"
                            variant="secondary"
                        >
                            Add Line
                        </Button>
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Button type="submit">{isSubmitting ? 'Saving...' : 'Create Transfer Order'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
