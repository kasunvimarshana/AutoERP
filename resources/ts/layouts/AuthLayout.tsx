import { Outlet } from 'react-router-dom';

export function AuthLayout() {
    return (
        <div className="min-h-screen bg-slate-50">
            <div className="grid min-h-screen lg:grid-cols-[0.95fr_1.05fr]">
                <section className="hidden border-r border-slate-200 bg-white px-10 py-10 lg:flex lg:flex-col lg:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-950 text-sm font-bold text-white">
                            AE
                        </div>
                        <div>
                            <p className="text-sm font-bold text-slate-950">AutoERP</p>
                            <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">Secure access</p>
                        </div>
                    </div>

                    <div className="max-w-lg">
                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Secure workspace</p>
                        <h1 className="mt-5 text-4xl font-bold leading-tight tracking-normal text-slate-950">
                            Sign in to your AutoERP account.
                        </h1>
                        <p className="mt-5 text-base leading-7 text-slate-500">
                            Authentication is connected to the backend auth service. Your tenant and organization context is restored with the session.
                        </p>
                    </div>

                    <div className="grid grid-cols-3 gap-3 text-sm">
                        {[
                            ['Backend', '/api/auth/login'],
                            ['Session', 'Bearer token'],
                            ['Context', 'Tenant aware'],
                        ].map(([label, value]) => (
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4" key={label}>
                                <p className="text-[11px] font-bold uppercase tracking-wide text-slate-400">{label}</p>
                                <p className="mt-2 truncate font-semibold text-slate-900">{value}</p>
                            </div>
                        ))}
                    </div>
                </section>

                <main className="flex min-h-screen items-center justify-center px-5 py-10">
                    <div className="w-full max-w-md">
                        <div className="mb-8 flex items-center gap-3 lg:hidden">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-950 text-sm font-bold text-white">
                                AE
                            </div>
                            <div>
                                <p className="text-sm font-bold text-slate-950">AutoERP</p>
                                <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">Secure access</p>
                            </div>
                        </div>
                        <Outlet />
                    </div>
                </main>
            </div>
        </div>
    );
}
