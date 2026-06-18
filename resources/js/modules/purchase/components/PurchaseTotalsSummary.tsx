import { MoneyDisplay } from '@/shared/components/MoneyDisplay';

export interface PurchaseSummaryRow {
    label: string;
    value?: string | null;
    strong?: boolean;
    hidden?: boolean;
}

export function PurchaseTotalsSummary({ title = 'Totals', rows }: { title?: string; rows: PurchaseSummaryRow[] }) {
    const visible = rows.filter((row) => !row.hidden);

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="text-sm font-semibold text-slate-950">{title}</h2>
            <dl className="mt-3 space-y-3 text-sm">
                {visible.map((row, index) => (
                    <div
                        key={`${row.label}-${index}`}
                        className={`flex items-center justify-between gap-4 ${row.strong ? 'border-t border-slate-200 pt-3 font-semibold text-slate-950' : 'text-slate-700'}`}
                    >
                        <dt>{row.label}</dt>
                        <dd className="tabular-nums"><MoneyDisplay value={row.value ?? '0.000000'} /></dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}
