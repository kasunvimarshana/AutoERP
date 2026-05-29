import { PageHeader } from '../../../shared/components/business/PageHeader';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { JournalEntryLineTable, JournalPostingPreviewPanel } from '../components/FinanceComponents';
import { postingPreview } from '../mock/financeMock';

export function FinancePostingPreviewPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Finance"
                subtitle="Standalone posting preview for source events or manual journal lines. Backend returns journal impact, warnings, and eligibility."
                title="Finance Posting Preview"
            />
            <FormSection description="Send source context or draft journal lines to backend. Frontend does not calculate posting impact." title="Preview Context">
                <div className="grid gap-4 md:grid-cols-4">
                    <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Source module</span><Select><option>Manual Journal</option><option>Payment</option><option>Inventory</option><option>Voucher</option><option>Document</option></Select></label>
                    <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Source type</span><Input placeholder="payment, movement, voucher..." /></label>
                    <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Source reference</span><Input placeholder="Reference number" /></label>
                    <label className="space-y-2 text-sm"><span className="font-semibold text-slate-700">Preview date</span><Input type="date" defaultValue="2026-05-30" /></label>
                    <label className="space-y-2 text-sm md:col-span-4"><span className="font-semibold text-slate-700">Notes</span><Textarea placeholder="Optional posting preview notes" /></label>
                </div>
                <div className="mt-4 flex justify-end"><Button variant="blue">Preview Posting</Button></div>
            </FormSection>
            <JournalPostingPreviewPanel preview={postingPreview} />
            <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Backend Journal Lines</h2>
                <JournalEntryLineTable rows={postingPreview.journalLines.map((line, index) => ({ ...line, id: `preview-line-${index}` }))} />
            </section>
        </div>
    );
}
