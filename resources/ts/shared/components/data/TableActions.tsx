import type { ReactNode } from 'react';
import { Button } from '../ui/Button';

type TableActionsProps = {
    children?: ReactNode;
};

export function TableActions({ children }: TableActionsProps) {
    return (
        <div className="flex items-center justify-end gap-2">
            {children ?? (
                <>
                    <Button variant="secondary">View</Button>
                    <Button variant="ghost">More</Button>
                </>
            )}
        </div>
    );
}
