import { useState, useEffect, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { apiClient } from '@/shared/api/apiClient';
import { createRentalExpense, type StoreRentalExpensePayload } from './rentalExpenseApi';

interface Option { value: number; label: string }

export function RentalExpenseEditor({
    onSaved,
    onCancel,
}: {
    onSaved: () => void;
    onCancel: () => void;
}) {
    const [expenseNumber, setExpenseNumber] = useState('');
    const [vehicleId, setVehicleId] = useState('');
    const [expenseType, setExpenseType] = useState('fuel');
    const [expenseDate, setExpenseDate] = useState(new Date().toISOString().slice(0, 10));
    const [currencyId, setCurrencyId] = useState('');
    const [netAmount, setNetAmount] = useState('0.00');
    const [taxAmount, setTaxAmount] = useState('0.00');
    const [supplierId, setSupplierId] = useState('');
    const [referenceNumber, setReferenceNumber] = useState('');
    const [description, setDescription] = useState('');

    const [vehicles, setVehicles] = useState<Option[]>([]);
    const [suppliers, setSuppliers] = useState<Option[]>([]);
    const [currencies, setCurrencies] = useState<Option[]>([]);

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        apiClient.get<{ data: Array<{ id: number; vehicle_number: string; registration_number?: string }> }>('/api/v1/vehicles?per_page=100')
            .then(r => setVehicles(r.data.data.map(v => ({ value: v.id, label: v.registration_number ?? v.vehicle_number }))))
            .catch(() => {});
        apiClient.get<{ data: Array<{ id: number; name: string }> }>('/api/v1/suppliers?per_page=100')
            .then(r => setSuppliers(r.data.data.map(s => ({ value: s.id, label: s.name }))))
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

        const payload: StoreRentalExpensePayload = {
            expense_number: expenseNumber.trim() || undefined,
            vehicle_id: Number(vehicleId),
            expense_type: expenseType,
            expense_date: expenseDate,
            currency_id: Number(currencyId),
            net_amount: netAmount,
            tax_amount: taxAmount,
            supplier_id: supplierId ? Number(supplierId) : null,
            reference_number: referenceNumber.trim() || null,
            description: description.trim() || null,
        };

        try {
            await createRentalExpense(payload);
            onSaved();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={submit} className="mt-4 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 className="text-lg font-semibold text-slate-900">Record Rental Fleet Expense</h3>
            <ErrorAlert error={error} inline />

            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Input
                    label="Expense Number (Optional)"
                    value={expenseNumber}
                    onChange={e => setExpenseNumber(e.target.value)}
                    placeholder="Auto-generated if blank"
                />
                <Select
                    label="Vehicle"
                    value={vehicleId}
                    onChange={e => setVehicleId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select vehicle…' }, ...vehicles]}
                    error={error?.fields.vehicle_id?.[0]}
                />
                <Select
                    label="Expense Type"
                    value={expenseType}
                    onChange={e => setExpenseType(e.target.value)}
                    options={[
                        { value: 'fuel', label: 'Fuel' },
                        { value: 'toll', label: 'Highway Toll' },
                        { value: 'maintenance', label: 'Maintenance & Repairs' },
                        { value: 'fine', label: 'Traffic Fine' },
                        { value: 'other', label: 'Other Operational Cost' },
                    ]}
                />
                <Input
                    label="Expense Date"
                    type="date"
                    value={expenseDate}
                    onChange={e => setExpenseDate(e.target.value)}
                    required
                    error={error?.fields.expense_date?.[0]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Select
                    label="Currency"
                    value={currencyId}
                    onChange={e => setCurrencyId(e.target.value)}
                    required
                    options={[{ value: '', label: 'Select currency…' }, ...currencies]}
                    error={error?.fields.currency_id?.[0]}
                />
                <Input
                    label="Net Amount"
                    value={netAmount}
                    onChange={e => setNetAmount(e.target.value)}
                    required
                    inputMode="decimal"
                    error={error?.fields.net_amount?.[0]}
                />
                <Input
                    label="Tax Amount"
                    value={taxAmount}
                    onChange={e => setTaxAmount(e.target.value)}
                    inputMode="decimal"
                    error={error?.fields.tax_amount?.[0]}
                />
                <Select
                    label="Supplier / Station (Optional)"
                    value={supplierId}
                    onChange={e => setSupplierId(e.target.value)}
                    options={[{ value: '', label: 'None / Cash purchase' }, ...suppliers]}
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Input
                    label="Bill / Receipt Reference"
                    value={referenceNumber}
                    onChange={e => setReferenceNumber(e.target.value)}
                    placeholder="e.g. Receipt #12345"
                />
                <Textarea
                    label="Description"
                    value={description}
                    onChange={e => setDescription(e.target.value)}
                    rows={2}
                />
            </div>

            <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                <Button type="submit" loading={saving}>Save Expense Record</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
