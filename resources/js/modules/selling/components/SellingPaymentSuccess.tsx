import { getInvoice, getInvoiceSignedPrintLink } from '@/modules/invoice/invoiceApi';
import type { Invoice } from '@/modules/invoice/invoiceTypes';
import type { Payment } from '@/modules/payment/paymentApi';
import type { ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useApi } from '@/shared/hooks/useApi';
import { formatMoney } from '@/shared/utils/formatMoney';
import { resolveSameOriginUrl } from '@/shared/utils/safeNavigation';

interface SellingPaymentSuccessProps {
    invoice: Invoice;
    payment: Payment;
    printing: boolean;
    sharingWhatsApp: boolean;
    actionError: ApiError | null;
    onPrint: () => void;
    onShare: () => void;
}

export function SellingPaymentSuccess({ invoice, payment, printing, sharingWhatsApp, actionError, onPrint, onShare }: SellingPaymentSuccessProps) {
    const updatedInvoice = useApi((signal) => getInvoice(invoice.id, signal), [invoice.id]);
    const preview = useApi((signal) => getInvoiceSignedPrintLink(invoice.id, signal), [invoice.id]);
    const previewUrl = preview.data?.pdf_url ? resolveSameOriginUrl(preview.data.pdf_url) : null;
    const currency = invoice.currency?.code ?? undefined;

    return <div className="mx-auto max-w-6xl space-y-5 pb-10">
        <ContentHeader title="Payment complete" description={`Sales invoice ${invoice.invoice_number ?? ''} is ready to print or share.`} />
        <section className="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                    <span aria-hidden="true" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xl font-bold text-emerald-700">✓</span>
                    <div>
                        <h2 role="status" className="text-lg font-semibold text-slate-950">Payment finalized</h2>
                        <p className="mt-1 text-sm text-slate-600">The receipt is posted and applied to this bill.</p>
                    </div>
                </div>
                <div>
                    <p className="text-sm text-slate-600">Amount received</p>
                    <p className="text-2xl font-bold tabular-nums text-emerald-800">{formatMoney(payment.total_amount, currency)}</p>
                </div>
            </div>
            <div className="mt-5 grid gap-3 border-t border-slate-200 pt-4 text-sm sm:grid-cols-3">
                <div><p className="text-slate-500">Invoice</p><p className="mt-1 font-semibold text-slate-900">{invoice.invoice_number}</p></div>
                <div><p className="text-slate-500">Payment reference</p><p className="mt-1 font-semibold text-slate-900">{payment.payment_number}</p></div>
                <div><p className="text-slate-500">Balance remaining</p><p className="mt-1 font-semibold tabular-nums text-slate-900">{updatedInvoice.data ? formatMoney(updatedInvoice.data.balance_due, currency) : updatedInvoice.loading ? 'Updating…' : 'Unavailable'}</p></div>
            </div>
            <div className="mt-5 flex flex-wrap gap-3">
                <Button onClick={onPrint} loading={printing}>Print bill</Button>
                <Button variant="secondary" onClick={onShare} loading={sharingWhatsApp}>Share via WhatsApp</Button>
                <LinkButton variant="secondary" to="/selling/sales">Return to Selling</LinkButton>
                <LinkButton variant="ghost" to={`/payments/${payment.id}`}>View payment receipt</LinkButton>
            </div>
            <ErrorAlert error={actionError ?? updatedInvoice.error} inline />
        </section>

        <section aria-labelledby="bill-preview-title" className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="bill-preview-title" className="text-lg font-semibold text-slate-950">Bill preview</h2>
                    <p className="text-sm text-slate-600">Review the bill before printing or sharing.</p>
                </div>
                <Button variant="secondary" onClick={preview.reload} disabled={preview.loading}>Refresh preview</Button>
            </div>
            <ErrorAlert error={preview.error} inline />
            {preview.loading ? <div className="flex min-h-[420px] items-center justify-center text-sm text-slate-600">Preparing bill preview…</div>
                : previewUrl ? <iframe title={`Bill preview for ${invoice.invoice_number}`} src={previewUrl} className="h-[70vh] min-h-[520px] w-full bg-slate-100" />
                    : <div className="flex min-h-[320px] flex-col items-center justify-center gap-3 p-6 text-center text-sm text-slate-600">
                        <p>The bill preview is unavailable. You can still print or share the bill above.</p>
                        <Button variant="secondary" onClick={preview.reload}>Try preview again</Button>
                    </div>}
        </section>
    </div>;
}
