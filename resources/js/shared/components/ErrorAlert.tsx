import { useState } from 'react';
import type { ApiError } from '@/shared/api/apiError';
import { useErrorToast } from '@/shared/notifications/appToast';

export function ErrorAlert({ error, title = 'Request failed', inline = false }: { error: ApiError | null; title?: string; inline?: boolean }) {
    const [copiedReference, setCopiedReference] = useState<string | null>(null);
    useErrorToast(error, title);
    if (!error) return null;
    if (!inline) return null;

    const fieldMessages = Object.entries(error.fields).flatMap(([field, messages]) =>
        messages.map((message) => `${field.replaceAll('.', ' ')}: ${message}`),
    );
    const correlationId = stringDetail(error.details.correlation_id);
    const guidance = stringDetail(error.details.guidance);

    async function copyReference() {
        if (!correlationId || typeof navigator.clipboard?.writeText !== 'function') return;
        await navigator.clipboard.writeText(correlationId);
        setCopiedReference(correlationId);
    }

    return (
        <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p className="font-semibold">{title}</p>
            <p className="mt-1">{error.message}</p>
            {guidance ? <p className="mt-2">{guidance}</p> : null}
            {fieldMessages.length > 0 ? (
                <ul className="mt-2 list-disc space-y-1 pl-5">
                    {fieldMessages.map((message) => <li key={message}>{message}</li>)}
                </ul>
            ) : null}
            {correlationId ? (
                <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-rose-700">
                    <span>Support reference: <span className="font-mono">{correlationId}</span></span>
                    <button type="button" className="font-semibold underline" onClick={() => void copyReference()}>
                        {copiedReference === correlationId ? 'Copied' : 'Copy'}
                    </button>
                </div>
            ) : null}
            {error.code ? <p className="mt-1 text-xs text-rose-700">Error code: {error.code}</p> : null}
        </div>
    );
}

function stringDetail(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
