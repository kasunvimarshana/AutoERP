import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { JournalForm } from '../components/JournalForm';
import { createJournal, getFinanceLookups, type JournalPayload } from '../financeApi';

const initial: JournalPayload = {
    journal_date: new Date().toISOString().slice(0, 10),
    journal_type: 'general',
    exchange_rate: '1.000000',
    lines: [
        { account_id: null, line_number: 1, debit: '0.000000', credit: '0.000000' },
        { account_id: null, line_number: 2, debit: '0.000000', credit: '0.000000' },
    ],
};

export default function FinanceJournalCreatePage() {
    const navigate = useNavigate();
    const lookups = useApi(getFinanceLookups, []);
    const [form, setForm] = useState(initial);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    if (lookups.loading) return <LoadingState />;
    if (!lookups.data) return <ErrorAlert error={lookups.error} />;

    async function save() {
        setSubmitting(true);
        setError(null);
        try {
            const journal = await createJournal(form);
            navigate(`/finance/journals/${journal.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return <>
        <ContentHeader title="New journal entry" description="Create a balanced draft for review and posting." />
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <JournalForm value={form} onChange={setForm} lookups={lookups.data} error={error} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create draft</Button>
            </div>
        </form>
    </>;
}
