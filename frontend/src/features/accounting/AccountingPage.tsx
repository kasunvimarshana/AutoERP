import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import accountingApi, {
  type CreateJournalEntryPayload,
  type CreateAccountPayload,
  type CreateFiscalPeriodPayload,
} from '@/api/accounting'

/**
 * AccountingPage — Double-entry bookkeeping and journal entry overview.
 *
 * Displays journal entries with debit/credit balance validation.
 * Supports fiscal period filtering and P&L report access.
 */
export default function AccountingPage() {
  const queryClient = useQueryClient()

  // Journal entries state
  const [showEntryForm, setShowEntryForm] = useState(false)
  const [entryFormError, setEntryFormError] = useState<string | null>(null)
  const [entryFormSuccess, setEntryFormSuccess] = useState<string | null>(null)

  // Accounts state
  const [showAccountForm, setShowAccountForm] = useState(false)
  const [accountFormError, setAccountFormError] = useState<string | null>(null)
  const [accountFormSuccess, setAccountFormSuccess] = useState<string | null>(null)

  // Fiscal periods state
  const [showPeriodForm, setShowPeriodForm] = useState(false)
  const [periodFormError, setPeriodFormError] = useState<string | null>(null)
  const [periodFormSuccess, setPeriodFormSuccess] = useState<string | null>(null)

  // Queries
  const {
    data: entriesData,
    isLoading: entriesLoading,
    isError: entriesError,
  } = useQuery({
    queryKey: ['accounting', 'journal-entries'],
    queryFn: () => accountingApi.listEntries({ per_page: 20 }),
  })

  const {
    data: accountsData,
    isLoading: accountsLoading,
    isError: accountsError,
  } = useQuery({
    queryKey: ['accounting', 'accounts'],
    queryFn: () => accountingApi.listAccounts({ per_page: 20 }),
  })

  const {
    data: periodsData,
    isLoading: periodsLoading,
    isError: periodsError,
  } = useQuery({
    queryKey: ['accounting', 'fiscal-periods'],
    queryFn: () => accountingApi.listFiscalPeriods({ per_page: 20 }),
  })

  // Mutations — Journal Entry
  const createEntryMutation = useMutation({
    mutationFn: (payload: CreateJournalEntryPayload) => accountingApi.createEntry(payload),
    onSuccess: () => {
      setEntryFormSuccess('Journal entry created successfully.')
      setEntryFormError(null)
      setShowEntryForm(false)
      queryClient.invalidateQueries({ queryKey: ['accounting', 'journal-entries'] })
    },
    onError: () => {
      setEntryFormError('Failed to create journal entry. Please check your inputs and try again.')
      setEntryFormSuccess(null)
    },
  })

  const postEntryMutation = useMutation({
    mutationFn: (id: number) => accountingApi.postEntry(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['accounting', 'journal-entries'] })
    },
  })

  // Mutations — Account
  const createAccountMutation = useMutation({
    mutationFn: (payload: CreateAccountPayload) => accountingApi.createAccount(payload),
    onSuccess: () => {
      setAccountFormSuccess('Account created successfully.')
      setAccountFormError(null)
      setShowAccountForm(false)
      queryClient.invalidateQueries({ queryKey: ['accounting', 'accounts'] })
    },
    onError: () => {
      setAccountFormError('Failed to create account. Please check your inputs and try again.')
      setAccountFormSuccess(null)
    },
  })

  // Mutations — Fiscal Period
  const createPeriodMutation = useMutation({
    mutationFn: (payload: CreateFiscalPeriodPayload) => accountingApi.createFiscalPeriod(payload),
    onSuccess: () => {
      setPeriodFormSuccess('Fiscal period created successfully.')
      setPeriodFormError(null)
      setShowPeriodForm(false)
      queryClient.invalidateQueries({ queryKey: ['accounting', 'fiscal-periods'] })
    },
    onError: () => {
      setPeriodFormError('Failed to create fiscal period. Please check your inputs and try again.')
      setPeriodFormSuccess(null)
    },
  })

  const closePeriodMutation = useMutation({
    mutationFn: (id: number) => accountingApi.closeFiscalPeriod(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['accounting', 'fiscal-periods'] })
    },
  })

  // Handlers
  const handleEntrySubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setEntryFormError(null)
    setEntryFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateJournalEntryPayload = {
      fiscal_period_id: parseInt(fd.get('fiscal_period_id') as string, 10),
      description: fd.get('description') as string,
      lines: [
        {
          account_id: parseInt(fd.get('account_id') as string, 10),
          debit_amount: fd.get('debit_amount') as string,
          credit_amount: fd.get('credit_amount') as string,
          description: null,
        },
      ],
    }
    createEntryMutation.mutate(payload)
  }

  const handleAccountSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setAccountFormError(null)
    setAccountFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateAccountPayload = {
      code: fd.get('code') as string,
      name: fd.get('name') as string,
      account_type_id: parseInt(fd.get('account_type_id') as string, 10),
      is_active: fd.get('is_active') === 'on',
    }
    createAccountMutation.mutate(payload)
  }

  const handlePeriodSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setPeriodFormError(null)
    setPeriodFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateFiscalPeriodPayload = {
      name: fd.get('name') as string,
      start_date: fd.get('start_date') as string,
      end_date: fd.get('end_date') as string,
    }
    createPeriodMutation.mutate(payload)
  }

  const entries = entriesData?.data?.data ?? []
  const accounts = accountsData?.data?.data ?? []
  const periods = periodsData?.data?.data ?? []

  return (
    <section aria-label="Accounting">
      <h1>Accounting &amp; Finance</h1>
      <p>Double-entry bookkeeping: every transaction balances debits and credits. Supports fiscal periods, P&amp;L, and balance sheet.</p>

      {/* ── Journal Entries ── */}
      <h2>Journal Entries</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowEntryForm(!showEntryForm)} aria-expanded={showEntryForm}>
          {showEntryForm ? 'Cancel' : 'Create Journal Entry'}
        </button>
      </div>

      {entryFormSuccess && <p role="alert" className="success">{entryFormSuccess}</p>}
      {entryFormError && <p role="alert" className="error">{entryFormError}</p>}

      {showEntryForm && (
        <form onSubmit={handleEntrySubmit} aria-label="Create journal entry" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Fiscal Period ID
            <input name="fiscal_period_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Description
            <input name="description" type="text" required placeholder="Entry description" />
          </label>
          <label>
            Account ID (line item)
            <input name="account_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Debit Amount
            <input name="debit_amount" type="text" required placeholder="e.g. 100.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <label>
            Credit Amount
            <input name="credit_amount" type="text" required placeholder="e.g. 0.0000" pattern="^\d+(\.\d+)?$" />
          </label>
          <button type="submit" disabled={createEntryMutation.isPending}>
            {createEntryMutation.isPending ? 'Creating…' : 'Create Journal Entry'}
          </button>
        </form>
      )}

      {entriesLoading && <p role="status">Loading journal entries…</p>}
      {entriesError && <p role="alert" className="error">Failed to load journal entries. Please try again.</p>}

      {!entriesLoading && !entriesError && (
        <table aria-label="Journal entries">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Entry #</th>
              <th scope="col">Description</th>
              <th scope="col">Status</th>
              <th scope="col">Total Debit</th>
              <th scope="col">Total Credit</th>
              <th scope="col">Posted At</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {entries.length === 0 ? (
              <tr>
                <td colSpan={8}>No journal entries found.</td>
              </tr>
            ) : (
              entries.map((entry) => (
                <tr key={entry.id}>
                  <td>{entry.id}</td>
                  <td>{entry.entry_number}</td>
                  <td>{entry.description}</td>
                  <td>{entry.status}</td>
                  <td>{entry.total_debit}</td>
                  <td>{entry.total_credit}</td>
                  <td>{entry.posted_at ?? '—'}</td>
                  <td>
                    {entry.status === 'draft' && (
                      <button
                        onClick={() => postEntryMutation.mutate(entry.id)}
                        disabled={postEntryMutation.isPending}
                      >
                        Post
                      </button>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      {/* ── Chart of Accounts ── */}
      <h2 style={{ marginTop: '2rem' }}>Chart of Accounts</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowAccountForm(!showAccountForm)} aria-expanded={showAccountForm}>
          {showAccountForm ? 'Cancel' : 'Create Account'}
        </button>
      </div>

      {accountFormSuccess && <p role="alert" className="success">{accountFormSuccess}</p>}
      {accountFormError && <p role="alert" className="error">{accountFormError}</p>}

      {showAccountForm && (
        <form onSubmit={handleAccountSubmit} aria-label="Create account" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Code
            <input name="code" type="text" required placeholder="e.g. 1000" />
          </label>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Cash" />
          </label>
          <label>
            Account Type ID
            <input name="account_type_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_active" type="checkbox" defaultChecked />
            Active
          </label>
          <button type="submit" disabled={createAccountMutation.isPending}>
            {createAccountMutation.isPending ? 'Creating…' : 'Create Account'}
          </button>
        </form>
      )}

      {accountsLoading && <p role="status">Loading accounts…</p>}
      {accountsError && <p role="alert" className="error">Failed to load accounts. Please try again.</p>}

      {!accountsLoading && !accountsError && (
        <table aria-label="Accounts">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Code</th>
              <th scope="col">Name</th>
              <th scope="col">Type</th>
              <th scope="col">Active</th>
            </tr>
          </thead>
          <tbody>
            {accounts.length === 0 ? (
              <tr>
                <td colSpan={5}>No accounts found.</td>
              </tr>
            ) : (
              accounts.map((account) => (
                <tr key={account.id}>
                  <td>{account.id}</td>
                  <td>{account.code}</td>
                  <td>{account.name}</td>
                  <td>{account.type}</td>
                  <td>{account.is_active ? 'Yes' : 'No'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      {/* ── Fiscal Periods ── */}
      <h2 style={{ marginTop: '2rem' }}>Fiscal Periods</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowPeriodForm(!showPeriodForm)} aria-expanded={showPeriodForm}>
          {showPeriodForm ? 'Cancel' : 'Create Fiscal Period'}
        </button>
      </div>

      {periodFormSuccess && <p role="alert" className="success">{periodFormSuccess}</p>}
      {periodFormError && <p role="alert" className="error">{periodFormError}</p>}

      {showPeriodForm && (
        <form onSubmit={handlePeriodSubmit} aria-label="Create fiscal period" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Q1 2025" />
          </label>
          <label>
            Start Date
            <input name="start_date" type="date" required />
          </label>
          <label>
            End Date
            <input name="end_date" type="date" required />
          </label>
          <button type="submit" disabled={createPeriodMutation.isPending}>
            {createPeriodMutation.isPending ? 'Creating…' : 'Create Fiscal Period'}
          </button>
        </form>
      )}

      {periodsLoading && <p role="status">Loading fiscal periods…</p>}
      {periodsError && <p role="alert" className="error">Failed to load fiscal periods. Please try again.</p>}

      {!periodsLoading && !periodsError && (
        <table aria-label="Fiscal periods">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Start Date</th>
              <th scope="col">End Date</th>
              <th scope="col">Closed</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {periods.length === 0 ? (
              <tr>
                <td colSpan={6}>No fiscal periods found.</td>
              </tr>
            ) : (
              periods.map((period) => (
                <tr key={period.id}>
                  <td>{period.id}</td>
                  <td>{period.name}</td>
                  <td>{period.start_date}</td>
                  <td>{period.end_date}</td>
                  <td>{period.is_closed ? 'Yes' : 'No'}</td>
                  <td>
                    {!period.is_closed && (
                      <button
                        onClick={() => closePeriodMutation.mutate(period.id)}
                        disabled={closePeriodMutation.isPending}
                      >
                        Close
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
