import { useAuth } from '../../contexts/AuthContext';
import { useTenant } from '../../contexts/TenantContext';
import { useUi } from '../../contexts/UiContext';

export function Topbar() {
    const { user, signOut } = useAuth();
    const { tenant, organizationUnit } = useTenant();
    const { toggleSidebar, setCommandPaletteOpen } = useUi();

    return (
        <header className="flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-slate-200/80 bg-white/85 px-5 py-4 shadow-[0_16px_44px_-36px_rgba(15,23,42,0.45)] backdrop-blur">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={toggleSidebar}
                    className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-brand-200 hover:text-brand-700 lg:hidden"
                    aria-label="Toggle sidebar"
                >
                    ☰
                </button>
                <div>
                    <div className="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Workspace</div>
                    <div className="font-display text-lg font-semibold tracking-tight text-slate-950">Backend-first ERP shell</div>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    onClick={() => setCommandPaletteOpen(true)}
                    className="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-brand-200 hover:text-brand-700"
                >
                    Search modules
                </button>

                <InfoPill label="Tenant" value={tenant?.name ?? 'No tenant selected'} />
                <InfoPill label="Unit" value={organizationUnit?.name ?? 'Global'} />
                <InfoPill label="User" value={user?.name ?? 'Guest'} />

                <button
                    type="button"
                    onClick={signOut}
                    className="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-slate-950/20 transition hover:bg-slate-800"
                >
                    Sign out
                </button>
            </div>
        </header>
    );
}

function InfoPill({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm">
            <span className="font-semibold text-slate-500">{label}:</span> {value}
        </div>
    );
}
