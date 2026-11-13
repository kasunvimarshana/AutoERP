import type { ReactNode } from 'react';

export function ContentHeader({ title, description, actions, badge }: {
    title: string;
    description?: string;
    actions?: ReactNode;
    badge?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-col justify-between gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-end">
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-[1.75rem]">{title}</h1>
                    {badge}
                </div>
                {description && <p className="mt-1 max-w-3xl text-sm leading-relaxed text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
