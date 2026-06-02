import { useEffect, useMemo, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { SearchFilterBar } from '../../../shared/components/data/SearchFilterBar';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { VehicleMasterSummaryTable } from '../components/VehiclePanels';
import { vehicleApi } from '../services/vehicleApi';
import type { VehicleMasterSummary } from '../types/vehicle.types';

function pageTitle(kind: VehicleMasterSummary['kind']) {
    return {
        brand: 'Vehicle Brands',
        category: 'Vehicle Categories',
        model: 'Vehicle Models',
        type: 'Vehicle Types',
    }[kind];
}

function pageSubtitle(kind: VehicleMasterSummary['kind']) {
    return {
        brand: 'Brand/make values currently come from persisted vehicle profiles.',
        category: 'Category values currently come from persisted vehicle profiles.',
        model: 'Model values currently come from persisted vehicle profiles.',
        type: 'Vehicle type is represented by the backend usage profile until a separate setup endpoint exists.',
    }[kind];
}

function VehicleMasterDataPage({ kind }: { kind: VehicleMasterSummary['kind'] }) {
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [rows, setRows] = useState<VehicleMasterSummary[]>([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        let mounted = true;

        setIsLoading(true);
        setError('');
        vehicleApi
            .listMasterSummaries(kind)
            .then((response) => {
                if (mounted) {
                    setRows(response.data);
                }
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setError(error instanceof Error ? error.message : 'Unable to load vehicle setup data.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [kind]);

    const filteredRows = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term === '' ? rows : rows.filter((row) => row.name.toLowerCase().includes(term));
    }, [rows, search]);

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Vehicle Setup"
                subtitle={`${pageSubtitle(kind)} CRUD setup endpoints were not exposed by the current Vehicle backend, so this page reads real backend vehicle data without inventing frontend-owned master data.`}
                title={pageTitle(kind)}
            />
            <Card className="p-4">
                <SearchFilterBar onSearch={setSearch} placeholder={`Search ${pageTitle(kind).toLowerCase()}...`} />
            </Card>
            {isLoading ? <EmptyState description="Loading setup summary from vehicle profiles..." title="Loading setup data" /> : null}
            {error ? <EmptyState description={error} title="Unable to load setup data" /> : null}
            {!isLoading && !error && filteredRows.length === 0 ? <EmptyState description="No values were returned by the backend vehicle profiles." title="No setup records" /> : null}
            {!isLoading && !error && filteredRows.length > 0 ? <VehicleMasterSummaryTable rows={filteredRows} /> : null}
        </div>
    );
}

export function VehicleTypeListPage() {
    return <VehicleMasterDataPage kind="type" />;
}

export function VehicleCategoryListPage() {
    return <VehicleMasterDataPage kind="category" />;
}

export function VehicleBrandListPage() {
    return <VehicleMasterDataPage kind="brand" />;
}

export function VehicleModelListPage() {
    return <VehicleMasterDataPage kind="model" />;
}
