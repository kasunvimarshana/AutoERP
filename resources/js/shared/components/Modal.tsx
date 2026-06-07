import { useEffect, useId, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { Button } from './Button';

export function Modal({ open, title, onClose, children }: {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}) {
    const titleId = useId();
    useEffect(() => {
        if (!open) return;
        const onKeyDown = (event: KeyboardEvent) => event.key === 'Escape' && onClose();
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onClose, open]);

    if (!open) return null;
    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby={titleId} onSubmit={(event) => event.stopPropagation()}>
            <div className="max-h-[90vh] w-full max-w-2xl overflow-auto rounded-lg bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 id={titleId} className="text-lg font-semibold">{title}</h2>
                    <Button variant="ghost" onClick={onClose} aria-label="Close modal">Close</Button>
                </div>
                <div className="p-6">{children}</div>
            </div>
        </div>,
        document.body,
    );
}
