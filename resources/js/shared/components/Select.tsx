import { forwardRef, useId, type SelectHTMLAttributes } from 'react';
import type { SelectOption } from '@/shared/types/common';

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    hint?: string;
    options: SelectOption[];
    placeholder?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { label, error, hint, options, placeholder = 'Select...', className = '', id, ...props },
    ref,
) {
    const generatedId = useId();
    const selectId = id ?? props.name ?? generatedId;
    const messageId = `${selectId}-message`;
    return (
        <div className="block text-sm text-slate-700">
            {label ? <label className="mb-1.5 block font-medium" htmlFor={selectId}>{label}</label> : null}
            <select
                ref={ref}
                id={selectId}
                aria-invalid={Boolean(error)}
                aria-describedby={error || hint ? messageId : undefined}
                className={`min-h-10 w-full rounded-lg border bg-white px-3 py-2 outline-none focus:ring-2 ${error ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-100'} ${className}`}
                {...props}
            >
                <option value="">{placeholder}</option>
                {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
            {error ? <span id={messageId} className="mt-1 block text-xs text-rose-600">{error}</span> : hint ? <span id={messageId} className="mt-1 block text-xs text-slate-500">{hint}</span> : null}
        </div>
    );
});
