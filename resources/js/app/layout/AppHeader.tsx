import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import type { NavigationMatch } from '@/app/navigation/navigationTypes';
import { Breadcrumbs } from './Breadcrumbs';

export function AppHeader({
    collapsed,
    match,
    mode,
    onToggleCollapsed,
    onOpenMobile,
}: {
    collapsed: boolean;
    match: NavigationMatch | null;
    mode: 'tenant' | 'platform';
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
                    <p className="truncate text-sm font-semibold text-slate-800">
                        {mode === 'platform' ? 'Platform Administration' : auth.organizationUnit?.name ?? auth.tenant?.name ?? 'Tenant workspace'}
                    </p>
                    <p className="truncate text-xs text-slate-500">
                        {mode === 'platform' ? 'SaaS control plane' : auth.tenant?.name ?? 'Tenant context'}
                    </p>
                </div>
                <span className="h-8 w-px bg-slate-200" aria-hidden="true" />
            </div>

            <details className="group relative">
                <summary className="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-lg px-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-blue-500">
                    <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white" aria-hidden="true">
                        {userInitials(auth.user?.name ?? auth.user?.email)}
                    </span>
                    <span className="hidden max-w-36 truncate sm:block">{auth.user?.name ?? auth.user?.email ?? 'User'}</span>
                    <svg viewBox="0 0 20 20" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                        <path d="m6 8 4 4 4-4" />
                    </svg>
                </summary>
                <div className="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Profile</p>
                    <p className="mt-2 truncate text-sm font-semibold text-slate-900">{auth.user?.name ?? 'AutoERP user'}</p>
                    <p className="truncate text-xs text-slate-500">{auth.user?.email ?? 'No email address'}</p>
                    <div className="mt-3 border-t border-slate-100 pt-2">
                        <Button
                            variant="ghost"
                            loading={loggingOut}
                            className="w-full justify-start px-3"
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
                    </div>
                </div>
            </details>
        </header>
    );
}

function userInitials(value?: string | null): string {
    return (value ?? 'AU')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function MenuIcon() {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
            <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
    );
}
