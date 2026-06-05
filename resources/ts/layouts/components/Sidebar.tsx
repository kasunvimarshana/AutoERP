import { NavLink } from 'react-router-dom';
import { menuConfig, type MenuItem } from '../../config/menuConfig';
import { useSidebarContext } from '../../contexts/SidebarContext';
import { cn } from '../../shared/utils/cn';

function Icon({ name }: { name: string }) {
    const common = 'h-4 w-4';
    const paths: Record<string, string> = {
        bank: 'M4 10h16M5 10l7-5 7 5M6 10v8m4-8v8m4-8v8m4-8v8M4 18h16',
        box: 'M4 7l8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7',
        building: 'M4 21V5l8-3 8 3v16M8 8h.01M12 8h.01M16 8h.01M8 12h.01M12 12h.01M16 12h.01M9 21v-5h6v5',
        car: 'M5 16h14l-1.5-5h-11L5 16Zm2 0v2m10-2v2M8 11l1-3h6l1 3',
        card: 'M4 7h16v10H4V7Zm0 3h16',
        cart: 'M5 6h2l2 9h8l2-6H8m2 10h.01M17 19h.01',
        clipboard: 'M8 4h8v3H8V4Zm-2 2h12v14H6V6Z',
        doc: 'M7 3h7l4 4v14H7V3Zm7 0v5h5',
        file: 'M7 3h8l3 3v15H7V3Zm3 10h5m-5 4h5',
        grid: 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z',
        id: 'M5 5h14v14H5V5Zm3 5h4m-4 4h8',
        key: 'M15 8a4 4 0 1 0-3.4 3.95L9 14.5V17H6v3H3v-3l6.05-6.05',
        receipt: 'M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h4',
        ruler: 'M4 17 17 4l3 3L7 20l-3-3Zm4-1-1-1m4-3-1-1m4-3-1-1',
        settings: 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-5v3m0 12v3M4.2 4.2l2.1 2.1m11.4 11.4 2.1 2.1M3 12h3m12 0h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1',
        tag: 'M4 12V5h7l9 9-7 7-9-9Zm4-4h.01',
        truck: 'M3 7h11v8H3V7Zm11 3h4l3 3v2h-7v-5ZM6 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        users: 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8-1a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3 20a5 5 0 0 1 10 0m1-2a4 4 0 0 1 7 2',
        warehouse: 'M3 10l9-6 9 6v10H3V10Zm4 10v-7h10v7',
    };

    return (
        <svg className={common} fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
            <path d={paths[name] ?? paths.grid} />
        </svg>
    );
}

function SidebarItem({ item }: { item: MenuItem }) {
    const { closeSidebar } = useSidebarContext();

    return (
        <div>
            <NavLink
                className={({ isActive }) =>
                    cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        isActive ? 'bg-black text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950',
                    )
                }
                onClick={closeSidebar}
                to={item.path}
            >
                <Icon name={item.icon} />
                <span className="flex-1">{item.label}</span>
            </NavLink>
            {item.children?.length ? (
                <div className="ml-7 mt-1 space-y-0.5 border-l border-slate-100 pl-3">
                    {item.children.map((child) => (
                        <NavLink
                            className={({ isActive }) =>
                                cn(
                                    'block rounded-md px-2 py-1.5 text-xs font-semibold transition',
                                    isActive ? 'bg-slate-950 text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
                                )
                            }
                            key={child.path}
                            onClick={closeSidebar}
                            to={child.path}
                        >
                            {child.label}
                        </NavLink>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function SidebarContent() {
    return (
        <>
            <div className="flex h-16 items-center gap-3 px-6">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-950">
                    <Icon name="car" />
                </div>
                <div>
                    <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">Enterprise Fleet</p>
                </div>
            </div>

            <nav className="flex-1 space-y-5 overflow-y-auto px-3 py-5">
                {menuConfig.map((group, groupIndex) => (
                    <div key={group.label ?? groupIndex}>
                        {group.label ? <p className="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">{group.label}</p> : null}
                        <div className="space-y-1">
                            {group.items.map((item) => (
                                <SidebarItem item={item} key={item.path} />
                            ))}
                        </div>
                    </div>
                ))}
            </nav>

            <div className="space-y-3 px-6 py-6">
                <NavLink className="flex items-center gap-2 text-sm font-bold text-black" to="/vehicle-service/job-cards/new">
                    <span className="text-xl leading-none">+</span>
                    Create New Job
                </NavLink>
                <div className="space-y-2 text-sm font-medium text-slate-400">
                    <p>Support</p>
                    <NavLink className="block transition hover:text-slate-900" to="/logout">Logout</NavLink>
                </div>
            </div>
        </>
    );
}

export function Sidebar() {
    const { closeSidebar, isSidebarOpen } = useSidebarContext();

    return (
        <>
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-100 bg-white shadow-xl shadow-slate-200/50 lg:flex">
                <SidebarContent />
            </aside>

            <div className={cn('fixed inset-0 z-40 lg:hidden', isSidebarOpen ? 'pointer-events-auto' : 'pointer-events-none')}>
                <button
                    aria-label="Close navigation"
                    className={cn(
                        'absolute inset-0 bg-slate-950/35 transition-opacity',
                        isSidebarOpen ? 'opacity-100' : 'opacity-0',
                    )}
                    onClick={closeSidebar}
                    type="button"
                />
                <aside
                    className={cn(
                        'relative flex h-full w-72 max-w-[85vw] flex-col border-r border-slate-100 bg-white shadow-2xl transition-transform',
                        isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    )}
                >
                    <SidebarContent />
                </aside>
            </div>
        </>
    );
}
