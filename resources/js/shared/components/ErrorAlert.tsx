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
        <div className="rounded-xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-800 shadow-2xs" role="alert">
            <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center text-rose-600" aria-hidden="true">
                    <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                    </svg>
                </div>
                <div className="min-w-0 flex-1">
                    <p className="font-semibold">{title}</p>
                    <p className="mt-1">{error.message}</p>
                    {guidance ? <p className="mt-2 text-rose-700">{guidance}</p> : null}
                    {fieldMessages.length > 0 ? (
                        <ul className="mt-2 list-disc space-y-1 pl-5 text-rose-700">
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
            </div>
        </div>
    );
}

function stringDetail(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
