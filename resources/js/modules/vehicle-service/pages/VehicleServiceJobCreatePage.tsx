import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { VehicleServiceJobForm } from '../components/VehicleServiceJobForm';
import { getSupervisorCommissionDefault } from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';

export default function VehicleServiceJobCreatePage() {
    const auth = useAuth();
    const canViewCommissions = hasPermission(auth, vehicleServicePermissions.commissionsView);
    const supervisorDefault = useApi(
        (signal) => getSupervisorCommissionDefault(signal),
        [],
        canViewCommissions,
    );

    if (canViewCommissions && supervisorDefault.loading) {
        return <LoadingState label="Loading service job defaults..." />;
    }

    return (
        <>
            <ContentHeader title="New vehicle service job" description="Save a draft by selecting the vehicle first, then review the linked customer, supervisor, odometer, fuel level, and complaint." />
            <ErrorAlert error={supervisorDefault.error} />
            <VehicleServiceJobForm defaultSupervisorCommission={supervisorDefault.data ?? null} />
        </>
    );
}
