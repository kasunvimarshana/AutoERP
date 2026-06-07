import type { ReactNode } from 'react';

export function EntityDetailLayout({
    summary,
    actions,
    children,
}: {
    summary?: ReactNode;
    actions?: ReactNode;
    children: ReactNode;
}) {
    if (!summary && !actions) return <>{children}</>;

    return (
        <div className="grid gap-5 xl:grid-cols-[minmax(18rem,22rem)_1fr]">
            <aside className="space-y-4 xl:sticky xl:top-24 xl:self-start">
                {summary}
                {actions && (
                    <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <h2 className="mb-3 text-sm font-semibold text-slate-900">Quick actions</h2>
                        <div className="grid gap-2">
                            {actions}
                        </div>
                    </section>
                )}
            </aside>
            <div className="min-w-0">
                {children}
            </div>
        </div>
    );
}
