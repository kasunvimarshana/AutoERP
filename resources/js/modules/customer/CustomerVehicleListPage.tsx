import { PartyVehicleListPage } from '@/modules/party-vehicle/PartyVehicleListPage';
import { CustomerLookupSelect } from './components/CustomerLookupSelect';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { vehiclePermissions } from '@/modules/vehicle/vehiclePermissions';
import { clearVehicleOwnershipCurrent, endVehicleOwnership, listVehicleOwnerships, setVehicleOwnershipCurrent } from '@/modules/vehicle/vehicleOwnershipApi';

export default function CustomerVehicleListPage() {
    return <PartyVehicleListPage ownerType="customer" title="Customer Vehicle Ownership" createPath="/customer-vehicles/create" supersedePath={(id) => `/customer-vehicles/${id}/edit`} permissions={{ view: vehiclePermissions.ownershipsView, manage: vehiclePermissions.ownershipsManage }} PartyLookup={CustomerLookupSelect} VehicleLookup={VehicleLookupSelect} list={listVehicleOwnerships} setCurrent={setVehicleOwnershipCurrent} clearCurrent={clearVehicleOwnershipCurrent} end={endVehicleOwnership} />;
}
