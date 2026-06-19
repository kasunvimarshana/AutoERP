import { Link } from 'react-router-dom';
import type { FastPurchaseResult } from '../purchaseTypes';

const emptySummary = {
    subtotal: '0.000000',
    discount_total: '0.000000',
    tax_total: '0.000000',
    withholding_total: '0.000000',
    charge_total: '0.000000',
    adjustment_total: '0.000000',
    grand_total: '0.000000',
    paid_total: '0.000000',
    balance_due: '0.000000',
};

interface FastPurchaseSummaryProps {
    preview: FastPurchaseResult | null;
    result: FastPurchaseResult | null;
    stale?: boolean;
}

export function FastPurchaseSummary({ preview, result, stale }: FastPurchaseSummaryProps) {
    const active = result ?? preview;
    const summary = active?.summary ?? emptySummary;
    const mode = active?.mode ? active.mode.replaceAll('_', ' ') : 'draft';
    const options = active?.options;
    const impact = [
        'Purchase Order',
        options?.receive_stock_now ? 'Goods Receipt' : null,
        options?.create_supplier_invoice_now ? 'Supplier Invoice' : null,
        options?.record_payment_now ? 'Payment' : null,
    ].filter(Boolean);
    const documents = result?.documents;
    const links = [
        documents?.purchase_order,
        documents?.goods_receipt,
        documents?.supplier_invoice,
        documents?.supplier_payment,
        ...(documents?.inventory_transactions ?? []),
        ...(documents?.finance_postings ?? []),
    ].filter(Boolean);

    return (
        <div className="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-700">{mode}</span>
            </div>
            <dl className="space-y-2 text-sm">
                {[
                    ['Subtotal', summary.subtotal],
                    ['Discount', summary.discount_total],
                    ['Tax', summary.tax_total],
                    ['Charges', summary.charge_total ?? '0.000000'],
                    ['Withholding', summary.withholding_total],
                    ['Header adjustment net', summary.adjustment_total ?? '0.000000'],
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
            {impact.length > 0 && (
                <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                    <div className="font-semibold text-slate-900">This transaction will create</div>
                    <ul className="mt-2 space-y-1 text-slate-700">
                        {impact.map((label) => <li key={label}>{label}</li>)}
                    </ul>
                </div>
            )}
            {stale && !result && <div className="rounded-md bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">Preview is stale. Refresh before submitting.</div>}
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
        </div>
    );
}
