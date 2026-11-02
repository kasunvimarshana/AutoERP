import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { listBaseUomRevisions } from '../itemApi';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';

export default function BaseUomRevisionHistoryTab({ itemId, refreshKey = 0 }: { itemId: number; refreshKey?: number }) {
    const revisions = useApi((signal) => listBaseUomRevisions(itemId, signal), [itemId, refreshKey]);

    return (
        <Panel title="Base UOM revision history">
            {revisions.loading && <LoadingState />}
            <ErrorAlert error={revisions.error} />
            {!revisions.loading && revisions.data?.length === 0 && <p className="text-sm text-slate-500">No base UOM conversions have been applied.</p>}
            {revisions.data && revisions.data.length > 0 && (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr><th className="px-4 py-3">Effective</th><th className="px-4 py-3">Change</th><th className="px-4 py-3">Factor</th><th className="px-4 py-3">Reason</th><th className="px-4 py-3">Status</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {revisions.data.map((revision) => (
                                    <tr key={revision.id} className="transition-colors hover:bg-blue-50/40">
                                        <td className="px-4 py-3 text-slate-700">{formatBusinessDateTime(revision.effective_at)}</td>
                                        <td className="px-4 py-3 font-semibold text-slate-900">{revision.old_base_uom?.code ?? '-'} to {revision.new_base_uom?.code ?? '-'}</td>
                                        <td className="px-4 py-3 font-mono text-slate-700">{revision.conversion_factor}</td>
                                        <td className="px-4 py-3 text-slate-600">{revision.reason || '-'}</td>
                                        <td className="px-4 py-3"><StatusBadge status={revision.status} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </Panel>
    );
}
