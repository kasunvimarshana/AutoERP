import { useEffect, useRef, type ReactNode } from 'react';

export function ActionMenu({ children, label = 'More actions', className = '', triggerClassName = '' }: {
    children: ReactNode;
    label?: string;
    className?: string;
    triggerClassName?: string;
}) {
    const detailsRef = useRef<HTMLDetailsElement>(null);

    useEffect(() => {
        const handlePointerDown = (event: PointerEvent) => {
            const menu = detailsRef.current;
            if (!menu?.open) return;
            if (event.target instanceof Node && !menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        };

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && detailsRef.current?.open) {
                detailsRef.current.removeAttribute('open');
                detailsRef.current.querySelector('summary')?.focus();
            }
        };

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    return (
        <details
            ref={detailsRef}
            className={`group relative inline-block ${className}`}
            onClick={(event) => {
                const target = event.target;
                if (target instanceof Element && target.closest('button, a')) {
                    detailsRef.current?.removeAttribute('open');
                }
            }}
        >
            <summary className={`flex min-h-10 cursor-pointer list-none [&::-webkit-details-marker]:hidden items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 ${triggerClassName}`}>
                <span>{label}</span>
                <svg className="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                </svg>
            </summary>
            <div className="absolute right-0 top-full z-50 mt-1 min-w-44 space-y-1 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl">
                {children}
            </div>
        </details>
    );
}
