import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/pos', () => ({
  default: {
    listTransactions: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listSessions: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getTransaction: vi.fn(),
    createTransaction: vi.fn(),
    voidTransaction: vi.fn(),
    openSession: vi.fn(),
    closeSession: vi.fn(),
  },
}))

import POSPage from '../POSPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('POSPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<POSPage />)
    expect(screen.getByRole('heading', { name: /Point of Sale/i })).toBeInTheDocument()
  })

  it('renders the POS transactions table after loading', async () => {
    renderWithQuery(<POSPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /POS transactions/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<POSPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
