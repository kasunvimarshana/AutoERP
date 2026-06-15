import type { ReactNode } from 'react';

export function ContentHeader({ title, description, actions }: {
    title: string;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-col justify-between gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end">
            <div className="min-w-0">
                <h1 className="text-2xl font-bold tracking-tight text-slate-950 sm:text-[1.7rem]">{title}</h1>
                {description && <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-500">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </div>
    );
}
