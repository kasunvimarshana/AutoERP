import type { ReactNode } from 'react';
import { usePermissionContext } from '../../contexts/PermissionContext';

type PermissionGuardProps = {
    children: ReactNode;
    permission: string;
};

export function PermissionGuard({ children, permission }: PermissionGuardProps) {
    const { can } = usePermissionContext();

    if (!can(permission)) {
        return <div className="p-8 text-sm text-slate-500">You do not have access to this screen.</div>;
    }

    return children;
}
