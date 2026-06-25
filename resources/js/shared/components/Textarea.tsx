import { forwardRef, useId, type TextareaHTMLAttributes } from 'react';

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
    hint?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
    { label, error, hint, className = '', id, ...props },
    ref,
) {
    const generatedId = useId();
    const textareaId = id ?? props.name ?? generatedId;
    const messageId = `${textareaId}-message`;
    return (
        <div className="block text-sm text-slate-700">
            {label ? <label className="mb-1.5 block font-medium" htmlFor={textareaId}>{label}</label> : null}
            <textarea
                ref={ref}
                id={textareaId}
                aria-invalid={Boolean(error)}
                aria-describedby={error || hint ? messageId : undefined}
                className={`min-h-28 w-full rounded-lg border bg-white px-3 py-2 outline-none focus:ring-2 ${error ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-100'} ${className}`}
                {...props}
            />
            {error ? <span id={messageId} className="mt-1 block text-xs text-rose-600">{error}</span> : hint ? <span id={messageId} className="mt-1 block text-xs text-slate-500">{hint}</span> : null}
        </div>
    );
});
