import { PartyDetailPage } from '../../../shared/party/PartyDetailPage';
import { customerApi } from '../services/customerApi';

export function CustomerDetailPage() {
    return <PartyDetailPage api={customerApi} basePath="/customers" noun="Customer" />;
}
