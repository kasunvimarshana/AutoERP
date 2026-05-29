import type { ModuleDefinition } from '../moduleCatalog';
import { ModuleHeader } from '../../components/ui/ModuleHeader';
import { StatCard } from '../../components/ui/StatCard';
import { Surface } from '../../components/ui/Surface';

export function createModulePage(module: ModuleDefinition) {
    function ModulePage() {
        return (
            <div className="space-y-6">
                <ModuleHeader
                    eyebrow={module.group}
                    title={module.label}
                    description={module.description}
                    accent={module.accent}
                />

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard label="Primary promise" value={module.previewPromise} tone="brand" />
                    <StatCard label="Top workflows" value={module.actions[0] ?? 'Operational flow'} tone="slate" />
                    <StatCard label="Routing" value={module.path} tone="amber" />
                </div>

                <Surface title="Foundational capabilities" subtitle="Placeholder module experience for routing, layout, and backend preview alignment.">
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {module.actions.map((action) => (
                            <div key={action} className="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm">
                                <div className="text-sm font-medium text-slate-900">{action}</div>
                                <p className="mt-2 text-sm leading-6 text-slate-600">
                                    This placeholder route exists so the shell, navigation, and lazy module loading can be tested end-to-end.
                                </p>
                            </div>
                        ))}
                    </div>
                </Surface>
            </div>
        );
    }

    ModulePage.displayName = `${module.label.replace(/\s+/g, '')}Page`;

    return ModulePage;
}
