import type { ReactNode } from 'react';
import { cn } from '../../lib/cn';

type ErrorStateProps = {
    title: string;
    description: string;
    action?: ReactNode;
    className?: string;
};

export function ErrorState({ action, className, description, title }: ErrorStateProps) {
    return (
        <div className={cn('rounded-3xl border border-red-200 bg-red-50/80 px-6 py-10', className)}>
            <div className="max-w-2xl">
                <h3 className="text-xl font-semibold text-red-900">{title}</h3>
                <p className="mt-3 text-sm leading-6 text-red-700">{description}</p>
                {action ? <div className="mt-6">{action}</div> : null}
            </div>
        </div>
    );
}
