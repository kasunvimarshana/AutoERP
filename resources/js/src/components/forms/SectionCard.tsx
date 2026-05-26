import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '../../lib/cn';

type SectionCardProps = PropsWithChildren<{
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
}>;

export function SectionCard({ action, children, className, description, title }: SectionCardProps) {
    return (
        <section className={cn('rounded-3xl border border-stone-200/80 bg-stone-50/55 p-5', className)}>
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 className="text-lg font-semibold text-stone-950">{title}</h3>
                    {description ? <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p> : null}
                </div>
                {action}
            </div>
            <div className="mt-5">{children}</div>
        </section>
    );
}
