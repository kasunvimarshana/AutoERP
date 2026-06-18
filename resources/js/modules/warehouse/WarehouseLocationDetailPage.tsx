import { useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { humanize, readableRelation } from '@/shared/utils/object';
import { getWarehouseLocation } from './warehouseApi';

export default function WarehouseLocationDetailPage() {
    const locationId = Number(useParams().id);
    const location = useApi((signal) => getWarehouseLocation(locationId, signal), [locationId], Number.isFinite(locationId));

    if (location.loading) return <LoadingState />;
    if (!location.data) return <ErrorAlert error={location.error} />;

    const record = location.data;

    return (
        <>
            <ContentHeader title={`${record.code ? `${record.code} - ` : ''}${record.name}`} description="Read-only warehouse location detail." />
            <Panel>
                <h2 className="mb-4 text-base font-semibold text-slate-900">Summary</h2>
                <DetailGrid items={[
                    { label: 'Code', value: record.code || '-' },
                    { label: 'Name', value: record.name },
                    { label: 'Warehouse', value: readableRelation(record.warehouse) },
                    { label: 'Organization Unit', value: readableRelation(record.organization_unit) },
                    { label: 'Parent Location', value: readableRelation(record.parent) },
                    { label: 'Path', value: record.path ?? '-' },
                    { label: 'Depth', value: record.depth },
                    { label: 'Type', value: record.type_label ?? humanize(record.type) },
                    { label: 'Capacity', value: record.capacity ?? '-' },
                    { label: 'Default Location', value: record.is_default ? 'Yes' : 'No' },
                    { label: 'Pickable', value: record.is_pickable ? 'Yes' : 'No' },
                    { label: 'Receivable', value: record.is_receivable ? 'Yes' : 'No' },
                    { label: 'Status', value: record.is_active ? 'Active' : 'Inactive' },
                ]} />
            </Panel>
        </>
    );
}
