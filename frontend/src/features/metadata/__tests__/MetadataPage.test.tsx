import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/metadata', () => ({
  default: {
    listFields: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getField: vi.fn(),
  },
}))

import MetadataPage from '../MetadataPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('MetadataPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<MetadataPage />)
    expect(screen.getByRole('heading', { name: /Metadata/i })).toBeInTheDocument()
  })

  it('renders the custom fields table after loading', async () => {
    renderWithQuery(<MetadataPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Custom fields/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<MetadataPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
