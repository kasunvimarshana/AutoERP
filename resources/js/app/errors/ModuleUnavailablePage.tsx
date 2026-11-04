import { DASHBOARD_PATH } from '@/app/routePaths';
import type { TenantModuleCode } from '@/app/access/tenantModules';
import { TENANT_MODULES } from '@/app/access/tenantModules';
import { LinkButton } from '@/shared/components/Button';

export function ModuleUnavailablePage({ modules }: { modules: readonly TenantModuleCode[] }) {
    const labels = modules.map((code) => TENANT_MODULES.find((module) => module.code === code)?.label ?? code);

    return (
        <main className="flex min-h-[60vh] items-center justify-center px-4 py-12">
            <section className="w-full max-w-xl rounded-2xl border border-slate-200/80 bg-white p-7 shadow-xl">
                <p className="text-sm font-bold uppercase tracking-wider text-slate-500">Module unavailable</p>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">This feature is not enabled for the tenant</h1>
                <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    Required module{labels.length === 1 ? '' : 's'}: {labels.join(', ')}. Ask a platform administrator to review the tenant plan.
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
