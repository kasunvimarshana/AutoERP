import type { ReactNode } from 'react';
import { cn } from '../../utils/cn';

type DrawerProps = {
    children: ReactNode;
    open: boolean;
};

export function Drawer({ children, open }: DrawerProps) {
    return (
        <aside
            className={cn(
                'fixed inset-y-0 right-0 z-40 w-full max-w-md border-l border-slate-200 bg-white p-6 shadow-2xl transition',
                open ? 'translate-x-0' : 'translate-x-full',
            )}
        >
            {children}
        </aside>
    );
}
