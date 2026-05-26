import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '../../lib/cn';

type FormFieldProps = PropsWithChildren<{
    label: string;
    required?: boolean;
    error?: string;
    hint?: string;
    action?: ReactNode;
    className?: string;
}>;

export function FormField({ action, children, className, error, hint, label, required = false }: FormFieldProps) {
    return (
        <div className={cn('space-y-2', className)}>
            <div className="flex items-center justify-between gap-3">
                <label className="text-sm font-medium text-stone-800">
                    {label}
                    {required ? <span className="ml-1 text-red-600">*</span> : null}
                </label>
                {action}
            </div>
            {children}
            {error ? <p className="text-xs text-red-600">{error}</p> : hint ? <p className="text-xs text-stone-500">{hint}</p> : null}
        </div>
    );
}
