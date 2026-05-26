import type { PropsWithChildren, ReactNode } from 'react';
import { cn } from '../../lib/cn';

type ActionBarProps = PropsWithChildren<{
    leading?: ReactNode;
    className?: string;
}>;

export function ActionBar({ children, className, leading }: ActionBarProps) {
    return (
        <div className={cn('flex flex-col gap-3 border-t border-stone-200/80 pt-5 sm:flex-row sm:items-center sm:justify-between', className)}>
            <div>{leading}</div>
            <div className="flex flex-wrap justify-end gap-2">{children}</div>
        </div>
    );
}
