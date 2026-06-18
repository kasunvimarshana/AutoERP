import { Navigate } from 'react-router-dom';

export default function PurchasePaymentWorkspacePage() {
    return <Navigate to="/payments?view=supplier" replace />;
}
