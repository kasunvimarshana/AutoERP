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
        <Modal open={open} title={title} onClose={onCancel}>
            <div className="space-y-5">
                <div className="text-sm text-slate-700">{message}</div>
                <div className="flex justify-end gap-2">
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
