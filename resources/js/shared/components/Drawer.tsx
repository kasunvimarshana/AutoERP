import { useCallback, useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { useDialogAccessibility } from '@/shared/hooks/useDialogAccessibility';
import { Button } from './Button';

interface DrawerProps {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
    canClose?: () => boolean;
    closeDisabled?: boolean;
}

export function Drawer({ open, title, onClose, children, canClose, closeDisabled = false }: DrawerProps) {
    const titleId = useId();
    const closeRef = useRef<HTMLButtonElement>(null);
    const dialogRef = useRef<HTMLDivElement>(null);
    const requestClose = useCallback(() => {
        if (closeDisabled || canClose?.() === false) return;
        onClose();
    }, [canClose, closeDisabled, onClose]);
    useDialogAccessibility(open, dialogRef, requestClose);

    if (!open) return null;

    return createPortal(
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/50" onMouseDown={(event) => event.target === event.currentTarget && requestClose()}>
            <div ref={dialogRef} tabIndex={-1} className="flex h-full w-full flex-col bg-white shadow-xl sm:max-w-2xl" role="dialog" aria-modal="true" aria-labelledby={titleId} onSubmit={(event) => event.stopPropagation()}>
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 id={titleId} className="text-lg font-semibold text-slate-900">{title}</h2>
                    <Button ref={closeRef} variant="ghost" onClick={requestClose} disabled={closeDisabled} aria-label="Close drawer">Close</Button>
                </div>
                <div className="min-h-0 flex-1 overflow-auto p-5">{children}</div>
            </div>
        </div>,
        document.body,
    );
}

export function FormDrawer(props: DrawerProps) {
    return <Drawer {...props} />;
}
