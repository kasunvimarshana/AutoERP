import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/organisation', () => ({
  default: {
    listOrganisations: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getOrganisation: vi.fn(),
  },
}))

import OrganisationPage from '../OrganisationPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('OrganisationPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<OrganisationPage />)
    expect(screen.getByRole('heading', { name: /Organisation Hierarchy/i })).toBeInTheDocument()
  })

  it('renders the organisations table after loading', async () => {
    renderWithQuery(<OrganisationPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Organisations/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<OrganisationPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
