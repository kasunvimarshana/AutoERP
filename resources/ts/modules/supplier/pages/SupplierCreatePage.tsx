import { PartyEditorPage } from '../../../shared/party/PartyEditorPage';
import { supplierApi } from '../services/supplierApi';

export function SupplierCreatePage() {
    return <PartyEditorPage api={supplierApi} basePath="/suppliers" codeField="supplier_code" mode="create" noun="Supplier" />;
}
