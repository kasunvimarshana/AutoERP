import { ContentHeader } from '@/shared/components/ContentHeader';
import { VehicleServiceJobForm } from '../components/VehicleServiceJobForm';

export default function VehicleServiceJobCreatePage() {
    return (
        <>
            <ContentHeader title="New vehicle service job" description="Save a draft with customer, vehicle, supervisor, odometer, fuel level, and complaint." />
            <VehicleServiceJobForm />
        </>
    );
}
