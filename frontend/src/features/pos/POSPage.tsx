import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import posApi, { type CreatePosTransactionPayload, type OpenSessionPayload } from '@/api/pos'

/**
 * POSPage — Point-of-Sale transaction overview.
 *
 * Displays recent POS transactions with payment and status details.
 * Supports offline-first sync reconciliation workflow.
 */
export default function POSPage() {
  const queryClient = useQueryClient()
  const [showTxForm, setShowTxForm] = useState(false)
  const [showSessionForm, setShowSessionForm] = useState(false)
  const [txFormError, setTxFormError] = useState<string | null>(null)
  const [txFormSuccess, setTxFormSuccess] = useState<string | null>(null)
  const [sessionFormError, setSessionFormError] = useState<string | null>(null)
  const [sessionFormSuccess, setSessionFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['pos', 'transactions'],
    queryFn: () => posApi.listTransactions({ per_page: 20 }),
  })

  const { data: sessionData, isLoading: sessionLoading, isError: sessionError } = useQuery({
    queryKey: ['pos', 'sessions'],
    queryFn: () => posApi.listSessions({ per_page: 20 }),
  })

  const createTxMutation = useMutation({
    mutationFn: (payload: CreatePosTransactionPayload) => posApi.createTransaction(payload),
    onSuccess: () => {
      setTxFormSuccess('Transaction created successfully.')
      setTxFormError(null)
      setShowTxForm(false)
      queryClient.invalidateQueries({ queryKey: ['pos', 'transactions'] })
    },
    onError: () => {
      setTxFormError('Failed to create transaction. Please check your inputs and try again.')
      setTxFormSuccess(null)
    },
  })

  const voidMutation = useMutation({
    mutationFn: (id: number) => posApi.voidTransaction(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['pos', 'transactions'] }),
  })

  const openSessionMutation = useMutation({
    mutationFn: (payload: OpenSessionPayload) => posApi.openSession(payload),
    onSuccess: () => {
      setSessionFormSuccess('Session opened successfully.')
      setSessionFormError(null)
      setShowSessionForm(false)
      queryClient.invalidateQueries({ queryKey: ['pos', 'sessions'] })
    },
    onError: () => {
      setSessionFormError('Failed to open session. Please check your inputs and try again.')
      setSessionFormSuccess(null)
    },
  })

  const handleTxSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setTxFormError(null)
    setTxFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreatePosTransactionPayload = {
      terminal_id: parseInt(formData.get('terminal_id') as string, 10),
      session_id: parseInt(formData.get('session_id') as string, 10),
      lines: [
        {
          product_id: parseInt(formData.get('product_id') as string, 10),
          quantity: formData.get('quantity') as string,
          unit_price: formData.get('unit_price') as string,
          discount_amount: '0.0000',
          tax_rate: '0.0000',
        },
      ],
      payments: [
        {
          payment_method: formData.get('payment_method') as string,
          amount: formData.get('amount_paid') as string,
        },
      ],
    }
    createTxMutation.mutate(payload)
  }

  const handleSessionSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setSessionFormError(null)
    setSessionFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: OpenSessionPayload = {
      terminal_id: parseInt(formData.get('terminal_id') as string, 10),
      opening_balance: formData.get('opening_balance') as string,
    }
    openSessionMutation.mutate(payload)
  }

  const transactions = data?.data?.data ?? []
  const sessions = sessionData?.data?.data ?? []

  return (
    <section aria-label="Point of Sale">
      <h1>Point of Sale (POS)</h1>
      <p>Offline-first POS terminal management with sync reconciliation and split payment support.</p>

      <h2>Transactions</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowTxForm(!showTxForm)} aria-expanded={showTxForm}>
          {showTxForm ? 'Cancel' : 'Create Transaction'}
        </button>
      </div>

      {txFormSuccess && <p role="alert" className="success">{txFormSuccess}</p>}
      {txFormError && <p role="alert" className="error">{txFormError}</p>}

      {showTxForm && (
        <form onSubmit={handleTxSubmit} aria-label="Create POS transaction" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Terminal ID
            <input name="terminal_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Session ID
            <input name="session_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Product ID
            <input name="product_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Quantity
            <input name="quantity" type="text" required placeholder="e.g. 1.0000" />
          </label>
          <label>
            Unit Price
            <input name="unit_price" type="text" required placeholder="e.g. 10.0000" />
          </label>
          <label>
            Payment Method
            <select name="payment_method" required>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="online">Online</option>
            </select>
          </label>
          <label>
            Amount Paid
            <input name="amount_paid" type="text" required placeholder="e.g. 10.0000" />
          </label>
          <button type="submit" disabled={createTxMutation.isPending}>
            {createTxMutation.isPending ? 'Creating…' : 'Create Transaction'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading transactions…</p>}
      {isError && <p role="alert" className="error">Failed to load POS transactions. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="POS transactions">
          <thead>
            <tr>
              <th scope="col">Transaction #</th>
              <th scope="col">Terminal</th>
              <th scope="col">Status</th>
              <th scope="col">Subtotal</th>
              <th scope="col">Total</th>
              <th scope="col">Paid</th>
              <th scope="col">Change</th>
              <th scope="col">Created</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {transactions.length === 0 ? (
              <tr>
                <td colSpan={9}>No POS transactions found.</td>
              </tr>
            ) : (
              transactions.map((tx) => (
                <tr key={tx.id}>
                  <td>{tx.transaction_number}</td>
                  <td>{tx.terminal_id}</td>
                  <td>{tx.status}</td>
                  <td>{tx.subtotal}</td>
                  <td>{tx.total_amount}</td>
                  <td>{tx.amount_paid}</td>
                  <td>{tx.change_due}</td>
                  <td>{tx.created_at}</td>
                  <td>
                    {(tx.status === 'open' || tx.status === 'paid') && (
                      <button
                        onClick={() => voidMutation.mutate(tx.id)}
                        disabled={voidMutation.isPending}
                        aria-label={`Void transaction ${tx.transaction_number}`}
                      >
                        Void
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      <h2 style={{ marginTop: '2rem' }}>Sessions</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowSessionForm(!showSessionForm)} aria-expanded={showSessionForm}>
          {showSessionForm ? 'Cancel' : 'Open Session'}
        </button>
      </div>

      {sessionFormSuccess && <p role="alert" className="success">{sessionFormSuccess}</p>}
      {sessionFormError && <p role="alert" className="error">{sessionFormError}</p>}

      {showSessionForm && (
        <form onSubmit={handleSessionSubmit} aria-label="Open POS session" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Terminal ID
            <input name="terminal_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Opening Balance
            <input name="opening_balance" type="text" required placeholder="e.g. 500.0000" />
          </label>
          <button type="submit" disabled={openSessionMutation.isPending}>
            {openSessionMutation.isPending ? 'Opening…' : 'Open Session'}
          </button>
        </form>
      )}

      {sessionLoading && <p role="status">Loading sessions…</p>}
      {sessionError && <p role="alert" className="error">Failed to load sessions. Please try again.</p>}

      {!sessionLoading && !sessionError && (
        <table aria-label="POS sessions">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Terminal</th>
              <th scope="col">Status</th>
              <th scope="col">Opening Balance</th>
              <th scope="col">Closing Balance</th>
              <th scope="col">Opened At</th>
              <th scope="col">Closed At</th>
            </tr>
          </thead>
          <tbody>
            {sessions.length === 0 ? (
              <tr>
                <td colSpan={7}>No sessions found.</td>
              </tr>
            ) : (
              sessions.map((session) => (
                <tr key={session.id}>
                  <td>{session.id}</td>
                  <td>{session.terminal_id}</td>
                  <td>{session.status}</td>
                  <td>{session.opening_balance}</td>
                  <td>{session.closing_balance ?? '—'}</td>
                  <td>{session.opened_at}</td>
                  <td>{session.closed_at ?? '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
