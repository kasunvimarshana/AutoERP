import { Link, useLocation } from 'react-router-dom';
import { currentNavigationItem } from './navigation';

type UserSummary = {
    email?: string | null;
    name?: string | null;
} | null;

export function Topbar({ onOpenMobile, onSignOut, user }: { onOpenMobile: () => void; onSignOut: () => void; user: UserSummary }) {
    const { pathname } = useLocation();
    const current = currentNavigationItem(pathname);
    const currentParts = current?.to.split('/').filter(Boolean) ?? [];
    const trailingParts = pathname.split('/').filter(Boolean).slice(currentParts.length);

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div className="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div className="flex min-w-0 items-center gap-3">
                    <button aria-label="Open navigation" className="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden" onClick={onOpenMobile} type="button">
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-bold text-slate-900">{current?.label ?? 'AutoERP'}</p>
                        <nav aria-label="Breadcrumb" className="hidden items-center gap-1 text-xs text-slate-400 sm:flex">
                            <Link className="hover:text-blue-600" to="/">Workspace</Link>
                            {current ? <span className="flex items-center gap-1"><span>/</span><Link className="hover:text-blue-600" to={current.to}>{current.label}</Link></span> : null}
                            {trailingParts.map((part) => <span className="flex items-center gap-1" key={part}><span>/</span><span className="max-w-36 truncate capitalize">{/^\d+$/.test(part) ? `Record ${part}` : part.replaceAll('-', ' ')}</span></span>)}
                        </nav>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <div className="hidden text-right sm:block">
                        <p className="max-w-52 truncate text-sm font-semibold text-slate-800">{user?.name || 'Signed in user'}</p>
                        <p className="max-w-52 truncate text-xs text-slate-400">{user?.email}</p>
                    </div>
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700 ring-1 ring-slate-200">
                        {(user?.name || user?.email || 'U').slice(0, 1).toUpperCase()}
                    </div>
                    <button aria-label="Sign out" className="rounded-lg p-2 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900 sm:px-3" onClick={onSignOut} type="button">
                        <svg className="h-5 w-5 sm:hidden" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" /></svg>
                        <span className="hidden sm:inline">Sign out</span>
                    </button>
                </div>
            </div>
        </header>
    );
}
