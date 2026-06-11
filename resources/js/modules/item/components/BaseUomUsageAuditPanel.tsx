import { Panel } from '@/shared/components/Panel';
import type { BaseUomUsageAudit } from '../itemTypes';

export function BaseUomUsageAuditPanel({ audit }: { audit: BaseUomUsageAudit }) {
    return (
        <Panel title="Usage audit">
            <div className="flex flex-wrap gap-3 text-sm">
                <span className={`rounded-full px-3 py-1 font-semibold ${audit.has_usage ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'}`}>
                    {audit.has_usage ? `${audit.usage_count} usage references` : 'Unused item'}
                </span>
                <span className="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">
                    {audit.can_direct_edit ? 'Direct edit allowed' : 'Conversion wizard required'}
                </span>
            </div>

            {audit.affected_modules.length > 0 && (
                <div className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {audit.affected_modules.map((module) => (
                        <div key={module.module} className="rounded-lg border border-slate-200 p-3">
                            <div className="flex justify-between gap-3">
                                <span className="font-semibold capitalize text-slate-800">{module.module.replaceAll('_', ' ')}</span>
                                <span className="text-sm text-slate-500">{module.count}</span>
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                {Object.entries(module.references).filter(([, count]) => count > 0).map(([name, count]) => `${name.replaceAll('_', ' ')}: ${count}`).join(', ')}
                            </p>
                        </div>
                    ))}
                </div>
            )}

            {audit.blockers.length > 0 && (
                <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-semibold">Conversion blockers</p>
                    <ul className="mt-2 list-disc space-y-1 pl-5">
                        {audit.blockers.map((blocker) => <li key={blocker.code}>{blocker.message} ({blocker.count})</li>)}
                    </ul>
                </div>
            )}

            {audit.warnings.length > 0 && (
                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <ul className="list-disc space-y-1 pl-5">{audit.warnings.map((warning) => <li key={warning}>{warning}</li>)}</ul>
                </div>
            )}
        </Panel>
    );
}
