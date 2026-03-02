import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/accounting', () => ({
  default: {
    listEntries: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getEntry: vi.fn(),
    createEntry: vi.fn(),
    listAccounts: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listFiscalPeriods: vi.fn().mockResolvedValue({ data: { data: [] } }),
    postEntry: vi.fn(),
    createAccount: vi.fn(),
    createFiscalPeriod: vi.fn(),
    closeFiscalPeriod: vi.fn(),
  },
}))

import AccountingPage from '../AccountingPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('AccountingPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<AccountingPage />)
    expect(screen.getByRole('heading', { name: /Accounting/i })).toBeInTheDocument()
  })

  it('renders the journal entries table after loading', async () => {
    renderWithQuery(<AccountingPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Journal entries/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<AccountingPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
