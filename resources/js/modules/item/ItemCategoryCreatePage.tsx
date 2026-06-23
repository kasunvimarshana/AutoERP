import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useAuth } from '@/modules/auth/AuthProvider';
import { createItemCategory } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemCategoryPayload } from './itemTypes';
import { ItemCategoryForm } from './components/ItemCategoryForm';

export default function ItemCategoryCreatePage() {
    const auth = useAuth();
    const canManage = hasItemPermission(auth, itemPermissions.manageCategories);
    const navigate = useNavigate();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    return <>
        <ContentHeader title="Create Category" description="Create an item category within the current tenant scope." />
        {!canManage && <CapabilityNotice>You do not have permission to create item categories.</CapabilityNotice>}
        {canManage && <ErrorAlert error={error} />}
        {canManage && <ItemCategoryForm error={error} submitting={submitting} onCancel={() => navigate('/item-categories')} onSubmit={(payload) => void save(payload)} />}
    </>;

    async function save(payload: ItemCategoryPayload) {
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createItemCategory(payload);
            navigate(`/item-categories/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
