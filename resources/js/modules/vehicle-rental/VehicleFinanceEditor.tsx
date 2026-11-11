import { useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { lookupApi } from '@/shared/api/lookupApi';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { createFinanceAgreement, type StoreFinanceAgreementPayload } from './vehicleFinanceApi';

const currencies = (params: LookupLoadParams) => requestLookup<NamedResource>(endpoints.currencies, params);
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number ?? row.vehicle_number), code: String(row.vehicle_number) })) }));

function computeMaturity(startStr: string, months: number): string {
    if (!startStr) return '';
    const [datePart, timePart = '00:00'] = startStr.split('T');
    const [y, m, d] = datePart.split('-').map(Number);
    const date = new Date(y, m - 1 + (Number.isFinite(months) && months > 0 ? months : 12), d);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${timePart}`;
}

export function VehicleFinanceEditor({
    onSaved,
    onCancel,
}: {
    onSaved: () => void;
    onCancel: () => void;
}) {
    const today = businessDateInputValue();
    const initialStart = `${today}T00:00`;
    const [agreementNumber, setAgreementNumber] = useState('');
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [vehicle, setVehicle] = useState<NamedResource | null>(null);
    const [agreementDate, setAgreementDate] = useState(today);
    const [startsAt, setStartsAt] = useState(initialStart);
    const [maturesAt, setMaturesAt] = useState(() => computeMaturity(initialStart, 12));
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [principalAmount, setPrincipalAmount] = useState('1000000.00');
    const [depositAmount, setDepositAmount] = useState('0.00');
    const [residualValue, setResidualValue] = useState('0.00');
    const [interestMethod, setInterestMethod] = useState('flat');
    const [annualRate, setAnnualRate] = useState('12.00');
    const [frequency, setFrequency] = useState('monthly');
    const [count, setCount] = useState('12');
    const [termDays, setTermDays] = useState('30');
    const [remarks, setRemarks] = useState('');

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!supplier || !vehicle || !currency) return;

        if (new Date(maturesAt).getTime() <= new Date(startsAt).getTime()) {
            setError(new ApiError(
                'Vehicle finance maturity must follow start date.',
                422,
                'VALIDATION_FAILED',
                'validation',
                { matures_at: ['Vehicle finance maturity must follow start date.'] },
            ));
            return;
        }

        if (Number(depositAmount) > Number(principalAmount)) {
            setError(new ApiError(
                'Initial finance deposit cannot exceed the principal amount.',
                422,
                'VALIDATION_FAILED',
                'validation',
                { initial_deposit_amount: ['Initial finance deposit cannot exceed the principal amount.'] },
            ));
            return;
        }

        setSaving(true);
        setError(null);

        const payload: StoreFinanceAgreementPayload = {
            agreement_number: agreementNumber.trim() || undefined,
            supplier_id: supplier.id,
            vehicle_id: vehicle.id,
            agreement_date: agreementDate,
            starts_at: startsAt,
            matures_at: maturesAt,
            currency_id: currency.id,
            principal_amount: principalAmount.trim() || '0.00',
            initial_deposit_amount: depositAmount.trim() || '0.00',
            residual_value: residualValue.trim() || '0.00',
            interest_method: interestMethod,
            annual_interest_rate: annualRate.trim() || '0.00',
            installment_frequency: frequency,
            installment_count: count.trim() ? Number(count) : 1,
            payment_term_days: termDays.trim() ? Number(termDays) : 0,
            remarks: remarks.trim() || null,
        };

        try {
            await createFinanceAgreement(payload);
            onSaved();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 className="text-lg font-semibold text-slate-900">New Vehicle Finance Agreement</h3>
            <ErrorAlert error={error} inline />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Input
                    label="Agreement Number (Optional)"
                    value={agreementNumber}
                    onChange={e => setAgreementNumber(e.target.value)}
                    placeholder="Auto-generated if blank"
                />
                <LookupSelect
                    label="Financing Company / Supplier"
                    value={supplier}
                    onChange={setSupplier}
                    search={lookupApi.suppliers}
                    required
                    error={error?.fields.supplier_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
                />
                <LookupSelect
                    label="Financed Vehicle"
                    value={vehicle}
                    onChange={setVehicle}
                    search={vehicles}
                    required
                    error={error?.fields.vehicle_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Input
                    label="Agreement Date"
                    type="date"
                    value={agreementDate}
                    onChange={e => setAgreementDate(e.target.value)}
                    required
                    error={error?.fields.agreement_date?.[0]}
                />
                <Input
                    label="Finance Start Date"
                    type="datetime-local"
                    value={startsAt}
                    onChange={e => {
                        setStartsAt(e.target.value);
                        setMaturesAt(computeMaturity(e.target.value, Number(count) || 12));
                    }}
                    required
                    error={error?.fields.starts_at?.[0]}
                />
                <Input
                    label="Maturity Date"
                    type="datetime-local"
                    value={maturesAt}
                    onChange={e => setMaturesAt(e.target.value)}
                    required
                    error={error?.fields.matures_at?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <LookupSelect
                    label="Currency"
                    value={currency}
                    onChange={setCurrency}
                    search={currencies}
                    required
                    error={error?.fields.currency_id?.[0]}
                    loadOnOpen
                    minSearchLength={0}
                />
                <Input
                    label="Principal Financed Amount"
                    value={principalAmount}
                    onChange={e => setPrincipalAmount(e.target.value)}
                    required
                    inputMode="decimal"
                    error={error?.fields.principal_amount?.[0]}
                />
                <Input
                    label="Annual Interest Rate (%)"
                    value={annualRate}
                    onChange={e => setAnnualRate(e.target.value)}
                    required
                    inputMode="decimal"
                    error={error?.fields.annual_interest_rate?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Select
                    label="Interest Method"
                    value={interestMethod}
                    onChange={e => setInterestMethod(e.target.value)}
                    options={[
                        { value: 'flat', label: 'Flat Rate' },
                        { value: 'reducing', label: 'Reducing Balance' },
                    ]}
                />
                <Select
                    label="Installment Frequency"
                    value={frequency}
                    onChange={e => setFrequency(e.target.value)}
                    options={[
                        { value: 'monthly', label: 'Monthly' },
                        { value: 'quarterly', label: 'Quarterly' },
                        { value: 'weekly', label: 'Weekly' },
                    ]}
                />
                <Input
                    label="Installment Count"
                    type="number"
                    value={count}
                    onChange={e => {
                        setCount(e.target.value);
                        setMaturesAt(computeMaturity(startsAt, Number(e.target.value) || 12));
                    }}
                    required
                    min={1}
                    error={error?.fields.installment_count?.[0]}
                />
            </div>

            <details open={Boolean((depositAmount && depositAmount !== '0.00') || (residualValue && residualValue !== '0.00') || (remarks && remarks.trim()))} className="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                <summary className="cursor-pointer text-sm font-medium text-slate-700">
                    Additional lease terms & conditions (optional)
                </summary>
                <div className="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Input
                        label="Initial Deposit Paid"
                        value={depositAmount}
                        onChange={e => setDepositAmount(e.target.value)}
                        inputMode="decimal"
                        error={error?.fields.initial_deposit_amount?.[0]}
                    />
                    <Input
                        label="Residual / Balloon Value"
                        value={residualValue}
                        onChange={e => setResidualValue(e.target.value)}
                        inputMode="decimal"
                        error={error?.fields.residual_value?.[0]}
                    />
                    <Input
                        label="Payment Term (Days)"
                        type="number"
                        value={termDays}
                        onChange={e => setTermDays(e.target.value)}
                        min={0}
                    />
                    <div className="md:col-span-3">
                        <Textarea
                            label="Remarks & Conditions"
                            value={remarks}
                            onChange={e => setRemarks(e.target.value)}
                            rows={2}
                            error={error?.fields.remarks?.[0]}
                        />
                    </div>
                </div>
            </details>

            <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <Button type="submit" loading={saving} disabled={!supplier || !vehicle || !currency || !startsAt || !maturesAt}>Generate Schedule & Create Agreement</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
