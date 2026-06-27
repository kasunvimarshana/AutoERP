import { PartyVehicleFormPage } from '@/modules/party-vehicle/PartyVehicleFormPage';
import { SupplierLookupSelect } from './components/SupplierLookupSelect';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { createVehicleOwnership, getVehicleOwnership, supersedeVehicleOwnership } from '@/modules/vehicle/vehicleOwnershipApi';

export default function SupplierVehicleFormPage() {
    return <PartyVehicleFormPage ownerType="supplier" title="Supplier Vehicle Ownership" listPath="/supplier-vehicles" PartyLookup={SupplierLookupSelect} VehicleLookup={VehicleLookupSelect} get={getVehicleOwnership} create={createVehicleOwnership} supersede={supersedeVehicleOwnership} />;
}
