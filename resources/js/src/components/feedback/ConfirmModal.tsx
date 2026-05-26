import type { ReactNode } from 'react';
import { Button } from '../ui/Button';

type ConfirmModalProps = {
    open: boolean;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    onCancel: () => void;
    onConfirm: () => void;
    isLoading?: boolean;
    children?: ReactNode;
};

export function ConfirmModal({
    cancelLabel = 'Cancel',
    children,
    confirmLabel = 'Confirm',
    description,
    isLoading = false,
    onCancel,
    onConfirm,
    open,
    title,
}: ConfirmModalProps) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/35 px-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl shadow-stone-950/15">
                <h3 className="text-xl font-semibold text-stone-950">{title}</h3>
                <p className="mt-3 text-sm leading-6 text-stone-600">{description}</p>
                {children ? <div className="mt-4">{children}</div> : null}
                <div className="mt-6 flex justify-end gap-2">
                    <Button onClick={onCancel} type="button" variant="secondary">
                        {cancelLabel}
                    </Button>
                    <Button disabled={isLoading} onClick={onConfirm} type="button">
                        {isLoading ? 'Working...' : confirmLabel}
                    </Button>
                </div>
            </div>
        </div>
    );
}
