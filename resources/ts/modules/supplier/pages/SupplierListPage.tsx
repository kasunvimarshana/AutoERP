import { PartyListPage } from '../../../shared/party/PartyListPage';
import { supplierApi } from '../services/supplierApi';

export function SupplierListPage() {
    return <PartyListPage api={supplierApi} basePath="/suppliers" noun="Supplier" title="Suppliers" />;
}
