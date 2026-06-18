import type { ReactNode } from 'react';

export function PurchaseDocumentShell({ header, tabs, children, summary }: {
    header: ReactNode;
    tabs?: ReactNode;
    children: ReactNode;
    summary?: ReactNode;
}) {
    return (
        <div className="mx-auto max-w-7xl space-y-5">
            {header}
            {tabs}
            {summary ? (
                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="min-w-0 space-y-5">{children}</div>
                    <aside className="xl:sticky xl:top-20 xl:self-start">{summary}</aside>
                </div>
            ) : (
                <div className="min-w-0 space-y-5">{children}</div>
            )}
        </div>
    );
}

export function PurchasePageHeader({ title, description, status, actions }: {
    title: string;
    description?: string;
    status?: ReactNode;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-normal text-slate-950">{title}</h1>
                    {status}
                </div>
                {description && <p className="mt-1 max-w-3xl text-sm text-slate-600">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap justify-end gap-2">{actions}</div>}
        </div>
    );
}
