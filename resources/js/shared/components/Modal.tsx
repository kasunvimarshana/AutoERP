import { useCallback, useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { useDialogAccessibility } from '@/shared/hooks/useDialogAccessibility';
import { Button } from './Button';

interface ModalProps {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
    canClose?: () => boolean;
    closeDisabled?: boolean;
}

export function Modal({ open, title, onClose, children, canClose, closeDisabled = false }: ModalProps) {
    const titleId = useId();
    const dialogRef = useRef<HTMLDivElement>(null);
    const requestClose = useCallback(() => {
        if (closeDisabled || canClose?.() === false) return;
        onClose();
    }, [canClose, closeDisabled, onClose]);
    useDialogAccessibility(open, dialogRef, requestClose);

    if (!open) return null;
    return createPortal(
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
            onMouseDown={(event) => event.target === event.currentTarget && requestClose()}
        >
            <div ref={dialogRef} tabIndex={-1} className="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-lg bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby={titleId} onSubmit={(event) => event.stopPropagation()}>
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 id={titleId} className="text-lg font-semibold">{title}</h2>
                    <Button variant="ghost" onClick={requestClose} disabled={closeDisabled} aria-label="Close modal">Close</Button>
                </div>
                <div className="p-6">{children}</div>
            </div>
        </div>,
        document.body,
    );
}
