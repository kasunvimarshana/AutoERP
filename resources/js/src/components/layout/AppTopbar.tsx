import { useEffect, useState } from 'react';
import { Button } from '../ui/Button';
import type { AppPageMeta } from '../../app/router/app-navigation';
import { useTenant } from '../../features/auth/context/TenantContext';
import { useAuth } from '../../features/auth/context/AuthContext';
import { cn } from '../../lib/cn';

type AppTopbarProps = {
    currentPage: AppPageMeta | null;
    isSidebarOpen: boolean;
    onSidebarToggle: () => void;
};

export function AppTopbar({ currentPage, isSidebarOpen, onSidebarToggle }: AppTopbarProps) {
    const { logout, user } = useAuth();
    const { tenantId, setTenantId } = useTenant();
    const [isProfileOpen, setIsProfileOpen] = useState(false);
    const [tenantInput, setTenantInput] = useState(String(tenantId));

    useEffect(() => {
        setTenantInput(String(tenantId));
    }, [tenantId]);

    function applyTenantSelection() {
        const nextTenantId = Number(tenantInput);

        if (Number.isFinite(nextTenantId) && nextTenantId > 0) {
            setTenantId(nextTenantId);
            return;
        }

        setTenantInput(String(tenantId));
    }

    const userName = [user?.first_name, user?.last_name].filter(Boolean).join(' ') || 'Workspace user';
    const userInitials = userName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');

    return (
        <header className="sticky top-0 z-20 border-b border-stone-200/80 bg-white/75 backdrop-blur-xl">
            <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex min-w-0 items-start gap-3">
                        <button
                            className={cn(
                                'inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-stone-200 text-stone-600 transition hover:bg-stone-50 lg:hidden',
                                isSidebarOpen && 'bg-stone-950 text-white',
                            )}
                            onClick={onSidebarToggle}
                            type="button"
                        >
                            <svg
                                aria-hidden="true"
                                className="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="M4 7h16" />
                                <path d="M4 12h16" />
                                <path d="M4 17h16" />
                            </svg>
                        </button>

                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-stone-500">
                                {currentPage?.breadcrumbs.map((crumb, index) => (
                                    <div key={crumb} className="flex items-center gap-2">
                                        {index > 0 ? <span className="text-stone-300">/</span> : null}
                                        <span>{crumb}</span>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-2">
                                <h1 className="truncate text-2xl font-semibold text-stone-950">
                                    {currentPage?.title ?? 'Workspace'}
                                </h1>
                                <p className="mt-1 max-w-2xl text-sm leading-6 text-stone-600">
                                    {currentPage?.description ?? 'Structured workspace for operating the ERP shell.'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="relative hidden shrink-0 lg:block">
                        <button
                            className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm transition hover:bg-stone-50"
                            onClick={() => setIsProfileOpen((current) => !current)}
                            type="button"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-stone-950 text-xs font-semibold text-white">
                                {userInitials || 'AU'}
                            </div>
                            <div className="text-left">
                                <p className="text-sm font-medium text-stone-900">{userName}</p>
                                <p className="text-xs text-stone-500">{user?.email ?? 'No email loaded'}</p>
                            </div>
                            <svg
                                aria-hidden="true"
                                className="h-4 w-4 text-stone-500"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        {isProfileOpen ? (
                            <div className="absolute right-0 top-[calc(100%+0.75rem)] w-64 rounded-2xl border border-stone-200 bg-white p-3 shadow-xl shadow-stone-950/10">
                                <div className="rounded-2xl bg-stone-50 px-4 py-3">
                                    <p className="text-sm font-medium text-stone-900">{userName}</p>
                                    <p className="mt-1 text-xs text-stone-500">{user?.email ?? 'No email loaded'}</p>
                                </div>
                                <div className="mt-3">
                                    <Button className="w-full" onClick={() => void logout()} variant="secondary">
                                        Sign out
                                    </Button>
                                </div>
                            </div>
                        ) : null}
                    </div>
                </div>

                <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div className="flex flex-1 flex-wrap items-center gap-3">
                        <div className="hidden min-w-[18rem] items-center gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-500 shadow-sm sm:flex">
                            <svg
                                aria-hidden="true"
                                className="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="m21 21-4.35-4.35" />
                                <path d="M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" />
                            </svg>
                            <span>Search modules, actions, and records</span>
                        </div>

                        <div className="flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm">
                            <span className="text-xs font-medium uppercase tracking-[0.16em] text-stone-500">Tenant</span>
                            <input
                                className="w-16 border-none bg-transparent text-sm font-medium text-stone-900 outline-none"
                                inputMode="numeric"
                                min={1}
                                onBlur={applyTenantSelection}
                                onChange={(event) => setTenantInput(event.target.value)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        event.preventDefault();
                                        applyTenantSelection();
                                    }
                                }}
                                type="number"
                                value={tenantInput}
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-between gap-3 xl:justify-end">
                        <div className="flex items-center gap-2">
                            <button
                                className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-600 shadow-sm transition hover:bg-stone-50"
                                type="button"
                            >
                                <svg
                                    aria-hidden="true"
                                    className="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.17V11a6 6 0 1 0-12 0v3.17a2 2 0 0 1-.6 1.42L4 17h5" />
                                    <path d="M10 20a2 2 0 0 0 4 0" />
                                </svg>
                            </button>

                            <button
                                className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-600 shadow-sm transition hover:bg-stone-50"
                                type="button"
                            >
                                <svg
                                    aria-hidden="true"
                                    className="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M12 18h.01" />
                                    <path d="M8.5 15.5A5 5 0 1 1 17 12c0 3-3 4.5-3 4.5" />
                                </svg>
                            </button>
                        </div>

                        <button
                            className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm transition hover:bg-stone-50 lg:hidden"
                            onClick={() => setIsProfileOpen((current) => !current)}
                            type="button"
                        >
                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-stone-950 text-xs font-semibold text-white">
                                {userInitials || 'AU'}
                            </div>
                            <svg
                                aria-hidden="true"
                                className="h-4 w-4 text-stone-500"
                                fill="none"
                                stroke="currentColor"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                    </div>
                </div>

                {isProfileOpen ? (
                    <div className="rounded-2xl border border-stone-200 bg-white p-3 shadow-sm lg:hidden">
                        <div className="rounded-2xl bg-stone-50 px-4 py-3">
                            <p className="text-sm font-medium text-stone-900">{userName}</p>
                            <p className="mt-1 text-xs text-stone-500">{user?.email ?? 'No email loaded'}</p>
                        </div>
                        <div className="mt-3">
                            <Button className="w-full" onClick={() => void logout()} variant="secondary">
                                Sign out
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>
        </header>
    );
}
