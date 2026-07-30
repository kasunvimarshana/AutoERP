import { ContentHeader } from '@/shared/components/ContentHeader';
import { VehicleServiceJobForm } from '../components/VehicleServiceJobForm';

export default function VehicleServiceJobCreatePage() {
    return (
        <>
            <ContentHeader title="New vehicle service job" description="Save a draft by selecting the vehicle first, then capture the supervisor, odometer, next service mileage, and complaint." />
            <VehicleServiceJobForm />
        </>
    );
}
