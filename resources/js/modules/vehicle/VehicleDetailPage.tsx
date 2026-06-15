import { lazy, Suspense } from 'react';
import { useParams } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { EntityDetailLayout } from '@/shared/components/EntityDetailLayout';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { getVehicle } from './vehicleApi';
import { VehicleSummaryCard } from './components/VehicleSummaryCard';

const VehicleAttributeTab = lazy(() => import('./components/VehicleAttributeTab').then((module) => ({ default: module.VehicleAttributeTab })));
const VehicleDocumentTab = lazy(() => import('./components/VehicleDocumentTab').then((module) => ({ default: module.VehicleDocumentTab })));
const VehicleOwnershipTab = lazy(() => import('./components/VehicleOwnershipTab').then((module) => ({ default: module.VehicleOwnershipTab })));
const VehicleStatusHistoryTab = lazy(() => import('./components/VehicleStatusHistoryTab').then((module) => ({ default: module.VehicleStatusHistoryTab })));

type DetailTab = 'summary' | 'ownership' | 'documents' | 'attributes' | 'history';

export default function VehicleDetailPage() {
    const { id } = useParams();
    const vehicleId = Number(id);
    const result = useApi((signal) => getVehicle(vehicleId, signal), [vehicleId], Number.isFinite(vehicleId));
    const tab = useOnDemandTab<DetailTab>('summary');

    if (result.loading) return <LoadingState label="Loading vehicle..." />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const vehicle = result.data;

    return (
        <div>
            <ContentHeader title={vehicle.vehicle_number} description={vehicle.registration_number ?? vehicle.code ?? undefined} actions={<LinkButton to={`/vehicles/${vehicle.id}/edit`}>Edit</LinkButton>} />
            <ErrorAlert error={result.error} />
            <EntityDetailLayout actions={
                <>
                    <LinkButton to="/vehicle-service/jobs/create" variant="secondary" className="w-full">Create service job</LinkButton>
                    <Button type="button" variant="secondary" className="w-full" onClick={() => tab.openTab('history')}>View status history</Button>
                    <Button type="button" variant="secondary" className="w-full" onClick={() => tab.openTab('documents')}>Review documents</Button>
                </>
            }>
                <Panel className="p-0">
                    <Tabs<DetailTab> active={tab.activeTab} onChange={tab.openTab} tabs={[
                        { id: 'summary', label: 'Summary' },
                        { id: 'ownership', label: 'Ownership' },
                        { id: 'documents', label: 'Documents' },
                        { id: 'attributes', label: 'Attributes' },
                        { id: 'history', label: 'Status History' },
                    ]} />
                    <div className="p-5">
                        {tab.activeTab === 'summary' && <VehicleSummaryCard vehicle={vehicle} />}
                        <Suspense fallback={<LoadingState label="Loading tab..." />}>
                            {tab.openedTabs.has('ownership') && <div hidden={tab.activeTab !== 'ownership'}><VehicleOwnershipTab vehicleId={vehicle.id} /></div>}
                            {tab.openedTabs.has('documents') && <div hidden={tab.activeTab !== 'documents'}><VehicleDocumentTab vehicleId={vehicle.id} /></div>}
                            {tab.openedTabs.has('attributes') && <div hidden={tab.activeTab !== 'attributes'}><VehicleAttributeTab vehicleId={vehicle.id} /></div>}
                            {tab.openedTabs.has('history') && <div hidden={tab.activeTab !== 'history'}><VehicleStatusHistoryTab vehicleId={vehicle.id} /></div>}
                        </Suspense>
                    </div>
                </Panel>
            </EntityDetailLayout>
        </div>
    );
}
