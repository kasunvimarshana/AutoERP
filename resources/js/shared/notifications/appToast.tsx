import { useEffect } from 'react';
import { ToastContainer, toast, type ToastOptions, type Id } from 'react-toastify';
import type { ApiError } from '@/shared/api/apiError';
import 'react-toastify/dist/ReactToastify.css';

const TOAST_OPTIONS: ToastOptions = {
    position: 'bottom-right',
    autoClose: 5000,
    hideProgressBar: false,
    closeOnClick: true,
    pauseOnHover: true,
    draggable: true,
    theme: 'light',
};

export function AppToastContainer() {
    return <ToastContainer {...TOAST_OPTIONS} newestOnTop />;
}

export function notifySuccess(message: string, title = 'Completed', toastId?: Id) {
    if (!message.trim()) return;
    toast.success(renderToastContent(title, [message]), { ...TOAST_OPTIONS, toastId });
}

export function notifyError(error: ApiError, title = 'Request failed', toastId?: Id) {
    const messages = [error.message];
    const guidance = stringDetail(error.details.guidance);
    if (guidance) messages.push(guidance);
    const fieldMessages = Object.entries(error.fields).flatMap(([field, value]) =>
        value.map((message) => `${field.replaceAll('.', ' ')}: ${message}`),
    );

    toast.error(renderToastContent(title, [...messages, ...fieldMessages]), {
        ...TOAST_OPTIONS,
        toastId: toastId ?? errorToastId(error, title),
    });
}

export function useErrorToast(error: ApiError | null, title = 'Request failed') {
    useEffect(() => {
        if (!error) return;
        notifyError(error, title);
    }, [error, title]);
}

export function useSuccessToast(message: string | null, title = 'Completed') {
    useEffect(() => {
        if (!message) return;
        notifySuccess(message, title, `${title}:${message}`);
    }, [message, title]);
}

function renderToastContent(title: string, messages: string[]) {
    return (
        <div className="space-y-1 text-sm">
            <p className="font-semibold">{title}</p>
            {messages.map((message) => <p key={message}>{message}</p>)}
        </div>
    );
}

function errorToastId(error: ApiError, title: string) {
    return [
        title,
        error.code ?? '',
        error.status ?? '',
        error.message,
        stringDetail(error.details.correlation_id) ?? '',
    ].join(':');
}

function stringDetail(value: unknown): string | null {
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
