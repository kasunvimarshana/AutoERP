import type { ReactNode } from 'react';

type PageHeaderProps = {
    actions?: ReactNode;
    eyebrow?: string;
    subtitle?: string;
    title: string;
};

export function PageHeader({ actions, eyebrow, subtitle, title }: PageHeaderProps) {
    return (
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                {eyebrow ? <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{eyebrow}</p> : null}
                <h1 className="mt-1 text-3xl font-bold tracking-normal text-slate-950">{title}</h1>
                {subtitle ? <p className="mt-2 max-w-3xl text-sm text-slate-500">{subtitle}</p> : null}
            </div>
            {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
        </div>
    );
}
