import { lazy, Suspense } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { TabPanel, Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { useAuth } from '@/modules/auth/AuthProvider';
import { getVehicle } from './vehicleApi';
import { VehicleAttributesView } from './components/VehicleAttributesView';
import { VehicleDocumentsView } from './components/VehicleDocumentsView';
import { VehicleSummaryCard } from './components/VehicleSummaryCard';

const VehicleStatusHistoryTab = lazy(() => import('./components/VehicleStatusHistoryTab').then((module) => ({ default: module.VehicleStatusHistoryTab })));

type DetailTab = 'summary' | 'documents' | 'attributes' | 'history';
const tabs = [
    { id: 'summary' as const, label: 'Summary' },
    { id: 'documents' as const, label: 'Documents' },
    { id: 'attributes' as const, label: 'Attributes' },
    { id: 'history' as const, label: 'Status History' },
];

export default function VehicleDetailPage() {
    const navigate = useNavigate();
    const vehicleId = Number(useParams().id);
    const auth = useAuth();
    const result = useApi((signal) => getVehicle(vehicleId, signal), [vehicleId], Number.isFinite(vehicleId));
    const tab = useOnDemandTab<DetailTab>('summary');

    if (result.loading) return <LoadingState label="Loading vehicle..." />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const vehicle = result.data;
    const canEdit = auth.permissions.length === 0 || auth.permissions.some((permission) => permission.startsWith('vehicle.'));

    return (
        <div className="mx-auto max-w-6xl">
            <ContentHeader
                title={vehicle.vehicle_number}
                description={vehicle.registration_number ?? vehicle.code ?? undefined}
                actions={
                    <div className="flex gap-2">
                        <Button type="button" variant="secondary" onClick={() => navigate(-1)}>Back</Button>
                        {canEdit && <LinkButton to={`/vehicles/${vehicle.id}/edit`}>Edit</LinkButton>}
                    </div>
                }
            />
            <ErrorAlert error={result.error} />
            <Panel className="p-0">
                <Tabs<DetailTab> id="vehicle-detail-tabs" active={tab.activeTab} onChange={tab.openTab} tabs={tabs} />
                <div className="p-5">
                    <TabPanel tabsId="vehicle-detail-tabs" tabId="summary" active={tab.activeTab}>
                        <VehicleSummaryCard vehicle={vehicle} />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-detail-tabs" tabId="documents" active={tab.activeTab}>
                        <VehicleDocumentsView vehicleId={vehicle.id} />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-detail-tabs" tabId="attributes" active={tab.activeTab}>
                        <VehicleAttributesView vehicleId={vehicle.id} />
                    </TabPanel>
                    <TabPanel tabsId="vehicle-detail-tabs" tabId="history" active={tab.activeTab}>
                        <Suspense fallback={<LoadingState label="Loading status history..." />}>
                            {tab.openedTabs.has('history') && <VehicleStatusHistoryTab vehicleId={vehicle.id} />}
                        </Suspense>
                    </TabPanel>
                </div>
            </Panel>
        </div>
    );
}
