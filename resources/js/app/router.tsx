import { lazy } from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AppLayout } from './layout/AppLayout';
import { ProtectedRoute } from '@/modules/auth/ProtectedRoute';

const LoginPage = lazy(() => import('@/modules/auth/LoginPage'));
const DashboardPage = lazy(() => import('@/modules/dashboard/DashboardPage'));
const UomListPage = lazy(() => import('@/modules/uom/UomListPage'));
const UomCreatePage = lazy(() => import('@/modules/uom/UomCreatePage'));
const UomEditPage = lazy(() => import('@/modules/uom/UomEditPage'));
const UomDetailPage = lazy(() => import('@/modules/uom/UomDetailPage'));
const UomConversionListPage = lazy(() => import('@/modules/uom/UomConversionListPage'));
const UomConversionForm = lazy(() => import('@/modules/uom/UomConversionForm'));
const UomConvertTool = lazy(() => import('@/modules/uom/UomConvertTool'));
const SupplierListPage = lazy(() => import('@/modules/supplier/pages/SupplierListPage'));
const SupplierFormPage = lazy(() => import('@/modules/supplier/pages/SupplierFormPage'));
const SupplierDetailPage = lazy(() => import('@/modules/supplier/pages/SupplierDetailPage'));
const ItemListPage = lazy(() => import('@/modules/item/pages/ItemListPage'));
const ItemFormPage = lazy(() => import('@/modules/item/pages/ItemFormPage'));
const ItemDetailPage = lazy(() => import('@/modules/item/pages/ItemDetailPage'));
const InventoryPage = lazy(() => import('@/modules/inventory/pages/InventoryPage'));
const PurchaseOrderListPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderListPage'));
const PurchaseOrderFormPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderFormPage'));
const PurchaseOrderDetailPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderDetailPage'));
const InvoiceListPage = lazy(() => import('@/modules/invoice/pages/InvoiceListPage'));
const InvoiceDetailPage = lazy(() => import('@/modules/invoice/pages/InvoiceDetailPage'));
const PaymentListPage = lazy(() => import('@/modules/payment/pages/PaymentListPage'));
const PaymentDetailPage = lazy(() => import('@/modules/payment/pages/PaymentDetailPage'));
const FinanceAccountsPage = lazy(() => import('@/modules/finance/pages/FinanceAccountsPage'));
const FinanceAccountDetailPage = lazy(() => import('@/modules/finance/pages/FinanceAccountDetailPage'));
const FinanceJournalsPage = lazy(() => import('@/modules/finance/pages/FinanceJournalsPage'));
const NotFoundPage = lazy(() => import('@/modules/not-found/NotFoundPage'));

export function AppRouter() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route index element={<Navigate to="/dashboard" replace />} />
                    <Route path="/dashboard" element={<DashboardPage />} />
                    <Route path="/uoms" element={<UomListPage />} />
                    <Route path="/uoms/create" element={<UomCreatePage />} />
                    <Route path="/uoms/:id/edit" element={<UomEditPage />} />
                    <Route path="/uoms/:id" element={<UomDetailPage />} />
                    <Route path="/uom-conversions" element={<UomConversionListPage />} />
                    <Route path="/uom-conversions/create" element={<UomConversionForm />} />
                    <Route path="/uom-conversions/:id/edit" element={<UomConversionForm />} />
                    <Route path="/uom-convert" element={<UomConvertTool />} />
                    <Route path="/suppliers" element={<SupplierListPage />} />
                    <Route path="/suppliers/create" element={<SupplierFormPage />} />
                    <Route path="/suppliers/:id/edit" element={<SupplierFormPage />} />
                    <Route path="/suppliers/:id" element={<SupplierDetailPage />} />
                    <Route path="/items" element={<ItemListPage />} />
                    <Route path="/items/create" element={<ItemFormPage />} />
                    <Route path="/items/:id/edit" element={<ItemFormPage />} />
                    <Route path="/items/:id" element={<ItemDetailPage />} />
                    <Route path="/inventory" element={<InventoryPage />} />
                    <Route path="/purchase/orders" element={<PurchaseOrderListPage />} />
                    <Route path="/purchase/orders/create" element={<PurchaseOrderFormPage />} />
                    <Route path="/purchase/orders/:id/edit" element={<PurchaseOrderFormPage />} />
                    <Route path="/purchase/orders/:id" element={<PurchaseOrderDetailPage />} />
                    <Route path="/invoices" element={<InvoiceListPage />} />
                    <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                    <Route path="/payments" element={<PaymentListPage />} />
                    <Route path="/payments/:id" element={<PaymentDetailPage />} />
                    <Route path="/finance/accounts" element={<FinanceAccountsPage />} />
                    <Route path="/finance/accounts/:id" element={<FinanceAccountDetailPage />} />
                    <Route path="/finance/journals" element={<FinanceJournalsPage />} />
                    <Route path="*" element={<NotFoundPage />} />
                </Route>
            </Route>
        </Routes>
    );
}
