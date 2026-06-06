import type { InputHTMLAttributes } from 'react';
import { cn } from '../../utils/cn';

export function Input({ className, ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            className={cn(
                'h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-400 focus:ring-4 focus:ring-blue-50',
                className,
            )}
            {...props}
        />
    );
}
