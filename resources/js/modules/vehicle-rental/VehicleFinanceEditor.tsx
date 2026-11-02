import { useState, useEffect, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { apiClient } from '@/shared/api/apiClient';
import { createFinanceAgreement, type StoreFinanceAgreementPayload } from './vehicleFinanceApi';

interface Option { value: number; label: string }

function computeMaturity(startStr: string, months: number): string {
    if (!startStr) return '';
    const d = new Date(startStr);
    d.setMonth(d.getMonth() + (Number.isFinite(months) && months > 0 ? months : 12));
    return d.toISOString().slice(0, 16);
}

export function VehicleFinanceEditor({
    onSaved,
    onCancel,
}: {
    onSaved: () => void;
    onCancel: () => void;
}) {
    const initialStart = new Date().toISOString().slice(0, 10) + 'T00:00';
    const [agreementNumber, setAgreementNumber] = useState('');
    const [supplierId, setSupplierId] = useState('');
    const [vehicleId, setVehicleId] = useState('');
    const [agreementDate, setAgreementDate] = useState(new Date().toISOString().slice(0, 10));
    const [startsAt, setStartsAt] = useState(initialStart);
    const [maturesAt, setMaturesAt] = useState(() => computeMaturity(initialStart, 12));
    const [currencyId, setCurrencyId] = useState('');
    const [principalAmount, setPrincipalAmount] = useState('1000000.00');
    const [depositAmount, setDepositAmount] = useState('0.00');
    const [residualValue, setResidualValue] = useState('0.00');
    const [interestMethod, setInterestMethod] = useState('flat');
    const [annualRate, setAnnualRate] = useState('12.00');
    const [frequency, setFrequency] = useState('monthly');
    const [count, setCount] = useState('12');
    const [termDays, setTermDays] = useState('30');
    const [remarks, setRemarks] = useState('');

    const [suppliers, setSuppliers] = useState<Option[]>([]);
    const [vehicles, setVehicles] = useState<Option[]>([]);
    const [currencies, setCurrencies] = useState<Option[]>([]);

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        apiClient.get<{ data: Array<{ id: number; name: string }> }>('/api/v1/suppliers?per_page=100')
            .then(r => setSuppliers(r.data.data.map(s => ({ value: s.id, label: s.name }))))
            .catch(() => {});
        apiClient.get<{ data: Array<{ id: number; vehicle_number: string; registration_number?: string }> }>('/api/v1/vehicles?per_page=100')
            .then(r => setVehicles(r.data.data.map(v => ({ value: v.id, label: v.registration_number ?? v.vehicle_number }))))
            .catch(() => {});
        apiClient.get<{ data: Array<{ id: number; code: string; name: string }> }>('/api/v1/reference-data/currencies')
            .then(r => {
                setCurrencies(r.data.data.map(c => ({ value: c.id, label: `${c.code} – ${c.name}` })));
                if (!currencyId && r.data.data.length > 0) setCurrencyId(String(r.data.data[0].id));
            })
            .catch(() => {});
    }, [currencyId]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const payload: StoreFinanceAgreementPayload = {
            agreement_number: agreementNumber.trim() || undefined,
            supplier_id: Number(supplierId),
            vehicle_id: Number(vehicleId),
            agreement_date: agreementDate,
            starts_at: startsAt,
            matures_at: maturesAt,
            currency_id: Number(currencyId),
            principal_amount: principalAmount,
            initial_deposit_amount: depositAmount,
            residual_value: residualValue,
            interest_method: interestMethod,
            annual_interest_rate: annualRate,
            installment_frequency: frequency,
            installment_count: Number(count),
            payment_term_days: Number(termDays),
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
                <Select
                    label="Financing Company / Supplier"
                    value={supplierId}
                    onChange={e => setSupplierId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select supplier/bank…' }, ...suppliers]}
                    error={error?.fields.supplier_id?.[0]}
                />
                <Select
                    label="Financed Vehicle"
                    value={vehicleId}
                    onChange={e => setVehicleId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select vehicle…' }, ...vehicles]}
                    error={error?.fields.vehicle_id?.[0]}
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

            <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
                <Select
                    label="Currency"
                    value={currencyId}
                    onChange={e => setCurrencyId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select currency…' }, ...currencies]}
                    error={error?.fields.currency_id?.[0]}
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
                    label="Annual Interest Rate (%)"
                    value={annualRate}
                    onChange={e => setAnnualRate(e.target.value)}
                    required
                    inputMode="decimal"
                    error={error?.fields.annual_interest_rate?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                <Input
                    label="Payment Term (Days)"
                    type="number"
                    value={termDays}
                    onChange={e => setTermDays(e.target.value)}
                    min={0}
                />
            </div>

            <Textarea
                label="Remarks & Conditions"
                value={remarks}
                onChange={e => setRemarks(e.target.value)}
                rows={2}
                error={error?.fields.remarks?.[0]}
            />

            <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <Button type="submit" loading={saving}>Generate Schedule & Create Agreement</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
