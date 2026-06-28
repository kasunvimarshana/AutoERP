import { PartyVehicleListPage } from '@/modules/party-vehicle/PartyVehicleListPage';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { SupplierLookupSelect } from './components/SupplierLookupSelect';
import { clearSupplierVehicleCurrent, endSupplierVehicle, listSupplierVehicles, setSupplierVehicleCurrent } from './supplierApi';
export default function SupplierVehicleListPage(){return <PartyVehicleListPage partyKey="supplier" title="Supplier Vehicles" createPath="/supplier-vehicles/create" editPath={(id)=>`/supplier-vehicles/${id}/edit`} permissions={{create:'vehicle.ownerships.manage',update:'vehicle.ownerships.manage',setCurrent:'vehicle.ownerships.manage',clearCurrent:'vehicle.ownerships.manage',delete:'vehicle.ownerships.manage'}} PartyLookup={SupplierLookupSelect} VehicleLookup={VehicleLookupSelect} list={listSupplierVehicles} setCurrent={setSupplierVehicleCurrent} clearCurrent={clearSupplierVehicleCurrent} end={endSupplierVehicle}/>}
