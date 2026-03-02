import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import warehouseApi from '@/api/warehouse'

/**
 * WarehousePage — Warehouse Management System overview.
 *
 * Displays picking orders and bin-level location tracking.
 * Supports batch, wave, and zone picking strategies.
 */
export default function WarehousePage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['warehouse', 'picking-orders'],
    queryFn: () => warehouseApi.listPickingOrders({ per_page: 20 }),
  })

  const { data: binData, isLoading: binLoading, isError: binError } = useQuery({
    queryKey: ['warehouse', 'bin-locations'],
    queryFn: () => warehouseApi.listBinLocations({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: Parameters<typeof warehouseApi.createPickingOrder>[0]) =>
      warehouseApi.createPickingOrder(payload),
    onSuccess: () => {
      setFormSuccess('Picking order created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['warehouse', 'picking-orders'] })
    },
    onError: () => {
      setFormError('Failed to create picking order. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const completeMutation = useMutation({
    mutationFn: (id: number) => warehouseApi.completePickingOrder(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['warehouse', 'picking-orders'] }),
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    createMutation.mutate({
      warehouse_id: parseInt(formData.get('warehouse_id') as string, 10),
      reference: formData.get('reference') as string,
      picking_strategy: formData.get('picking_strategy') as 'batch' | 'wave' | 'zone',
      lines: [
        {
          product_id: parseInt(formData.get('product_id') as string, 10),
          quantity: formData.get('quantity') as string,
          quantity_to_pick: formData.get('quantity') as string,
        },
      ],
    } as Parameters<typeof warehouseApi.createPickingOrder>[0])
  }

  const orders = data?.data?.data ?? []
  const bins = binData?.data?.data ?? []

  return (
    <section aria-label="Warehouse Management">
      <h1>Warehouse Management</h1>
      <p>Bin-level tracking, intelligent putaway suggestions, and optimised picking (batch, wave, zone).</p>

      <h2>Picking Orders</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Picking Order'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create picking order" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Warehouse ID
            <input name="warehouse_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Reference
            <input name="reference" type="text" required placeholder="e.g. PO-001" />
          </label>
          <label>
            Picking Strategy
            <select name="picking_strategy" required>
              <option value="batch">Batch</option>
              <option value="wave">Wave</option>
              <option value="zone">Zone</option>
            </select>
          </label>
          <label>
            Product ID
            <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Quantity
            <input name="quantity" type="text" required placeholder="e.g. 10.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Picking Order'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading picking orders…</p>}
      {isError && <p role="alert" className="error">Failed to load picking orders. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Picking orders">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Reference</th>
              <th scope="col">Warehouse</th>
              <th scope="col">Strategy</th>
              <th scope="col">Status</th>
              <th scope="col">Created</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {orders.length === 0 ? (
              <tr>
                <td colSpan={7}>No picking orders found.</td>
              </tr>
            ) : (
              orders.map((order) => (
                <tr key={order.id}>
                  <td>{order.id}</td>
                  <td>{order.reference}</td>
                  <td>{order.warehouse_id}</td>
                  <td>{order.picking_strategy}</td>
                  <td>{order.status}</td>
                  <td>{order.created_at}</td>
                  <td>
                    {(order.status === 'pending' || order.status === 'in_progress') && (
                      <button
                        onClick={() => completeMutation.mutate(order.id)}
                        disabled={completeMutation.isPending}
                        aria-label={`Complete picking order ${order.reference}`}
                      >
                        Complete
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      <h2 style={{ marginTop: '2rem' }}>Bin Locations</h2>

      {binLoading && <p role="status">Loading bin locations…</p>}
      {binError && <p role="alert" className="error">Failed to load bin locations. Please try again.</p>}

      {!binLoading && !binError && (
        <table aria-label="Bin locations">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Code</th>
              <th scope="col">Warehouse</th>
              <th scope="col">Capacity</th>
              <th scope="col">Active</th>
            </tr>
          </thead>
          <tbody>
            {bins.length === 0 ? (
              <tr>
                <td colSpan={5}>No bin locations found.</td>
              </tr>
            ) : (
              bins.map((bin) => (
                <tr key={bin.id}>
                  <td>{bin.id}</td>
                  <td>{bin.code}</td>
                  <td>{bin.warehouse_id}</td>
                  <td>{bin.capacity}</td>
                  <td>{bin.is_active ? 'Yes' : 'No'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
