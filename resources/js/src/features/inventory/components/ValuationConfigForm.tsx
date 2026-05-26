import type { UseFormReturn } from 'react-hook-form';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { OrganizationUnitRecord } from '../../organization/types';
import type { ProductRecord } from '../../products/types';
import type { WarehouseRecord } from '../../warehouse/types';
import type { ValuationConfigFormInput, ValuationConfigFormValues } from '../schemas';

type ValuationConfigFormProps = {
    form: UseFormReturn<ValuationConfigFormInput, unknown, ValuationConfigFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onCancel: () => void;
    onSubmit: (values: ValuationConfigFormValues) => void | Promise<void>;
    organizationUnits: OrganizationUnitRecord[];
    products: ProductRecord[];
    warehouses: WarehouseRecord[];
};

export function ValuationConfigForm({ form, formError = null, isSubmitting, mode, onCancel, onSubmit, organizationUnits, products, warehouses }: ValuationConfigFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Valuation configs define how inventory costing resolves across tenant, org unit, warehouse, product, and transaction scopes." title={mode === 'create' ? 'Add Valuation Config' : 'Edit Valuation Config'}>
                    <FormGrid>
                        <FormField error={errors.org_unit_id?.message} label="Organization Unit">
                            <Select error={errors.org_unit_id?.message} {...register('org_unit_id')}>
                                <option value="">Any unit</option>
                                {organizationUnits.map((organizationUnit) => (
                                    <option key={organizationUnit.id} value={organizationUnit.id}>
                                        {organizationUnit.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.warehouse_id?.message} label="Warehouse">
                            <Select error={errors.warehouse_id?.message} {...register('warehouse_id')}>
                                <option value="">Any warehouse</option>
                                {warehouses.map((warehouse) => (
                                    <option key={warehouse.id} value={warehouse.id}>
                                        {warehouse.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.product_id?.message} label="Product">
                            <Select error={errors.product_id?.message} {...register('product_id')}>
                                <option value="">Any product</option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>
                                        {product.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.transaction_type?.message} label="Transaction Type">
                            <Input error={errors.transaction_type?.message} placeholder="shipment" {...register('transaction_type')} />
                        </FormField>
                        <FormField error={errors.valuation_method?.message} label="Valuation Method" required>
                            <Select error={errors.valuation_method?.message} {...register('valuation_method')}>
                                <option value="fifo">FIFO</option>
                                <option value="lifo">LIFO</option>
                                <option value="fefo">FEFO</option>
                                <option value="weighted_average">Weighted Average</option>
                                <option value="standard">Standard</option>
                                <option value="specific">Specific</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.allocation_strategy?.message} label="Allocation Strategy" required>
                            <Select error={errors.allocation_strategy?.message} {...register('allocation_strategy')}>
                                <option value="fifo">FIFO</option>
                                <option value="lifo">LIFO</option>
                                <option value="fefo">FEFO</option>
                                <option value="nearest_bin">Nearest Bin</option>
                                <option value="manual">Manual</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.is_active?.message} label="Status">
                            <Checkbox className="border-stone-200/80 bg-stone-50/70" description="Inactive configs remain on file but will no longer participate in valuation resolution." label="Config is active" {...register('is_active')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Button onClick={onCancel} type="button" variant="secondary">
                        Cancel
                    </Button>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Config' : 'Save Config'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
