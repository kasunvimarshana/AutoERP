import { DASHBOARD_PATH } from '@/app/routePaths';
import { LinkButton } from '@/shared/components/Button';

export function OrganizationUnitRequiredPage() {
    return (
        <main className="flex min-h-[60vh] items-center justify-center px-4 py-12">
            <section className="w-full max-w-xl rounded-2xl border border-slate-200/80 bg-white p-7 shadow-xl">
                <p className="text-sm font-bold uppercase tracking-wider text-slate-500">Workspace required</p>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">Select an organization unit</h1>
                <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    This operational page requires an active branch or department context. Select an organization unit before continuing.
                </p>
                <LinkButton
                    to={DASHBOARD_PATH}
                    replace
                    className="mt-6"
                >
                    Return to Dashboard
                </LinkButton>
            </section>
        </main>
    );
}
