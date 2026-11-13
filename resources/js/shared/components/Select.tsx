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
    const isMultiple = Boolean(props.multiple);

    return (
        <div className="block text-sm text-slate-700">
            {label ? (
                <label
                    className={`mb-1.5 block font-medium text-slate-700 ${props.required ? "after:content-['*'] after:ml-0.5 after:text-rose-500" : ''}`}
                    htmlFor={selectId}
                >
                    {label}
                </label>
            ) : null}
            <div className="relative">
                <select
                    ref={ref}
                    id={selectId}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error || hint ? messageId : undefined}
                    className={`min-h-10 w-full ${isMultiple ? 'rounded-lg p-2' : 'appearance-none cursor-pointer rounded-lg pl-3 pr-9 py-2'} border bg-white text-slate-900 shadow-2xs transition-colors focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-200 ${error ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-500/20'} ${className}`}
                    {...props}
                >
                    {!isMultiple && <option value="">{placeholder}</option>}
                    {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
                {!isMultiple && (
                    <span className={`pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 ${props.disabled ? 'text-slate-300' : 'text-slate-400'}`} aria-hidden="true">
                        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                        </svg>
                    </span>
                )}
            </div>
            {error ? (
                <span id={messageId} className="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                    <svg className="h-3.5 w-3.5 shrink-0" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fillRule="evenodd" d="M8 15A7 7 0 108 1a7 7 0 000 14zm.75-10.25a.75.75 0 00-1.5 0v4.5a.75.75 0 001.5 0v-4.5zm0 6.5a.75.75 0 10-1.5 0 .75.75 0 001.5 0z" clipRule="evenodd" />
                    </svg>
                    <span>{error}</span>
                </span>
            ) : hint ? (
                <span id={messageId} className="mt-1.5 block text-xs text-slate-500">
                    {hint}
                </span>
            ) : null}
        </div>
    );
});
