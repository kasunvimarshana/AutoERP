import { useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { useDialogAccessibility } from '@/shared/hooks/useDialogAccessibility';
import { Button } from './Button';

export function Drawer({ open, title, onClose, children }: {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}) {
    const titleId = useId();
    const closeRef = useRef<HTMLButtonElement>(null);
    const dialogRef = useRef<HTMLDivElement>(null);
    useDialogAccessibility(open, dialogRef, onClose);

    if (!open) return null;

    return createPortal(
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/50" onMouseDown={(event) => event.target === event.currentTarget && onClose()}>
            <div ref={dialogRef} tabIndex={-1} className="flex h-full w-full flex-col bg-white shadow-xl sm:max-w-2xl" role="dialog" aria-modal="true" aria-labelledby={titleId} onSubmit={(event) => event.stopPropagation()}>
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 id={titleId} className="text-lg font-semibold text-slate-900">{title}</h2>
                    <Button ref={closeRef} variant="ghost" onClick={onClose} aria-label="Close drawer">Close</Button>
                </div>
                <div className="min-h-0 flex-1 overflow-auto p-5">{children}</div>
            </div>
        </div>,
        document.body,
    );
}

export function FormDrawer({ open, title, onClose, children }: {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}) {
    return <Drawer open={open} title={title} onClose={onClose}>{children}</Drawer>;
}
