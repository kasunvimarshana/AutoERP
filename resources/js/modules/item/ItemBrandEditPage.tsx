import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getItemBrand, updateItemBrand } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemBrandPayload } from './itemTypes';
import { ItemBrandForm } from './components/ItemBrandForm';

export default function ItemBrandEditPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const canManage = hasItemPermission(auth.permissions, itemPermissions.manageBrands);
    const navigate = useNavigate();
    const brand = useApi((signal) => getItemBrand(id, signal), [id], Number.isFinite(id));
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    if (brand.loading) return <LoadingState />;
    if (!brand.data) return <ErrorAlert error={brand.error} />;

    return <>
        <ContentHeader title="Edit Brand" description="Update brand fields and status." />
        {!canManage && <CapabilityNotice>You do not have permission to edit item brands.</CapabilityNotice>}
        {canManage && <ErrorAlert error={error} />}
        {canManage && <ItemBrandForm initial={brand.data} error={error} submitting={submitting} onCancel={() => navigate(`/item-brands/${id}`)} onSubmit={(payload) => void save(payload)} />}
    </>;

    async function save(payload: ItemBrandPayload) {
        setSubmitting(true);
        setError(null);
        try {
            const saved = await updateItemBrand(id, payload);
            navigate(`/item-brands/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
