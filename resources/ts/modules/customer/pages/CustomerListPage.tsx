import { PartyListPage } from '../../../shared/party/PartyListPage';
import { customerApi } from '../services/customerApi';

export function CustomerListPage() {
    return <PartyListPage api={customerApi} basePath="/customers" noun="Customer" title="Customers" />;
}
