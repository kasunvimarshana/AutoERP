import { useSuccessToast } from '@/shared/notifications/appToast';

interface SuccessAlertProps {
    message: string | null;
    title?: string;
    onDismiss?: () => void;
    inline?: boolean;
}

export function SuccessAlert({ message, title = 'Completed', onDismiss, inline = false }: SuccessAlertProps) {
    useSuccessToast(message, title);
    if (!message) return null;
    if (!inline) return null;

    return (
        <div className="rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 text-sm text-emerald-900 shadow-2xs" role="status" aria-live="polite">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                    <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center text-emerald-600" aria-hidden="true">
                        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p className="font-semibold">{title}</p>
                        <p className="mt-1">{message}</p>
                    </div>
                </div>
                {onDismiss ? (
                    <button type="button" className="text-xs font-semibold text-emerald-800 hover:underline" onClick={onDismiss}>
                        Dismiss
                    </button>
                ) : null}
            </div>
        </div>
    );
}
