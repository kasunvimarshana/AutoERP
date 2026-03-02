import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/notification', () => ({
  default: {
    listTemplates: vi.fn().mockResolvedValue({ data: { data: [] } }),
    listLogs: vi.fn().mockResolvedValue({ data: { data: [] } }),
    sendNotification: vi.fn(),
    createTemplate: vi.fn(),
    deleteTemplate: vi.fn(),
  },
}))

import NotificationPage from '../NotificationPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('NotificationPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<NotificationPage />)
    expect(screen.getByRole('heading', { name: /Notification Engine/i })).toBeInTheDocument()
  })

  it('renders the notification templates table after loading', async () => {
    renderWithQuery(<NotificationPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Notification templates/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<NotificationPage />)
    expect(screen.getAllByRole('status').length).toBeGreaterThan(0)
  })
})
