import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { JournalForm } from '../components/JournalForm';
import { getFinanceLookups, getJournal, updateJournal, type FinanceLookups, type JournalPayload } from '../financeApi';

export default function FinanceJournalEditPage() {
    const journalId = Number(useParams().id);
    const navigate = useNavigate();
    const [form, setForm] = useState<JournalPayload | null>(null);
    const [lookups, setLookups] = useState<FinanceLookups | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        Promise.all([getJournal(journalId, controller.signal), getFinanceLookups(controller.signal)])
            .then(([journal, options]) => {
                if (controller.signal.aborted) return;
                if (!journal.can_edit) throw new Error('Only draft journals can be edited.');
                setLookups(options);
                setForm({
                    journal_date: journal.journal_date,
                    journal_type: journal.journal_type,
                    fiscal_period_id: journal.fiscal_period?.id ?? null,
                    posting_profile_id: journal.posting_profile?.id ?? null,
                    source_module: journal.source_module,
                    source_type: journal.source_type,
                    source_id: journal.source_id,
                    source_number: journal.source_number,
                    source_date: journal.source_date,
                    description: journal.description,
                    exchange_rate: journal.exchange_rate ?? '1.000000',
                    lines: (journal.lines ?? []).map((line, index) => ({
                        account_id: line.account ? Number(line.account.id) : line.account_id,
                        line_number: index + 1,
                        description: line.description,
                        debit: line.debit,
                        credit: line.credit,
                    })),
                });
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [journalId]);

    if (loading) return <LoadingState />;
    if (!form || !lookups) return <ErrorAlert error={error} />;

    async function save() {
        if (!form) return;
        setSubmitting(true);
        setError(null);
        try {
            const journal = await updateJournal(journalId, form);
            navigate(`/finance/journals/${journal.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }

    return <>
        <ContentHeader title="Edit journal draft" description="Posted journals are read-only and corrected through reversal." />
        <ErrorAlert error={error} />
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
            <JournalForm value={form} onChange={setForm} lookups={lookups} error={error} />
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Cancel</Button>
                <Button type="submit" loading={submitting}>Save draft</Button>
            </div>
        </form>
    </>;
}
