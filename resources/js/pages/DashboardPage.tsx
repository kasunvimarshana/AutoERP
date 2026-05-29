import { Link } from 'react-router-dom';
import { Surface } from '../components/ui/Surface';
import { StatCard } from '../components/ui/StatCard';
import { moduleCatalog } from '../modules/moduleCatalog';

export function DashboardPage() {
    const featuredModules = moduleCatalog.slice(0, 6);

    return (
        <div className="space-y-6">
            <section className="grid gap-4 xl:grid-cols-[1.6fr_1fr]">
                <Surface
                    title="Operations command center"
                    subtitle="A backend-authoritative shell for finance, sales, inventory, HR, and master data workflows."
                >
                    <div className="grid gap-4 md:grid-cols-3">
                        <StatCard label="Calculation boundary" value="Frontend only renders previews; business math stays in services." tone="slate" />
                        <StatCard label="Routing model" value="Modules are lazy loaded by route and grouped in the shell." tone="slate" />
                        <StatCard label="Context layer" value="Auth, tenant, and UI state are shared through Context API." tone="slate" />
                    </div>
                </Surface>

                <Surface title="Operational guardrails" subtitle="The shell is designed around enterprise-grade boundaries from day one.">
                    <ul className="space-y-3 text-sm leading-6 text-slate-600">
                        <li>• Finance, stock, payment, and tax logic are resolved by backend preview services.</li>
                        <li>• Employee and user identity remain separate concepts in the UI and API model.</li>
                        <li>• Module pages are placeholders now, but routing and layout are production-shaped.</li>
                    </ul>
                </Surface>
            </section>

            <Surface title="Featured modules" subtitle="Shortcuts into the ERP surface area.">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {featuredModules.map((module) => (
                        <Link
                            key={module.key}
                            to={module.path}
                            className="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg"
                        >
                            <div className={`h-2 w-16 rounded-full bg-gradient-to-r ${module.accent}`} />
                            <div className="mt-4 font-display text-lg font-semibold text-slate-950">{module.label}</div>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{module.description}</p>
                        </Link>
                    ))}
                </div>
            </Surface>
        </div>
    );
}
