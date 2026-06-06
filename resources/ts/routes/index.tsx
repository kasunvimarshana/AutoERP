import { Navigate, RouterProvider as ReactRouterProvider, createBrowserRouter } from 'react-router-dom';
import { ProtectedRoute } from '../app/guards/ProtectedRoute';
import { AppLayout } from '../layouts/AppLayout';
import { AuthLayout } from '../layouts/AuthLayout';
import { LoginPage } from '../modules/auth/pages/LoginPage';
import { CustomerCreatePage } from '../modules/customer/pages/CustomerCreatePage';
import { CustomerDetailPage } from '../modules/customer/pages/CustomerDetailPage';
import { CustomerEditPage } from '../modules/customer/pages/CustomerEditPage';
import { CustomerListPage } from '../modules/customer/pages/CustomerListPage';
import { DashboardPage } from '../modules/dashboard/pages/DashboardPage';
import { JournalDetailPage } from '../modules/finance/pages/JournalDetailPage';
import { JournalListPage } from '../modules/finance/pages/JournalListPage';
import { InvoiceCreatePage } from '../modules/invoice/pages/InvoiceCreatePage';
import { InvoiceDetailPage } from '../modules/invoice/pages/InvoiceDetailPage';
import { InvoiceEditPage } from '../modules/invoice/pages/InvoiceEditPage';
import { InvoiceListPage } from '../modules/invoice/pages/InvoiceListPage';
import { ItemCreatePage } from '../modules/item/pages/ItemCreatePage';
import { ItemDetailPage } from '../modules/item/pages/ItemDetailPage';
import { ItemEditPage } from '../modules/item/pages/ItemEditPage';
import { ItemListPage } from '../modules/item/pages/ItemListPage';
import { PaymentCreatePage } from '../modules/payment/pages/PaymentCreatePage';
import { PaymentDetailPage } from '../modules/payment/pages/PaymentDetailPage';
import { PaymentListPage } from '../modules/payment/pages/PaymentListPage';
import { GrnDetailPage } from '../modules/purchase/pages/GrnDetailPage';
import { GrnEditorPage } from '../modules/purchase/pages/GrnEditorPage';
import { GrnListPage } from '../modules/purchase/pages/GrnListPage';
import { PurchaseOrderDetailPage } from '../modules/purchase/pages/PurchaseOrderDetailPage';
import { PurchaseOrderEditorPage } from '../modules/purchase/pages/PurchaseOrderEditorPage';
import { PurchaseOrderListPage } from '../modules/purchase/pages/PurchaseOrderListPage';
import { PurchaseReturnDetailPage } from '../modules/purchase/pages/PurchaseReturnDetailPage';
import { PurchaseReturnEditorPage } from '../modules/purchase/pages/PurchaseReturnEditorPage';
import { PurchaseReturnListPage } from '../modules/purchase/pages/PurchaseReturnListPage';
import { SupplierCreatePage } from '../modules/supplier/pages/SupplierCreatePage';
import { SupplierDetailPage } from '../modules/supplier/pages/SupplierDetailPage';
import { SupplierEditPage } from '../modules/supplier/pages/SupplierEditPage';
import { SupplierListPage } from '../modules/supplier/pages/SupplierListPage';
import { VehicleCreatePage } from '../modules/vehicle/pages/VehicleCreatePage';
import { VehicleDetailPage } from '../modules/vehicle/pages/VehicleDetailPage';
import { VehicleEditPage } from '../modules/vehicle/pages/VehicleEditPage';
import { VehicleListPage } from '../modules/vehicle/pages/VehicleListPage';
import { JobCardDetailPage } from '../modules/vehicleService/pages/JobCardDetailPage';
import { JobCardEditorPage } from '../modules/vehicleService/pages/JobCardEditorPage';
import { JobCardListPage } from '../modules/vehicleService/pages/JobCardListPage';
import { ServiceTypePage } from '../modules/vehicleService/pages/ServiceTypePage';
import { UomCreatePage } from '../modules/uom/pages/UomCreatePage';
import { UomDetailPage } from '../modules/uom/pages/UomDetailPage';
import { UomEditPage } from '../modules/uom/pages/UomEditPage';
import { UomListPage } from '../modules/uom/pages/UomListPage';
import { PlaceholderPage } from '../shared/components/erp/PlaceholderPage';

const router = createBrowserRouter([
    {
        element: <AuthLayout />,
        children: [
            { element: <LoginPage />, path: 'login' },
        ],
    },
    {
        element: <ProtectedRoute />,
        children: [
            {
                element: <AppLayout />,
                children: [
                    { element: <Navigate replace to="/dashboard" />, index: true },
                    { element: <DashboardPage />, path: 'dashboard' },
                    { element: <CustomerListPage />, path: 'customers' },
                    { element: <CustomerCreatePage />, path: 'customers/new' },
                    { element: <CustomerDetailPage />, path: 'customers/:id' },
                    { element: <CustomerEditPage />, path: 'customers/:id/edit' },
                    { element: <SupplierListPage />, path: 'suppliers' },
                    { element: <SupplierCreatePage />, path: 'suppliers/new' },
                    { element: <SupplierDetailPage />, path: 'suppliers/:id' },
                    { element: <SupplierEditPage />, path: 'suppliers/:id/edit' },
                    { element: <VehicleListPage />, path: 'vehicles' },
                    { element: <VehicleCreatePage />, path: 'vehicles/new' },
                    { element: <VehicleDetailPage />, path: 'vehicles/:id' },
                    { element: <VehicleEditPage />, path: 'vehicles/:id/edit' },
                    { element: <JobCardListPage />, path: 'vehicle-service/jobs' },
                    { element: <JobCardEditorPage mode="create" />, path: 'vehicle-service/jobs/new' },
                    { element: <JobCardDetailPage />, path: 'vehicle-service/jobs/:id' },
                    { element: <JobCardEditorPage mode="edit" />, path: 'vehicle-service/jobs/:id/edit' },
                    { element: <ServiceTypePage />, path: 'vehicle-service/types' },
                    { element: <UomListPage />, path: 'uoms' },
                    { element: <UomCreatePage />, path: 'uoms/new' },
                    { element: <UomDetailPage />, path: 'uoms/:id' },
                    { element: <UomEditPage />, path: 'uoms/:id/edit' },
                    { element: <ItemListPage />, path: 'items' },
                    { element: <ItemCreatePage />, path: 'items/new' },
                    { element: <ItemDetailPage />, path: 'items/:id' },
                    { element: <ItemEditPage />, path: 'items/:id/edit' },
                    { element: <PlaceholderPage area="Master data" title="Warehouses" />, path: 'warehouses' },
                    { element: <InvoiceListPage />, path: 'invoices' },
                    { element: <InvoiceCreatePage />, path: 'invoices/new' },
                    { element: <InvoiceDetailPage />, path: 'invoices/:id' },
                    { element: <InvoiceEditPage />, path: 'invoices/:id/edit' },
                    { element: <PaymentListPage />, path: 'payments' },
                    { element: <PaymentCreatePage />, path: 'payments/new' },
                    { element: <PaymentDetailPage />, path: 'payments/:id' },
                    { element: <PurchaseOrderListPage />, path: 'purchase/orders' },
                    { element: <PurchaseOrderEditorPage mode="create" />, path: 'purchase/orders/new' },
                    { element: <PurchaseOrderDetailPage />, path: 'purchase/orders/:id' },
                    { element: <PurchaseOrderEditorPage mode="edit" />, path: 'purchase/orders/:id/edit' },
                    { element: <GrnListPage />, path: 'purchase/grns' },
                    { element: <GrnEditorPage mode="create" />, path: 'purchase/grns/new' },
                    { element: <GrnDetailPage />, path: 'purchase/grns/:id' },
                    { element: <GrnEditorPage mode="edit" />, path: 'purchase/grns/:id/edit' },
                    { element: <PurchaseReturnListPage />, path: 'purchase/returns' },
                    { element: <PurchaseReturnEditorPage mode="create" />, path: 'purchase/returns/new' },
                    { element: <PurchaseReturnDetailPage />, path: 'purchase/returns/:id' },
                    { element: <PurchaseReturnEditorPage mode="edit" />, path: 'purchase/returns/:id/edit' },
                    { element: <JournalListPage />, path: 'finance/journals' },
                    { element: <JournalDetailPage />, path: 'finance/journals/:id' },
                    { element: <PlaceholderPage area="Administration" title="Users" />, path: 'administration/users' },
                    { element: <PlaceholderPage area="Administration" title="Settings" />, path: 'settings' },
                ],
            },
        ],
    },
    { element: <Navigate replace to="/" />, path: '*' },
]);

export function AppRouter() {
    return <ReactRouterProvider router={router} />;
}
