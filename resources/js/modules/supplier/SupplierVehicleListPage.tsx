import { PartyVehicleListPage } from '@/modules/party-vehicle/PartyVehicleListPage';
import { SupplierLookupSelect } from './components/SupplierLookupSelect';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { vehiclePermissions } from '@/modules/vehicle/vehiclePermissions';
import { clearVehicleOwnershipCurrent, endVehicleOwnership, listVehicleOwnerships, setVehicleOwnershipCurrent } from '@/modules/vehicle/vehicleOwnershipApi';

export default function SupplierVehicleListPage() {
    return <PartyVehicleListPage ownerType="supplier" title="Supplier Vehicle Ownership" createPath="/supplier-vehicles/create" supersedePath={(id) => `/supplier-vehicles/${id}/edit`} permissions={{ view: vehiclePermissions.ownershipsView, manage: vehiclePermissions.ownershipsManage }} PartyLookup={SupplierLookupSelect} VehicleLookup={VehicleLookupSelect} list={listVehicleOwnerships} setCurrent={setVehicleOwnershipCurrent} clearCurrent={clearVehicleOwnershipCurrent} end={endVehicleOwnership} />;
}
