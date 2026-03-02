import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/sales', () => ({
  default: {
    listOrders: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getOrder: vi.fn(),
    createOrder: vi.fn().mockResolvedValue({ data: { id: 1 } }),
    confirmOrder: vi.fn().mockResolvedValue({ data: {} }),
    cancelOrder: vi.fn().mockResolvedValue({ data: {} }),
    createReturn: vi.fn().mockResolvedValue({ data: [] }),
  },
}))

import SalesPage from '../SalesPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('SalesPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<SalesPage />)
    expect(screen.getByRole('heading', { name: /Sales/i })).toBeInTheDocument()
  })

  it('renders the sales orders table after loading', async () => {
    renderWithQuery(<SalesPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Sales orders/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<SalesPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('shows Create Sales Order button', () => {
    renderWithQuery(<SalesPage />)
    expect(screen.getByRole('button', { name: /Create Sales Order/i })).toBeInTheDocument()
  })

  it('opens the create order form when button is clicked', async () => {
    renderWithQuery(<SalesPage />)
    const btn = screen.getByRole('button', { name: /Create Sales Order/i })
    await userEvent.click(btn)
    expect(screen.getByRole('form', { name: /Create sales order/i })).toBeInTheDocument()
  })

  it('create order form has required fields', async () => {
    renderWithQuery(<SalesPage />)
    await userEvent.click(screen.getByRole('button', { name: /Create Sales Order/i }))
    const inputs = screen.getAllByPlaceholderText(/e\.g\. 1/i)
    expect(inputs.length).toBeGreaterThanOrEqual(1)
    expect(screen.getByRole('button', { name: /Create Order/i })).toBeInTheDocument()
  })

  it('shows Process Return button', () => {
    renderWithQuery(<SalesPage />)
    expect(screen.getByRole('button', { name: /Process Return/i })).toBeInTheDocument()
  })

  it('opens the return form when Process Return button is clicked', async () => {
    renderWithQuery(<SalesPage />)
    await userEvent.click(screen.getByRole('button', { name: /Process Return/i }))
    expect(screen.getByRole('form', { name: /Process sales return/i })).toBeInTheDocument()
  })

  it('return form has quantity, warehouse and unit cost fields', async () => {
    renderWithQuery(<SalesPage />)
    await userEvent.click(screen.getByRole('button', { name: /Process Return/i }))
    expect(screen.getByLabelText(/Quantity to Return/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Warehouse ID/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Unit Cost/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /^Process Return$/i })).toBeInTheDocument()
  })
})
