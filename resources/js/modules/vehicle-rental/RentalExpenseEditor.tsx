import { useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { lookupApi } from '@/shared/api/lookupApi';
import { requestLookup } from '@/shared/api/lookupRequest';
import { endpoints } from '@/shared/api/endpoints';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { createRentalExpense, ExpenseType, type StoreRentalExpensePayload } from './rentalExpenseApi';

const currencies = (params: LookupLoadParams) => requestLookup<NamedResource>(endpoints.currencies, params);
const vehicles = (params: LookupLoadParams) => requestLookup<Record<string, unknown>>(`${endpoints.vehicles}/lookup/active`, params)
    .then(result => ({ ...result, data: result.data.map(row => ({ id: Number(row.id), name: String(row.registration_number ?? row.vehicle_number), code: String(row.vehicle_number) })) }));

export function RentalExpenseEditor({
    onSaved,
    onCancel,
}: {
    onSaved: () => void;
    onCancel: () => void;
}) {
    const [expenseNumber, setExpenseNumber] = useState('');
    const [vehicle, setVehicle] = useState<NamedResource | null>(null);
    const [expenseType, setExpenseType] = useState<ExpenseType>(ExpenseType.Fuel);
    const [expenseDate, setExpenseDate] = useState(businessDateInputValue());
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [netAmount, setNetAmount] = useState('0.00');
    const [taxAmount, setTaxAmount] = useState('0.00');
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [referenceNumber, setReferenceNumber] = useState('');
    const [description, setDescription] = useState('');

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!vehicle || !currency) return;
        setSaving(true);
        setError(null);

        const payload: StoreRentalExpensePayload = {
            expense_number: expenseNumber.trim() || undefined,
            vehicle_id: vehicle.id,
            expense_type: expenseType,
            expense_date: expenseDate,
            currency_id: currency.id,
            net_amount: netAmount.trim() || '0.00',
            tax_amount: taxAmount.trim() || '0.00',
            supplier_id: supplier ? supplier.id : null,
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
                <LookupSelect
                    label="Vehicle"
                    value={vehicle}
                    onChange={setVehicle}
                    search={vehicles}
                    required
                    error={error?.fields.vehicle_id?.[0]}
                />
                <Select
                    label="Expense Type"
                    value={expenseType}
                    onChange={e => setExpenseType(e.target.value as ExpenseType)}
                    options={[
                        { value: ExpenseType.Fuel, label: 'Fuel' },
                        { value: ExpenseType.Toll, label: 'Highway Toll' },
                        { value: ExpenseType.Maintenance, label: 'Maintenance & Repairs' },
                        { value: ExpenseType.Fine, label: 'Traffic Fine' },
                        { value: ExpenseType.Other, label: 'Other Operational Cost' },
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
                <LookupSelect
                    label="Currency"
                    value={currency}
                    onChange={setCurrency}
                    search={currencies}
                    required
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
                <LookupSelect
                    label="Supplier / Station (Optional)"
                    value={supplier}
                    onChange={setSupplier}
                    search={lookupApi.suppliers}
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
                <Button type="submit" loading={saving} disabled={!vehicle || !currency || !expenseDate || !netAmount}>Save Expense Record</Button>
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
            </div>
        </form>
    );
}
