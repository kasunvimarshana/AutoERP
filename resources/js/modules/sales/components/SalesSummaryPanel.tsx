import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';

export function SalesSummaryPanel({ totals }: {
    totals: {
        subtotal?: string;
        line_discount_total?: string;
        line_tax_total?: string;
        line_charge_total?: string;
        header_increase_total?: string;
        header_decrease_total?: string;
        grand_total?: string;
    };
}) {
    const rows = [
        ['Subtotal', totals.subtotal],
        ['Line discounts', totals.line_discount_total],
        ['Line tax', totals.line_tax_total],
        ['Line charges', totals.line_charge_total],
        ['Header increases', totals.header_increase_total],
        ['Header decreases', totals.header_decrease_total],
        ['Grand total', totals.grand_total],
    ] as const;

    return (
        <Panel title="Totals">
            <dl className="space-y-3 text-sm">
                {rows.map(([label, value], index) => (
                    <div key={label} className="flex justify-between gap-4">
                        <dt className="text-slate-500">{label}</dt>
                        <dd className={index === rows.length - 1 ? 'font-semibold text-slate-900' : 'text-slate-700'}>
                            <MoneyDisplay value={value} />
                        </dd>
                    </div>
                ))}
            </dl>
        </Panel>
    );
}
