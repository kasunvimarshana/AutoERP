import { PartyVehicleFormPage } from '@/modules/party-vehicle/PartyVehicleFormPage';
import { CustomerLookupSelect } from './components/CustomerLookupSelect';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { createVehicleOwnership, getVehicleOwnership, supersedeVehicleOwnership } from '@/modules/vehicle/vehicleOwnershipApi';

export default function CustomerVehicleFormPage() {
    return <PartyVehicleFormPage ownerType="customer" title="Customer Vehicle Ownership" listPath="/customer-vehicles" PartyLookup={CustomerLookupSelect} VehicleLookup={VehicleLookupSelect} get={getVehicleOwnership} create={createVehicleOwnership} supersede={supersedeVehicleOwnership} />;
}
