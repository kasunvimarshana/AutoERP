import { SectionCard } from '../../../components/forms/SectionCard';
import { formatCurrency } from '../utils';

type TotalsSummaryCardProps = {
    subtotal?: number | string | null;
    taxTotal?: number | string | null;
    discountTotal?: number | string | null;
    extraTotalLabel?: string;
    extraTotalValue?: number | string | null;
    grandTotal?: number | string | null;
};

export function TotalsSummaryCard({ subtotal, taxTotal, discountTotal, extraTotalLabel, extraTotalValue, grandTotal }: TotalsSummaryCardProps) {
    return (
        <SectionCard
            description="Document totals use the exact monetary values returned by the backend resource, so workflow screens stay aligned with posting and approval logic."
            title="Totals"
        >
            <dl className="space-y-4">
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-sm text-stone-500">Subtotal</dt>
                    <dd className="text-sm font-medium text-stone-950">{formatCurrency(subtotal)}</dd>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <dt className="text-sm text-stone-500">Tax</dt>
                    <dd className="text-sm font-medium text-stone-950">{formatCurrency(taxTotal)}</dd>
                </div>
                {discountTotal !== undefined ? (
                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-sm text-stone-500">Discounts</dt>
                        <dd className="text-sm font-medium text-stone-950">{formatCurrency(discountTotal)}</dd>
                    </div>
                ) : null}
                {extraTotalLabel ? (
                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-sm text-stone-500">{extraTotalLabel}</dt>
                        <dd className="text-sm font-medium text-stone-950">{formatCurrency(extraTotalValue)}</dd>
                    </div>
                ) : null}
                <div className="border-t border-stone-200/80 pt-4">
                    <div className="flex items-center justify-between gap-3">
                        <dt className="text-sm font-semibold text-stone-950">Grand Total</dt>
                        <dd className="text-base font-semibold text-stone-950">{formatCurrency(grandTotal)}</dd>
                    </div>
                </div>
            </dl>
        </SectionCard>
    );
}
