import { PartyVehicleFormPage } from '@/modules/party-vehicle/PartyVehicleFormPage';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { CustomerLookupSelect } from './components/CustomerLookupSelect';
import { createCustomerVehicle, getCustomerVehicle, updateCustomerVehicle } from './customerApi';
export default function CustomerVehicleFormPage(){return <PartyVehicleFormPage partyKey="customer" title="Customer Vehicle" listPath="/customer-vehicles" PartyLookup={CustomerLookupSelect} VehicleLookup={VehicleLookupSelect} get={getCustomerVehicle} create={createCustomerVehicle} update={updateCustomerVehicle}/>}
