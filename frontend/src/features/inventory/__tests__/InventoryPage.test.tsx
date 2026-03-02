import { describe, it, expect, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

vi.mock('@/api/inventory', () => ({
  default: {
    listStockItems: vi.fn().mockResolvedValue({ data: { data: [] } }),
    getStockLevel: vi.fn(),
    recordTransaction: vi.fn().mockResolvedValue({ data: { id: 1 } }),
    listTransactions: vi.fn(),
    createBatch: vi.fn().mockResolvedValue({ data: { id: 1 } }),
    showBatch: vi.fn(),
    updateBatch: vi.fn().mockResolvedValue({ data: { id: 1 } }),
    deleteBatch: vi.fn().mockResolvedValue({ data: null }),
    deductByStrategy: vi.fn().mockResolvedValue({ data: [] }),
  },
}))

import InventoryPage from '../InventoryPage'

function renderWithQuery(ui: React.ReactElement) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
}

describe('InventoryPage', () => {
  it('renders the page heading', () => {
    renderWithQuery(<InventoryPage />)
    expect(screen.getByRole('heading', { name: /Inventory Management/i })).toBeInTheDocument()
  })

  it('renders the stock items table on the default Stock Items tab', async () => {
    renderWithQuery(<InventoryPage />)
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Stock items/i })).toBeInTheDocument()
    })
  })

  it('shows loading status while fetching', () => {
    renderWithQuery(<InventoryPage />)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('shows tab buttons for Stock Items, Record Transaction, and Batch Management', () => {
    renderWithQuery(<InventoryPage />)
    expect(screen.getByRole('tab', { name: /Stock Items/i })).toBeInTheDocument()
    expect(screen.getByRole('tab', { name: /Record Transaction/i })).toBeInTheDocument()
    expect(screen.getByRole('tab', { name: /Batch Management/i })).toBeInTheDocument()
  })

  it('switches to Record Transaction tab and shows the Record button', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Record Transaction/i }))
    expect(screen.getByRole('button', { name: /Record Stock Transaction/i })).toBeInTheDocument()
  })

  it('opens the record transaction form when the Record button is clicked', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Record Transaction/i }))
    const btn = screen.getByRole('button', { name: /Record Stock Transaction/i })
    await userEvent.click(btn)
    expect(screen.getByRole('form', { name: /Record stock transaction/i })).toBeInTheDocument()
  })

  it('transaction form contains Buy/Sell/Return options', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Record Transaction/i }))
    await userEvent.click(screen.getByRole('button', { name: /Record Stock Transaction/i }))
    expect(screen.getByRole('option', { name: /Buy \(Purchase Receipt\)/i })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /Sell \(Sales Shipment\)/i })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /Return/i })).toBeInTheDocument()
  })

  it('switches to Batch Management tab and shows Create Batch and Deduct buttons', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Batch Management/i }))
    expect(screen.getByRole('button', { name: /Create Batch \(Buy\)/i })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /Deduct Stock \(Sell/i })).toBeInTheDocument()
  })

  it('shows Create Batch form when the Create Batch button is clicked', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Batch Management/i }))
    await userEvent.click(screen.getByRole('button', { name: /Create Batch \(Buy\)/i }))
    expect(screen.getByRole('form', { name: /Create batch/i })).toBeInTheDocument()
  })

  it('shows Deduct form when the Deduct button is clicked', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Batch Management/i }))
    await userEvent.click(screen.getByRole('button', { name: /Deduct Stock \(Sell/i }))
    expect(screen.getByRole('form', { name: /Deduct stock by strategy/i })).toBeInTheDocument()
  })

  it('Deduct form contains FIFO/LIFO/FEFO/Manual strategy options', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Batch Management/i }))
    await userEvent.click(screen.getByRole('button', { name: /Deduct Stock \(Sell/i }))
    expect(screen.getByRole('option', { name: /FIFO/i })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /LIFO/i })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /FEFO/i })).toBeInTheDocument()
    expect(screen.getByRole('option', { name: /Manual/i })).toBeInTheDocument()
  })

  it('Batch Management tab shows a batch list table', async () => {
    renderWithQuery(<InventoryPage />)
    await userEvent.click(screen.getByRole('tab', { name: /Batch Management/i }))
    await waitFor(() => {
      expect(screen.getByRole('table', { name: /Batch list/i })).toBeInTheDocument()
    })
  })
})
