import { useState, type FormEvent } from 'react';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { createAgreementSuccessor } from './agreementApi';
import { AgreementKind, type Agreement } from './agreements';

export function AgreementSuccessorForm({ kind, agreement, onSaved, onCancel }: { kind: AgreementKind; agreement: Agreement; onSaved: () => void; onCancel: () => void }) {
    const [reference, setReference] = useState('');
    const [agreedOn, setAgreedOn] = useState('');
    const [executingOn, setExecutingOn] = useState('');
    const [startsOn, setStartsOn] = useState('');
    const [endsOn, setEndsOn] = useState('');
    const [reason, setReason] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true); setError(null);
        try {
            await createAgreementSuccessor(kind, agreement, { reference, agreed_on: agreedOn, executing_on: executingOn || null, starts_on: startsOn, ends_on: endsOn || null, reason });
            onSaved();
        } catch (failure) { setError(toApiError(failure)); }
        finally { setSaving(false); }
    }

    return <form onSubmit={submit} className="space-y-3 border-t pt-4" aria-label="Create successor agreement">
        <h3 className="font-semibold">Future agreement revision</h3>
        <p className="text-sm text-slate-600">Create a Draft for future commercial terms without interrupting the current agreement. The predecessor remains Active while this Draft is reviewed. Activate the successor on or after its effective start date; activation revalidates vehicle-use and financial cutover conflicts, then closes the predecessor at the effective boundary in the same transaction.</p>
        <p className="text-sm text-slate-600">Existing commercial terms are copied for review, except the security-deposit requirement. A deposit belongs to its original Payment source and is never duplicated automatically; record a new deposit requirement on the successor Draft only when the amended agreement explicitly requires it.</p>
        <ErrorAlert error={error} inline />
        <fieldset disabled={saving} className="grid gap-3 sm:grid-cols-2">
            <Input label="New agreement reference" value={reference} onChange={event => setReference(event.target.value)} required error={error?.fields.reference?.[0]} />
            <Input label="Agreement date" type="date" value={agreedOn} onChange={event => setAgreedOn(event.target.value)} required error={error?.fields.agreed_on?.[0]} />
            <Input label="Executing date" type="date" value={executingOn} onChange={event => setExecutingOn(event.target.value)} error={error?.fields.executing_on?.[0]} />
            <Input label="Effective start date" type="date" value={startsOn} onChange={event => setStartsOn(event.target.value)} required error={error?.fields.starts_on?.[0]} />
            <Input label="End date" type="date" value={endsOn} onChange={event => setEndsOn(event.target.value)} error={error?.fields.ends_on?.[0]} />
            <Input label="Revision reason" value={reason} onChange={event => setReason(event.target.value)} required error={error?.fields.reason?.[0]} />
        </fieldset>
        <div className="flex gap-2"><Button type="submit" loading={saving} disabled={!reference.trim() || !agreedOn || !startsOn || !reason.trim()}>Create successor draft</Button><Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button></div>
    </form>;
}
