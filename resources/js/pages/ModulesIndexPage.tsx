import { Link } from 'react-router-dom';
import { Surface } from '../components/ui/Surface';
import { moduleCatalog } from '../modules/moduleCatalog';

export function ModulesIndexPage() {
    return (
        <Surface title="Module index" subtitle="Route-aware placeholder pages for the ERP shell.">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {moduleCatalog.map((module) => (
                    <Link key={module.key} to={module.path} className="rounded-[1.4rem] border border-slate-200 bg-slate-50 p-5 transition hover:border-brand-200 hover:bg-white">
                        <div className={`h-2 w-16 rounded-full bg-gradient-to-r ${module.accent}`} />
                        <div className="mt-4 flex items-center justify-between gap-3">
                            <div>
                                <div className="font-display text-lg font-semibold text-slate-950">{module.label}</div>
                                <div className="text-xs uppercase tracking-[0.22em] text-slate-500">{module.group}</div>
                            </div>
                            <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">{module.key}</span>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-slate-600">{module.description}</p>
                    </Link>
                ))}
            </div>
        </Surface>
    );
}
