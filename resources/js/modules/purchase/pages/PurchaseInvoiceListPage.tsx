import { LinkButton } from '@/shared/components/Button';
import { InvoiceListWorkspace } from '@/modules/invoice/pages/InvoiceListPage';
import { useAuth } from '@/modules/auth/AuthProvider';
import { PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

export default function PurchaseInvoiceListPage() {
    const auth = useAuth();
    const canCreateInvoice = hasPurchasePermission(auth.permissions, purchasePermissions.supplierInvoicesCreate);

    return (
        <div className="space-y-5">
            <InvoiceListWorkspace
                viewKey="supplier"
                rowHref={(invoice) => `/invoices/${invoice.id}?from=purchase`}
                renderHeader={(view) => (
                    <PurchasePageHeader
                        title={view?.title ?? 'Supplier Invoices'}
                        description={view?.description ?? 'Purchase invoices payable to suppliers.'}
                        actions={canCreateInvoice ? <LinkButton to="/purchase/invoices/create">Create supplier invoice</LinkButton> : undefined}
                    />
                )}
            />
        </div>
    );
}
