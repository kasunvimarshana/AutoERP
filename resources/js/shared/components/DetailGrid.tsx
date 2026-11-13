import type { ReactNode } from 'react';

export function DetailGrid({ items }: { items: Array<{ label: string; value: ReactNode }> }) {
    return (
        <dl className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => {
                const displayValue = item.value === null || item.value === undefined || item.value === '' ? '-' : item.value;
                return (
                    <div key={item.label} className="min-w-0">
                        <dt className="text-xs font-semibold uppercase tracking-wider text-slate-500">{item.label}</dt>
                        <dd className="mt-1 text-sm font-medium text-slate-900 break-words">{displayValue}</dd>
                    </div>
                );
            })}
        </dl>
    );
}
