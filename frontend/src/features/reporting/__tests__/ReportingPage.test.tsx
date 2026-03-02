import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/reporting', () => ({
  default: {
    listDefinitions: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listExports: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listSchedules: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getDefinition: vi.fn(),
    generateReport: vi.fn(),
    createDefinition: vi.fn(),
    deleteDefinition: vi.fn(),
    scheduleReport: vi.fn(),
  },
}))

import ReportingPage from '../ReportingPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('ReportingPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<ReportingPage />)
    expect(screen.getByRole('heading', { name: /Reporting/i })).toBeInTheDocument()
  })

  it('renders the report definitions table after loading', async () => {
    renderWithQuery(<ReportingPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Report definitions/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<ReportingPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
