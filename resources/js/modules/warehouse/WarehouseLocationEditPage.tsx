import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useMutationFormGuard } from '@/shared/hooks/useMutationFormGuard';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from '@/modules/auth/AuthProvider';
import { WarehouseLocationForm } from './components/WarehouseLocationForm';
import { getWarehouseLocation, updateWarehouseLocation } from './warehouseApi';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import type { WarehouseLocation, WarehouseLocationPayload, WarehouseLocationSummary, WarehouseSummary } from './warehouseTypes';

export default function WarehouseLocationEditPage() {
    const locationId = Number(useParams().id);
    const auth = useAuth();
    const canUpdate = hasWarehousePermission(auth, warehousePermissions.locationsUpdate);
    const canManageDefault = hasWarehousePermission(auth, warehousePermissions.locationsManageDefaults);
    const navigate = useNavigate();
    const [location, setLocation] = useState<WarehouseLocation | null>(null);
    const [form, setForm] = useState<WarehouseLocationPayload | null>(null);
    const [warehouse, setWarehouse] = useState<WarehouseSummary | null>(null);
    const [parent, setParent] = useState<WarehouseLocationSummary | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const formGuard = useMutationFormGuard(saving);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        getWarehouseLocation(locationId, controller.signal)
            .then((record) => {
                if (controller.signal.aborted) return;
                setLocation(record);
                setForm(toForm(record));
                setWarehouse(record.warehouse ?? null);
                setParent((record.parent ?? null) as WarehouseLocationSummary | null);
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));

        return () => controller.abort();
    }, [locationId]);

    async function save() {
        if (!form) return;
        setSaving(true);
        setError(null);
        try {
            const payload = canManageDefault ? form : omitDefault(form);
            const updated = await updateWarehouseLocation(locationId, payload);
            formGuard.markSaved();
            navigate(`/warehouse-locations/${updated.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    if (loading) return <LoadingState />;
    if (!location || !form) return <ErrorAlert error={error} />;

    return (
        <>
            <ContentHeader title={`Edit ${location.name}`} description="Update location setup, hierarchy, and status." />
            {!canUpdate && <CapabilityNotice>You do not have permission to update warehouse locations.</CapabilityNotice>}
            <ErrorAlert error={error} />
            {canUpdate && (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <WarehouseLocationForm
                        value={form}
                        onChange={(next) => { formGuard.markDirty(); setForm(next); }}
                        warehouse={warehouse}
                        onWarehouseChange={(next) => { formGuard.markDirty(); setWarehouse(next); }}
                        parent={parent}
                        onParentChange={(next) => { formGuard.markDirty(); setParent(next); }}
                        error={error}
                        currentLocationId={locationId}
                        lockWarehouse
                        canManageDefault={canManageDefault}
                    />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate(`/warehouse-locations/${locationId}`)}>Cancel</Button>
                        <Button type="submit" loading={saving}>Save Location</Button>
                    </div>
                </form>
            )}
        </>
    );
}

function toForm(location: WarehouseLocation): WarehouseLocationPayload {
    return {
        warehouse_id: location.warehouse ? Number(location.warehouse.id) : null,
        parent_id: location.parent ? Number(location.parent.id) : null,
        name: location.name,
        code: location.code ?? '',
        type: location.type,
        capacity: location.capacity ?? '',
        is_pickable: location.is_pickable,
        is_receivable: location.is_receivable,
        is_active: location.is_active,
        is_default: location.is_default,
        row_version: location.row_version ?? null,
    };
}

function omitDefault(form: WarehouseLocationPayload): Partial<WarehouseLocationPayload> {
    const payload: Partial<WarehouseLocationPayload> = { ...form };
    delete payload.is_default;
    return payload;
}
