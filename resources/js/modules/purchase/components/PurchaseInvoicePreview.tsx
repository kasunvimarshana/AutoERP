import { formatMoney } from '@/shared/utils/formatMoney';
import type { PurchaseInvoicePreviewResult } from '../purchaseApi';

export function PurchaseInvoicePreview({ preview }: { preview: PurchaseInvoicePreviewResult | null }) {
    if (!preview) {
        return <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Preview appears after the backend accepts the selected source lines.</div>;
    }

    return (
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <p className="font-semibold">Backend invoice preview</p>
            <p className="mt-1 text-lg font-bold tabular-nums">{formatMoney(preview.grand_total)}</p>
        </div>
    );
}
