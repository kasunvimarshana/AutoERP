import type { SelectHTMLAttributes } from 'react';
import { cn } from '../../utils/cn';
import type { SelectOption } from '../../types/select.types';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
    options?: SelectOption[];
    placeholder?: string;
};

export function Select({ children, className, options, placeholder, ...props }: SelectProps) {
    return (
        <select
            className={cn(
                'h-11 w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100',
                className,
            )}
            {...props}
        >
            {placeholder ? <option value="">{placeholder}</option> : null}
            {options?.map((option) => (
                <option key={`${option.value}:${option.label}`} value={option.value}>
                    {option.label}
                </option>
            ))}
            {children}
        </select>
    );
}
