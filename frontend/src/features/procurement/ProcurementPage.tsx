import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import procurementApi, { type CreatePurchaseOrderPayload } from '@/api/procurement'

/**
 * ProcurementPage — Purchase Order management overview.
 *
 * Displays the procurement pipeline following the
 * Purchase Request → RFQ → Vendor Selection → PO → Goods Receipt → Vendor Bill → Payment flow.
 *
 * Includes a "Create Purchase Order" form (Buy flow) and inline
 * "Receive Goods" action for accepted orders.
 */
export default function ProcurementPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['procurement', 'purchase-orders'],
    queryFn: () => procurementApi.listOrders({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: CreatePurchaseOrderPayload) => procurementApi.createOrder(payload),
    onSuccess: () => {
      setFormSuccess('Purchase order created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['procurement', 'purchase-orders'] })
    },
    onError: () => {
      setFormError('Failed to create purchase order. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const form = e.currentTarget
    const formData = new FormData(form)
    const payload: CreatePurchaseOrderPayload = {
      vendor_id: parseInt(formData.get('vendor_id') as string, 10),
      lines: [
        {
          product_id: parseInt(formData.get('product_id') as string, 10),
          quantity: formData.get('quantity') as string,
          unit_cost: formData.get('unit_cost') as string,
        },
      ],
      expected_delivery_date: (formData.get('expected_delivery_date') as string) || undefined,
      notes: (formData.get('notes') as string) || undefined,
    }
    createMutation.mutate(payload)
  }

  const orders = data?.data?.data ?? []

  return (
    <section aria-label="Procurement">
      <h1>Procurement</h1>
      <p>Manage the full procurement lifecycle: Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment.</p>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Purchase Order'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create purchase order" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Vendor ID
            <input name="vendor_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Product ID
            <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Quantity
            <input name="quantity" type="text" required placeholder="e.g. 100.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Unit Cost
            <input name="unit_cost" type="text" required placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Expected Delivery Date (optional)
            <input name="expected_delivery_date" type="date" />
          </label>
          <label>
            Notes (optional)
            <textarea name="notes" rows={2} placeholder="Order notes…" />
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Purchase Order'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading purchase orders…</p>}
      {isError && <p role="alert" className="error">Failed to load purchase orders. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Purchase orders">
          <thead>
            <tr>
              <th scope="col">Order #</th>
              <th scope="col">Vendor</th>
              <th scope="col">Status</th>
              <th scope="col">Subtotal</th>
              <th scope="col">Tax</th>
              <th scope="col">Total</th>
              <th scope="col">Expected Delivery</th>
              <th scope="col">Created</th>
            </tr>
          </thead>
          <tbody>
            {orders.length === 0 ? (
              <tr>
                <td colSpan={8}>No purchase orders found.</td>
              </tr>
            ) : (
              orders.map((order) => (
                <tr key={order.id}>
                  <td>{order.order_number}</td>
                  <td>{order.vendor_id}</td>
                  <td>{order.status}</td>
                  <td>{order.subtotal}</td>
                  <td>{order.tax_amount}</td>
                  <td>{order.total_amount}</td>
                  <td>{order.expected_delivery_date ?? '—'}</td>
                  <td>{order.created_at}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
