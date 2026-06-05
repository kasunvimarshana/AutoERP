import type { ReactNode } from 'react';

type ModuleHeaderProps = {
    actions?: ReactNode;
    subtitle?: string;
    title: string;
};

export function ModuleHeader({ actions, subtitle, title }: ModuleHeaderProps) {
    return (
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 className="text-3xl font-bold tracking-normal text-slate-950">{title}</h1>
                {subtitle ? <p className="mt-1 text-sm text-slate-500">{subtitle}</p> : null}
            </div>
            {actions ? <div className="flex items-center gap-3">{actions}</div> : null}
        </div>
    );
}
