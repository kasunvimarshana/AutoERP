import { NavLink, useLocation } from 'react-router-dom';
import { cn } from '../../utils/cn';
import { navigation } from './navigation';

type SidebarProps = {
    collapsed: boolean;
    mobileOpen: boolean;
    onCloseMobile: () => void;
    onToggle: () => void;
};

export function Sidebar({ collapsed, mobileOpen, onCloseMobile, onToggle }: SidebarProps) {
    const { pathname } = useLocation();

    return (
        <>
            {mobileOpen ? <button aria-label="Close navigation" className="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden" onClick={onCloseMobile} type="button" /> : null}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-800 bg-slate-950 text-slate-300 shadow-2xl shadow-slate-950/20 transition-transform duration-200 lg:translate-x-0',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                    collapsed && 'lg:w-20',
                )}
            >
                <div className={cn('flex h-16 items-center border-b border-slate-800 px-4', collapsed ? 'lg:justify-center' : 'justify-between')}>
                    <NavLink className="flex min-w-0 items-center gap-3" onClick={onCloseMobile} to="/customers">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-xs font-black tracking-wide text-white shadow-lg shadow-blue-950/40">AE</span>
                        <span className={cn('min-w-0', collapsed && 'lg:hidden')}>
                            <span className="block truncate text-sm font-bold text-white">AutoERP</span>
                            <span className="block truncate text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Operations suite</span>
                        </span>
                    </NavLink>
                    <button aria-label="Close navigation" className="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" onClick={onCloseMobile} type="button">
                        <Icon name="close" />
                    </button>
                </div>

                <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    {navigation.map((section) => (
                        <section key={section.label}>
                            <p className={cn('mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600', collapsed && 'lg:text-center lg:text-[0px]')}>
                                {collapsed ? <span className="hidden lg:inline">...</span> : null}
                                <span className={collapsed ? 'lg:hidden' : ''}>{section.label}</span>
                            </p>
                            <div className="space-y-1">
                                {section.items.map((item) => {
                                    const active = pathname === item.to || pathname.startsWith(`${item.to}/`);

                                    return (
                                        <NavLink
                                            className={cn(
                                                'group flex h-10 items-center gap-3 rounded-lg px-3 text-sm font-semibold transition',
                                                active ? 'bg-blue-600 text-white shadow-md shadow-blue-950/30' : 'text-slate-400 hover:bg-slate-900 hover:text-white',
                                                collapsed && 'lg:justify-center lg:px-0',
                                            )}
                                            key={item.to}
                                            onClick={onCloseMobile}
                                            title={collapsed ? item.label : undefined}
                                            to={item.to}
                                        >
                                            <Icon name={item.icon} />
                                            <span className={collapsed ? 'lg:hidden' : ''}>{item.label}</span>
                                        </NavLink>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </nav>

                <div className="border-t border-slate-800 p-3">
                    <button
                        className={cn('hidden h-10 w-full items-center gap-3 rounded-lg px-3 text-sm font-semibold text-slate-400 hover:bg-slate-900 hover:text-white lg:flex', collapsed && 'justify-center px-0')}
                        onClick={onToggle}
                        type="button"
                    >
                        <span className={collapsed ? 'rotate-180' : ''}><Icon name="collapse" /></span>
                        <span className={collapsed ? 'hidden' : ''}>Collapse sidebar</span>
                    </button>
                </div>
            </aside>
        </>
    );
}

function Icon({ name }: { name: string }) {
    const paths: Record<string, React.ReactNode> = {
        close: <path d="m6 6 12 12M18 6 6 18" />,
        collapse: <path d="m15 18-6-6 6-6" />,
        customers: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></>,
        finance: <><path d="M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M3 10l9-7 9 7z" /></>,
        invoice: <><path d="M6 2h9l5 5v15H6z" /><path d="M14 2v6h6M9 13h6M9 17h6" /></>,
        items: <><path d="m21 8-9-5-9 5 9 5z" /><path d="m3 8 9 5 9-5M3 12l9 5 9-5M3 16l9 5 9-5" /></>,
        payments: <><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20M6 15h2" /></>,
        purchase: <><path d="M3 3h2l2 12h11l2-8H6" /><circle cx="9" cy="20" r="1" /><circle cx="18" cy="20" r="1" /></>,
        service: <><path d="M14.7 6.3a4 4 0 0 0-5-5L7 4l3 3 2.7-2.7a4 4 0 0 0 2 2z" /><path d="m5 13-3 3 6 6 3-3M12 12l8.5 8.5" /></>,
        suppliers: <><path d="M3 21h18M5 21V5h10v16M15 9h4v12M8 9h4M8 13h4M8 17h4" /></>,
        uom: <><path d="M4 4v16h16M8 16l8-8M7 8h2M15 16h2" /></>,
        vehicles: <><path d="m5 17-1 2v2h2l1-2h10l1 2h2v-2l-1-2-2-7H7z" /><path d="M5 17h14M7 10h10" /><circle cx="8" cy="16" r="1" /><circle cx="16" cy="16" r="1" /></>,
    };

    return <svg aria-hidden="true" className="h-5 w-5 shrink-0" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">{paths[name]}</svg>;
}
