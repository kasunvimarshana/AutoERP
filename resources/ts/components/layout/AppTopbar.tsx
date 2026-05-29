import { useAuth } from '../../app/contexts/AuthContext';
import { useUi } from '../../app/contexts/UiContext';

function IconButton({ children, label }: { children: React.ReactNode; label: string }) {
    return (
        <button aria-label={label} className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50" type="button">
            <span className="text-lg leading-none">{children}</span>
        </button>
    );
}

export function AppTopbar() {
    const { user } = useAuth();
    const { toggleSidebar } = useUi();

    return (
        <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
            <div className="flex flex-col gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-3">
                        <button className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden" onClick={toggleSidebar} type="button">
                            ☰
                        </button>

                        <div className="hidden w-[30rem] items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2.5 shadow-sm md:flex">
                            <svg aria-hidden="true" className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
                                <path d="m21 21-4.35-4.35" />
                                <path d="M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" />
                            </svg>
                            <input className="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400" placeholder="Search VIN, Customer, or Order ID..." />
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <IconButton label="Notifications">🔔</IconButton>
                        <IconButton label="Settings">⚙</IconButton>
                        <div className="border-l border-slate-200 pl-4">
                            <div className="flex items-center gap-3">
                                <div className="hidden text-right sm:block">
                                    <div className="text-sm font-semibold text-slate-950">{user?.name ?? 'John Workshop'}</div>
                                    <div className="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">{user?.role ?? 'Site Manager'}</div>
                                </div>
                                <img alt={user?.name ?? 'John Workshop'} className="h-10 w-10 rounded-full object-cover ring-2 ring-slate-100" src={user?.avatarUrl ?? 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=128&q=80'} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    );
}
