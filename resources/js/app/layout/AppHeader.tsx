import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import type { NavigationMatch } from '@/app/navigation/navigationTypes';
import { Breadcrumbs } from './Breadcrumbs';

export function AppHeader({
    collapsed,
    match,
    onToggleCollapsed,
    onOpenMobile,
}: {
    collapsed: boolean;
    match: NavigationMatch | null;
    onToggleCollapsed: () => void;
    onOpenMobile: () => void;
}) {
    const auth = useAuth();
    const navigate = useNavigate();
    const [loggingOut, setLoggingOut] = useState(false);

    return (
        <header className="app-header sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <button
                type="button"
                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:hidden"
                onClick={onOpenMobile}
                aria-label="Open navigation"
                aria-controls="app-sidebar"
            >
                <MenuIcon />
            </button>
            <button
                type="button"
                className="hidden h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 lg:inline-flex"
                onClick={onToggleCollapsed}
                aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
                aria-controls="app-sidebar"
                aria-expanded={!collapsed}
            >
                <MenuIcon />
            </button>

            <div className="min-w-0 flex-1">
                <Breadcrumbs match={match} />
            </div>

            <div className="hidden min-w-0 items-center gap-3 md:flex">
                <div className="min-w-0 text-right">
                    <p className="truncate text-sm font-semibold text-slate-800">{auth.organizationUnit?.name ?? auth.tenant?.name ?? 'Current workspace'}</p>
                    <p className="truncate text-xs text-slate-500">{auth.tenant?.name ?? 'Tenant context'}</p>
                </div>
                <span className="h-8 w-px bg-slate-200" aria-hidden="true" />
            </div>

            <Button
                variant="ghost"
                loading={loggingOut}
                className="px-3"
                onClick={async () => {
                    setLoggingOut(true);
                    try {
                        await auth.logout();
                    } finally {
                        setLoggingOut(false);
                        navigate('/login', { replace: true });
                    }
                }}
            >
                Logout
            </Button>
        </header>
    );
}

function MenuIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    );
}
