import { PartyVehicleListPage } from '@/modules/party-vehicle/PartyVehicleListPage';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { CustomerLookupSelect } from './components/CustomerLookupSelect';
import { clearCustomerVehicleCurrent, endCustomerVehicle, listCustomerVehicles, setCustomerVehicleCurrent } from './customerApi';
export default function CustomerVehicleListPage(){return <PartyVehicleListPage partyKey="customer" title="Customer Vehicles" createPath="/customer-vehicles/create" editPath={(id)=>`/customer-vehicles/${id}/edit`} permissions={{create:'vehicle.ownerships.manage',update:'vehicle.ownerships.manage',setCurrent:'vehicle.ownerships.manage',clearCurrent:'vehicle.ownerships.manage',delete:'vehicle.ownerships.manage'}} PartyLookup={CustomerLookupSelect} VehicleLookup={VehicleLookupSelect} list={listCustomerVehicles} setCurrent={setCustomerVehicleCurrent} clearCurrent={clearCustomerVehicleCurrent} end={endCustomerVehicle}/>}
