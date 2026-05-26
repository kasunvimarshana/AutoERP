import type { UseFormReturn } from 'react-hook-form';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Button } from '../../../components/ui/Button';
import type { ProductRecord } from '../../products/types';
import type { WarehouseLocationRecord } from '../../warehouse/types';
import type { StockReservationFormInput, StockReservationFormValues } from '../schemas';

type StockReservationFormProps = {
    form: UseFormReturn<StockReservationFormInput, unknown, StockReservationFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    locations: WarehouseLocationRecord[];
    onSubmit: (values: StockReservationFormValues) => void | Promise<void>;
    products: ProductRecord[];
};

export function StockReservationForm({ form, formError = null, isSubmitting, locations, onSubmit, products }: StockReservationFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Reservations hold available stock against a target record while remaining aligned with the current inventory API contract." title="Stock reservation">
                    <FormGrid>
                        <FormField error={errors.product_id?.message} label="Product" required>
                            <Select error={errors.product_id?.message} {...register('product_id')}>
                                <option value="">Select product</option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>
                                        {product.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.location_id?.message} label="Location" required>
                            <Select error={errors.location_id?.message} {...register('location_id')}>
                                <option value="">Select location</option>
                                {locations.map((location) => (
                                    <option key={location.id} value={location.id}>
                                        {location.path ?? location.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                        <FormField error={errors.quantity?.message} label="Quantity" required>
                            <Input error={errors.quantity?.message} placeholder="5" {...register('quantity')} />
                        </FormField>
                        <FormField error={errors.expires_at?.message} label="Expires At">
                            <Input error={errors.expires_at?.message} type="date" {...register('expires_at')} />
                        </FormField>
                        <FormField error={errors.reserved_for_type?.message} label="Reserved For Type">
                            <Input error={errors.reserved_for_type?.message} placeholder="sales_order" {...register('reserved_for_type')} />
                        </FormField>
                        <FormField error={errors.reserved_for_id?.message} label="Reserved For ID">
                            <Input error={errors.reserved_for_id?.message} placeholder="125" {...register('reserved_for_id')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar>
                    <Button type="submit">{isSubmitting ? 'Saving...' : 'Create Reservation'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
