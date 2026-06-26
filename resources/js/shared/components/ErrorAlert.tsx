import type { ApiError } from '@/shared/api/apiError';

export function ErrorAlert({ error, title = 'Request failed' }: { error: ApiError | null; title?: string }) {
    if (!error) return null;
    const fieldMessages = Object.entries(error.fields).flatMap(([field, messages]) =>
        messages.map((message) => `${field.replaceAll('.', ' ')}: ${message}`),
    );
    const correlationId = stringDetail(error.details.correlation_id);
    const guidance = stringDetail(error.details.guidance);

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
                <p className="mt-3 text-xs text-rose-700">
                    Support reference: <span className="font-mono">{correlationId}</span>
                </p>
            ) : null}
            {error.code ? <p className="mt-1 text-xs text-rose-700">Error code: {error.code}</p> : null}
        </div>
    );
}

function stringDetail(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
