import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { createPurchaseDebitNote } from '../purchaseApi';
import { SupplierLookupSelect } from './PurchaseLookups';

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export function PurchaseDebitNoteForm() {
    const navigate = useNavigate();
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [debitNoteDate, setDebitNoteDate] = useState(today());
    const [amount, setAmount] = useState('0.000000');
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const errorFor = (field: string) => fieldError(error, field);

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            setBusy(true);
            setError(null);
            try {
                const saved = await createPurchaseDebitNote({
                    debit_note_date: debitNoteDate,
                    supplier_type: 'supplier',
                    supplier_id: supplier?.id ?? 0,
                    amount: amount || '0.000000',
                    reason,
                    source_type: 'supplier_debit_note_only',
                });
                navigate(`/purchase/debit-notes/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setBusy(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Supplier debit note only">
                <div className="grid gap-4 md:grid-cols-3">
                    <SupplierLookupSelect value={supplier} onChange={setSupplier} error={errorFor('supplier_id')} />
                    <Input label="Date" type="date" value={debitNoteDate} error={errorFor('debit_note_date')} onChange={(event) => setDebitNoteDate(event.target.value)} />
                    <DecimalInput label="Amount" value={amount} error={errorFor('amount')} onChange={(event) => setAmount(event.target.value)} />
                </div>
                <div className="mt-4">
                    <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
                </div>
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/debit-notes')}>Cancel</Button>
                <Button type="submit" loading={busy}>Create debit note</Button>
            </div>
        </form>
    );
}
