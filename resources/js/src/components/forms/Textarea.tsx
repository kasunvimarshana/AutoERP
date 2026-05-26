import type { TextareaHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & {
    error?: string;
};

export function Textarea({ className, error, rows = 4, ...props }: TextareaProps) {
    return (
        <textarea
            {...props}
            className={cn(
                'w-full rounded-xl border border-stone-200 bg-white px-3 py-3 text-sm text-stone-900 shadow-xs outline-none transition',
                'placeholder:text-stone-400 focus:border-stone-400 focus:ring-2 focus:ring-stone-200',
                error && 'border-red-300 focus:border-red-400 focus:ring-red-100',
                className,
            )}
            rows={rows}
        />
    );
}
