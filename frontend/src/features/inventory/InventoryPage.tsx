import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import inventoryApi, {
  type RecordTransactionPayload,
  type CreateBatchPayload,
  type UpdateBatchPayload,
  type DeductByStrategyPayload,
} from '@/api/inventory'

/**
 * InventoryPage — ledger-driven stock management with full batch/lot support.
 *
 * Provides three panels:
 *  1. Stock Items — paginated list of all batch-level stock records
 *  2. Record Transaction — manual stock movement (Buy/Sell/Return/etc.)
 *  3. Batch Management — create, update, delete batches; strategy-based deduction
 *
 * All Buy operations create or update batches with quantity, cost price, lot number,
 * and expiry date. Sales deduct stock from the correct batch via FIFO/LIFO/FEFO/Manual
 * strategy. Returns restore quantities to the specified batch.
 */
export default function InventoryPage() {
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'stock' | 'transaction' | 'batch'>('stock')

  // ── Transaction form state ─────────────────────────────────────────────────
  const [txFormError, setTxFormError] = useState<string | null>(null)
  const [txFormSuccess, setTxFormSuccess] = useState<string | null>(null)
  const [showTxForm, setShowTxForm] = useState(false)

  // ── Batch form state ───────────────────────────────────────────────────────
  const [batchFormError, setBatchFormError] = useState<string | null>(null)
  const [batchFormSuccess, setBatchFormSuccess] = useState<string | null>(null)
  const [showCreateBatch, setShowCreateBatch] = useState(false)
  const [showDeductForm, setShowDeductForm] = useState(false)
  const [editingBatchId, setEditingBatchId] = useState<number | null>(null)

  // ── Stock items query ─────────────────────────────────────────────────────
  const { data, isLoading, isError } = useQuery({
    queryKey: ['inventory', 'stock-items'],
    queryFn: () => inventoryApi.listStockItems({ per_page: 20 }),
  })

  // ── Record transaction mutation ────────────────────────────────────────────
  const recordTxMutation = useMutation({
    mutationFn: (payload: RecordTransactionPayload) => inventoryApi.recordTransaction(payload),
    onSuccess: () => {
      setTxFormSuccess('Transaction recorded successfully.')
      setTxFormError(null)
      setShowTxForm(false)
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-items'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to record transaction.'
      setTxFormError(msg)
      setTxFormSuccess(null)
    },
  })

  // ── Create batch mutation ──────────────────────────────────────────────────
  const createBatchMutation = useMutation({
    mutationFn: (payload: CreateBatchPayload) => inventoryApi.createBatch(payload),
    onSuccess: () => {
      setBatchFormSuccess('Batch created successfully.')
      setBatchFormError(null)
      setShowCreateBatch(false)
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-items'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to create batch.'
      setBatchFormError(msg)
      setBatchFormSuccess(null)
    },
  })

  // ── Update batch mutation ──────────────────────────────────────────────────
  const updateBatchMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateBatchPayload }) =>
      inventoryApi.updateBatch(id, payload),
    onSuccess: () => {
      setBatchFormSuccess('Batch updated successfully.')
      setBatchFormError(null)
      setEditingBatchId(null)
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-items'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to update batch.'
      setBatchFormError(msg)
    },
  })

  // ── Delete batch mutation ──────────────────────────────────────────────────
  const deleteBatchMutation = useMutation({
    mutationFn: (id: number) => inventoryApi.deleteBatch(id),
    onSuccess: () => {
      setBatchFormSuccess('Batch deleted.')
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-items'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to delete batch. Ensure stock is zero.'
      setBatchFormError(msg)
    },
  })

  // ── Deduct by strategy mutation ────────────────────────────────────────────
  const deductMutation = useMutation({
    mutationFn: (payload: DeductByStrategyPayload) => inventoryApi.deductByStrategy(payload),
    onSuccess: () => {
      setBatchFormSuccess('Stock deducted successfully.')
      setBatchFormError(null)
      setShowDeductForm(false)
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-items'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to deduct stock.'
      setBatchFormError(msg)
    },
  })

  // ── Handlers ────────────────────────────────────────────────────────────────
  const handleTxSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setTxFormError(null)
    setTxFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: RecordTransactionPayload = {
      transaction_type: fd.get('transaction_type') as string,
      product_id: parseInt(fd.get('product_id') as string, 10),
      warehouse_id: parseInt(fd.get('warehouse_id') as string, 10),
      uom_id: parseInt(fd.get('uom_id') as string, 10) || undefined,
      quantity: fd.get('quantity') as string,
      unit_cost: fd.get('unit_cost') as string,
      batch_number: (fd.get('batch_number') as string) || undefined,
      lot_number: (fd.get('lot_number') as string) || undefined,
      expiry_date: (fd.get('expiry_date') as string) || undefined,
      notes: (fd.get('notes') as string) || undefined,
    }
    recordTxMutation.mutate(payload)
  }

  const handleCreateBatchSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setBatchFormError(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateBatchPayload = {
      warehouse_id: parseInt(fd.get('warehouse_id') as string, 10),
      product_id: parseInt(fd.get('product_id') as string, 10),
      uom_id: parseInt(fd.get('uom_id') as string, 10),
      quantity: fd.get('quantity') as string,
      cost_price: fd.get('cost_price') as string,
      batch_number: (fd.get('batch_number') as string) || undefined,
      lot_number: (fd.get('lot_number') as string) || undefined,
      expiry_date: (fd.get('expiry_date') as string) || undefined,
      costing_method: (fd.get('costing_method') as CreateBatchPayload['costing_method']) || 'fifo',
    }
    createBatchMutation.mutate(payload)
  }

  const handleUpdateBatchSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!editingBatchId) return
    setBatchFormError(null)
    const fd = new FormData(e.currentTarget)
    const payload: UpdateBatchPayload = {
      cost_price: (fd.get('cost_price') as string) || undefined,
      expiry_date: (fd.get('expiry_date') as string) || undefined,
      lot_number: (fd.get('lot_number') as string) || undefined,
      batch_number: (fd.get('batch_number') as string) || undefined,
      costing_method: (fd.get('costing_method') as UpdateBatchPayload['costing_method']) || undefined,
    }
    updateBatchMutation.mutate({ id: editingBatchId, payload })
  }

  const handleDeductSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setBatchFormError(null)
    const fd = new FormData(e.currentTarget)
    const strategy = fd.get('strategy') as DeductByStrategyPayload['strategy']
    const payload: DeductByStrategyPayload = {
      product_id: parseInt(fd.get('product_id') as string, 10),
      warehouse_id: parseInt(fd.get('warehouse_id') as string, 10),
      uom_id: parseInt(fd.get('uom_id') as string, 10),
      quantity: fd.get('quantity') as string,
      unit_cost: fd.get('unit_cost') as string,
      strategy,
      batch_number: strategy === 'manual' ? (fd.get('batch_number') as string) || undefined : undefined,
      notes: (fd.get('notes') as string) || undefined,
    }
    deductMutation.mutate(payload)
  }

  const items = data?.data?.data ?? []
  const formGridStyle: React.CSSProperties = {
    display: 'grid',
    gap: '0.5rem',
    maxWidth: '480px',
    marginBottom: '1.5rem',
  }

  return (
    <section aria-label="Inventory Management">
      <h1>Inventory Management</h1>
      <p>
        Ledger-driven batch/lot stock management — Buy, Sell, and Return operations with FIFO /
        LIFO / FEFO / Manual batch selection.
      </p>

      {/* Tab bar */}
      <div role="tablist" style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem' }}>
        <button role="tab" aria-selected={activeTab === 'stock'} onClick={() => setActiveTab('stock')}>
          Stock Items
        </button>
        <button role="tab" aria-selected={activeTab === 'transaction'} onClick={() => setActiveTab('transaction')}>
          Record Transaction
        </button>
        <button role="tab" aria-selected={activeTab === 'batch'} onClick={() => setActiveTab('batch')}>
          Batch Management
        </button>
      </div>

      {/* ── Stock Items panel ──────────────────────────────────────────────── */}
      {activeTab === 'stock' && (
        <div role="tabpanel" aria-label="Stock items panel">
          {isLoading && <p role="status">Loading stock items…</p>}
          {isError && <p role="alert" className="error">Failed to load stock items.</p>}
          {!isLoading && !isError && (
            <table aria-label="Stock items">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Product</th>
                  <th scope="col">Warehouse</th>
                  <th scope="col">On Hand</th>
                  <th scope="col">Reserved</th>
                  <th scope="col">Available</th>
                  <th scope="col">Cost Price</th>
                  <th scope="col">Costing Method</th>
                  <th scope="col">Batch / Lot</th>
                  <th scope="col">Expiry</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={10}>No stock items found.</td>
                  </tr>
                ) : (
                  items.map((item) => (
                    <tr key={item.id}>
                      <td>{item.id}</td>
                      <td>{item.product_id}</td>
                      <td>{item.warehouse_id}</td>
                      <td>{item.quantity_on_hand}</td>
                      <td>{item.quantity_reserved}</td>
                      <td>{item.quantity_available}</td>
                      <td>{item.cost_price}</td>
                      <td>{item.costing_method}</td>
                      <td>{[item.batch_number, item.lot_number].filter(Boolean).join(' / ') || '—'}</td>
                      <td>{item.expiry_date ?? '—'}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* ── Record Transaction panel ───────────────────────────────────────── */}
      {activeTab === 'transaction' && (
        <div role="tabpanel" aria-label="Record transaction panel">
          <button onClick={() => setShowTxForm(!showTxForm)} aria-expanded={showTxForm}>
            {showTxForm ? 'Cancel' : 'Record Stock Transaction'}
          </button>

          {txFormSuccess && <p role="alert" className="success">{txFormSuccess}</p>}
          {txFormError && <p role="alert" className="error">{txFormError}</p>}

          {showTxForm && (
            <form
              onSubmit={handleTxSubmit}
              aria-label="Record stock transaction"
              style={formGridStyle}
            >
              <label>
                Transaction Type
                <select name="transaction_type" required>
                  <option value="purchase_receipt">Buy (Purchase Receipt)</option>
                  <option value="sales_shipment">Sell (Sales Shipment)</option>
                  <option value="return">Return</option>
                  <option value="adjustment">Adjustment</option>
                  <option value="internal_transfer">Internal Transfer</option>
                </select>
              </label>
              <label>
                Product ID
                <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Warehouse ID
                <input name="warehouse_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                UOM ID
                <input name="uom_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Quantity
                <input name="quantity" type="text" required placeholder="e.g. 10.0000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Unit Cost
                <input name="unit_cost" type="text" required placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Batch Number (optional)
                <input name="batch_number" type="text" placeholder="Batch #" />
              </label>
              <label>
                Lot Number (optional)
                <input name="lot_number" type="text" placeholder="Lot #" />
              </label>
              <label>
                Expiry Date (optional)
                <input name="expiry_date" type="date" />
              </label>
              <label>
                Notes (optional)
                <textarea name="notes" rows={2} placeholder="Additional notes…" />
              </label>
              <button type="submit" disabled={recordTxMutation.isPending}>
                {recordTxMutation.isPending ? 'Recording…' : 'Record Transaction'}
              </button>
            </form>
          )}
        </div>
      )}

      {/* ── Batch Management panel ─────────────────────────────────────────── */}
      {activeTab === 'batch' && (
        <div role="tabpanel" aria-label="Batch management panel">
          {batchFormSuccess && <p role="alert" className="success">{batchFormSuccess}</p>}
          {batchFormError && <p role="alert" className="error">{batchFormError}</p>}

          <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem', flexWrap: 'wrap' }}>
            <button onClick={() => { setShowCreateBatch(!showCreateBatch); setShowDeductForm(false); setEditingBatchId(null) }}>
              {showCreateBatch ? 'Cancel' : 'Create Batch (Buy)'}
            </button>
            <button onClick={() => { setShowDeductForm(!showDeductForm); setShowCreateBatch(false); setEditingBatchId(null) }}>
              {showDeductForm ? 'Cancel' : 'Deduct Stock (Sell / Strategy)'}
            </button>
          </div>

          {/* Create Batch form */}
          {showCreateBatch && (
            <form
              onSubmit={handleCreateBatchSubmit}
              aria-label="Create batch"
              style={formGridStyle}
            >
              <h3>Create New Batch</h3>
              <label>
                Warehouse ID
                <input name="warehouse_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Product ID
                <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                UOM ID
                <input name="uom_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Quantity
                <input name="quantity" type="text" required placeholder="e.g. 100.0000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Cost Price
                <input name="cost_price" type="text" required placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Batch Number (optional)
                <input name="batch_number" type="text" placeholder="BATCH-2026-001" />
              </label>
              <label>
                Lot Number (optional)
                <input name="lot_number" type="text" placeholder="LOT-A" />
              </label>
              <label>
                Expiry Date (optional)
                <input name="expiry_date" type="date" />
              </label>
              <label>
                Costing Method
                <select name="costing_method">
                  <option value="fifo">FIFO (First-In, First-Out)</option>
                  <option value="lifo">LIFO (Last-In, Last-Out)</option>
                  <option value="weighted_average">Weighted Average</option>
                </select>
              </label>
              <button type="submit" disabled={createBatchMutation.isPending}>
                {createBatchMutation.isPending ? 'Creating…' : 'Create Batch'}
              </button>
            </form>
          )}

          {/* Deduct by Strategy form */}
          {showDeductForm && (
            <form
              onSubmit={handleDeductSubmit}
              aria-label="Deduct stock by strategy"
              style={formGridStyle}
            >
              <h3>Deduct Stock (Sell / Return)</h3>
              <label>
                Product ID
                <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Warehouse ID
                <input name="warehouse_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                UOM ID
                <input name="uom_id" type="number" min="1" required placeholder="e.g. 1" />
              </label>
              <label>
                Quantity to Deduct
                <input name="quantity" type="text" required placeholder="e.g. 5.0000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Unit Cost
                <input name="unit_cost" type="text" required placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Deduction Strategy
                <select name="strategy">
                  <option value="fifo">FIFO — Oldest batch first</option>
                  <option value="lifo">LIFO — Newest batch first</option>
                  <option value="fefo">FEFO — Nearest expiry first (pharma)</option>
                  <option value="manual">Manual — Specific batch number</option>
                </select>
              </label>
              <label>
                Batch Number (required for Manual strategy)
                <input name="batch_number" type="text" placeholder="BATCH-2026-001" />
              </label>
              <label>
                Notes (optional)
                <textarea name="notes" rows={2} placeholder="Sales order reference…" />
              </label>
              <button type="submit" disabled={deductMutation.isPending}>
                {deductMutation.isPending ? 'Deducting…' : 'Deduct Stock'}
              </button>
            </form>
          )}

          {/* Edit Batch form */}
          {editingBatchId !== null && (
            <form
              onSubmit={handleUpdateBatchSubmit}
              aria-label="Update batch"
              style={formGridStyle}
            >
              <h3>Update Batch #{editingBatchId}</h3>
              <label>
                Cost Price
                <input name="cost_price" type="text" placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
              </label>
              <label>
                Expiry Date
                <input name="expiry_date" type="date" />
              </label>
              <label>
                Lot Number
                <input name="lot_number" type="text" placeholder="LOT-B" />
              </label>
              <label>
                Batch Number
                <input name="batch_number" type="text" placeholder="BATCH-2026-002" />
              </label>
              <label>
                Costing Method
                <select name="costing_method">
                  <option value="">— No change —</option>
                  <option value="fifo">FIFO</option>
                  <option value="lifo">LIFO</option>
                  <option value="weighted_average">Weighted Average</option>
                </select>
              </label>
              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <button type="submit" disabled={updateBatchMutation.isPending}>
                  {updateBatchMutation.isPending ? 'Saving…' : 'Save Changes'}
                </button>
                <button type="button" onClick={() => setEditingBatchId(null)}>Cancel</button>
              </div>
            </form>
          )}

          {/* Batch list with edit/delete actions */}
          {isLoading && <p role="status">Loading batches…</p>}
          {!isLoading && !isError && (
            <table aria-label="Batch list">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">Product</th>
                  <th scope="col">Warehouse</th>
                  <th scope="col">Batch / Lot</th>
                  <th scope="col">On Hand</th>
                  <th scope="col">Available</th>
                  <th scope="col">Cost Price</th>
                  <th scope="col">Costing</th>
                  <th scope="col">Expiry</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 ? (
                  <tr>
                    <td colSpan={10}>No batches found. Create one above.</td>
                  </tr>
                ) : (
                  items.map((item) => (
                    <tr key={item.id}>
                      <td>{item.id}</td>
                      <td>{item.product_id}</td>
                      <td>{item.warehouse_id}</td>
                      <td>{[item.batch_number, item.lot_number].filter(Boolean).join(' / ') || '—'}</td>
                      <td>{item.quantity_on_hand}</td>
                      <td>{item.quantity_available}</td>
                      <td>{item.cost_price}</td>
                      <td>{item.costing_method}</td>
                      <td>{item.expiry_date ?? '—'}</td>
                      <td>
                        <button
                          onClick={() => { setEditingBatchId(item.id); setShowCreateBatch(false); setShowDeductForm(false) }}
                          aria-label={`Edit batch ${item.id}`}
                        >
                          Edit
                        </button>
                        {' '}
                        <button
                          onClick={() => deleteBatchMutation.mutate(item.id)}
                          aria-label={`Delete batch ${item.id}`}
                          disabled={deleteBatchMutation.isPending}
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </div>
      )}
    </section>
  )
}
