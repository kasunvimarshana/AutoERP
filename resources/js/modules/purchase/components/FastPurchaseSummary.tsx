import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import type { FastPurchaseResult } from '../purchaseTypes';

const emptySummary = {
    subtotal: '0.000000',
    discount_total: '0.000000',
    tax_total: '0.000000',
    withholding_total: '0.000000',
    grand_total: '0.000000',
    paid_total: '0.000000',
    balance_due: '0.000000',
};

interface FastPurchaseSummaryProps {
    preview: FastPurchaseResult | null;
    result: FastPurchaseResult | null;
    submitting: boolean;
    previewing: boolean;
    canSubmit: boolean;
    onPreview: () => void;
}

export function FastPurchaseSummary({ preview, result, submitting, previewing, canSubmit, onPreview }: FastPurchaseSummaryProps) {
    const active = result ?? preview;
    const summary = active?.summary ?? emptySummary;
    const mode = active?.mode ? active.mode.replaceAll('_', ' ') : 'draft';
    const documents = result?.documents;
    const links = [
        documents?.goods_receipt,
        documents?.supplier_invoice,
        documents?.supplier_payment,
        ...(documents?.inventory_transactions ?? []),
        ...(documents?.finance_postings ?? []),
    ].filter(Boolean);

    return (
        <aside className="sticky bottom-0 top-4 space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:w-80">
            <div className="flex items-center justify-between gap-3">
                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{mode}</span>
                <Button type="button" variant="secondary" loading={previewing} onClick={onPreview}>Preview</Button>
            </div>
            <dl className="space-y-2 text-sm">
                {[
                    ['Subtotal', summary.subtotal],
                    ['Discount', summary.discount_total],
                    ['Tax', summary.tax_total],
                    ['Withholding', summary.withholding_total],
                    ['Grand total', summary.grand_total],
                    ['Paid', summary.paid_total],
                    ['Balance', summary.balance_due],
                ].map(([label, value]) => (
                    <div key={label} className="flex items-center justify-between gap-3">
                        <dt className="text-slate-500">{label}</dt>
                        <dd className="font-semibold text-slate-900">{value}</dd>
                    </div>
                ))}
            </dl>
            <Button type="submit" className="w-full" loading={submitting} disabled={!canSubmit}>Create fast purchase</Button>
            {links.length > 0 && (
                <div className="border-t border-slate-200 pt-3 text-sm">
                    <div className="space-y-1.5">
                        {links.map((link) => link && (
                            <Link key={`${link.url}-${link.number}`} className="block rounded-md px-2 py-1 font-medium text-blue-700 hover:bg-blue-50" to={link.url}>
                                {link.number}
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </aside>
    );
}
