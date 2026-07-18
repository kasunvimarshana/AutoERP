import { lazy } from "react";
import { Route, RouterProvider, createBrowserRouter, createRoutesFromElements } from "react-router-dom";
import { AppLayout } from "./layout/AppLayout";
import { PlatformLayout } from "./layout/PlatformLayout";
import { PositiveIntegerRouteParamsBoundary } from "./routing/PositiveIntegerRouteParamsBoundary";
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from "./routePaths";
import { ProtectedRoute } from "@/modules/auth/ProtectedRoute";
import { TenantRoute } from "@/modules/auth/TenantRoute";
import { TenantEntitlementRoute } from "@/modules/auth/TenantEntitlementRoute";
import { AuthenticatedHomeRedirect } from "@/modules/auth/AuthenticatedHomeRedirect";
import { PlatformOperatorRoute } from "@/modules/auth/PlatformOperatorRoute";
import { PermissionRoute } from "@/modules/auth/PermissionRoute";
import { PLATFORM_PERMISSION } from "./access/platformPermissions";
import { RouteErrorPage } from "./errors/RouteErrorPage";
import { tenantPermissions } from "@/modules/tenant/tenantPermissions";

const LoginPage = lazy(() => import("@/modules/auth/LoginPage"));
const AuditLogListPage = lazy(() => import("@/modules/audit/AuditLogListPage"));
const AuditLogDetailPage = lazy(() => import("@/modules/audit/AuditLogDetailPage"));
const DashboardPage = lazy(() => import("@/modules/dashboard/DashboardPage"));
const AccessOverviewPage = lazy(() => import("@/modules/access/AccessOverviewPage"));
const UserListPage = lazy(() => import("@/modules/access/UserListPage"));
const CreateUserPage = lazy(() => import("@/modules/access/CreateUserPage"));
const UserEditPage = lazy(() => import("@/modules/access/UserEditPage"));
const UserDetailPage = lazy(() => import("@/modules/access/UserDetailPage"));
const RoleListPage = lazy(() => import("@/modules/access/RoleListPage"));
const RoleCreatePage = lazy(() => import("@/modules/access/RoleCreatePage"));
const RoleEditPage = lazy(() => import("@/modules/access/RoleEditPage"));
const RoleDetailPage = lazy(() => import("@/modules/access/RoleDetailPage"));
const PermissionCataloguePage = lazy(() => import("@/modules/access/PermissionCataloguePage"));
const SettingsPage = lazy(() => import("@/modules/settings/SettingsPage"));
const OrganizationUnitManagementPage = lazy(() => import("@/modules/organization-unit/OrganizationUnitManagementPage"));
const ReferenceDataPage = lazy(() => import("@/modules/reference-data/ReferenceDataPage"));
const TenantWorkspacePage = lazy(() => import("@/modules/tenant/TenantWorkspacePage"));
const PlatformTenantsPage = lazy(() => import("@/modules/tenant/PlatformTenantsPage"));
const TenantPlansPage = lazy(() => import("@/modules/tenant/TenantPlansPage"));
const PlatformOperatorsPage = lazy(() => import("@/modules/platform-administration/PlatformOperatorsPage"));
const PlatformSecurityPage = lazy(() => import("@/modules/platform-administration/PlatformSecurityPage"));
const PlatformAuditPage = lazy(() => import("@/modules/platform-administration/PlatformAuditPage"));
const PlatformAuditDetailPage = lazy(() => import("@/modules/platform-administration/PlatformAuditDetailPage"));
const PlatformHealthPage = lazy(() => import("@/modules/platform-administration/PlatformHealthPage"));
const UomListPage = lazy(() => import("@/modules/uom/UomListPage"));
const UomCreatePage = lazy(() => import("@/modules/uom/UomCreatePage"));
const UomEditPage = lazy(() => import("@/modules/uom/UomEditPage"));
const UomDetailPage = lazy(() => import("@/modules/uom/UomDetailPage"));
const UomConversionListPage = lazy(() => import("@/modules/uom/UomConversionListPage"));
const UomConversionForm = lazy(() => import("@/modules/uom/UomConversionForm"));
const UomConvertTool = lazy(() => import("@/modules/uom/UomConvertTool"));
const SupplierListPage = lazy(() => import("@/modules/supplier/SupplierListPage"));
const SupplierCreatePage = lazy(() => import("@/modules/supplier/SupplierCreatePage"));
const SupplierEditPage = lazy(() => import("@/modules/supplier/SupplierEditPage"));
const SupplierDetailPage = lazy(() => import("@/modules/supplier/SupplierDetailPage"));
const SupplierVehicleListPage = lazy(() => import("@/modules/supplier/SupplierVehicleListPage"));
const SupplierVehicleFormPage = lazy(() => import("@/modules/supplier/SupplierVehicleFormPage"));
const CustomerListPage = lazy(() => import("@/modules/customer/CustomerListPage"));
const CustomerCreatePage = lazy(() => import("@/modules/customer/CustomerCreatePage"));
const CustomerEditPage = lazy(() => import("@/modules/customer/CustomerEditPage"));
const CustomerDetailPage = lazy(() => import("@/modules/customer/CustomerDetailPage"));
const CustomerVehicleListPage = lazy(() => import("@/modules/customer/CustomerVehicleListPage"));
const CustomerVehicleFormPage = lazy(() => import("@/modules/customer/CustomerVehicleFormPage"));
const VehicleListPage = lazy(() => import("@/modules/vehicle/VehicleListPage"));
const VehicleCreatePage = lazy(() => import("@/modules/vehicle/VehicleCreatePage"));
const VehicleEditPage = lazy(() => import("@/modules/vehicle/VehicleEditPage"));
const VehicleDetailPage = lazy(() => import("@/modules/vehicle/VehicleDetailPage"));
const VehicleMasterDataPage = lazy(() => import("@/modules/vehicle/VehicleMasterDataPage"));
const ItemListPage = lazy(() => import("@/modules/item/ItemListPage"));
const ItemCreatePage = lazy(() => import("@/modules/item/ItemCreatePage"));
const ItemEditPage = lazy(() => import("@/modules/item/ItemEditPage"));
const ItemDetailPage = lazy(() => import("@/modules/item/ItemDetailPage"));
const ItemCategoryListPage = lazy(() => import("@/modules/item/ItemCategoryListPage"));
const ItemCategoryCreatePage = lazy(() => import("@/modules/item/ItemCategoryCreatePage"));
const ItemCategoryEditPage = lazy(() => import("@/modules/item/ItemCategoryEditPage"));
const ItemCategoryDetailPage = lazy(() => import("@/modules/item/ItemCategoryDetailPage"));
const ItemBrandListPage = lazy(() => import("@/modules/item/ItemBrandListPage"));
const ItemBrandCreatePage = lazy(() => import("@/modules/item/ItemBrandCreatePage"));
const ItemBrandEditPage = lazy(() => import("@/modules/item/ItemBrandEditPage"));
const ItemBrandDetailPage = lazy(() => import("@/modules/item/ItemBrandDetailPage"));
const InventoryPage = lazy(() => import("@/modules/inventory/pages/InventoryPage"));
const WarehouseListPage = lazy(() => import("@/modules/warehouse/WarehouseListPage"));
const WarehouseCreatePage = lazy(() => import("@/modules/warehouse/WarehouseCreatePage"));
const WarehouseEditPage = lazy(() => import("@/modules/warehouse/WarehouseEditPage"));
const WarehouseDetailPage = lazy(() => import("@/modules/warehouse/WarehouseDetailPage"));
const WarehouseLocationListPage = lazy(() => import("@/modules/warehouse/WarehouseLocationListPage"));
const WarehouseLocationCreatePage = lazy(() => import("@/modules/warehouse/WarehouseLocationCreatePage"));
const WarehouseLocationEditPage = lazy(() => import("@/modules/warehouse/WarehouseLocationEditPage"));
const WarehouseLocationDetailPage = lazy(() => import("@/modules/warehouse/WarehouseLocationDetailPage"));
const FastPurchasePage = lazy(() => import("@/modules/purchase/pages/FastPurchasePage"));
const PurchaseOrderListPage = lazy(() => import("@/modules/purchase/pages/PurchaseOrderListPage"));
const PurchaseOrderFormPage = lazy(() => import("@/modules/purchase/pages/PurchaseOrderFormPage"));
const PurchaseOrderDetailPage = lazy(() => import("@/modules/purchase/pages/PurchaseOrderDetailPage"));
const GoodsReceiptListPage = lazy(() => import("@/modules/purchase/pages/GoodsReceiptListPage"));
const GoodsReceiptCreatePage = lazy(() => import("@/modules/purchase/pages/GoodsReceiptCreatePage"));
const GoodsReceiptDetailPage = lazy(() => import("@/modules/purchase/pages/GoodsReceiptDetailPage"));
const PurchaseReturnListPage = lazy(() => import("@/modules/purchase/pages/PurchaseReturnListPage"));
const PurchaseReturnCreatePage = lazy(() => import("@/modules/purchase/pages/PurchaseReturnCreatePage"));
const PurchaseReturnDetailPage = lazy(() => import("@/modules/purchase/pages/PurchaseReturnDetailPage"));
const ManualSupplierReturnCreatePage = lazy(() => import("@/modules/purchase/pages/ManualSupplierReturnCreatePage"));
const PurchaseInvoiceListPage = lazy(() => import("@/modules/purchase/pages/PurchaseInvoiceListPage"));
const PurchaseInvoiceCreatePage = lazy(() => import("@/modules/purchase/pages/PurchaseInvoiceCreatePage"));
const PurchasePaymentWorkspacePage = lazy(() => import("@/modules/purchase/pages/PurchasePaymentWorkspacePage"));
const PurchasePaymentCreatePage = lazy(() => import("@/modules/purchase/pages/PurchasePaymentCreatePage"));
const PurchasePaymentPreparePage = lazy(() => import("@/modules/purchase/pages/PurchasePaymentPreparePage"));
const PurchaseDebitNoteListPage = lazy(() => import("@/modules/purchase/pages/PurchaseDebitNoteListPage"));
const PurchaseDebitNoteCreatePage = lazy(() => import("@/modules/purchase/pages/PurchaseDebitNoteCreatePage"));
const PurchaseDebitNoteDetailPage = lazy(() => import("@/modules/purchase/pages/PurchaseDebitNoteDetailPage"));
const InvoiceListPage = lazy(() => import("@/modules/invoice/pages/InvoiceListPage"));
const InvoiceDetailPage = lazy(() => import("@/modules/invoice/pages/InvoiceDetailPage"));
const PaymentListPage = lazy(() => import("@/modules/payment/pages/PaymentListPage"));
const PaymentEntryPage = lazy(() => import("@/modules/payment/pages/PaymentEntryPage"));
const PaymentDetailPage = lazy(() => import("@/modules/payment/pages/PaymentDetailPage"));
const PaymentMethodListPage = lazy(() => import("@/modules/payment/pages/PaymentMethodListPage"));
const PaymentMethodFormPage = lazy(() => import("@/modules/payment/pages/PaymentMethodFormPage"));
const ChequeTemplateListPage = lazy(() => import("@/modules/payment/cheque-print/ChequeTemplateListPage"));
const ChequeTemplateFormPage = lazy(() => import("@/modules/payment/cheque-print/ChequeTemplateFormPage"));
const ChequePrintPreviewPage = lazy(() => import("@/modules/payment/cheque-print/ChequePrintPreviewPage"));
const VoucherListPage = lazy(() => import("@/modules/voucher/pages/VoucherListPage"));
const VoucherDetailPage = lazy(() => import("@/modules/voucher/pages/VoucherDetailPage"));
const FinanceAccountsPage = lazy(() => import("@/modules/finance/pages/FinanceAccountsPage"));
const FinanceAccountCreatePage = lazy(() => import("@/modules/finance/pages/FinanceAccountCreatePage"));
const FinanceAccountEditPage = lazy(() => import("@/modules/finance/pages/FinanceAccountEditPage"));
const FinanceAccountDetailPage = lazy(() => import("@/modules/finance/pages/FinanceAccountDetailPage"));
const FinanceJournalsPage = lazy(() => import("@/modules/finance/pages/FinanceJournalsPage"));
const FinanceJournalCreatePage = lazy(() => import("@/modules/finance/pages/FinanceJournalCreatePage"));
const FinanceJournalEditPage = lazy(() => import("@/modules/finance/pages/FinanceJournalEditPage"));
const FinanceJournalDetailPage = lazy(() => import("@/modules/finance/pages/FinanceJournalDetailPage"));
const LedgerReportPage = lazy(() => import("@/modules/finance/pages/LedgerReportPage"));
const TrialBalanceReportPage = lazy(() => import("@/modules/finance/pages/TrialBalanceReportPage"));
const AccountBalanceReportPage = lazy(() => import("@/modules/finance/pages/AccountBalanceReportPage"));
const PostingProfilePage = lazy(() => import("@/modules/finance/pages/PostingProfilePage"));
const FinanceReportsPage = lazy(() => import("@/modules/finance/pages/FinanceReportsPage"));
const BankReconciliationPage = lazy(() => import("@/modules/finance/pages/BankReconciliationPage"));
const FinanceReversalsPage = lazy(() => import("@/modules/finance/pages/FinanceReversalsPage"));
const BudgetPage = lazy(() => import("@/modules/finance/pages/BudgetPage"));
const TaxListPage = lazy(() => import("@/modules/tax/pages/TaxListPage"));
const TaxCreatePage = lazy(() => import("@/modules/tax/pages/TaxCreatePage"));
const TaxEditPage = lazy(() => import("@/modules/tax/pages/TaxEditPage"));
const TaxGroupPage = lazy(() => import("@/modules/tax/pages/TaxGroupPage"));
const CustomerTaxProfilePage = lazy(() => import("@/modules/tax/pages/CustomerTaxProfilePage"));
const SupplierTaxProfilePage = lazy(() => import("@/modules/tax/pages/SupplierTaxProfilePage"));
const TaxPostingProfilePage = lazy(() => import("@/modules/tax/pages/TaxPostingProfilePage"));
const TaxReportPages = lazy(() => import("@/modules/tax/pages/TaxReportPages"));
const ReportListPage = lazy(() => import("@/modules/reporting/pages/ReportListPage"));
const ReportPage = lazy(() => import("@/modules/reporting/pages/ReportPage"));
const TechnicianWorkReportPage = lazy(() => import("@/modules/reporting/pages/TechnicianWorkReportPage"));
const EmployeeCommissionReportPage = lazy(() => import("@/modules/reporting/pages/EmployeeCommissionReportPage"));
const OperationalReportPage = lazy(() => import("@/modules/reporting/pages/OperationalReportPage"));
const EmployeeListPage = lazy(() => import("@/modules/hr/EmployeeListPage"));
const EmployeeCreatePage = lazy(() => import("@/modules/hr/EmployeeCreatePage"));
const EmployeeEditPage = lazy(() => import("@/modules/hr/EmployeeEditPage"));
const EmployeeDetailPage = lazy(() => import("@/modules/hr/EmployeeDetailPage"));
const VehicleServiceJobListPage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServiceJobListPage"));
const VehicleServiceJobCreatePage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServiceJobCreatePage"));
const VehicleServiceJobEditPage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServiceJobEditPage"));
const VehicleServiceJobDetailPage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServiceJobDetailPage"));
const VehicleServiceInvoiceCreatePage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServiceInvoiceCreatePage"));
const VehicleServicePaymentPreparePage = lazy(() => import("@/modules/vehicle-service/pages/VehicleServicePaymentPreparePage"));
const NotFoundPage = lazy(() => import("@/modules/not-found/NotFoundPage"));

const appRouter = createBrowserRouter(
    createRoutesFromElements(
        <Route errorElement={<RouteErrorPage />}>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<ProtectedRoute />}>
                <Route index element={<AuthenticatedHomeRedirect />} />
                <Route element={<PlatformOperatorRoute />}>
                    <Route element={<PlatformLayout />}>
                        <Route path={PLATFORM_HOME_PATH} element={<PermissionRoute permission={PLATFORM_PERMISSION.tenantsView}><PlatformTenantsPage /></PermissionRoute>} />
                        <Route path="/administration/tenant-plans" element={<PermissionRoute permission={PLATFORM_PERMISSION.plansView}><TenantPlansPage /></PermissionRoute>} />
                        <Route path="/administration/platform-configuration" element={<PermissionRoute permission={PLATFORM_PERMISSION.configurationView}><SettingsPage mode="platform" /></PermissionRoute>} />
                        <Route path="/administration/platform-operators" element={<PermissionRoute permission={PLATFORM_PERMISSION.operatorsView}><PlatformOperatorsPage /></PermissionRoute>} />
                        <Route path="/administration/platform-security" element={<PermissionRoute permission={PLATFORM_PERMISSION.sessionsView}><PlatformSecurityPage /></PermissionRoute>} />
                        <Route path="/administration/platform-audit" element={<PermissionRoute permission={PLATFORM_PERMISSION.auditView}><PlatformAuditPage /></PermissionRoute>} />
                        <Route path="/administration/platform-audit/:id" element={<PermissionRoute permission={PLATFORM_PERMISSION.auditView}><PlatformAuditDetailPage /></PermissionRoute>} />
                        <Route path="/administration/platform-health" element={<PermissionRoute permission={PLATFORM_PERMISSION.healthView}><PlatformHealthPage /></PermissionRoute>} />
                    </Route>
                </Route>
                <Route element={<TenantRoute />}>
                    <Route element={<AppLayout />}>
                        <Route element={<TenantEntitlementRoute />}>
                            <Route element={<PositiveIntegerRouteParamsBoundary />}>
                                <Route path={DASHBOARD_PATH} element={<DashboardPage />} />
                                <Route path="/access/users" element={<UserListPage />} />
                                <Route path="/access/users/create" element={<CreateUserPage />} />
                                <Route path="/access/users/:id/edit" element={<UserEditPage />} />
                                <Route path="/access/users/:id" element={<UserDetailPage />} />
                                <Route path="/access/roles" element={<RoleListPage />} />
                                <Route path="/access/roles/create" element={<RoleCreatePage />} />
                                <Route path="/access/roles/:id/edit" element={<RoleEditPage />} />
                                <Route path="/access/roles/:id" element={<RoleDetailPage />} />
                                <Route path="/access/permissions" element={<PermissionCataloguePage />} />
                                <Route path="/administration/access" element={<AccessOverviewPage />} />
                                <Route path="/administration/organization-units" element={<PermissionRoute permission="organization-units.view"><OrganizationUnitManagementPage /></PermissionRoute>} />
                                <Route path="/administration/audit-logs" element={<AuditLogListPage />} />
                                <Route path="/administration/audit-logs/:id" element={<AuditLogDetailPage />} />
                                <Route path="/settings" element={<SettingsPage />} />
                                <Route path="/reference-data" element={<ReferenceDataPage />} />
                                <Route
                                    path="/administration/tenant"
                                    element={(
                                        <PermissionRoute anyOf={[
                                            tenantPermissions.profileView,
                                            tenantPermissions.domainsView,
                                            tenantPermissions.documentsView,
                                        ]}>
                                            <TenantWorkspacePage />
                                        </PermissionRoute>
                                    )}
                                />
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
                                <Route path="/supplier-vehicles" element={<SupplierVehicleListPage />} />
                                <Route path="/supplier-vehicles/create" element={<SupplierVehicleFormPage />} />
                                <Route path="/supplier-vehicles/:id/edit" element={<SupplierVehicleFormPage />} />
                                <Route path="/customers" element={<CustomerListPage />} />
                                <Route path="/customers/create" element={<CustomerCreatePage />} />
                                <Route path="/customers/:id/edit" element={<CustomerEditPage />} />
                                <Route path="/customers/:id" element={<CustomerDetailPage />} />
                                <Route path="/customer-vehicles" element={<CustomerVehicleListPage />} />
                                <Route path="/customer-vehicles/create" element={<CustomerVehicleFormPage />} />
                                <Route path="/customer-vehicles/:id/edit" element={<CustomerVehicleFormPage />} />
                                <Route path="/vehicles/makes" element={<VehicleMasterDataPage kind="makes" />} />
                                <Route path="/vehicles/types" element={<VehicleMasterDataPage kind="types" />} />
                                <Route path="/vehicles/categories" element={<VehicleMasterDataPage kind="categories" />} />
                                <Route path="/vehicles/models" element={<VehicleMasterDataPage kind="models" />} />
                                <Route path="/vehicles" element={<VehicleListPage />} />
                                <Route path="/vehicles/create" element={<VehicleCreatePage />} />
                                <Route path="/vehicles/:id/edit" element={<VehicleEditPage />} />
                                <Route path="/vehicles/:id" element={<VehicleDetailPage />} />
                                <Route path="/items" element={<ItemListPage />} />
                                <Route path="/items/create" element={<ItemCreatePage />} />
                                <Route path="/items/:id/edit" element={<ItemEditPage />} />
                                <Route path="/items/:id" element={<ItemDetailPage />} />
                                <Route path="/item-categories" element={<ItemCategoryListPage />} />
                                <Route path="/item-categories/create" element={<ItemCategoryCreatePage />} />
                                <Route path="/item-categories/:id/edit" element={<ItemCategoryEditPage />} />
                                <Route path="/item-categories/:id" element={<ItemCategoryDetailPage />} />
                                <Route path="/item-brands" element={<ItemBrandListPage />} />
                                <Route path="/item-brands/create" element={<ItemBrandCreatePage />} />
                                <Route path="/item-brands/:id/edit" element={<ItemBrandEditPage />} />
                                <Route path="/item-brands/:id" element={<ItemBrandDetailPage />} />
                                <Route path="/inventory" element={<InventoryPage />} />
                                <Route path="/warehouses" element={<WarehouseListPage />} />
                                <Route path="/warehouses/create" element={<WarehouseCreatePage />} />
                                <Route path="/warehouses/:id/edit" element={<WarehouseEditPage />} />
                                <Route path="/warehouses/:id" element={<WarehouseDetailPage />} />
                                <Route path="/warehouse-locations" element={<WarehouseLocationListPage />} />
                                <Route path="/warehouse-locations/create" element={<WarehouseLocationCreatePage />} />
                                <Route path="/warehouse-locations/:id/edit" element={<WarehouseLocationEditPage />} />
                                <Route path="/warehouse-locations/:id" element={<WarehouseLocationDetailPage />} />
                                <Route path="/purchase/fast-purchase" element={<FastPurchasePage />} />
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
                                <Route path="/purchase/invoices" element={<PurchaseInvoiceListPage />} />
                                <Route path="/purchase/invoices/create" element={<PurchaseInvoiceCreatePage />} />
                                <Route path="/purchase/payments" element={<PurchasePaymentWorkspacePage />} />
                                <Route path="/purchase/payments/create" element={<PurchasePaymentCreatePage />} />
                                <Route path="/purchase/payments/prepare" element={<PurchasePaymentPreparePage />} />
                                <Route path="/purchase/debit-notes" element={<PurchaseDebitNoteListPage />} />
                                <Route path="/purchase/debit-notes/create" element={<PurchaseDebitNoteCreatePage />} />
                                <Route path="/purchase/debit-notes/:id" element={<PurchaseDebitNoteDetailPage />} />
                                <Route path="/invoices" element={<InvoiceListPage />} />
                                <Route path="/invoices/:id" element={<InvoiceDetailPage />} />
                                <Route path="/payments" element={<PaymentListPage />} />
                                <Route path="/payments/create" element={<PaymentEntryPage />} />
                                <Route path="/payments/methods" element={<PaymentMethodListPage />} />
                                <Route path="/payments/methods/create" element={<PaymentMethodFormPage />} />
                                <Route path="/payments/methods/:id/edit" element={<PaymentMethodFormPage />} />
                                <Route path="/payments/cheque-templates" element={<ChequeTemplateListPage />} />
                                <Route path="/payments/cheque-templates/create" element={<ChequeTemplateFormPage />} />
                                <Route path="/payments/cheque-templates/:id/edit" element={<ChequeTemplateFormPage />} />
                                <Route path="/payments/:paymentId/lines/:lineId/cheque-print" element={<ChequePrintPreviewPage />} />
                                <Route path="/payments/:id" element={<PaymentDetailPage />} />
                                <Route path="/vouchers" element={<VoucherListPage />} />
                                <Route path="/vouchers/:voucherType/:sourceId" element={<VoucherDetailPage />} />
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
                                <Route path="/finance/posting-profiles" element={<PostingProfilePage />} />
                                <Route path="/finance/reversals" element={<FinanceReversalsPage />} />
                                <Route path="/finance/reports" element={<FinanceReportsPage />} />
                                <Route path="/finance/bank-reconciliations" element={<BankReconciliationPage />} />
                                <Route path="/finance/budgets" element={<BudgetPage />} />
                                <Route path="/tax/taxes" element={<TaxListPage />} />
                                <Route path="/tax/taxes/create" element={<TaxCreatePage />} />
                                <Route path="/tax/taxes/:id/edit" element={<TaxEditPage />} />
                                <Route path="/tax/groups" element={<TaxGroupPage />} />
                                <Route path="/tax/customer-profiles" element={<CustomerTaxProfilePage />} />
                                <Route path="/tax/supplier-profiles" element={<SupplierTaxProfilePage />} />
                                <Route path="/tax/posting-profiles" element={<TaxPostingProfilePage />} />
                                <Route path="/tax/reports" element={<TaxReportPages />} />
                                <Route path="/reports" element={<ReportListPage />} />
                                <Route path="/reports/purchase/detailed" element={<OperationalReportPage reportKey="purchase/detailed" kind="purchase" />} />
                                <Route path="/reports/vehicle-service/detailed" element={<OperationalReportPage reportKey="vehicle-service/detailed" kind="vehicle-service" />} />
                                <Route path="/reports/vehicle-service/employee-incentives" element={<OperationalReportPage reportKey="vehicle-service/employee-incentives" kind="employee-incentive" />} />
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
                            </Route>
                        </Route>
                        <Route path="*" element={<NotFoundPage />} />
                    </Route>
                </Route>
            </Route>
        </Route>,
    ),
);

export function AppRouter() {
    return <RouterProvider router={appRouter} />;
}
