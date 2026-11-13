import type { ReactNode } from 'react';

export function Panel({
    title,
    description,
    headerAction,
    children,
    className = '',
}: {
    title?: string;
    description?: string;
    headerAction?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={`rounded-xl border border-slate-200/80 bg-white p-5 shadow-2xs ${className}`}>
            {title && (
                <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-slate-900">{title}</h2>
                        {description && <p className="mt-0.5 text-xs text-slate-500">{description}</p>}
                    </div>
                    {headerAction && <div className="shrink-0">{headerAction}</div>}
                </div>
            )}
            {children}
        </section>
    );
}
