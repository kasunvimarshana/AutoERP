import type { ReactNode } from 'react';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';

export interface LinePreview {
    subtotal: string;
    discount: string;
    tax: string;
    charge: string;
    total: string;
}

export function LineSummary({ preview }: { preview: LinePreview }) {
    return (
        <div className="rounded-lg border border-slate-200 p-4 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="font-semibold text-slate-900">Pricing summary</h3>
                <span className="text-xs text-slate-500">Auto-calculated from line quantity, unit price, discounts, and taxes</span>
            </div>
            <div className="mt-3 grid gap-2 sm:grid-cols-5">
                <SummaryValue label="Subtotal" value={<MoneyDisplay value={preview.subtotal} />} />
                <SummaryValue label="Discount" value={<MoneyDisplay value={preview.discount} />} />
                <SummaryValue label="Tax" value={<MoneyDisplay value={preview.tax} />} />
                <SummaryValue label="Charge" value={<MoneyDisplay value={preview.charge} />} />
                <SummaryValue label="Total" value={<MoneyDisplay value={preview.total} />} />
            </div>
        </div>
    );
}

export function SummaryValue({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div>
            <span className="text-xs uppercase text-slate-500">{label}</span>
            <div className="font-semibold tabular-nums text-slate-900">{value}</div>
        </div>
    );
}
