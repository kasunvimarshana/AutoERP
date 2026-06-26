import type { ReactNode } from 'react';

export function InlineFieldAction({
    id,
    label,
    input,
    action,
    hint,
}: {
    id: string;
    label: string;
    input: ReactNode;
    action: ReactNode;
    hint?: string;
}) {
    return (
        <div>
            <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">{label}</label>
            <div className="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                {input}
                <div className="md:self-start">{action}</div>
            </div>
            {hint ? <p className="mt-1 text-xs text-slate-500">{hint}</p> : null}
        </div>
    );
}
