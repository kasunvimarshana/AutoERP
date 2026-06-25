import { useEffect, useMemo } from 'react';
import { isRouteErrorResponse, useRouteError } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';

export function RouteErrorPage() {
    const error = useRouteError();
    const reference = useMemo(() => createErrorReference(), []);

    useEffect(() => {
        console.error('Unhandled AutoERP route error', { error, reference });
    }, [error, reference]);

    const status = isRouteErrorResponse(error) ? error.status : null;
    const title = status === 404 ? 'Page not found' : 'This screen could not be displayed';
    const message = status === 404
        ? 'The requested page does not exist or is no longer available.'
        : 'A safe recovery screen has replaced the failed page. Reload the application or return to sign in.';

    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
            <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" role="alert">
                <p className="text-sm font-semibold uppercase tracking-wide text-rose-600">Application error</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-950">{title}</h1>
                <p className="mt-3 text-sm text-slate-600">{message}</p>
                <p className="mt-3 text-xs text-slate-500">Reference: {reference}</p>
                <div className="mt-5 flex flex-wrap gap-3">
                    <button
                        type="button"
                        className="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                        onClick={() => window.location.reload()}
                    >
                        Reload application
                    </button>
                    <LinkButton to="/login" variant="secondary">Return to sign in</LinkButton>
                </div>
            </section>
        </main>
    );
}

function createErrorReference(): string {
    const random = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
        ? crypto.randomUUID().slice(0, 8)
        : Math.random().toString(36).slice(2, 10);

    return `${new Date().toISOString()}-${random}`;
}
