import type { PropsWithChildren } from 'react';
import { cn } from '../../lib/cn';

export function Card({ children, className }: PropsWithChildren<{ className?: string }>) {
    return <div className={cn('rounded-3xl border border-slate-200/80 bg-white shadow-[0_18px_50px_-36px_rgba(15,23,42,0.35)]', className)}>{children}</div>;
}
