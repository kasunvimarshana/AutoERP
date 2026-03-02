import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/warehouse', () => ({
  default: {
    listPickingOrders: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listBinLocations: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getPickingOrder: vi.fn(),
    createPickingOrder: vi.fn(),
    completePickingOrder: vi.fn(),
    getPutawayRecommendation: vi.fn(),
  },
}))

import WarehousePage from '../WarehousePage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('WarehousePage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<WarehousePage />)
    expect(screen.getByRole('heading', { name: /Warehouse Management/i })).toBeInTheDocument()
  })

  it('renders the picking orders table after loading', async () => {
    renderWithQuery(<WarehousePage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Picking orders/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<WarehousePage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
