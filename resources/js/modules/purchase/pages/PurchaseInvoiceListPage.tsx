import { Navigate } from 'react-router-dom';

export default function PurchaseInvoiceListPage() {
    return <Navigate to="/invoices?view=supplier" replace />;
}
