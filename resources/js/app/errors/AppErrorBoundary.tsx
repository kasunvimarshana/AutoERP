import { Component, type ErrorInfo, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';

interface AppErrorBoundaryProps {
    children: ReactNode;
}

interface AppErrorBoundaryState {
    error: Error | null;
    reference: string | null;
}

export class AppErrorBoundary extends Component<AppErrorBoundaryProps, AppErrorBoundaryState> {
    state: AppErrorBoundaryState = {
        error: null,
        reference: null,
    };

    static getDerivedStateFromError(error: Error): AppErrorBoundaryState {
        return {
            error,
            reference: createErrorReference(),
        };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error('Unhandled AutoERP render error', {
            error,
            componentStack: info.componentStack,
            reference: this.state.reference,
        });
    }

    render() {
        if (!this.state.error) return this.props.children;

        return (
            <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
                <section className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" role="alert">
                    <p className="text-sm font-semibold uppercase tracking-wide text-rose-600">Application error</p>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950">This screen could not be displayed.</h1>
                    <p className="mt-3 text-sm text-slate-600">
                        Reload the application to retry. Unsaved changes on this screen may not be recoverable.
                    </p>
                    {this.state.reference ? (
                        <p className="mt-3 text-xs text-slate-500">Reference: {this.state.reference}</p>
                    ) : null}
                    <Button className="mt-5" onClick={() => window.location.reload()}>
                        Reload application
                    </Button>
                </section>
            </main>
        );
    }
}

function createErrorReference(): string {
    const random = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
        ? crypto.randomUUID().slice(0, 8)
        : Math.random().toString(36).slice(2, 10);

    return `${new Date().toISOString()}-${random}`;
}
