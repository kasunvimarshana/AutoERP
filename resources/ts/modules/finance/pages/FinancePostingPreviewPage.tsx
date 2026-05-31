import { useState, type FormEvent } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { ApiErrorBanner, JournalEntryLineTable, JournalPostingPreviewPanel } from '../components/FinanceComponents';
import { financeApi } from '../services/financeApi';
import type { FinancePostingPreview } from '../types/finance.types';

export function FinancePostingPreviewPage() {
    const [preview, setPreview] = useState<FinancePostingPreview>();
    const [error, setError] = useState<Error | null>(null);
    const [loading, setLoading] = useState(false);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const payload = {
            journal_entry_id: form.get('journalEntryId') ? Number(form.get('journalEntryId')) : undefined,
            posting_date: String(form.get('postingDate') ?? '') || undefined,
            source_module: String(form.get('sourceModule') ?? '') || undefined,
            source_reference: String(form.get('sourceReference') ?? '') || undefined,
            source_type: String(form.get('sourceType') ?? '') || undefined,
        };

        setLoading(true);
        financeApi.previewSourcePosting(payload)
            .then((response) => {
                setPreview(response);
                setError(null);
            })
            .catch((caught: Error) => setError(caught))
            .finally(() => setLoading(false));
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Finance"
                subtitle="Standalone posting preview for a persisted journal entry or generic source context. Backend returns journal impact, warnings, and eligibility."
                title="Finance Posting Preview"
            />
            <ApiErrorBanner error={error} />
            <form onSubmit={submit}>
                <FormSection description="Provide a journal entry id for authoritative backend preview. Source fields are carried as context only." title="Preview Context">
                    <div className="grid gap-4 md:grid-cols-4">
                        <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Journal entry id</span><Input inputMode="numeric" name="journalEntryId" placeholder="Persisted journal id" /></label>
                        <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Source module</span><Select name="sourceModule"><option value="">Manual Journal</option><option value="payment">Payment</option><option value="inventory">Inventory</option><option value="document">Document</option></Select></label>
                        <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Source type</span><Input name="sourceType" placeholder="payment, movement, document..." /></label>
                        <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Preview date</span><Input name="postingDate" type="date" /></label>
                        <label className="space-y-2 text-sm md:col-span-2"><span className="font-semibold text-slate-700">Source reference</span><Input name="sourceReference" placeholder="Reference number" /></label>
                        <label className="space-y-2 text-sm md:col-span-2"><span className="font-semibold text-slate-700">Notes</span><Textarea placeholder="Optional preview notes. Backend ignores unsupported fields." /></label>
                    </div>
                    <div className="mt-4 flex justify-end"><Button disabled={loading} type="submit" variant="blue">{loading ? 'Previewing...' : 'Preview Posting'}</Button></div>
                </FormSection>
            </form>
            <JournalPostingPreviewPanel preview={preview} />
            {preview?.journalLines.length ? (
                <section className="space-y-3">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Backend Journal Lines</h2>
                    <JournalEntryLineTable rows={preview.journalLines.map((line, index) => ({ ...line, costCenter: '', id: `preview-line-${index}`, party: '', taxRate: '' }))} />
                </section>
            ) : null}
        </div>
    );
}
