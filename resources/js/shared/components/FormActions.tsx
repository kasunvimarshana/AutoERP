import type { ReactNode } from 'react';

export function FormActions({ children }: { children: ReactNode }) {
    return (
        <div className="sticky bottom-0 z-10 -mx-1 flex justify-end gap-2 border-t border-slate-200 bg-slate-50/95 px-1 py-4 backdrop-blur">
            {children}
        </div>
    );
}
