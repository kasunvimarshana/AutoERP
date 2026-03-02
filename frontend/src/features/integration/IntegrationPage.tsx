import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import integrationApi, { type RegisterWebhookPayload } from '@/api/integration'

/**
 * IntegrationPage — webhook and integration management overview.
 *
 * Displays a paginated list of registered webhook endpoints
 * with their subscribed events and active status.
 */
export default function IntegrationPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)
  const [dispatchSuccess, setDispatchSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['integration', 'webhooks'],
    queryFn: () => integrationApi.listWebhooks({ per_page: 20 }),
  })

  const { data: logData, isLoading: logLoading, isError: logError } = useQuery({
    queryKey: ['integration', 'logs'],
    queryFn: () => integrationApi.listLogs({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: RegisterWebhookPayload) => integrationApi.createWebhook(payload),
    onSuccess: () => {
      setFormSuccess('Webhook created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['integration', 'webhooks'] })
    },
    onError: () => {
      setFormError('Failed to create webhook. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => integrationApi.deleteWebhook(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['integration', 'webhooks'] }),
  })

  const dispatchMutation = useMutation({
    mutationFn: (id: number) => integrationApi.dispatchWebhook(id, { test: true }),
    onSuccess: () => {
      setDispatchSuccess('Test dispatch sent successfully.')
      queryClient.invalidateQueries({ queryKey: ['integration', 'logs'] })
    },
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: RegisterWebhookPayload = {
      name: formData.get('name') as string,
      url: formData.get('url') as string,
      events: (formData.get('events') as string).split(',').map((ev) => ev.trim()).filter(Boolean),
      is_active: (formData.get('is_active') as string) === 'on',
    }
    createMutation.mutate(payload)
  }

  const handleDelete = (id: number) => {
    if (window.confirm('Are you sure you want to delete this webhook?')) {
      deleteMutation.mutate(id)
    }
  }

  const items = data?.data?.data ?? []
  const logs = logData?.data?.data ?? []

  return (
    <section aria-label="Integration &amp; Webhooks">
      <h1>Integration &amp; Webhooks</h1>
      <p>Webhooks, event publishing, and third-party ERP/CRM connectors.</p>

      <h2>Webhook Endpoints</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Webhook'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}
      {dispatchSuccess && <p role="alert" className="success">{dispatchSuccess}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create webhook" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Order Events" />
          </label>
          <label>
            URL
            <input name="url" type="url" required placeholder="https://example.com/webhook" />
          </label>
          <label>
            Events (comma-separated)
            <input name="events" type="text" required placeholder="e.g. order.created, order.updated" />
          </label>
          <label>
            <input name="is_active" type="checkbox" defaultChecked />
            {' '}Active
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Webhook'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading webhook endpoints…</p>}
      {isError && <p role="alert" className="error">Failed to load webhook endpoints. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Webhook endpoints">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">URL</th>
              <th scope="col">Events</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={6}>No webhook endpoints found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.url}</td>
                  <td>{item.events.join(', ')}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => dispatchMutation.mutate(item.id)}
                      disabled={dispatchMutation.isPending}
                      aria-label={`Dispatch test to webhook ${item.name}`}
                    >
                      Dispatch Test
                    </button>
                    {' '}
                    <button
                      onClick={() => handleDelete(item.id)}
                      disabled={deleteMutation.isPending}
                      aria-label={`Delete webhook ${item.name}`}
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

      <h2 style={{ marginTop: '2rem' }}>Integration Logs</h2>

      {logLoading && <p role="status">Loading integration logs…</p>}
      {logError && <p role="alert" className="error">Failed to load integration logs. Please try again.</p>}

      {!logLoading && !logError && (
        <table aria-label="Integration logs">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Event</th>
              <th scope="col">Status</th>
              <th scope="col">Response Code</th>
              <th scope="col">Created At</th>
            </tr>
          </thead>
          <tbody>
            {logs.length === 0 ? (
              <tr>
                <td colSpan={5}>No integration logs found.</td>
              </tr>
            ) : (
              logs.map((log) => (
                <tr key={log.id}>
                  <td>{log.id}</td>
                  <td>{log.event}</td>
                  <td>{log.status}</td>
                  <td>{log.response_code ?? '—'}</td>
                  <td>{log.created_at}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
