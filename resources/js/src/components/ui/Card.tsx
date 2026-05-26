import type { PropsWithChildren } from 'react';
import { cn } from '../../lib/cn';

type CardProps = PropsWithChildren<{
    className?: string;
}>;

export function Card({ children, className }: CardProps) {
    return <div className={cn('rounded-3xl border border-stone-200/80 bg-white/90 shadow-sm', className)}>{children}</div>;
}
