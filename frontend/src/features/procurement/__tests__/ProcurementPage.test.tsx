import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/procurement', () => ({
  default: {
    listOrders: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getOrder: vi.fn(),
    createOrder: vi.fn().mockResolvedValue({ data: { id: 1 } }),
    receiveGoods: vi.fn(),
  },
}))

import ProcurementPage from '../ProcurementPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('ProcurementPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<ProcurementPage />)
    expect(screen.getByRole('heading', { name: /Procurement/i })).toBeInTheDocument()
  })

  it('renders the purchase orders table after loading', async () => {
    renderWithQuery(<ProcurementPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Purchase orders/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<ProcurementPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('shows Create Purchase Order button', () => {
    renderWithQuery(<ProcurementPage />)
    expect(screen.getByRole('button', { name: /Create Purchase Order/i })).toBeInTheDocument()
  })

  it('opens the create purchase order form when button is clicked', async () => {
    renderWithQuery(<ProcurementPage />)
    const btn = screen.getByRole('button', { name: /Create Purchase Order/i })
    await userEvent.click(btn)
    expect(screen.getByRole('form', { name: /Create purchase order/i })).toBeInTheDocument()
  })

  it('create order form has vendor id and product id fields', async () => {
    renderWithQuery(<ProcurementPage />)
    await userEvent.click(screen.getByRole('button', { name: /Create Purchase Order/i }))
    const inputs = screen.getAllByPlaceholderText(/e\.g\. 1/i)
    expect(inputs.length).toBeGreaterThanOrEqual(2)
  })
})
