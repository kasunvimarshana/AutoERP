import type { SelectHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
    error?: string;
};

export function Select({ className, error, ...props }: SelectProps) {
    return (
        <select
            {...props}
            className={cn(
                'h-11 w-full rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-900 shadow-xs outline-none transition',
                'focus:border-stone-400 focus:ring-2 focus:ring-stone-200',
                error && 'border-red-300 focus:border-red-400 focus:ring-red-100',
                className,
            )}
        />
    );
}
