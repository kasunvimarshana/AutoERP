import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from '@/modules/auth/AuthProvider';
import { WarehouseLocationForm } from './components/WarehouseLocationForm';
import { createWarehouseLocation, getDefaultWarehouse } from './warehouseApi';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import type { WarehouseLocationPayload, WarehouseLocationSummary, WarehouseSummary } from './warehouseTypes';

const initialForm: WarehouseLocationPayload = {
    warehouse_id: null,
    parent_id: null,
    name: '',
    code: '',
    type: 'bin',
    capacity: '',
    is_pickable: true,
    is_receivable: true,
    is_active: true,
    is_default: false,
};

export default function WarehouseLocationCreatePage() {
    const auth = useAuth();
    const canCreate = hasWarehousePermission(auth.permissions, warehousePermissions.locationsCreate);
    const canManageDefault = hasWarehousePermission(auth.permissions, warehousePermissions.locationsManageDefaults);
    const navigate = useNavigate();
    const [form, setForm] = useState<WarehouseLocationPayload>(initialForm);
    const [warehouse, setWarehouse] = useState<WarehouseSummary | null>(null);
    const [parent, setParent] = useState<WarehouseLocationSummary | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const warehouseTouched = useRef(false);

    useEffect(() => {
        const controller = new AbortController();
        void getDefaultWarehouse(controller.signal)
            .then((defaultWarehouse) => {
                if (controller.signal.aborted || warehouseTouched.current || form.warehouse_id || !defaultWarehouse) return;
                setWarehouse(defaultWarehouse);
                setForm((current) => current.warehouse_id ? current : { ...current, warehouse_id: Number(defaultWarehouse.id) });
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [form.warehouse_id]);

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const payload = canManageDefault ? form : { ...form, is_default: false };
            const created = await createWarehouseLocation(payload);
            navigate(`/warehouse-locations/${created.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <ContentHeader title="Create Warehouse Location" description="Add a location under a selected warehouse." />
            {!canCreate && <CapabilityNotice>You do not have permission to create warehouse locations.</CapabilityNotice>}
            <ErrorAlert error={error} />
            {canCreate && (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <WarehouseLocationForm
                        value={form}
                        onChange={setForm}
                        warehouse={warehouse}
                        onWarehouseChange={(next) => {
                            warehouseTouched.current = true;
                            setWarehouse(next);
                        }}
                        parent={parent}
                        onParentChange={setParent}
                        error={error}
                        canManageDefault={canManageDefault}
                    />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate('/warehouse-locations')}>Cancel</Button>
                        <Button type="submit" loading={saving}>Create Location</Button>
                    </div>
                </form>
            )}
        </>
    );
}
