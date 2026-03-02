import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/crm', () => ({
  default: {
    listLeads: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getLead: vi.fn(),
    createLead: vi.fn(),
    listOpportunities: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getOpportunity: vi.fn(),
    closeWon: vi.fn(),
    closeLost: vi.fn(),
  },
}))

import CRMPage from '../CRMPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('CRMPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<CRMPage />)
    expect(screen.getByRole('heading', { name: /CRM/i })).toBeInTheDocument()
  })

  it('renders the leads table after loading', async () => {
    renderWithQuery(<CRMPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /CRM leads/i })).toBeInTheDocument()
    })
  })

  it('renders the opportunities table after loading', async () => {
    renderWithQuery(<CRMPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /CRM opportunities/i })).toBeInTheDocument()
    })
  })
})
