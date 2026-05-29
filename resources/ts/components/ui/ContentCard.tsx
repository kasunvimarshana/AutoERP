import type { PropsWithChildren } from 'react';
import { Card } from './Card';

export function ContentCard({ children, className }: PropsWithChildren<{ className?: string }>) {
    return <Card className={className ? `${className}` : ''}>{children}</Card>;
}
