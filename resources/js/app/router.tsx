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
const VehicleListPage = lazy(() => import('@/modules/vehicle/VehicleListPage'));
const VehicleCreatePage = lazy(() => import('@/modules/vehicle/VehicleCreatePage'));
const VehicleEditPage = lazy(() => import('@/modules/vehicle/VehicleEditPage'));
const VehicleDetailPage = lazy(() => import('@/modules/vehicle/VehicleDetailPage'));
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
const SalesDocumentListPage = lazy(() => import('@/modules/sales/pages/SalesDocumentListPage'));
const SalesDocumentFormPage = lazy(() => import('@/modules/sales/pages/SalesDocumentFormPage'));
const SalesDocumentDetailPage = lazy(() => import('@/modules/sales/pages/SalesDocumentDetailPage'));
const SalesDeliveryListPage = lazy(() => import('@/modules/sales/pages/SalesDeliveryListPage'));
const SalesDeliveryCreatePage = lazy(() => import('@/modules/sales/pages/SalesDeliveryCreatePage'));
const SalesInvoiceCreatePage = lazy(() => import('@/modules/sales/pages/SalesInvoiceCreatePage'));
const SalesPaymentPreparePage = lazy(() => import('@/modules/sales/pages/SalesPaymentPreparePage'));
const SalesReturnListPage = lazy(() => import('@/modules/sales/pages/SalesReturnListPage'));
const SalesReturnCreatePage = lazy(() => import('@/modules/sales/pages/SalesReturnCreatePage'));
const SalesCreditNotePage = lazy(() => import('@/modules/sales/pages/SalesCreditNotePage'));
const InvoiceListPage = lazy(() => import('@/modules/invoice/pages/InvoiceListPage'));
const InvoiceDetailPage = lazy(() => import('@/modules/invoice/pages/InvoiceDetailPage'));
const PaymentListPage = lazy(() => import('@/modules/payment/pages/PaymentListPage'));
const PaymentDetailPage = lazy(() => import('@/modules/payment/pages/PaymentDetailPage'));
const ChequeTemplateListPage = lazy(() => import('@/modules/payment/cheque-print/ChequeTemplateListPage'));
const ChequeTemplateFormPage = lazy(() => import('@/modules/payment/cheque-print/ChequeTemplateFormPage'));
const ChequePrintPreviewPage = lazy(() => import('@/modules/payment/cheque-print/ChequePrintPreviewPage'));
const FinanceAccountsPage = lazy(() => import('@/modules/finance/pages/FinanceAccountsPage'));
const FinanceAccountCreatePage = lazy(() => import('@/modules/finance/pages/FinanceAccountCreatePage'));
const FinanceAccountEditPage = lazy(() => import('@/modules/finance/pages/FinanceAccountEditPage'));
const FinanceAccountDetailPage = lazy(() => import('@/modules/finance/pages/FinanceAccountDetailPage'));
const FinanceJournalsPage = lazy(() => import('@/modules/finance/pages/FinanceJournalsPage'));
const FinanceJournalCreatePage = lazy(() => import('@/modules/finance/pages/FinanceJournalCreatePage'));
const FinanceJournalEditPage = lazy(() => import('@/modules/finance/pages/FinanceJournalEditPage'));
const FinanceJournalDetailPage = lazy(() => import('@/modules/finance/pages/FinanceJournalDetailPage'));
const LedgerReportPage = lazy(() => import('@/modules/finance/pages/LedgerReportPage'));
const TrialBalanceReportPage = lazy(() => import('@/modules/finance/pages/TrialBalanceReportPage'));
const AccountBalanceReportPage = lazy(() => import('@/modules/finance/pages/AccountBalanceReportPage'));
const ReportListPage = lazy(() => import('@/modules/reporting/pages/ReportListPage'));
const ReportPage = lazy(() => import('@/modules/reporting/pages/ReportPage'));
const TechnicianWorkReportPage = lazy(() => import('@/modules/reporting/pages/TechnicianWorkReportPage'));
const EmployeeCommissionReportPage = lazy(() => import('@/modules/reporting/pages/EmployeeCommissionReportPage'));
const EmployeeListPage = lazy(() => import('@/modules/hr/EmployeeListPage'));
const EmployeeCreatePage = lazy(() => import('@/modules/hr/EmployeeCreatePage'));
const EmployeeEditPage = lazy(() => import('@/modules/hr/EmployeeEditPage'));
const EmployeeDetailPage = lazy(() => import('@/modules/hr/EmployeeDetailPage'));
const VehicleServiceJobListPage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServiceJobListPage'));
const VehicleServiceJobCreatePage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServiceJobCreatePage'));
const VehicleServiceJobEditPage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServiceJobEditPage'));
const VehicleServiceJobDetailPage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServiceJobDetailPage'));
const VehicleServiceInvoiceCreatePage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServiceInvoiceCreatePage'));
const VehicleServicePaymentPreparePage = lazy(() => import('@/modules/vehicle-service/pages/VehicleServicePaymentPreparePage'));
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
                    <Route path="/vehicles" element={<VehicleListPage />} />
                    <Route path="/vehicles/create" element={<VehicleCreatePage />} />
                    <Route path="/vehicles/:id/edit" element={<VehicleEditPage />} />
                    <Route path="/vehicles/:id" element={<VehicleDetailPage />} />
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
                    <Route path="/sales/quotations" element={<SalesDocumentListPage kind="quotation" />} />
                    <Route path="/sales/quotations/create" element={<SalesDocumentFormPage kind="quotation" />} />
                    <Route path="/sales/quotations/:id/edit" element={<SalesDocumentFormPage kind="quotation" />} />
                    <Route path="/sales/quotations/:id" element={<SalesDocumentDetailPage kind="quotation" />} />
                    <Route path="/sales/orders" element={<SalesDocumentListPage kind="order" />} />
                    <Route path="/sales/orders/create" element={<SalesDocumentFormPage kind="order" />} />
                    <Route path="/sales/orders/:id/edit" element={<SalesDocumentFormPage kind="order" />} />
                    <Route path="/sales/orders/:id" element={<SalesDocumentDetailPage kind="order" />} />
                    <Route path="/sales/deliveries" element={<SalesDeliveryListPage />} />
                    <Route path="/sales/deliveries/create" element={<SalesDeliveryCreatePage />} />
                    <Route path="/sales/invoices/create" element={<SalesInvoiceCreatePage />} />
                    <Route path="/sales/payments/prepare" element={<SalesPaymentPreparePage />} />
                    <Route path="/sales/returns" element={<SalesReturnListPage />} />
                    <Route path="/sales/returns/create" element={<SalesReturnCreatePage />} />
                    <Route path="/sales/credit-notes" element={<SalesCreditNotePage />} />
                    <Route path="/invoices" element={<InvoiceListPage />} />
                    <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                    <Route path="/payments" element={<PaymentListPage />} />
                    <Route path="/payments/cheque-templates" element={<ChequeTemplateListPage />} />
                    <Route path="/payments/cheque-templates/create" element={<ChequeTemplateFormPage />} />
                    <Route path="/payments/cheque-templates/:id/edit" element={<ChequeTemplateFormPage />} />
                    <Route path="/payments/:id/cheque-print" element={<ChequePrintPreviewPage />} />
                    <Route path="/payments/:id" element={<PaymentDetailPage />} />
                    <Route path="/finance/accounts" element={<FinanceAccountsPage />} />
                    <Route path="/finance/accounts/create" element={<FinanceAccountCreatePage />} />
                    <Route path="/finance/accounts/:id/edit" element={<FinanceAccountEditPage />} />
                    <Route path="/finance/accounts/:id" element={<FinanceAccountDetailPage />} />
                    <Route path="/finance/journals" element={<FinanceJournalsPage />} />
                    <Route path="/finance/journals/create" element={<FinanceJournalCreatePage />} />
                    <Route path="/finance/journals/:id/edit" element={<FinanceJournalEditPage />} />
                    <Route path="/finance/journals/:id" element={<FinanceJournalDetailPage />} />
                    <Route path="/finance/ledger" element={<LedgerReportPage />} />
                    <Route path="/finance/trial-balance" element={<TrialBalanceReportPage />} />
                    <Route path="/finance/account-balances" element={<AccountBalanceReportPage />} />
                    <Route path="/reports" element={<ReportListPage />} />
                    <Route path="/reports/vehicle-service/technician-work" element={<TechnicianWorkReportPage />} />
                    <Route path="/reports/vehicle-service/employee-commissions" element={<EmployeeCommissionReportPage />} />
                    <Route path="/reports/:key" element={<ReportPage />} />
                    <Route path="/hr/employees" element={<EmployeeListPage />} />
                    <Route path="/hr/employees/create" element={<EmployeeCreatePage />} />
                    <Route path="/hr/employees/:id/edit" element={<EmployeeEditPage />} />
                    <Route path="/hr/employees/:id" element={<EmployeeDetailPage />} />
                    <Route path="/vehicle-service/jobs" element={<VehicleServiceJobListPage />} />
                    <Route path="/vehicle-service/jobs/create" element={<VehicleServiceJobCreatePage />} />
                    <Route path="/vehicle-service/jobs/:id/edit" element={<VehicleServiceJobEditPage />} />
                    <Route path="/vehicle-service/jobs/:id/invoice" element={<VehicleServiceInvoiceCreatePage />} />
                    <Route path="/vehicle-service/jobs/:id/payment" element={<VehicleServicePaymentPreparePage />} />
                    <Route path="/vehicle-service/jobs/:id" element={<VehicleServiceJobDetailPage />} />
                    <Route path="*" element={<NotFoundPage />} />
                </Route>
            </Route>
        </Routes>
    );
}
