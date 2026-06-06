import { formatMoney } from '@/shared/utils/formatMoney';

export function PurchaseInvoicePreview({ preview }: { preview: Record<string, unknown> | null }) {
    if (!preview) {
        return <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Preview appears after the backend accepts the selected source lines.</div>;
    }

    const total = String(preview.grandTotal ?? preview.grand_total ?? preview.total ?? '0.000000');
    return (
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <p className="font-semibold">Backend invoice preview</p>
            <p className="mt-1 text-lg font-bold tabular-nums">{formatMoney(total)}</p>
        </div>
    );
}
