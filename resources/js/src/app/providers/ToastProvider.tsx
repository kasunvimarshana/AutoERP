import type { PropsWithChildren, ReactNode } from 'react';
import { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react';
import { cn } from '../../lib/cn';

type ToastTone = 'success' | 'error' | 'info';

type ToastItem = {
    id: number;
    title: string;
    description?: string;
    tone: ToastTone;
};

type ShowToastInput = {
    title: string;
    description?: string;
    tone?: ToastTone;
};

type ToastContextValue = {
    showToast: (input: ShowToastInput) => void;
};

const ToastContext = createContext<ToastContextValue | undefined>(undefined);

function getToastToneClass(tone: ToastTone) {
    if (tone === 'success') {
        return 'border-emerald-200 bg-emerald-50/95 text-emerald-900';
    }

    if (tone === 'error') {
        return 'border-red-200 bg-red-50/95 text-red-900';
    }

    return 'border-stone-200 bg-white/95 text-stone-900';
}

export function ToastProvider({ children }: PropsWithChildren) {
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const nextIdRef = useRef(1);

    const dismissToast = useCallback((toastId: number) => {
        setToasts((current) => current.filter((toast) => toast.id !== toastId));
    }, []);

    const showToast = useCallback(
        ({ description, title, tone = 'info' }: ShowToastInput) => {
            const id = nextIdRef.current++;
            setToasts((current) => [...current, { id, title, description, tone }]);

            window.setTimeout(() => {
                dismissToast(id);
            }, 4000);
        },
        [dismissToast],
    );

    const value = useMemo(() => ({ showToast }), [showToast]);

    return (
        <ToastContext.Provider value={value}>
            {children}
            <div className="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex justify-center px-4 sm:justify-end sm:px-6 lg:px-8">
                <div className="flex w-full max-w-sm flex-col gap-3">
                    {toasts.map((toast) => (
                        <div
                            key={toast.id}
                            className={cn(
                                'pointer-events-auto rounded-2xl border px-4 py-4 shadow-lg shadow-stone-950/10 backdrop-blur-sm',
                                getToastToneClass(toast.tone),
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold">{toast.title}</p>
                                    {toast.description ? <p className="mt-1 text-sm leading-6 opacity-80">{toast.description}</p> : null}
                                </div>
                                <button
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-current/10 bg-white/60 text-current transition hover:bg-white"
                                    onClick={() => dismissToast(toast.id)}
                                    type="button"
                                >
                                    <svg
                                        aria-hidden="true"
                                        className="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="m18 6-12 12" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const context = useContext(ToastContext);

    if (!context) {
        throw new Error('useToast must be used within ToastProvider');
    }

    return context;
}
