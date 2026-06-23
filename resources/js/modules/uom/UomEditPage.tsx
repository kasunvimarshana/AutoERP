import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { getUom, updateUom } from './uomApi';
import type { UomPayload } from './uomTypes';
import { UomFields } from './UomCreatePage';

export default function UomEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const uomId = Number(id);
    const [form, setForm] = useState<UomPayload | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const formGuard = useMutationFormGuard(saving);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        getUom(uomId, controller.signal)
            .then((uom) => {
                if (controller.signal.aborted) return;
                setForm({
                    code: uom.code,
                    name: uom.name,
                    symbol: uom.symbol ?? '',
                    type: uom.type,
                    category: uom.category,
                    decimal_precision: uom.decimal_precision,
                    allow_fractional_quantity: uom.allow_fractional_quantity,
                    is_base: uom.is_base,
                    is_active: uom.is_active,
                    description: uom.description ?? '',
                });
            })
            .catch((nextError) => !controller.signal.aborted && setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to load UOM.', null)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [uomId]);

    if (loading) return <LoadingState />;
    if (!form) return <ErrorAlert error={error ?? new ApiError('UOM was not found.', 404)} />;

    return (
        <>
            <ContentHeader title="Edit UOM" description="Update a generic unit definition." />
            <Panel>
                <ErrorAlert error={error} />
                <form className="mt-4 space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    setSaving(true);
                    setError(null);
                    try {
                        const updated = await updateUom(uomId, form);
                        formGuard.markSaved();
                        navigate(`/uoms/${updated.id}`);
                    } catch (nextError) {
                        setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to save UOM.', null));
                    } finally {
                        setSaving(false);
                    }
                }}>
                    <UomFields form={form} setForm={(next) => { formGuard.markDirty(); setForm(next); }} error={error} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate(`/uoms/${uomId}`)}>Cancel</Button>
                        <Button type="submit" loading={saving}>Save UOM</Button>
                    </div>
                </form>
            </Panel>
        </>
    );
}
