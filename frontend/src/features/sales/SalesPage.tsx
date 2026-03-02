import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import salesApi, { type CreateSalesOrderPayload, type CreateReturnPayload } from '@/api/sales'

/**
 * SalesPage — Sales Order management overview.
 *
 * Displays a paginated list of sales orders following the
 * Quotation → Order → Delivery → Invoice → Payment flow.
 *
 * Includes a "Create Sales Order" form and inline actions
 * to confirm or cancel existing orders.
 */
export default function SalesPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)
  const [showReturnForm, setShowReturnForm] = useState(false)
  const [returnError, setReturnError] = useState<string | null>(null)
  const [returnSuccess, setReturnSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['sales', 'orders'],
    queryFn: () => salesApi.listOrders({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: CreateSalesOrderPayload) => salesApi.createOrder(payload),
    onSuccess: () => {
      setFormSuccess('Sales order created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['sales', 'orders'] })
    },
    onError: () => {
      setFormError('Failed to create sales order. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const confirmMutation = useMutation({
    mutationFn: (id: number) => salesApi.confirmOrder(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales', 'orders'] }),
  })

  const cancelMutation = useMutation({
    mutationFn: (id: number) => salesApi.cancelOrder(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sales', 'orders'] }),
  })

  const returnMutation = useMutation({
    mutationFn: ({ orderId, payload }: { orderId: number; payload: CreateReturnPayload }) =>
      salesApi.createReturn(orderId, payload),
    onSuccess: () => {
      setReturnSuccess('Return processed; stock restored.')
      setReturnError(null)
      setShowReturnForm(false)
      queryClient.invalidateQueries({ queryKey: ['sales', 'orders'] })
    },
    onError: (err: unknown) => {
      const msg = err instanceof Error ? err.message : 'Failed to process return.'
      setReturnError(msg)
      setReturnSuccess(null)
    },
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const form = e.currentTarget
    const formData = new FormData(form)
    const payload: CreateSalesOrderPayload = {
      customer_id: parseInt(formData.get('customer_id') as string, 10),
      lines: [
        {
          product_id: parseInt(formData.get('product_id') as string, 10),
          quantity: formData.get('quantity') as string,
          unit_price: formData.get('unit_price') as string,
          discount_amount: '0.0000',
          tax_rate: '0.0000',
        },
      ],
      notes: (formData.get('notes') as string) || undefined,
    }
    createMutation.mutate(payload)
  }

  const handleReturnSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setReturnError(null)
    setReturnSuccess(null)
    const fd = new FormData(e.currentTarget)
    const orderId = parseInt(fd.get('order_id') as string, 10)
    const payload: CreateReturnPayload = {
      lines: [
        {
          product_id:   parseInt(fd.get('product_id') as string, 10),
          warehouse_id: parseInt(fd.get('warehouse_id') as string, 10),
          uom_id:       parseInt(fd.get('uom_id') as string, 10),
          quantity:     fd.get('quantity') as string,
          unit_cost:    fd.get('unit_cost') as string,
          batch_number: (fd.get('batch_number') as string) || undefined,
          lot_number:   (fd.get('lot_number') as string) || undefined,
          notes:        (fd.get('notes') as string) || undefined,
        },
      ],
    }
    returnMutation.mutate({ orderId, payload })
  }

  const orders = data?.data?.data ?? []

  return (
    <section aria-label="Sales Orders">
      <h1>Sales</h1>
      <p>Manage the full sales lifecycle: Quotation → Order → Delivery → Invoice → Payment.</p>

      <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem', flexWrap: 'wrap' }}>
        <button onClick={() => { setShowForm(!showForm); setShowReturnForm(false) }} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Sales Order'}
        </button>
        <button onClick={() => { setShowReturnForm(!showReturnForm); setShowForm(false) }} aria-expanded={showReturnForm}>
          {showReturnForm ? 'Cancel' : 'Process Return'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}
      {returnSuccess && <p role="alert" className="success">{returnSuccess}</p>}
      {returnError && <p role="alert" className="error">{returnError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create sales order" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Customer ID
            <input name="customer_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Product ID
            <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Quantity
            <input name="quantity" type="text" required placeholder="e.g. 1.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Unit Price
            <input name="unit_price" type="text" required placeholder="e.g. 10.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Notes (optional)
            <textarea name="notes" rows={2} placeholder="Order notes…" />
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Order'}
          </button>
        </form>
      )}

      {showReturnForm && (
        <form onSubmit={handleReturnSubmit} aria-label="Process sales return" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <h3>Process Return</h3>
          <label>
            Order ID
            <input name="order_id" type="number" min="1" required placeholder="e.g. 5" />
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
            Quantity to Return
            <input name="quantity" type="text" required placeholder="e.g. 2.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Unit Cost
            <input name="unit_cost" type="text" required placeholder="e.g. 5.5000" pattern="^\d+(\.\d+)?$" />
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
            Notes (optional)
            <textarea name="notes" rows={2} placeholder="Return reason…" />
          </label>
          <button type="submit" disabled={returnMutation.isPending}>
            {returnMutation.isPending ? 'Processing…' : 'Process Return'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading sales orders…</p>}
      {isError && <p role="alert" className="error">Failed to load sales orders. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Sales orders">
          <thead>
            <tr>
              <th scope="col">Order #</th>
              <th scope="col">Customer</th>
              <th scope="col">Status</th>
              <th scope="col">Subtotal</th>
              <th scope="col">Tax</th>
              <th scope="col">Total</th>
              <th scope="col">Created</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {orders.length === 0 ? (
              <tr>
                <td colSpan={8}>No sales orders found.</td>
              </tr>
            ) : (
              orders.map((order) => (
                <tr key={order.id}>
                  <td>{order.order_number}</td>
                  <td>{order.customer_id}</td>
                  <td>{order.status}</td>
                  <td>{order.subtotal}</td>
                  <td>{order.tax_amount}</td>
                  <td>{order.total_amount}</td>
                  <td>{order.created_at}</td>
                  <td>
                    {order.status === 'draft' && (
                      <>
                        <button
                          onClick={() => confirmMutation.mutate(order.id)}
                          disabled={confirmMutation.isPending}
                          aria-label={`Confirm order ${order.order_number}`}
                        >
                          Confirm
                        </button>
                        {' '}
                        <button
                          onClick={() => cancelMutation.mutate(order.id)}
                          disabled={cancelMutation.isPending}
                          aria-label={`Cancel order ${order.order_number}`}
                        >
                          Cancel
                        </button>
                      </>
                    )}
                    {order.status === 'confirmed' && (
                      <button
                        onClick={() => cancelMutation.mutate(order.id)}
                        disabled={cancelMutation.isPending}
                        aria-label={`Cancel order ${order.order_number}`}
                      >
                        Cancel
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
