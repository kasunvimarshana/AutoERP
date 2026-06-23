import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { useParams } from 'react-router-dom';
import { getItemBrand } from './itemApi';
import { hasItemPermission, itemPermissions } from './itemPermissions';

export default function ItemBrandDetailPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const canManage = hasItemPermission(auth, itemPermissions.manageBrands);
    const brand = useApi((signal) => getItemBrand(id, signal), [id], Number.isFinite(id));

    if (brand.loading) return <LoadingState />;
    if (!brand.data) return <ErrorAlert error={brand.error} />;

    return <>
        <ContentHeader
            title={brand.data.name}
            description="Read-only item brand details."
            actions={canManage ? <LinkButton to={`/item-brands/${id}/edit`} variant="secondary">Edit</LinkButton> : undefined}
        />
        <Panel>
            <DetailGrid items={[
                { label: 'Code', value: brand.data.code },
                { label: 'Name', value: brand.data.name },
                { label: 'Description', value: brand.data.description ?? '-' },
                { label: 'Status', value: <StatusBadge status={brand.data.is_active ? 'active' : 'inactive'} /> },
            ]} />
        </Panel>
    </>;
}
