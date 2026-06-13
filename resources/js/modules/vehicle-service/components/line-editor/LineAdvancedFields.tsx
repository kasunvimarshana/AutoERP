import { fieldError, type ApiError } from '@/shared/api/apiError';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Select } from '@/shared/components/Select';
import {
    calculationOptions,
    type CalculationType,
    type VehicleServiceLineFormValue,
} from './lineForm';

export function LineAdvancedFields({ value, error, set, onChange }: {
    value: VehicleServiceLineFormValue;
    error: ApiError | null;
    set: <K extends keyof VehicleServiceLineFormValue>(
        key: K,
        value: VehicleServiceLineFormValue[K],
    ) => void;
    onChange: (value: VehicleServiceLineFormValue) => void;
}) {
    const external = value.source === 'external_item';

    return (
        <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <summary className="cursor-pointer font-semibold text-slate-800">Advanced</summary>
            <p className="mt-1 text-sm text-slate-500">
                Discount, tax, charge, cost, and source-specific flags.
            </p>
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                <DecimalInput
                    label="Unit cost"
                    value={value.unit_cost}
                    error={fieldError(error, 'unit_cost')}
                    onChange={(event) => set('unit_cost', event.target.value)}
                />
                <AdjustmentFields
                    label="Discount"
                    type={value.discount_type}
                    value={value.discount_value}
                    error={fieldError(
                        error,
                        value.discount_type === 'percentage' ? 'discount_rate' : 'discount_amount',
                    )}
                    onTypeChange={(type) => set('discount_type', type)}
                    onValueChange={(next) => set('discount_value', next)}
                />
                <AdjustmentFields
                    label="Tax"
                    type={value.tax_type}
                    value={value.tax_value}
                    error={fieldError(
                        error,
                        value.tax_type === 'percentage' ? 'tax_rate' : 'tax_amount',
                    )}
                    onTypeChange={(type) => set('tax_type', type)}
                    onValueChange={(next) => set('tax_value', next)}
                />
                <AdjustmentFields
                    label="Charge"
                    type={value.charge_type}
                    value={value.charge_value}
                    error={fieldError(
                        error,
                        value.charge_type === 'percentage' ? 'charge_rate' : 'charge_amount',
                    )}
                    onTypeChange={(type) => set('charge_type', type)}
                    onValueChange={(next) => set('charge_value', next)}
                />
            </div>
            <div className="mt-4 flex flex-wrap gap-5 text-sm">
                {external && (
                    <label>
                        <input
                            type="checkbox"
                            checked={value.customer_supplied}
                            onChange={(event) => onChange({
                                ...value,
                                customer_supplied: event.target.checked,
                                billable: event.target.checked ? false : value.billable,
                            })}
                        />
                        <span className="ml-2">Customer supplied</span>
                    </label>
                )}
                <label>
                    <input
                        type="checkbox"
                        checked={value.billable}
                        onChange={(event) => set('billable', event.target.checked)}
                    />
                    <span className="ml-2">Billable</span>
                </label>
            </div>
        </details>
    );
}

function AdjustmentFields({ label, type, value, error, onTypeChange, onValueChange }: {
    label: string;
    type: CalculationType;
    value: string;
    error?: string;
    onTypeChange: (type: CalculationType) => void;
    onValueChange: (value: string) => void;
}) {
    return (
        <div className="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-3 sm:col-span-2">
            <Select
                label={`${label} type`}
                value={type}
                options={calculationOptions}
                onChange={(event) => onTypeChange(event.target.value as CalculationType)}
            />
            <DecimalInput
                label={`${label} value`}
                value={value}
                error={error}
                onChange={(event) => onValueChange(event.target.value)}
            />
        </div>
    );
}
