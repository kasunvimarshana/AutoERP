import { Panel } from '@/shared/components/Panel';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';

export interface PurchaseTotals {
    subtotal: string;
    discount_total: string;
    tax_total: string;
    charge_total: string;
    header_increase_total: string;
    header_decrease_total: string;
    grand_total: string;
}

export function PurchaseOrderSummaryPanel({ totals }: { totals: PurchaseTotals }) {
    const rows = [
        ['Subtotal', totals.subtotal],
        ['Line discounts', totals.discount_total],
        ['Line tax', totals.tax_total],
        ['Line charges', totals.charge_total],
        ['Header increases', totals.header_increase_total],
        ['Header decreases', totals.header_decrease_total],
        ['Grand total', totals.grand_total],
    ] as const;

    return (
        <Panel title="Summary">
            <dl className="space-y-3 text-sm">
                {rows.map(([label, value], index) => (
                    <div key={label} className={`flex items-center justify-between gap-4 ${index === rows.length - 1 ? 'border-t border-slate-200 pt-3 font-semibold text-slate-950' : 'text-slate-700'}`}>
                        <dt>{label}</dt>
                        <dd><MoneyDisplay value={value} /></dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}
