import { forwardRef, type TextareaHTMLAttributes } from 'react';

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
    { label, error, className = '', id, ...props },
    ref,
) {
    const textareaId = id ?? props.name;
    return (
        <label className="block text-sm text-slate-700" htmlFor={textareaId}>
            {label && <span className="mb-1.5 block font-medium">{label}</span>}
            <textarea
                ref={ref}
                id={textareaId}
                className={`min-h-28 w-full rounded-lg border bg-white px-3 py-2 outline-none focus:ring-2 ${error ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-100'} ${className}`}
                {...props}
            />
            {error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
});
