import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createItemBrand } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemBrandPayload } from './itemTypes';
import { ItemBrandForm } from './components/ItemBrandForm';

export default function ItemBrandCreatePage() {
    const auth = useAuth();
    const canManage = hasItemPermission(auth, itemPermissions.manageBrands);
    const navigate = useNavigate();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return <>
        <ContentHeader title="Create Brand" description="Create an item brand within the current tenant scope." />
        {!canManage && <CapabilityNotice>You do not have permission to create item brands.</CapabilityNotice>}
        {canManage && <ErrorAlert error={error} />}
        {canManage && <ItemBrandForm error={error} submitting={submitting} onCancel={() => navigate('/item-brands')} onSubmit={(payload) => void save(payload)} />}
    </>;

    async function save(payload: ItemBrandPayload) {
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createItemBrand(payload);
            navigate(`/item-brands/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
