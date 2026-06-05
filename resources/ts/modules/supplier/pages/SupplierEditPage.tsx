import { PartyEditorPage } from '../../../shared/party/PartyEditorPage';
import { supplierApi } from '../services/supplierApi';

export function SupplierEditPage() {
    return <PartyEditorPage api={supplierApi} basePath="/suppliers" codeField="supplier_code" mode="edit" noun="Supplier" />;
}
