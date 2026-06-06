import type { ApiError } from '@/shared/api/apiError';

export function ErrorAlert({ error, title = 'Request failed' }: { error: ApiError | null; title?: string }) {
    if (!error) return null;
    const fieldMessages = Object.entries(error.fields).flatMap(([field, messages]) =>
        messages.map((message) => `${field.replaceAll('.', ' ')}: ${message}`),
    );
    return (
        <div className="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p className="font-semibold">{title}</p>
            <p className="mt-1">{error.message}</p>
            {fieldMessages.length > 0 && <ul className="mt-2 list-disc space-y-1 pl-5">{fieldMessages.map((message) => <li key={message}>{message}</li>)}</ul>}
        </div>
    );
}
