import type { ReactNode } from 'react';
import { useAuthContext } from '../../contexts/AuthContext';

export function AuthGuard({ children }: { children: ReactNode }) {
    const { isAuthenticated } = useAuthContext();

    if (!isAuthenticated) {
        return <div className="p-8">Please sign in.</div>;
    }

    return children;
}
