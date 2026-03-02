import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/tenancy', () => ({
  default: {
    listTenants: vi.fn().mockResolvedValue({ data: { data: [] } }),
  },
}))

import TenancyPage from '../TenancyPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('TenancyPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<TenancyPage />)
    expect(screen.getByRole('heading', { name: /Tenant Management/i })).toBeInTheDocument()
  })

  it('renders the tenants table after loading', async () => {
    renderWithQuery(<TenancyPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Tenants/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<TenancyPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
