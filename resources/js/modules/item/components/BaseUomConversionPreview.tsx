import { Panel } from '@/shared/components/Panel';
import type { BaseUomConversionPreview as Preview } from '../itemTypes';

export function BaseUomConversionPreview({ preview }: { preview: Preview }) {
    return (
        <Panel title="Conversion preview">
            <div className="grid gap-3 text-sm md:grid-cols-3">
                <Summary label="Current base UOM" value={preview.old_base_uom?.code ?? 'Not set'} />
                <Summary label="New base UOM" value={preview.new_base_uom?.code ?? 'Not selected'} />
                <Summary label="Old to new factor" value={preview.conversion_factor ?? 'Missing'} />
            </div>

            {preview.blockers.length > 0 && (
                <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <p className="font-semibold">Cannot apply</p>
                    <ul className="mt-2 list-disc space-y-1 pl-5">{preview.blockers.map((blocker) => <li key={blocker.code}>{blocker.message}</li>)}</ul>
                </div>
            )}

            <div className="mt-4 overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th className="px-3 py-2">Area</th><th className="px-3 py-2">Reference</th><th className="px-3 py-2">Metric</th><th className="px-3 py-2 text-right">Before</th><th className="px-3 py-2 text-right">After</th></tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {preview.preview_rows.map((row, index) => (
                            <tr key={`${row.area}-${row.reference}-${row.metric}-${index}`}>
                                <td className="px-3 py-2 capitalize">{row.area.replaceAll('_', ' ')}</td>
                                <td className="px-3 py-2">{row.reference}</td>
                                <td className="px-3 py-2 capitalize">{row.metric.replaceAll('_', ' ')}</td>
                                <td className="px-3 py-2 text-right font-mono">{row.before}</td>
                                <td className="px-3 py-2 text-right font-mono font-semibold">{row.after}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {preview.preview_rows.length === 0 && <p className="py-5 text-center text-sm text-slate-500">No operational quantity rows require conversion.</p>}
            </div>
        </Panel>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return <div className="rounded-lg bg-slate-50 p-3"><span className="block text-xs uppercase tracking-wide text-slate-500">{label}</span><span className="mt-1 block font-semibold text-slate-900">{value}</span></div>;
}
