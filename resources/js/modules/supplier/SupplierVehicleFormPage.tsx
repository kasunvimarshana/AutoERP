import { PartyVehicleFormPage } from '@/modules/party-vehicle/PartyVehicleFormPage';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import { SupplierLookupSelect } from './components/SupplierLookupSelect';
import { createSupplierVehicle, getSupplierVehicle, updateSupplierVehicle } from './supplierApi';
export default function SupplierVehicleFormPage(){return <PartyVehicleFormPage partyKey="supplier" title="Supplier Vehicle" listPath="/supplier-vehicles" PartyLookup={SupplierLookupSelect} VehicleLookup={VehicleLookupSelect} get={getSupplierVehicle} create={createSupplierVehicle} update={updateSupplierVehicle}/>}
