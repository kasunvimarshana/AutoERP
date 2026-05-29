import { NavLink } from 'react-router-dom';
import { dashboardItem, modulesItem } from '../../config/navigation';
import { moduleCatalog } from '../../modules/moduleCatalog';
import { useUi } from '../../contexts/UiContext';

const groupedModules = moduleCatalog.reduce<Record<string, typeof moduleCatalog>>((groups, module) => {
    (groups[module.group] ??= []).push(module);
    return groups;
}, {});

const orderedGroups = ['Core', 'Commercial', 'Finance', 'Operations', 'Master Data', 'System'] as const;

export function Sidebar() {
    const { sidebarCollapsed } = useUi();

    return (
        <aside className={`sticky top-0 hidden h-screen shrink-0 border-r border-white/60 bg-slate-950 text-slate-100 shadow-2xl shadow-slate-950/20 lg:flex ${sidebarCollapsed ? 'w-24' : 'w-[19rem]'}`}>
            <div className="flex h-full w-full flex-col overflow-y-auto px-4 py-5">
                <div className="rounded-[1.6rem] border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                    <div className="text-xs font-semibold uppercase tracking-[0.28em] text-brand-200">AutoERP</div>
                    {!sidebarCollapsed ? (
                        <>
                            <div className="mt-2 font-display text-xl font-semibold tracking-tight text-white">Enterprise workspace</div>
                            <p className="mt-2 text-sm leading-6 text-slate-300">
                                Backend-authoritative ERP shell with modular routing and preview-first workflows.
                            </p>
                        </>
                    ) : null}
                </div>

                <nav className="mt-6 space-y-6 text-sm">
                    <div className="space-y-1">
                        <SidebarLink item={dashboardItem} collapsed={sidebarCollapsed} />
                        <SidebarLink item={modulesItem} collapsed={sidebarCollapsed} />
                    </div>

                    {orderedGroups.map((group) => {
                        const modules = groupedModules[group] ?? [];

                        return (
                            <div key={group} className="space-y-2">
                                {!sidebarCollapsed ? <div className="px-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{group}</div> : null}
                                <div className="space-y-1">
                                    {modules.map((module) => (
                                        <SidebarLink key={module.key} item={{ label: module.label, path: module.path, description: module.description }} collapsed={sidebarCollapsed} />
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </nav>
            </div>
        </aside>
    );
}

function SidebarLink({ item, collapsed }: { item: { label: string; path: string; description: string }; collapsed: boolean }) {
    return (
        <NavLink
            to={item.path}
            className={({ isActive }) =>
                [
                    'group flex items-center gap-3 rounded-2xl border px-3 py-3 transition-all duration-150',
                    isActive ? 'border-white/15 bg-white/10 text-white shadow-lg shadow-black/10' : 'border-transparent text-slate-300 hover:border-white/10 hover:bg-white/5 hover:text-white',
                    collapsed ? 'justify-center' : '',
                ].join(' ')
            }
        >
            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-xs font-semibold text-brand-100 ring-1 ring-inset ring-white/10">
                {item.label.slice(0, 2).toUpperCase()}
            </span>
            {!collapsed ? (
                <span className="min-w-0">
                    <span className="block truncate font-medium">{item.label}</span>
                    <span className="block truncate text-xs text-slate-400 group-hover:text-slate-300">{item.description}</span>
                </span>
            ) : null}
        </NavLink>
    );
}
