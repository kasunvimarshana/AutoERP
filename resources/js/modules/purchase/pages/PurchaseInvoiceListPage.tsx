import { LinkButton } from '@/shared/components/Button';
import { InvoiceListWorkspace } from '@/modules/invoice/pages/InvoiceListPage';
import { PurchasePageHeader } from '../components/PurchaseDocumentShell';

export default function PurchaseInvoiceListPage() {
    return (
        <div className="space-y-5">
            <InvoiceListWorkspace
                viewKey="supplier"
                rowHref={(invoice) => `/invoices/${invoice.id}?from=purchase`}
                renderHeader={(view) => (
                    <PurchasePageHeader
                        title={view?.title ?? 'Supplier Invoices'}
                        description={view?.description ?? 'Purchase invoices payable to suppliers.'}
                        actions={<LinkButton to="/purchase/invoices/create">Create supplier invoice</LinkButton>}
                    />
                )}
            />
        </div>
    );
}
