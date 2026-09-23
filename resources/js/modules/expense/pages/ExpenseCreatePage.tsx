import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import {
    createExpense,
    listExpensePaymentMethods,
    listExpenseTypeOptions,
} from '../expenseApi';

export default function ExpenseCreatePage() {
    const navigate = useNavigate();
    const types = useApi((signal) => listExpenseTypeOptions(signal), []);
    const methods = useApi((signal) => listExpensePaymentMethods(signal), []);
    const [expenseTypeId, setExpenseTypeId] = useState('');
    const [paymentMethodId, setPaymentMethodId] = useState('');
    const [expenseDate, setExpenseDate] = useState(businessDateInputValue());
    const [amount, setAmount] = useState('');
    const [reference, setReference] = useState('');
    const [instrumentNumber, setInstrumentNumber] = useState('');
    const [instrumentDate, setInstrumentDate] = useState('');
    const [externalBankName, setExternalBankName] = useState('');
    const [notes, setNotes] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const selectedMethod = useMemo(
        () => methods.data?.find((row) => String(row.id) === paymentMethodId),
        [methods.data, paymentMethodId],
    );
    const referenceValid = !selectedMethod?.requires_reference || reference.trim() !== '';
    const instrumentValid = !selectedMethod?.requires_instrument_details
        || instrumentNumber.trim() !== ''
        || instrumentDate !== ''
        || externalBankName.trim() !== '';
    const canSubmit = expenseTypeId !== ''
        && paymentMethodId !== ''
        && expenseDate !== ''
        && isPositiveDecimal(amount)
        && referenceValid
        && instrumentValid
        && !busy;

    async function submit() {
        if (!canSubmit) return;
        setBusy(true);
        setError(null);
        try {
            await createExpense({
                expense_type_id: Number(expenseTypeId),
                payment_method_id: Number(paymentMethodId),
                expense_date: expenseDate,
                amount,
                reference_number: reference.trim() || undefined,
                instrument_number: instrumentNumber.trim() || undefined,
                instrument_date: instrumentDate || undefined,
                external_bank_name: externalBankName.trim() || undefined,
                notes: notes.trim() || undefined,
            });
            navigate('/expenses', { state: { message: 'Expense posted successfully.' } });
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    if (types.loading || methods.loading) return <LoadingState />;

    return (
        <>
            <ContentHeader
                title="Add expense"
                description="Record a paid expense in the currently selected branch. Posting updates the Finance ledger immediately."
            />
            <ErrorAlert error={error ?? types.error ?? methods.error} />
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void submit(); }}>
                <Panel title="Expense">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Select
                            label="Expense type"
                            value={expenseTypeId}
                            onChange={(event) => setExpenseTypeId(event.target.value)}
                            options={(types.data?.data ?? []).map((row) => ({ value: row.id, label: row.name }))}
                            hint="Choose what the business paid for."
                        />
                        <Input
                            label="Expense date"
                            type="date"
                            value={expenseDate}
                            onChange={(event) => setExpenseDate(event.target.value)}
                        />
                        <Input
                            label="Amount"
                            type="number"
                            min="0.000001"
                            step="0.000001"
                            value={amount}
                            onChange={(event) => setAmount(event.target.value)}
                            hint="This amount reduces net profit, not stored revenue."
                        />
                        <Select
                            label="Paid through"
                            value={paymentMethodId}
                            onChange={(event) => setPaymentMethodId(event.target.value)}
                            options={(methods.data ?? []).map((row) => ({ value: row.id, label: `${row.name} · ${row.type.replaceAll('_', ' ')}` }))}
                            hint="Cash methods credit Cash; all other methods credit Bank."
                        />
                        <Input
                            label="Reference"
                            value={reference}
                            onChange={(event) => setReference(event.target.value)}
                            error={!referenceValid ? 'This payment method requires a reference.' : undefined}
                            placeholder="Receipt or transaction reference"
                        />
                    </div>
                </Panel>

                {selectedMethod?.requires_instrument_details && (
                    <Panel title="Payment details">
                        <div className="grid gap-4 md:grid-cols-3">
                            <Input label="Instrument number" value={instrumentNumber} onChange={(event) => setInstrumentNumber(event.target.value)} />
                            <Input label="Instrument date" type="date" value={instrumentDate} onChange={(event) => setInstrumentDate(event.target.value)} />
                            <Input label="Bank / provider" value={externalBankName} onChange={(event) => setExternalBankName(event.target.value)} />
                        </div>
                        {!instrumentValid && <p className="mt-3 text-sm font-medium text-rose-600">Enter at least one payment instrument detail.</p>}
                    </Panel>
                )}

                <Panel title="Note">
                    <Textarea
                        label="Description"
                        value={notes}
                        onChange={(event) => setNotes(event.target.value)}
                        placeholder="Optional context for audit and reporting"
                    />
                </Panel>

                <div className="flex justify-end gap-3">
                    <Button variant="secondary" onClick={() => navigate('/expenses')} disabled={busy}>Cancel</Button>
                    <Button type="submit" loading={busy} disabled={!canSubmit}>Post expense</Button>
                </div>
            </form>
        </>
    );
}
