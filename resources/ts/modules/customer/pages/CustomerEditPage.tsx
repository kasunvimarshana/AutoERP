import { PartyEditorPage } from '../../../shared/party/PartyEditorPage';
import { customerApi } from '../services/customerApi';

export function CustomerEditPage() {
    return <PartyEditorPage api={customerApi} basePath="/customers" codeField="customer_code" mode="edit" noun="Customer" />;
}
