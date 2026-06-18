import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getWarehouse, updateWarehouse } from './warehouseApi';
import { WarehouseForm } from './components/WarehouseForm';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import type { Warehouse, WarehousePayload } from './warehouseTypes';

export default function WarehouseEditPage() {
    const warehouseId = Number(useParams().id);
    const auth = useAuth();
    const canUpdate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesUpdate);
    const canManageDefault = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesManageDefaults);
    const navigate = useNavigate();
    const [warehouse, setWarehouse] = useState<Warehouse | null>(null);
    const [form, setForm] = useState<WarehousePayload | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        getWarehouse(warehouseId, controller.signal)
            .then((record) => {
                if (controller.signal.aborted) return;
                setWarehouse(record);
                setForm(toForm(record));
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));

        return () => controller.abort();
    }, [warehouseId]);

    async function save() {
        if (!form) return;
        setSaving(true);
        setError(null);
        try {
            const payload = canManageDefault ? form : omitDefault(form);
            const updated = await updateWarehouse(warehouseId, payload);
            navigate(`/warehouses/${updated.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    if (loading) return <LoadingState />;
    if (!warehouse || !form) return <ErrorAlert error={error} />;

    return (
        <>
            <ContentHeader title={`Edit ${warehouse.name}`} description="Update warehouse setup and status." />
            {!canUpdate && <CapabilityNotice>You do not have permission to update warehouses.</CapabilityNotice>}
            <ErrorAlert error={error} />
            {canUpdate && (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <WarehouseForm value={form} onChange={setForm} error={error} canManageDefault={canManageDefault} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate(`/warehouses/${warehouseId}`)}>Cancel</Button>
                        <Button type="submit" loading={saving}>Save Warehouse</Button>
                    </div>
                </form>
            )}
        </>
    );
}

function toForm(warehouse: Warehouse): WarehousePayload {
    return {
        name: warehouse.name,
        code: warehouse.code ?? '',
        type: warehouse.type,
        is_active: warehouse.is_active,
        is_default: warehouse.is_default,
        row_version: warehouse.row_version ?? null,
    };
}

function omitDefault(form: WarehousePayload): Partial<WarehousePayload> {
    const payload: Partial<WarehousePayload> = { ...form };
    delete payload.is_default;
    return payload;
}
