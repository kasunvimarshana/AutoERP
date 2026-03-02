import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/product', () => ({
  default: {
    listProducts: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getProduct: vi.fn(),
    createProduct: vi.fn(),
    updateProduct: vi.fn(),
  },
}))

import ProductPage from '../ProductPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('ProductPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<ProductPage />)
    expect(screen.getByRole('heading', { name: /Product Catalog/i })).toBeInTheDocument()
  })

  it('renders the products table after loading', async () => {
    renderWithQuery(<ProductPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Products/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<ProductPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
