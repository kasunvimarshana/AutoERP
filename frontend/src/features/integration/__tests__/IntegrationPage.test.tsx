import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/integration', () => ({
  default: {
    listWebhooks: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listLogs: vi.fn().mockResolvedValue({ data: { data: [] } }),
    createWebhook: vi.fn(),
    deleteWebhook: vi.fn(),
    dispatchWebhook: vi.fn(),
  },
}))

import IntegrationPage from '../IntegrationPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('IntegrationPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<IntegrationPage />)
    expect(screen.getByRole('heading', { name: /Integration/i, level: 1 })).toBeInTheDocument()
  })

  it('renders the webhook endpoints table after loading', async () => {
    renderWithQuery(<IntegrationPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Webhook endpoints/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<IntegrationPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
