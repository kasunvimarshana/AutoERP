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
        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status" aria-live="polite">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-semibold">{title}</p>
                    <p className="mt-1">{message}</p>
                </div>
                {onDismiss ? (
                    <button type="button" className="font-medium text-emerald-800 hover:underline" onClick={onDismiss}>
                        Dismiss
                    </button>
                ) : null}
            </div>
        </div>
    );
}
