import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/workflow', () => ({
  default: {
    listWorkflows: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listInstances: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getWorkflow: vi.fn(),
    createWorkflow: vi.fn(),
    deleteWorkflow: vi.fn(),
    createInstance: vi.fn(),
  },
}))

import WorkflowPage from '../WorkflowPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('WorkflowPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<WorkflowPage />)
    expect(screen.getByRole('heading', { name: /Workflow Engine/i })).toBeInTheDocument()
  })

  it('renders the workflow definitions table after loading', async () => {
    renderWithQuery(<WorkflowPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Workflow definitions/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<WorkflowPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
