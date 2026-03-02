import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { LoginPage, AuthGuard } from '@/features/auth'
import { DashboardPage } from '@/features/dashboard'
import { InventoryPage } from '@/features/inventory'
import { SalesPage } from '@/features/sales'
import { POSPage } from '@/features/pos'
import { CRMPage } from '@/features/crm'
import { ProcurementPage } from '@/features/procurement'
import { WarehousePage } from '@/features/warehouse'
import { AccountingPage } from '@/features/accounting'
import { ReportingPage } from '@/features/reporting'
import { ProductPage } from '@/features/product'
import { OrganisationPage } from '@/features/organisation'
import { MetadataPage } from '@/features/metadata'
import { WorkflowPage } from '@/features/workflow'
import { NotificationPage } from '@/features/notification'
import { IntegrationPage } from '@/features/integration'
import { PluginPage } from '@/features/plugin'
import TenancyPage from '@/features/tenancy'
import { PricingPage } from '@/features/pricing'
import AppShell from '@/components/layout/AppShell'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      retry: 1,
    },
  },
})

/**
 * App — root router and provider tree.
 *
 * Public routes:  /login
 * Protected routes: everything else — wrapped in <AuthGuard> + <AppShell>
 */
export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          {/* Public */}
          <Route path="/login" element={<LoginPage />} />

          {/* Protected — all routes wrapped in AuthGuard + AppShell */}
          <Route
            element={
              <AuthGuard>
                <AppShell>
                  <Outlet />
                </AppShell>
              </AuthGuard>
            }
          >
            <Route path="/" element={<DashboardPage />} />
            <Route path="/inventory" element={<InventoryPage />} />
            <Route path="/sales" element={<SalesPage />} />
            <Route path="/pos" element={<POSPage />} />
            <Route path="/crm" element={<CRMPage />} />
            <Route path="/procurement" element={<ProcurementPage />} />
            <Route path="/warehouse" element={<WarehousePage />} />
            <Route path="/accounting" element={<AccountingPage />} />
            <Route path="/reporting" element={<ReportingPage />} />
            <Route path="/product" element={<ProductPage />} />
            <Route path="/organisation" element={<OrganisationPage />} />
            <Route path="/metadata" element={<MetadataPage />} />
            <Route path="/workflow" element={<WorkflowPage />} />
            <Route path="/notification" element={<NotificationPage />} />
            <Route path="/integration" element={<IntegrationPage />} />
            <Route path="/plugin" element={<PluginPage />} />
            <Route path="/tenancy" element={<TenancyPage />} />
            <Route path="/pricing" element={<PricingPage />} />
          </Route>

          {/* Catch-all */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  )
}
