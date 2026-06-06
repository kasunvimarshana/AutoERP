import type { ReactNode } from 'react';

export function DetailGrid({ items }: { items: Array<{ label: string; value: ReactNode }> }) {
    return (
        <dl className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => (
                <div key={item.label}>
                    <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{item.label}</dt>
                    <dd className="mt-1 text-sm text-slate-900">{item.value ?? '-'}</dd>
                </div>
            ))}
        </dl>
    );
}
