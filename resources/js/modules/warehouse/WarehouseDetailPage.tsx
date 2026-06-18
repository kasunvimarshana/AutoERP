import { Link, useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { humanize, readableRelation } from '@/shared/utils/object';
import { getWarehouse } from './warehouseApi';
import type { WarehouseLocationSummary } from './warehouseTypes';

export default function WarehouseDetailPage() {
    const warehouseId = Number(useParams().id);
    const warehouse = useApi((signal) => getWarehouse(warehouseId, signal), [warehouseId], Number.isFinite(warehouseId));

    if (warehouse.loading) return <LoadingState />;
    if (!warehouse.data) return <ErrorAlert error={warehouse.error} />;

    const record = warehouse.data;
    const locationColumns: DataColumn<WarehouseLocationSummary>[] = [
        { key: 'code', header: 'Code', render: (row) => row.code || '-' },
        { key: 'name', header: 'Name', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/warehouse-locations/${row.id}`}>{row.name}</Link> },
        { key: 'type', header: 'Type', render: (row) => row.type_label ?? humanize(row.type) },
        { key: 'path', header: 'Path', render: (row) => row.path ?? '-' },
        { key: 'default', header: 'Default', render: (row) => row.is_default ? <StatusBadge status="default" /> : '-' },
        { key: 'active', header: 'Active', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
    ];

    return (
        <>
            <ContentHeader title={`${record.code ? `${record.code} - ` : ''}${record.name}`} description="Read-only warehouse detail." />
            <div className="space-y-5">
                <Panel>
                    <h2 className="mb-4 text-base font-semibold text-slate-900">Summary</h2>
                    <DetailGrid items={[
                        { label: 'Code', value: record.code || '-' },
                        { label: 'Name', value: record.name },
                        { label: 'Organization Unit', value: readableRelation(record.organization_unit) },
                        { label: 'Type', value: record.type_label ?? humanize(record.type) },
                        { label: 'Default Warehouse', value: record.is_default ? 'Yes' : 'No' },
                        { label: 'Status', value: record.is_active ? 'Active' : 'Inactive' },
                        { label: 'Locations', value: record.locations_count ?? record.locations?.length ?? 0 },
                        { label: 'Default Location', value: readableRelation(record.default_location) },
                    ]} />
                </Panel>
                <Panel>
                    <h2 className="mb-4 text-base font-semibold text-slate-900">Locations</h2>
                    <DataTable rows={record.locations ?? []} columns={locationColumns} rowKey={(row) => row.id} emptyMessage="No locations found for this warehouse." />
                </Panel>
            </div>
        </>
    );
}
