import type { ReactNode } from 'react';

export function Panel({ title, children, className = '' }: { title?: string; children: ReactNode; className?: string }) {
    return (
        <section className={`rounded-lg border border-slate-200 bg-white p-5 shadow-sm ${className}`}>
            {title && <h2 className="mb-4 font-semibold text-slate-900">{title}</h2>}
            {children}
        </section>
    );
}
