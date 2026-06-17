import type { ChangeEvent } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { VehicleCategorySelect } from './VehicleCategorySelect';
import { VehicleMakeSelect } from './VehicleMakeSelect';
import { VehicleModelSelect } from './VehicleModelSelect';
import { VehicleTypeSelect } from './VehicleTypeSelect';
import type { VehicleCategory, VehicleMake, VehicleModel, VehiclePayload, VehicleType } from '../vehicleTypes';

const statusOptions = ['active', 'inactive', 'under_service', 'rented', 'reserved', 'sold', 'blocked', 'scrapped'];
const fuelOptions = ['petrol', 'diesel', 'hybrid', 'electric', 'lpg', 'cng', 'other'];
const transmissionOptions = ['manual', 'automatic', 'semi_automatic', 'cvt', 'other'];

export function VehicleBasicFields({
    value,
    onChange,
    make,
    onMakeChange,
    model,
    onModelChange,
    type,
    onTypeChange,
    category,
    onCategoryChange,
    error,
    vehicleNumberReadOnly = false,
}: {
    value: VehiclePayload;
    onChange: (value: VehiclePayload) => void;
    make: VehicleMake | null;
    onMakeChange: (value: VehicleMake | null) => void;
    model: VehicleModel | null;
    onModelChange: (value: VehicleModel | null) => void;
    type: VehicleType | null;
    onTypeChange: (value: VehicleType | null) => void;
    category: VehicleCategory | null;
    onCategoryChange: (value: VehicleCategory | null) => void;
    error: ApiError | null;
    vehicleNumberReadOnly?: boolean;
}) {
    const set = (key: keyof VehiclePayload, next: unknown) => onChange({ ...value, [key]: next });
    const input = (key: keyof VehiclePayload) => ({
        value: String(value[key] ?? ''),
        onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => set(key, event.target.value),
        error: fieldError(error, key),
    });

    return (
        <div className="space-y-5">
            <fieldset className="grid gap-4 md:grid-cols-3">
                <legend className="sr-only">Vehicle identity</legend>
                <Input label="Vehicle Number" {...input('vehicle_number')} disabled={vehicleNumberReadOnly} />
                <Input label="Code" {...input('code')} />
                <Input label="Registration" {...input('registration_number')} />
                <VehicleMakeSelect value={make} onChange={(next) => { onMakeChange(next); if (!next) onModelChange(null); }} error={fieldError(error, 'vehicle_make_id')} />
                <VehicleModelSelect makeId={make?.id} value={model} onChange={onModelChange} error={fieldError(error, 'vehicle_model_id')} />
                <VehicleTypeSelect value={type} onChange={onTypeChange} error={fieldError(error, 'vehicle_type_id')} />
                <VehicleCategorySelect value={category} onChange={onCategoryChange} error={fieldError(error, 'vehicle_category_id')} />
                <Select label="Status" options={statusOptions.map((option) => ({ value: option, label: option.replaceAll('_', ' ') }))} {...input('status')} />
                <DecimalInput label="Odometer" value={String(value.odometer_reading ?? '')} onChange={(event) => set('odometer_reading', event.target.value)} error={fieldError(error, 'odometer_reading')} />
            </fieldset>

            <details className="rounded-lg border border-slate-200 bg-slate-50">
                <summary className="min-h-11 cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800">Additional identifiers and specifications</summary>
                <fieldset className="grid gap-4 border-t border-slate-200 p-4 md:grid-cols-3">
                    <legend className="sr-only">Additional vehicle details</legend>
                    <Input label="Chassis" {...input('chassis_number')} />
                    <Input label="Engine" {...input('engine_number')} />
                    <Input label="VIN" {...input('vin_number')} />
                    <Input label="Manufacture Year" type="number" {...input('manufacture_year')} />
                    <Input label="Registration Date" type="date" {...input('registration_date')} />
                    <Input label="Color" {...input('color')} />
                    <Select label="Fuel" options={fuelOptions.map((option) => ({ value: option, label: option.replaceAll('_', ' ') }))} {...input('fuel_type')} />
                    <Select label="Transmission" options={transmissionOptions.map((option) => ({ value: option, label: option.replaceAll('_', ' ') }))} {...input('transmission_type')} />
                    <Input label="Odometer Unit" {...input('odometer_unit')} />
                    <Input label="Fuel Level" {...input('fuel_level')} />
                    <div className="md:col-span-3">
                        <Textarea label="Notes" {...input('notes')} />
                    </div>
                </fieldset>
            </details>
        </div>
    );
}
