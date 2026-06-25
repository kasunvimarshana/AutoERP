import { useMemo, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import {
    filterNavigation,
    findActiveModuleId,
    findNavigationMatch,
} from '@/app/navigation/navigationUtils';
import type { NavigationModuleItem, NavigationSection } from '@/app/navigation/navigationTypes';
import { useAuth } from '@/modules/auth/AuthProvider';
import { AppHeader } from './AppHeader';
import { Sidebar } from './Sidebar';

interface WorkspaceLayoutProps {
    sections: NavigationSection[];
    homePath: string;
    workspaceLabel: string;
    mode: 'tenant' | 'platform';
}

export function WorkspaceLayout({ sections, homePath, workspaceLabel, mode }: WorkspaceLayoutProps) {
    const location = useLocation();
    const auth = useAuth();
    const [mobileOpenLocationKey, setMobileOpenLocationKey] = useState<string | null>(null);
    const [collapsed, setCollapsed] = useState(
        () => window.localStorage.getItem(`autoerp.${mode}.sidebar.collapsed`) === 'true',
    );
    const [preferredExpandedModuleId, setPreferredExpandedModuleId] = useState<string | null>(null);

    const visibleSections = useMemo(() => filterNavigation(sections, {
        tenantId: auth.tenant?.id ?? null,
        isPlatformOperator: auth.isPlatformOperator,
        organizationUnitId: auth.organizationUnit?.id ?? null,
        roles: auth.roles,
        permissions: auth.permissions,
        permissionsLoaded: auth.permissionsLoaded,
        enabledModules: auth.enabledModules,
        enabledModulesLoaded: auth.enabledModulesLoaded,
    }), [
        auth.enabledModules,
        auth.enabledModulesLoaded,
        auth.isPlatformOperator,
        auth.organizationUnit?.id,
        auth.permissions,
        auth.permissionsLoaded,
        auth.roles,
        auth.tenant?.id,
        sections,
    ]);

    const match = useMemo(
        () => findNavigationMatch(location.pathname, location.search, visibleSections),
        [location.pathname, location.search, visibleSections],
    );
    const activeModuleId = findActiveModuleId(location.pathname, location.search, visibleSections);
    const moduleIds = useMemo(
        () => visibleSections
            .flatMap((section) => section.items)
            .filter((item): item is NavigationModuleItem => item.type === 'module')
            .map((item) => item.id),
        [visibleSections],
    );
    const expandedModuleId = activeModuleId
        ?? (preferredExpandedModuleId && moduleIds.includes(preferredExpandedModuleId)
            ? preferredExpandedModuleId
            : moduleIds[0] ?? null);
    const mobileOpen = mobileOpenLocationKey === location.key;

    function updateCollapsed(next: boolean) {
        setCollapsed(next);
        window.localStorage.setItem(`autoerp.${mode}.sidebar.collapsed`, String(next));
    }

    return (
        <div className="min-h-screen bg-slate-100 text-slate-900">
            <Sidebar
                sections={visibleSections}
                activeItemId={match?.item.id ?? null}
                activeParentId={match?.parent?.id ?? null}
                expandedModuleId={expandedModuleId}
                mobileOpen={mobileOpen}
                collapsed={collapsed}
                user={auth.user}
                tenant={auth.tenant}
                organizationUnit={auth.organizationUnit}
                homePath={homePath}
                workspaceLabel={workspaceLabel}
                onCloseMobile={() => setMobileOpenLocationKey(null)}
                onExpandDesktop={() => updateCollapsed(false)}
                onToggleModule={(moduleId) => setPreferredExpandedModuleId(
                    expandedModuleId === moduleId ? null : moduleId,
                )}
            />
            <div className={`app-content min-h-screen ${collapsed ? 'lg:pl-20' : 'lg:pl-72'}`}>
                <AppHeader
                    collapsed={collapsed}
                    match={match}
                    mode={mode}
                    onToggleCollapsed={() => updateCollapsed(!collapsed)}
                    onOpenMobile={() => setMobileOpenLocationKey(location.key)}
                />
                <main className="app-main mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
