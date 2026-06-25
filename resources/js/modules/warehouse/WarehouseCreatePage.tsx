import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createWarehouse } from './warehouseApi';
import { WarehouseForm } from './components/WarehouseForm';
import { hasWarehousePermission, warehousePermissions } from './warehousePermissions';
import type { WarehousePayload } from './warehouseTypes';

const initialForm: WarehousePayload = {
    name: '',
    code: '',
    type: 'standard',
    is_active: true,
    is_default: false,
};

export default function WarehouseCreatePage() {
    const auth = useAuth();
    const canCreate = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesCreate);
    const canManageDefault = hasWarehousePermission(auth.permissions, warehousePermissions.warehousesManageDefaults);
    const navigate = useNavigate();
    const [form, setForm] = useState<WarehousePayload>(initialForm);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const created = await createWarehouse(canManageDefault ? form : { ...form, is_default: false });
            navigate(`/warehouses/${created.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <ContentHeader title="Create Warehouse" description="Add a warehouse for the current operating scope." />
            {!canCreate && <CapabilityNotice>You do not have permission to create warehouses.</CapabilityNotice>}
            <ErrorAlert error={error} />
            {canCreate && (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <WarehouseForm value={form} onChange={setForm} error={error} canManageDefault={canManageDefault} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate('/warehouses')}>Cancel</Button>
                        <Button type="submit" loading={saving}>Create Warehouse</Button>
                    </div>
                </form>
            )}
        </>
    );
}
