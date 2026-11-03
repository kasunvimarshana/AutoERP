import type { ReactNode } from 'react';

export function FormActions({ children }: { children: ReactNode }) {
    return (
        <div className="sticky bottom-0 z-10 -mx-1 flex justify-end gap-3 border-t border-slate-200/80 bg-slate-50/95 px-1 py-4 backdrop-blur-md">
            {children}
        </div>
    );
}
