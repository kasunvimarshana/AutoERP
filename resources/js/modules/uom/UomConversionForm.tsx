import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import { createUomConversion, getUomConversion, updateUomConversion } from './uomApi';
import { UomLookupSelect } from './UomLookupSelect';
import type { UomSummary } from './uomTypes';

export default function UomConversionForm() {
    const { id } = useParams();
    const conversionId = id ? Number(id) : null;
    const navigate = useNavigate();
    const [fromUom, setFromUom] = useState<UomSummary | null>(null);
    const [toUom, setToUom] = useState<UomSummary | null>(null);
    const [conversionFactor, setConversionFactor] = useState('');
    const [description, setDescription] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(Boolean(conversionId));
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!conversionId) return;
        const controller = new AbortController();
        getUomConversion(conversionId, controller.signal)
            .then((conversion) => {
                if (controller.signal.aborted) return;
                setFromUom(conversion.from_uom);
                setToUom(conversion.to_uom);
                setConversionFactor(conversion.conversion_factor);
                setDescription(conversion.description ?? '');
                setIsActive(conversion.is_active);
            })
            .catch((nextError) => !controller.signal.aborted && setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to load conversion.', null)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [conversionId]);

    if (loading) return <LoadingState />;

    return (
        <>
            <ContentHeader title={conversionId ? 'Edit UOM Conversion' : 'Create UOM Conversion'} description="Define a generic conversion factor." />
            <Panel>
                <ErrorAlert error={error} />
                <form className="mt-4 space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    setError(null);
                    setSaving(true);
                    try {
                        if (!fromUom || !toUom) {
                            throw new ApiError('Select both UOMs.', 422);
                        }
                        if (Number(fromUom.id) === Number(toUom.id)) {
                            throw new ApiError('From UOM and To UOM cannot be the same.', 422);
                        }
                        const payload = {
                            from_uom_id: Number(fromUom.id),
                            to_uom_id: Number(toUom.id),
                            conversion_factor: conversionFactor,
                            is_active: isActive,
                            description: description || null,
                        };
                        const saved = conversionId
                            ? await updateUomConversion(conversionId, payload)
                            : await createUomConversion(payload);
                        navigate('/uom-conversions', { replace: true, state: { saved: saved.id } });
                    } catch (nextError) {
                        setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to save conversion.', null));
                    } finally {
                        setSaving(false);
                    }
                }}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <UomLookupSelect label="From UOM" value={fromUom} onChange={setFromUom} excludeId={toUom?.id ?? null} error={fieldError(error, 'from_uom_id')} />
                        <UomLookupSelect label="To UOM" value={toUom} onChange={setToUom} excludeId={fromUom?.id ?? null} error={fieldError(error, 'to_uom_id')} />
                    </div>
                    <Input label="Conversion factor" value={conversionFactor} onChange={(event) => setConversionFactor(event.target.value)} error={fieldError(error, 'conversion_factor')} required />
                    <label className="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={isActive} onChange={(event) => setIsActive(event.target.checked)} /> Active</label>
                    <Textarea label="Description" value={description} onChange={(event) => setDescription(event.target.value)} error={fieldError(error, 'description')} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate('/uom-conversions')}>Cancel</Button>
                        <Button type="submit" loading={saving}>Save conversion</Button>
                    </div>
                </form>
            </Panel>
        </>
    );
}
