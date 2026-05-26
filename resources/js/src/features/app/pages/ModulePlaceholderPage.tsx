import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';
import type { AppPageMeta } from '../../../app/router/app-navigation';

type ModulePlaceholderPageProps = {
    page: AppPageMeta;
};

export function ModulePlaceholderPage({ page }: ModulePlaceholderPageProps) {
    return (
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
            <Card className="overflow-hidden">
                <div className="border-b border-stone-200/80 px-6 py-5">
                    <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">{page.sectionLabel}</p>
                    <h2 className="mt-2 text-2xl font-semibold text-stone-950">{page.title}</h2>
                    <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">{page.description}</p>
                </div>

                <div className="grid gap-6 px-6 py-6 lg:grid-cols-[1.45fr_0.9fr]">
                    <div className="space-y-4">
                        <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/70 p-5">
                            <h3 className="text-sm font-semibold text-stone-900">Phase 1B scope</h3>
                            <p className="mt-2 text-sm leading-6 text-stone-600">
                                This route exists so the ERP shell, topbar, sidebar navigation, and breadcrumbs behave like
                                a real application. CRUD screens, filters, forms, and tables for this module are intentionally
                                deferred to later phases.
                            </p>
                        </div>

                        <div className="rounded-2xl border border-stone-200 bg-white p-5">
                            <h3 className="text-sm font-semibold text-stone-900">What will come next</h3>
                            <ul className="mt-3 space-y-3 text-sm leading-6 text-stone-600">
                                <li>Module-specific list page and detail flows</li>
                                <li>Query hooks connected to real backend endpoints</li>
                                <li>Filters, tables, empty states, and mutation forms</li>
                            </ul>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-2xl border border-stone-200 bg-white p-5">
                            <h3 className="text-sm font-semibold text-stone-900">Current route</h3>
                            <p className="mt-3 rounded-xl bg-stone-50 px-3 py-2 font-mono text-xs text-stone-600">{page.path}</p>
                        </div>

                        <div className="rounded-2xl border border-stone-200 bg-white p-5">
                            <h3 className="text-sm font-semibold text-stone-900">Return to dashboard</h3>
                            <p className="mt-2 text-sm leading-6 text-stone-600">
                                Use the dashboard while the rest of the module screens are being layered in.
                            </p>
                            <div className="mt-4">
                                <Link to="/">
                                    <Button variant="secondary">Open dashboard</Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    );
}
