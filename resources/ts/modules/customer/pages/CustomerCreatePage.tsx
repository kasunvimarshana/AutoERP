import { PartyEditorPage } from '../../../shared/party/PartyEditorPage';
import { customerApi } from '../services/customerApi';

export function CustomerCreatePage() {
    return <PartyEditorPage api={customerApi} basePath="/customers" codeField="customer_code" mode="create" noun="Customer" />;
}
