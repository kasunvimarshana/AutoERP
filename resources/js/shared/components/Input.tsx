import { forwardRef, useId, type InputHTMLAttributes } from 'react';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    hint?: string;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
    { label, error, hint, className = '', id, ...props },
    ref,
) {
    const generatedId = useId();
    const inputId = id ?? props.name ?? generatedId;
    const messageId = `${inputId}-message`;
    return (
        <label className="block text-sm text-slate-700" htmlFor={inputId}>
            {label && <span className="mb-1.5 block font-medium">{label}</span>}
            <input
                ref={ref}
                id={inputId}
                aria-invalid={Boolean(error)}
                aria-describedby={error || hint ? messageId : undefined}
                className={`min-h-10 w-full rounded-lg border bg-white px-3 py-2 outline-none transition placeholder:text-slate-400 focus:ring-2 ${error ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-100'} ${className}`}
                {...props}
            />
            {error ? <span id={messageId} className="mt-1 block text-xs text-rose-600">{error}</span> : hint ? <span id={messageId} className="mt-1 block text-xs text-slate-500">{hint}</span> : null}
        </label>
    );
});
