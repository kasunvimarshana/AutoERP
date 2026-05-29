import type { InputHTMLAttributes } from 'react';
import { cn } from '../../lib/cn';

type InputProps = InputHTMLAttributes<HTMLInputElement> & {
    label?: string;
    error?: string;
};

export function Input({ className, error, id, label, ...props }: InputProps) {
    const input = <input {...props} id={id} className={cn('h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200', error && 'border-red-300 focus:border-red-400 focus:ring-red-100', className)} />;

    if (!label) {
        return input;
    }

    return (
        <label className="flex w-full flex-col gap-2 text-sm text-slate-700" htmlFor={id}>
            <span className="font-medium text-slate-700">{label}</span>
            {input}
            {error ? <span className="text-xs text-red-600">{error}</span> : null}
        </label>
    );
}
