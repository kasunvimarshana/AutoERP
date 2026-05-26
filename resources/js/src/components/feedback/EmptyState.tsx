import type { ReactNode } from 'react';
import { cn } from '../../lib/cn';

type EmptyStateProps = {
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

export function EmptyState({ action, className, description, title }: EmptyStateProps) {
    return (
        <div className={cn('rounded-3xl border border-dashed border-stone-200 bg-stone-50/80 px-6 py-10 text-center', className)}>
            <div className="mx-auto max-w-xl">
                <h3 className="text-xl font-semibold text-stone-950">{title}</h3>
                <p className="mt-3 text-sm leading-6 text-stone-600">{description}</p>
                {action ? <div className="mt-6 flex justify-center">{action}</div> : null}
            </div>
        </div>
    );
}
