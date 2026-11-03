import { useState, type ReactNode } from 'react';
import { Button } from './Button';
import { Modal } from './Modal';

export function ConfirmDialog({ open, title, message, confirmLabel = 'Confirm', cancelLabel = 'Cancel', loading, danger = true, onCancel, onConfirm }: {
    open: boolean;
    title: string;
    message: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    loading?: boolean;
    danger?: boolean;
    onCancel: () => void;
    onConfirm: () => void;
}) {
    return (
        <Modal open={open} title={title} onClose={onCancel} closeDisabled={loading}>
            <div className="space-y-6">
                <div className="flex items-start gap-4">
                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${danger ? 'bg-rose-100 text-rose-600' : 'bg-blue-100 text-blue-600'}`}>
                        {danger ? (
                            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.948 3.376c-.866-1.5-3.03-1.5-3.896 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        ) : (
                            <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        )}
                    </div>
                    <div className="min-w-0 flex-1 pt-1 text-sm leading-relaxed text-slate-700">{message}</div>
                </div>
                <div className="flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                    <Button type="button" variant="secondary" onClick={onCancel} disabled={loading}>{cancelLabel}</Button>
                    <Button type="button" variant={danger ? 'danger' : 'primary'} loading={loading} onClick={onConfirm}>{confirmLabel}</Button>
                </div>
            </div>
        </Modal>
    );
}

export function useConfirmDialog() {
    const [request, setRequest] = useState<{
        title: string;
        message: ReactNode;
        confirmLabel?: string;
        danger?: boolean;
        resolve: (confirmed: boolean) => void;
    } | null>(null);

    const confirm = (options: { title: string; message: ReactNode; confirmLabel?: string; danger?: boolean }) => new Promise<boolean>((resolve) => {
        setRequest({ ...options, resolve });
    });

    const close = (confirmed: boolean) => {
        request?.resolve(confirmed);
        setRequest(null);
    };

    const dialog = request ? (
        <ConfirmDialog
            open
            title={request.title}
            message={request.message}
            confirmLabel={request.confirmLabel}
            danger={request.danger ?? true}
            onCancel={() => close(false)}
            onConfirm={() => close(true)}
        />
    ) : null;

    return { confirm, confirmDialog: dialog };
}
