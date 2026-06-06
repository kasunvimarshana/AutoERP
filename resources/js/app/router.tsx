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
const SupplierListPage = lazy(() => import('@/modules/supplier/SupplierListPage'));
const SupplierCreatePage = lazy(() => import('@/modules/supplier/SupplierCreatePage'));
const SupplierEditPage = lazy(() => import('@/modules/supplier/SupplierEditPage'));
const SupplierDetailPage = lazy(() => import('@/modules/supplier/SupplierDetailPage'));
const CustomerListPage = lazy(() => import('@/modules/customer/CustomerListPage'));
const CustomerCreatePage = lazy(() => import('@/modules/customer/CustomerCreatePage'));
const CustomerEditPage = lazy(() => import('@/modules/customer/CustomerEditPage'));
const CustomerDetailPage = lazy(() => import('@/modules/customer/CustomerDetailPage'));
const ItemListPage = lazy(() => import('@/modules/item/ItemListPage'));
const ItemCreatePage = lazy(() => import('@/modules/item/ItemCreatePage'));
const ItemEditPage = lazy(() => import('@/modules/item/ItemEditPage'));
const ItemDetailPage = lazy(() => import('@/modules/item/ItemDetailPage'));
const InventoryPage = lazy(() => import('@/modules/inventory/pages/InventoryPage'));
const PurchaseOrderListPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderListPage'));
const PurchaseOrderFormPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderFormPage'));
const PurchaseOrderDetailPage = lazy(() => import('@/modules/purchase/pages/PurchaseOrderDetailPage'));
const GoodsReceiptListPage = lazy(() => import('@/modules/purchase/pages/GoodsReceiptListPage'));
const GoodsReceiptCreatePage = lazy(() => import('@/modules/purchase/pages/GoodsReceiptCreatePage'));
const GoodsReceiptDetailPage = lazy(() => import('@/modules/purchase/pages/GoodsReceiptDetailPage'));
const PurchaseReturnListPage = lazy(() => import('@/modules/purchase/pages/PurchaseReturnListPage'));
const PurchaseReturnCreatePage = lazy(() => import('@/modules/purchase/pages/PurchaseReturnCreatePage'));
const PurchaseReturnDetailPage = lazy(() => import('@/modules/purchase/pages/PurchaseReturnDetailPage'));
const ManualSupplierReturnCreatePage = lazy(() => import('@/modules/purchase/pages/ManualSupplierReturnCreatePage'));
const PurchaseInvoiceCreatePage = lazy(() => import('@/modules/purchase/pages/PurchaseInvoiceCreatePage'));
const PurchasePaymentPreparePage = lazy(() => import('@/modules/purchase/pages/PurchasePaymentPreparePage'));
const PurchaseDebitNoteListPage = lazy(() => import('@/modules/purchase/pages/PurchaseDebitNoteListPage'));
const PurchaseDebitNoteDetailPage = lazy(() => import('@/modules/purchase/pages/PurchaseDebitNoteDetailPage'));
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
                    <Route path="/suppliers/create" element={<SupplierCreatePage />} />
                    <Route path="/suppliers/:id/edit" element={<SupplierEditPage />} />
                    <Route path="/suppliers/:id" element={<SupplierDetailPage />} />
                    <Route path="/customers" element={<CustomerListPage />} />
                    <Route path="/customers/create" element={<CustomerCreatePage />} />
                    <Route path="/customers/:id/edit" element={<CustomerEditPage />} />
                    <Route path="/customers/:id" element={<CustomerDetailPage />} />
                    <Route path="/items" element={<ItemListPage />} />
                    <Route path="/items/create" element={<ItemCreatePage />} />
                    <Route path="/items/:id/edit" element={<ItemEditPage />} />
                    <Route path="/items/:id" element={<ItemDetailPage />} />
                    <Route path="/inventory" element={<InventoryPage />} />
                    <Route path="/purchase/orders" element={<PurchaseOrderListPage />} />
                    <Route path="/purchase/orders/create" element={<PurchaseOrderFormPage />} />
                    <Route path="/purchase/orders/:id/edit" element={<PurchaseOrderFormPage />} />
                    <Route path="/purchase/orders/:id" element={<PurchaseOrderDetailPage />} />
                    <Route path="/purchase/goods-receipts" element={<GoodsReceiptListPage />} />
                    <Route path="/purchase/goods-receipts/create" element={<GoodsReceiptCreatePage />} />
                    <Route path="/purchase/goods-receipts/:id" element={<GoodsReceiptDetailPage />} />
                    <Route path="/purchase/returns" element={<PurchaseReturnListPage />} />
                    <Route path="/purchase/returns/create" element={<PurchaseReturnCreatePage />} />
                    <Route path="/purchase/returns/:id" element={<PurchaseReturnDetailPage />} />
                    <Route path="/purchase/manual-supplier-returns/create" element={<ManualSupplierReturnCreatePage />} />
                    <Route path="/purchase/invoices/create" element={<PurchaseInvoiceCreatePage />} />
                    <Route path="/purchase/payments/prepare" element={<PurchasePaymentPreparePage />} />
                    <Route path="/purchase/debit-notes" element={<PurchaseDebitNoteListPage />} />
                    <Route path="/purchase/debit-notes/:id" element={<PurchaseDebitNoteDetailPage />} />
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
