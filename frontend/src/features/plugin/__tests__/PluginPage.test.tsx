import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/plugin', () => ({
  default: {
    listPlugins: vi.fn().mockResolvedValue({ data: { data: [] } }),
  },
}))

import PluginPage from '../PluginPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('PluginPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<PluginPage />)
    expect(screen.getByRole('heading', { name: /Plugin Marketplace/i })).toBeInTheDocument()
  })

  it('renders the plugins table after loading', async () => {
    renderWithQuery(<PluginPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Plugins/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<PluginPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })
})
