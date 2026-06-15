import { useMemo } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { navigationSections } from './navigationConfig';
import { filterNavigation, resolveEnabledModules } from './navigationUtils';
import type { NavigationItem } from './navigationTypes';

export default function WorkspaceOverviewPage({ itemId, title }: { itemId: string; title: string }) {
    const auth = useAuth();
    const sections = useMemo(() => filterNavigation(navigationSections, {
        permissions: auth.user?.permissions ?? [],
        roles: auth.user?.roles ?? [],
        enabledModules: resolveEnabledModules(
            auth.tenant?.enabled_modules,
            auth.organizationUnit?.enabled_modules,
        ),
        features: auth.tenant?.features,
    }), [
        auth.organizationUnit?.enabled_modules,
        auth.tenant?.enabled_modules,
        auth.tenant?.features,
        auth.user?.permissions,
        auth.user?.roles,
    ]);
    const destinations = overviewDestinations(sections, itemId);

    return (
        <>
            <ContentHeader title={title} description="Open a primary workspace, then use page tabs and record actions for the detailed workflow." />
            <div className="divide-y divide-slate-200 border-y border-slate-200 bg-white">
                {destinations.map((item) => (
                    <Link
                        key={item.id}
                        to={item.route!}
                        className="flex min-h-14 items-center justify-between gap-4 px-4 py-3 text-sm font-semibold text-slate-800 hover:bg-sky-50 hover:text-sky-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500"
                    >
                        <span>{item.label}</span>
                        <span aria-hidden="true">&gt;</span>
                    </Link>
                ))}
            </div>
        </>
    );
}

function overviewDestinations(
    sections: Array<{ id: string; items: NavigationItem[] }>,
    itemId: string,
): NavigationItem[] {
    const items = sections.flatMap((section) => section.items);
    const parent = items.find((item) => item.id === itemId);
    if (parent?.children) {
        return parent.children.filter((item) => item.id !== `${itemId}-overview` && item.route);
    }

    const section = sections.find((candidate) => candidate.id === itemId);
    return section?.items.filter((item) => item.id !== `${itemId}-overview` && item.route) ?? [];
}
