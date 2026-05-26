import type { ReactNode } from 'react';
import { cn } from '../../lib/cn';
import { Breadcrumbs, type BreadcrumbItem } from './Breadcrumbs';

type PageHeaderProps = {
    title: string;
    description?: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: ReactNode;
    className?: string;
};

export function PageHeader({ actions, breadcrumbs = [], className, description, title }: PageHeaderProps) {
    return (
        <div className={cn('flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between', className)}>
            <div className="min-w-0 space-y-3">
                <Breadcrumbs items={breadcrumbs} />
                <div>
                    <h2 className="text-3xl font-semibold text-stone-950">{title}</h2>
                    {description ? <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">{description}</p> : null}
                </div>
            </div>
            {actions ? <div className="flex shrink-0 flex-wrap gap-2">{actions}</div> : null}
        </div>
    );
}
