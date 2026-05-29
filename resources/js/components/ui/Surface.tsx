import type { ReactNode } from 'react';

interface SurfaceProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
    className?: string;
    action?: ReactNode;
}

export function Surface({ title, subtitle, children, className = '', action }: SurfaceProps) {
    return (
        <section className={`rounded-[1.75rem] border border-slate-200/80 bg-white/90 p-6 shadow-[0_20px_60px_-40px_rgba(15,23,42,0.35)] backdrop-blur ${className}`}>
            <div className="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 className="font-display text-xl font-semibold tracking-tight text-slate-900">{title}</h2>
                    {subtitle ? <p className="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{subtitle}</p> : null}
                </div>
                {action ? <div>{action}</div> : null}
            </div>
            {children}
        </section>
    );
}
