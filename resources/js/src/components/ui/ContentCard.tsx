import type { PropsWithChildren } from 'react';
import { cn } from '../../lib/cn';
import { Card } from './Card';

type ContentCardProps = PropsWithChildren<{
    className?: string;
}>;

export function ContentCard({ children, className }: ContentCardProps) {
    return <Card className={cn('overflow-hidden p-6 lg:p-7', className)}>{children}</Card>;
}
