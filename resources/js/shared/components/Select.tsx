import { forwardRef, useId, type SelectHTMLAttributes } from 'react';
import type { SelectOption } from '@/shared/types/common';

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    options: SelectOption[];
    placeholder?: string;
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
    { label, error, options, placeholder = 'Select...', className = '', id, ...props },
    ref,
) {
    const generatedId = useId();
    const selectId = id ?? props.name ?? generatedId;
    const errorId = `${selectId}-error`;
    return (
        <label className="block text-sm text-slate-700" htmlFor={selectId}>
            {label && <span className="mb-1.5 block font-medium">{label}</span>}
            <select
                ref={ref}
                id={selectId}
                aria-invalid={Boolean(error)}
                aria-describedby={error ? errorId : undefined}
                className={`min-h-10 w-full rounded-lg border bg-white px-3 py-2 outline-none focus:ring-2 ${error ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-100'} ${className}`}
                {...props}
            >
                <option value="">{placeholder}</option>
                {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
            {error && <span id={errorId} className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
});
