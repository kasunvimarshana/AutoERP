import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/pricing', () => ({
  default: {
    listPriceLists: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listDiscountRules: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getPriceList: vi.fn(),
    createPriceList: vi.fn(),
    updatePriceList: vi.fn(),
    deletePriceList: vi.fn(),
    calculatePrice: vi.fn(),
  },
}))

import PricingPage from '../PricingPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('PricingPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<PricingPage />)
    expect(screen.getByRole('heading', { name: /Pricing & Discounts/i })).toBeInTheDocument()
  })

  it('renders the price lists and discount rules tables after loading', async () => {
    renderWithQuery(<PricingPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Price Lists/i })).toBeInTheDocument()
      expect(screen.getByRole('table', { name: /Discount Rules/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<PricingPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
