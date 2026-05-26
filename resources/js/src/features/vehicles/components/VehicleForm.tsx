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
import type { VehicleFormInput, VehicleFormValues } from '../schemas';

type VehicleFormProps = {
    form: UseFormReturn<VehicleFormInput, unknown, VehicleFormValues>;
    formError?: string | null;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onSubmit: (values: VehicleFormValues) => void | Promise<void>;
};

export function VehicleForm({ form, formError = null, isSubmitting, mode, onSubmit }: VehicleFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-6">
                <SectionCard description="Capture the vehicle identity, ownership context, and registry numbers accepted by the Vehicle API." title="Vehicle setup">
                    <FormGrid>
                        <FormField error={errors.registration_number?.message} label="Registration Number">
                            <Input error={errors.registration_number?.message} placeholder="ABC-1234" {...register('registration_number')} />
                        </FormField>
                        <FormField error={errors.asset_code?.message} label="Vehicle Number / Asset Code">
                            <Input error={errors.asset_code?.message} placeholder="VH-1001" {...register('asset_code')} />
                        </FormField>
                        <FormField error={errors.ownership_type?.message} label="Ownership Type" required>
                            <Select error={errors.ownership_type?.message} {...register('ownership_type')}>
                                <option value="company_owned">Company Owned</option>
                                <option value="third_party_owned">Third Party Owned</option>
                                <option value="customer_owned">Customer Owned</option>
                                <option value="leased">Leased</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.make?.message} label="Make / Brand" required>
                            <Input error={errors.make?.message} placeholder="Toyota" {...register('make')} />
                        </FormField>
                        <FormField error={errors.model?.message} label="Model" required>
                            <Input error={errors.model?.message} placeholder="HiAce" {...register('model')} />
                        </FormField>
                        <FormField error={errors.year?.message} label="Year">
                            <Input error={errors.year?.message} max="2100" min="1900" placeholder="2024" type="number" {...register('year')} />
                        </FormField>
                        <FormField error={errors.customer_id?.message} label="Owner / Customer ID">
                            <Input error={errors.customer_id?.message} placeholder="Customer ID" type="number" {...register('customer_id')} />
                        </FormField>
                        <FormField error={errors.supplier_id?.message} label="Supplier ID">
                            <Input error={errors.supplier_id?.message} placeholder="Supplier ID" type="number" {...register('supplier_id')} />
                        </FormField>
                        <FormField error={errors.org_unit_id?.message} label="Organization Unit ID">
                            <Input error={errors.org_unit_id?.message} placeholder="Org unit ID" type="number" {...register('org_unit_id')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Technical identifiers and operating attributes are stored against the vehicle registry record." title="Technical details">
                    <FormGrid>
                        <FormField error={errors.vin?.message} label="VIN">
                            <Input error={errors.vin?.message} placeholder="Vehicle identification number" {...register('vin')} />
                        </FormField>
                        <FormField error={errors.chassis_number?.message} label="Chassis Number">
                            <Input error={errors.chassis_number?.message} placeholder="Chassis number" {...register('chassis_number')} />
                        </FormField>
                        <FormField error={errors.engine_number?.message} label="Engine Number">
                            <Input error={errors.engine_number?.message} placeholder="Engine number" {...register('engine_number')} />
                        </FormField>
                        <FormField error={errors.fuel_type?.message} label="Fuel Type">
                            <Select error={errors.fuel_type?.message} {...register('fuel_type')}>
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="electric">Electric</option>
                                <option value="cng">CNG</option>
                                <option value="lpg">LPG</option>
                                <option value="other">Other</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.transmission?.message} label="Transmission Type">
                            <Select error={errors.transmission?.message} {...register('transmission')}>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                                <option value="cvt">CVT</option>
                                <option value="semi_automatic">Semi Automatic</option>
                                <option value="other">Other</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.odometer?.message} label="Mileage / Odometer">
                            <Input error={errors.odometer?.message} min="0" placeholder="0" step="0.01" type="number" {...register('odometer')} />
                        </FormField>
                        <FormField error={errors.color?.message} label="Color">
                            <Input error={errors.color?.message} placeholder="White" {...register('color')} />
                        </FormField>
                        <FormField error={errors.primary_image_path?.message} label="Image Path">
                            <Input error={errors.primary_image_path?.message} placeholder="/images/vehicles/abc-1234.png" {...register('primary_image_path')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                <SectionCard description="Status fields use the Vehicle API status contract and keep the registry ready for operational handoff." title="Status and maintenance">
                    <FormGrid>
                        <FormField error={errors.rental_status?.message} label="Availability Status">
                            <Select error={errors.rental_status?.message} {...register('rental_status')}>
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                                <option value="rented">In Use</option>
                                <option value="blocked">Blocked</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.service_status?.message} label="Service Status">
                            <Select error={errors.service_status?.message} {...register('service_status')}>
                                <option value="none">None</option>
                                <option value="in_maintenance">In Maintenance</option>
                                <option value="under_repair">Under Repair</option>
                                <option value="awaiting_parts">Awaiting Parts</option>
                                <option value="quality_check">Quality Check</option>
                                <option value="ready_for_pickup">Ready For Pickup</option>
                                <option value="returned_to_fleet">Returned To Fleet</option>
                            </Select>
                        </FormField>
                        <FormField error={errors.next_maintenance_due_at?.message} label="Next Maintenance Due">
                            <Input error={errors.next_maintenance_due_at?.message} type="date" {...register('next_maintenance_due_at')} />
                        </FormField>
                        <FormField error={errors.is_active?.message} label="Status">
                            <Checkbox
                                className="border-stone-200/80 bg-stone-50/70"
                                description="Inactive vehicles stay visible for historical lookup but should not be used in active workflows."
                                label="Vehicle is active"
                                {...register('is_active')}
                            />
                        </FormField>
                        <FormField className="xl:col-span-3" error={errors.notes?.message} label="Notes">
                            <Textarea error={errors.notes?.message} placeholder="Registry notes, internal condition remarks, or ownership context." rows={5} {...register('notes')} />
                        </FormField>
                    </FormGrid>
                </SectionCard>

                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                <ActionBar leading={<p className="text-sm text-stone-500">Vehicle Registry is limited to vehicle master data, technical attributes, and status maintenance.</p>}>
                    <Link to="/vehicles">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Create Vehicle' : 'Save Changes'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
