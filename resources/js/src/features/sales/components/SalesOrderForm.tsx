import type { UseFormReturn } from 'react-hook-form';
import { Link } from 'react-router-dom';
import { ActionBar } from '../../../components/forms/ActionBar';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Textarea } from '../../../components/forms/Textarea';
import { Button } from '../../../components/ui/Button';
import type { CustomerRecord } from '../../customers/types';
import type { WarehouseRecord } from '../../warehouse/types';
import type { SalesOrderFormInput, SalesOrderFormValues } from '../schemas';

type SalesOrderFormProps = {
    form: UseFormReturn<SalesOrderFormInput, unknown, SalesOrderFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    onSubmit: (values: SalesOrderFormValues) => void | Promise<void>;
    customers: CustomerRecord[];
    warehouses: WarehouseRecord[];
};

export function SalesOrderForm({ form, formError = null, isSubmitting, onSubmit, customers, warehouses }: SalesOrderFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Sales orders use the same large-card ERP form pattern as the product and purchase setup flows, limited to fields supported by the backend request contract." title="Sales order">
                    <FormGrid>
                        <FormField error={errors.customer_id?.message} label="Customer" required>
                            <Select error={errors.customer_id?.message} {...register('customer_id')}>
                                <option value="">Select customer</option>
                                {customers.map((customer) => (
                                    <option key={customer.id} value={customer.id}>
                                        {customer.name}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
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
                        <FormField error={errors.currency_id?.message} label="Currency ID" required>
                            <Input error={errors.currency_id?.message} placeholder="1" {...register('currency_id')} />
                        </FormField>
                        <FormField error={errors.order_date?.message} label="Order Date" required>
                            <Input error={errors.order_date?.message} type="date" {...register('order_date')} />
                        </FormField>
                        <FormField error={errors.requested_delivery_date?.message} label="Requested Delivery Date">
                            <Input error={errors.requested_delivery_date?.message} type="date" {...register('requested_delivery_date')} />
                        </FormField>
                        <FormField error={errors.price_list_id?.message} label="Price List ID">
                            <Input error={errors.price_list_id?.message} placeholder="Optional" {...register('price_list_id')} />
                        </FormField>
                        <FormField error={errors.org_unit_id?.message} label="Organization Unit ID">
                            <Input error={errors.org_unit_id?.message} placeholder="Optional" {...register('org_unit_id')} />
                        </FormField>
                        <FormField error={errors.exchange_rate?.message} label="Exchange Rate">
                            <Input error={errors.exchange_rate?.message} placeholder="1.00" {...register('exchange_rate')} />
                        </FormField>
                        <FormField error={errors.subtotal?.message} label="Subtotal">
                            <Input error={errors.subtotal?.message} placeholder="0.00" {...register('subtotal')} />
                        </FormField>
                        <FormField error={errors.tax_total?.message} label="Tax Total">
                            <Input error={errors.tax_total?.message} placeholder="0.00" {...register('tax_total')} />
                        </FormField>
                        <FormField error={errors.discount_total?.message} label="Discount Total">
                            <Input error={errors.discount_total?.message} placeholder="0.00" {...register('discount_total')} />
                        </FormField>
                        <FormField error={errors.grand_total?.message} label="Grand Total">
                            <Input error={errors.grand_total?.message} placeholder="0.00" {...register('grand_total')} />
                        </FormField>
                    </FormGrid>
                    <div className="mt-4">
                        <FormField error={errors.notes?.message} label="Notes">
                            <Textarea error={errors.notes?.message} placeholder="Delivery instructions, order notes, or commercial remarks" {...register('notes')} />
                        </FormField>
                    </div>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Line-item entry is not exposed on the current backend sales order contract, so this workflow captures the supported header fields first.</p>}>
                    <Link to="/sales/orders">
                        <Button type="button" variant="secondary">Cancel</Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : 'Create Sales Order'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
