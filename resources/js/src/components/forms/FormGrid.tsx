import type { PropsWithChildren } from 'react';
import { cn } from '../../lib/cn';

type FormGridProps = PropsWithChildren<{
    className?: string;
}>;

export function FormGrid({ children, className }: FormGridProps) {
    return <div className={cn('grid gap-5 md:grid-cols-2 xl:grid-cols-3', className)}>{children}</div>;
}
