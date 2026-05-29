import type { ReactNode } from 'react';
import { Button } from './Button';

type ModalProps = {
    children: ReactNode;
    onClose: () => void;
    open: boolean;
    title: string;
};

export function Modal({ children, onClose, open, title }: ModalProps) {
    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4">
            <div className="w-full max-w-lg rounded-lg bg-white p-5 shadow-2xl">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-slate-950">{title}</h2>
                    <Button onClick={onClose} variant="ghost">
                        Close
                    </Button>
                </div>
                <div className="mt-4">{children}</div>
            </div>
        </div>
    );
}
