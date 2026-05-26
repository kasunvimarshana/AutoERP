import { useFieldArray, type UseFormReturn } from 'react-hook-form';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { ProductRecord } from '../../products/types';
import type { WarehouseLocationRecord, WarehouseRecord } from '../../warehouse/types';
import type { UserRecord } from '../../access/types';
import type { CycleCountFormInput, CycleCountFormValues } from '../schemas';

type CycleCountFormProps = {
    form: UseFormReturn<CycleCountFormInput, unknown, CycleCountFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    locations: WarehouseLocationRecord[];
    onSubmit: (values: CycleCountFormValues) => void | Promise<void>;
    products: ProductRecord[];
    users: UserRecord[];
    warehouses: WarehouseRecord[];
};

export function CycleCountForm({ form, formError = null, isSubmitting, locations, onSubmit, products, users, warehouses }: CycleCountFormProps) {
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
                <SectionCard description="Cycle counts follow the backend header-plus-lines structure and can later progress into start and complete workflow actions." title="Cycle count">
                    <FormGrid>
                        <FormField error={errors.warehouse_id?.message} label="Warehouse" required>
                            <Select error={errors.warehouse_id?.message} {...register('warehouse_id')}>
                                <option value="">Select warehouse</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.location_id?.message} label="Location">
                            <Select error={errors.location_id?.message} {...register('location_id')}>
                                <option value="">All locations</option>
                                {locations.map((location) => (
                                    <option key={location.id} value={location.id}>
                                        {location.path ?? location.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.counted_by_user_id?.message} label="Counted By">
                            <Select error={errors.counted_by_user_id?.message} {...register('counted_by_user_id')}>
                                <option value="">Select user</option>
                                {users.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.full_name ?? user.email ?? `User #${user.id}`}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Count lines can start with expected products and optional counted quantities, then be finalized on the detail screen." title="Count lines">
                    <div className="space-y-4">
                        {fields.map((field, index) => (
                            <div key={field.id} className="rounded-2xl border border-stone-200/80 bg-stone-50/80 p-4">
                                <div className="grid gap-4 xl:grid-cols-4">
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
                                    <FormField error={errors.lines?.[index]?.counted_qty?.message} label="Counted Qty">
                                        <Input error={errors.lines?.[index]?.counted_qty?.message} placeholder="20" {...register(`lines.${index}.counted_qty`)} />
                                    </FormField>
                                    <FormField error={errors.lines?.[index]?.unit_cost?.message} label="Unit Cost">
                                        <Input error={errors.lines?.[index]?.unit_cost?.message} placeholder="12.25" {...register(`lines.${index}.unit_cost`)} />
                                    </FormField>
                                    <div className="flex items-end">
                                        <Button className="w-full" onClick={() => remove(index)} type="button" variant="secondary">
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ))}
                        <Button onClick={() => append({ product_id: 0, counted_qty: '', unit_cost: '' })} type="button" variant="secondary">
                            Add Line
                        </Button>
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Button type="submit">{isSubmitting ? 'Saving...' : 'Create Cycle Count'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
