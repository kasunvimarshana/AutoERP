import { PartyDetailPage } from '../../../shared/party/PartyDetailPage';
import { supplierApi } from '../services/supplierApi';

export function SupplierDetailPage() {
    return <PartyDetailPage api={supplierApi} basePath="/suppliers" noun="Supplier" />;
}
