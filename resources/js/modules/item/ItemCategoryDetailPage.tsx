import { useParams } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getItemCategory } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';

export default function ItemCategoryDetailPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const canManage = hasItemPermission(auth, itemPermissions.manageCategories);
    const category = useApi((signal) => getItemCategory(id, signal), [id], Number.isFinite(id));

    if (category.loading) return <LoadingState />;
    if (!category.data) return <ErrorAlert error={category.error} />;

    return <>
        <ContentHeader title={category.data.name} description="Read-only item category details." actions={canManage ? <LinkButton to={`/item-categories/${id}/edit`} variant="secondary">Edit</LinkButton> : undefined} />
        <Panel>
            <DetailGrid items={[
                { label: 'Code', value: category.data.code },
                { label: 'Parent Category', value: category.data.parent?.name ?? '-' },
                { label: 'Status', value: <StatusBadge status={category.data.is_active ? 'active' : 'inactive'} /> },
                { label: 'Sort Order', value: category.data.sort_order },
                { label: 'Description', value: category.data.description ?? '-' },
            ]} />
        </Panel>
    </>;
}
