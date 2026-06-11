import { useEffect, useState } from 'react';
import type { NamedResource } from '@/shared/types/common';
import type { ApiError } from '@/shared/api/apiError';
import { toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { applyBaseUomChange, getBaseUomUsageAudit, previewBaseUomChange } from '../itemApi';
import type { BaseUomConversionPreview as Preview, BaseUomRevision, BaseUomUsageAudit } from '../itemTypes';
import { ItemUomSelect } from './ItemUomSelect';
import { BaseUomUsageAuditPanel } from './BaseUomUsageAuditPanel';
import { BaseUomConversionPreview } from './BaseUomConversionPreview';

export default function BaseUomChangeWizard({ itemId, onApplied }: { itemId: number; onApplied?: () => void }) {
    const [step, setStep] = useState(1);
    const [audit, setAudit] = useState<BaseUomUsageAudit | null>(null);
    const [newUom, setNewUom] = useState<NamedResource | null>(null);
    const [factor, setFactor] = useState('');
    const [reason, setReason] = useState('');
    const [preview, setPreview] = useState<Preview | null>(null);
    const [result, setResult] = useState<BaseUomRevision | null>(null);
    const [loading, setLoading] = useState(true);
    const [working, setWorking] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        getBaseUomUsageAudit(itemId, controller.signal)
            .then((data) => !controller.signal.aborted && setAudit(data))
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [itemId]);

    if (loading) return <LoadingState />;
    if (!audit) return <ErrorAlert error={error} />;

    return (
        <div className="space-y-5">
            <Panel title="Base UOM change wizard">
                <div className="flex flex-wrap gap-2">
                    {['Usage audit', 'Select UOM', 'Preview', 'Confirm', 'Result'].map((label, index) => {
                        const number = index + 1;
                        return <span key={label} className={`rounded-full px-3 py-1 text-xs font-semibold ${step === number ? 'bg-sky-600 text-white' : step > number ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>{number}. {label}</span>;
                    })}
                </div>
            </Panel>

            <ErrorAlert error={error} />

            {step === 1 && <>
                <BaseUomUsageAuditPanel audit={audit} />
                <div className="flex justify-end"><Button onClick={() => setStep(2)} disabled={!audit.has_usage || audit.blockers.length > 0}>Continue</Button></div>
                {!audit.has_usage && <p className="text-sm text-emerald-700">This item is unused. Change its base UOM through the normal Edit Item form.</p>}
            </>}

            {step === 2 && (
                <Panel title="Select new base UOM">
                    <div className="grid gap-4 md:grid-cols-2">
                        <ItemUomSelect label="New base UOM" value={newUom} onChange={(value) => { setNewUom(value); setPreview(null); }} />
                        <DecimalInput label="Old to new factor" value={factor} onChange={(event) => { setFactor(event.target.value); setPreview(null); }} hint="Optional when an item or global UOM conversion already exists." />
                    </div>
                    <p className="mt-3 text-xs text-slate-500">Example: if 1 old unit equals 0.100000 new units, enter 0.100000.</p>
                    <div className="mt-4 flex justify-between"><Button variant="secondary" onClick={() => setStep(1)}>Back</Button><Button onClick={() => void loadPreview()} loading={working} disabled={!newUom}>Preview impact</Button></div>
                </Panel>
            )}

            {step === 3 && preview && <>
                <BaseUomConversionPreview preview={preview} />
                <div className="flex justify-between"><Button variant="secondary" onClick={() => setStep(2)}>Back</Button><Button onClick={() => setStep(4)} disabled={!preview.is_valid}>Continue</Button></div>
            </>}

            {step === 4 && preview && (
                <Panel title="Confirm conversion">
                    <p className="text-sm text-slate-700">Convert {preview.old_base_uom?.code} to {preview.new_base_uom?.code} using factor <span className="font-mono font-semibold">{preview.conversion_factor}</span>. Historical document and movement quantities will not be rewritten.</p>
                    <div className="mt-4"><Textarea label="Reason" value={reason} onChange={(event) => setReason(event.target.value)} placeholder="Why is this base UOM changing?" /></div>
                    <div className="mt-4 flex justify-between"><Button variant="secondary" onClick={() => setStep(3)}>Back</Button><Button onClick={() => void applyChange()} loading={working}>Apply conversion</Button></div>
                </Panel>
            )}

            {step === 5 && result && (
                <Panel title="Conversion applied">
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        <p className="font-semibold">{result.old_base_uom?.code} to {result.new_base_uom?.code} was applied successfully.</p>
                        <p className="mt-1">Factor: <span className="font-mono">{result.conversion_factor}</span></p>
                    </div>
                </Panel>
            )}
        </div>
    );

    async function loadPreview() {
        if (!newUom) return;
        setWorking(true);
        setError(null);
        try {
            const data = await previewBaseUomChange(itemId, {
                new_base_uom_id: Number(newUom.id),
                conversion_factor: factor || null,
            });
            setPreview(data);
            setStep(3);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setWorking(false);
        }
    }

    async function applyChange() {
        if (!newUom || !preview) return;
        setWorking(true);
        setError(null);
        try {
            const revision = await applyBaseUomChange(itemId, {
                new_base_uom_id: Number(newUom.id),
                conversion_factor: preview.conversion_factor,
                effective_at: preview.effective_at,
                reason: reason || null,
            });
            setResult(revision);
            setStep(5);
            onApplied?.();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setWorking(false);
        }
    }
}
