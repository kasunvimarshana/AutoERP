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
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-xs" onMouseDown={(event) => event.target === event.currentTarget && requestClose()}>
            <div ref={dialogRef} tabIndex={-1} className="flex h-full w-full flex-col border-l border-slate-200/80 bg-white shadow-2xl sm:max-w-2xl" role="dialog" aria-modal="true" aria-labelledby={titleId} onSubmit={(event) => event.stopPropagation()}>
                <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <h2 id={titleId} className="text-lg font-bold tracking-tight text-slate-900">{title}</h2>
                    <Button
                        ref={closeRef}
                        variant="ghost"
                        className="h-9 px-2.5 text-slate-500 hover:text-slate-700"
                        onClick={requestClose}
                        disabled={closeDisabled}
                        aria-label="Close drawer"
                    >
                        <svg className="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                        <span>Close</span>
                    </Button>
                </div>
                <div className="min-h-0 flex-1 overflow-auto p-6">{children}</div>
            </div>
        </div>,
        document.body,
    );
}

export function FormDrawer(props: DrawerProps) {
    return <Drawer {...props} />;
}
