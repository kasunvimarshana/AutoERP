import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getItemCategory, updateItemCategory } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';
import type { ItemCategoryPayload } from './itemTypes';
import { ItemCategoryForm } from './components/ItemCategoryForm';

export default function ItemCategoryEditPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const canManage = hasItemPermission(auth.permissions, itemPermissions.manageCategories);
    const navigate = useNavigate();
    const category = useApi((signal) => getItemCategory(id, signal), [id], Number.isFinite(id));
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    if (category.loading) return <LoadingState />;
    if (!category.data) return <ErrorAlert error={category.error} />;

    return <>
        <ContentHeader title="Edit Category" description="Update category fields, hierarchy, and status." />
        {!canManage && <CapabilityNotice>You do not have permission to edit item categories.</CapabilityNotice>}
        {canManage && <ErrorAlert error={error} />}
        {canManage && <ItemCategoryForm initial={category.data} error={error} submitting={submitting} onCancel={() => navigate(`/item-categories/${id}`)} onSubmit={(payload) => void save(payload)} />}
    </>;

    async function save(payload: ItemCategoryPayload) {
        setSubmitting(true);
        setError(null);
        try {
            const saved = await updateItemCategory(id, payload);
            navigate(`/item-categories/${saved.id}`);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    }
}
