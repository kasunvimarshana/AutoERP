import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type {
    RentalAgreementKind,
    RentalBillingBasis,
    RentalRateCode,
    RentalRateLine,
    RentalRateUnit,
} from '../vehicleRentalTypes';

const EDITABLE_RATE_CODES: Array<{ value: RentalRateCode; label: string }> = [
    { value: 'base_rental', label: 'Base rental' },
    { value: 'excess_km', label: 'Excess kilometre' },
    { value: 'non_ac', label: 'Non-AC day' },
    { value: 'front_ac', label: 'Front AC day' },
    { value: 'dual_ac', label: 'Dual AC day' },
    { value: 'driver_salary', label: 'Driver salary' },
    { value: 'normal_overtime', label: 'Normal overtime' },
    { value: 'double_overtime', label: 'Double overtime' },
    { value: 'triple_overtime', label: 'Triple overtime' },
    { value: 'night_out', label: 'Night out' },
];

const UNSUPPORTED_OTHER_RATE = { value: 'other' as const, label: 'Unsupported fixed charge — remove this row' };

const UNITS_BY_CODE: Record<RentalRateCode, RentalRateUnit[]> = {
    base_rental: ['day', 'month'],
    excess_km: ['kilometre'],
    non_ac: ['day'],
    front_ac: ['day'],
    dual_ac: ['day'],
    driver_salary: ['day', 'month'],
    normal_overtime: ['hour'],
    double_overtime: ['hour'],
    triple_overtime: ['hour'],
    night_out: ['occurrence'],
    other: ['fixed'],
};

export function defaultRentalRate(billingBasis: RentalBillingBasis): RentalRateLine {
    return {
        code: 'base_rental',
        unit: billingBasis === 'monthly' ? 'month' : 'day',
        rate: '0',
        is_taxable: false,
        description: null,
    };
}

export function normalizeRatesForAgreement(
    rates: RentalRateLine[],
    kind: RentalAgreementKind,
    billingBasis: RentalBillingBasis,
): RentalRateLine[] {
    const allowed = rates.filter((rate) => !(kind === 'owner' || billingBasis === 'monthly') || !isAcCode(rate.code));
    const normalized: RentalRateLine[] = allowed.map((rate): RentalRateLine => {
        if (rate.code === 'base_rental') {
            return { ...rate, unit: billingBasis === 'monthly' ? 'month' : 'day' };
        }
        if (!UNITS_BY_CODE[rate.code].includes(rate.unit)) {
            return { ...rate, unit: UNITS_BY_CODE[rate.code][0] };
        }
        return rate;
    });

    return hasCommercialBase(normalized)
        ? normalized
        : [defaultRentalRate(billingBasis), ...normalized];
}

export function RentalRateEditor({
    rates,
    onChange,
    kind,
    billingBasis,
    error,
    disabled = false,
}: {
    rates: RentalRateLine[];
    onChange: (rates: RentalRateLine[]) => void;
    kind: RentalAgreementKind;
    billingBasis: RentalBillingBasis;
    error?: ApiError | null;
    disabled?: boolean;
}) {
    const usedCodes = new Set(rates.map((rate) => rate.code));
    const availableCodes = EDITABLE_RATE_CODES.filter((option) => {
        if (usedCodes.has(option.value)) return false;
        if ((kind === 'owner' || billingBasis === 'monthly') && isAcCode(option.value)) return false;
        if (hasAcRate(rates) && option.value === 'base_rental') return false;
        if (hasBaseRate(rates) && isAcCode(option.value)) return false;
        return true;
    });
    const hasUnsupportedRate = rates.some((rate) => rate.code === 'other');

    const update = (index: number, patch: Partial<RentalRateLine>) => {
        onChange(rates.map((rate, candidate) => candidate === index ? { ...rate, ...patch } : rate));
    };

    const add = () => {
        const code = availableCodes[0]?.value;
        if (!code) return;
        onChange([...rates, {
            code,
            unit: defaultUnit(code, billingBasis),
            rate: '0',
            is_taxable: false,
            description: null,
        }]);
    };

    return (
        <section className="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-900">Commercial rates</h3>
                    <p className="text-sm text-slate-600">Add only the charges that apply to this agreement. Base rental and AC-mode day rates cannot be combined.</p>
                </div>
                <Button type="button" variant="secondary" onClick={add} disabled={disabled || availableCodes.length === 0}>Add rate</Button>
            </div>
            {hasUnsupportedRate && (
                <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                    Fixed “Other” rates are not supported by automatic rental calculations. Remove the row and record approved extras through the governed adjustment workflow.
                </p>
            )}
            {rates.map((rate, index) => {
                const codeOptions = rentalRateCodeOptions(rates, index, kind, billingBasis);
                const units = UNITS_BY_CODE[rate.code];
                return (
                    <div key={`${rate.id ?? 'new'}-${index}`} className="grid gap-3 rounded-lg border border-slate-200 bg-white p-3 md:grid-cols-12">
                        <div className="md:col-span-3">
                            <Select
                                label="Rate type"
                                value={rate.code}
                                options={codeOptions}
                                disabled={disabled || rate.code === 'other'}
                                error={fieldError(error ?? null, `rates.${index}.code`)}
                                onChange={(event) => {
                                    const code = event.target.value as RentalRateCode;
                                    update(index, { code, unit: defaultUnit(code, billingBasis) });
                                }}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Select
                                label="Unit"
                                value={rate.unit}
                                options={units.map((unit) => ({ value: unit, label: unit.replaceAll('_', ' ') }))}
                                disabled={disabled || rate.code === 'other' || units.length === 1}
                                error={fieldError(error ?? null, `rates.${index}.unit`)}
                                onChange={(event) => update(index, { unit: event.target.value as RentalRateUnit })}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <Input
                                label="Rate"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                disabled={disabled || rate.code === 'other'}
                                value={rate.rate}
                                error={fieldError(error ?? null, `rates.${index}.rate`)}
                                onChange={(event) => update(index, { rate: event.target.value })}
                            />
                        </div>
                        <div className="md:col-span-3">
                            <Input
                                label="Description"
                                maxLength={255}
                                disabled={disabled || rate.code === 'other'}
                                value={rate.description ?? ''}
                                error={fieldError(error ?? null, `rates.${index}.description`)}
                                onChange={(event) => update(index, { description: event.target.value || null })}
                            />
                        </div>
                        <div className="flex items-end gap-3 md:col-span-2">
                            <label className="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={rate.is_taxable}
                                    disabled={disabled || rate.code === 'other'}
                                    onChange={(event) => update(index, { is_taxable: event.target.checked })}
                                />
                                Taxable
                            </label>
                            <Button type="button" variant="ghost" className="mb-0" disabled={disabled || rates.length === 1} onClick={() => onChange(rates.filter((_, candidate) => candidate !== index))}>Remove</Button>
                        </div>
                    </div>
                );
            })}
            {fieldError(error ?? null, 'rates') ? <p className="text-sm text-rose-600">{fieldError(error ?? null, 'rates')}</p> : null}
        </section>
    );
}

function defaultUnit(code: RentalRateCode, billingBasis: RentalBillingBasis): RentalRateUnit {
    if (code === 'base_rental' || code === 'driver_salary') return billingBasis === 'monthly' ? 'month' : 'day';
    return UNITS_BY_CODE[code][0];
}

function isAcCode(code: RentalRateCode): boolean {
    return code === 'non_ac' || code === 'front_ac' || code === 'dual_ac';
}

function hasAcRate(rates: RentalRateLine[]): boolean {
    return rates.some((rate) => isAcCode(rate.code));
}

function hasBaseRate(rates: RentalRateLine[]): boolean {
    return rates.some((rate) => rate.code === 'base_rental');
}

function hasCommercialBase(rates: RentalRateLine[]): boolean {
    return hasBaseRate(rates) || hasAcRate(rates);
}

export function rentalRateCodeOptions(
    rates: RentalRateLine[],
    index: number,
    kind: RentalAgreementKind,
    billingBasis: RentalBillingBasis,
): Array<{ value: RentalRateCode; label: string }> {
    const current = rates[index];
    const otherRates = rates.filter((_, candidate) => candidate !== index);
    const usedByOtherRows = new Set(otherRates.map((rate) => rate.code));
    const options = current.code === 'other'
        ? [UNSUPPORTED_OTHER_RATE, ...EDITABLE_RATE_CODES]
        : EDITABLE_RATE_CODES;

    return options.filter((option) => {
        if (option.value === current.code) return true;
        if (usedByOtherRows.has(option.value)) return false;
        if ((kind === 'owner' || billingBasis === 'monthly') && isAcCode(option.value)) return false;
        if (hasAcRate(otherRates) && option.value === 'base_rental') return false;
        if (hasBaseRate(otherRates) && isAcCode(option.value)) return false;
        return true;
    });
}
