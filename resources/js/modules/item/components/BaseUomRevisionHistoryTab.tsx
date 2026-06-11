import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { listBaseUomRevisions } from '../itemApi';

export default function BaseUomRevisionHistoryTab({ itemId, refreshKey = 0 }: { itemId: number; refreshKey?: number }) {
    const revisions = useApi((signal) => listBaseUomRevisions(itemId, signal), [itemId, refreshKey]);

    return (
        <Panel title="Base UOM revision history">
            {revisions.loading && <LoadingState />}
            <ErrorAlert error={revisions.error} />
            {!revisions.loading && revisions.data?.length === 0 && <p className="text-sm text-slate-500">No base UOM conversions have been applied.</p>}
            {revisions.data && revisions.data.length > 0 && (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th className="px-3 py-2">Effective</th><th className="px-3 py-2">Change</th><th className="px-3 py-2">Factor</th><th className="px-3 py-2">Reason</th><th className="px-3 py-2">Status</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {revisions.data.map((revision) => (
                                <tr key={revision.id}>
                                    <td className="px-3 py-2">{new Date(revision.effective_at).toLocaleString()}</td>
                                    <td className="px-3 py-2 font-semibold">{revision.old_base_uom?.code ?? '-'} to {revision.new_base_uom?.code ?? '-'}</td>
                                    <td className="px-3 py-2 font-mono">{revision.conversion_factor}</td>
                                    <td className="px-3 py-2">{revision.reason || '-'}</td>
                                    <td className="px-3 py-2"><StatusBadge status={revision.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </Panel>
    );
}
