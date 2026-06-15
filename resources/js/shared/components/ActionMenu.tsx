import { useRef, type ReactNode } from 'react';

export function ActionMenu({ children, label = 'More actions' }: {
    children: ReactNode;
    label?: string;
}) {
    const detailsRef = useRef<HTMLDetailsElement>(null);
    return (
        <details
            ref={detailsRef}
            className="relative"
            onClick={(event) => {
                const target = event.target;
                if (target instanceof Element && target.closest('button, a')) {
                    detailsRef.current?.removeAttribute('open');
                }
            }}
        >
            <summary className="flex min-h-10 cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-blue-500">
                {label}
            </summary>
            <div className="mt-1 min-w-40 space-y-1 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                {children}
            </div>
        </details>
    );
}
