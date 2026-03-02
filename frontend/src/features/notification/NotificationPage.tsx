import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import notificationApi, { type CreateNotificationTemplatePayload, type SendNotificationPayload } from '@/api/notification'

/**
 * NotificationPage — notification template management overview.
 *
 * Displays a paginated list of notification templates across
 * all supported channels: email, SMS, push, and in-app.
 */
export default function NotificationPage() {
  const queryClient = useQueryClient()
  const [showTemplateForm, setShowTemplateForm] = useState(false)
  const [showSendForm, setShowSendForm] = useState(false)
  const [templateFormError, setTemplateFormError] = useState<string | null>(null)
  const [templateFormSuccess, setTemplateFormSuccess] = useState<string | null>(null)
  const [sendFormError, setSendFormError] = useState<string | null>(null)
  const [sendFormSuccess, setSendFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['notification', 'templates'],
    queryFn: () => notificationApi.listTemplates({ per_page: 20 }),
  })

  const { data: logData, isLoading: logLoading, isError: logError } = useQuery({
    queryKey: ['notification', 'logs'],
    queryFn: () => notificationApi.listLogs({ per_page: 20 }),
  })

  const createTemplateMutation = useMutation({
    mutationFn: (payload: CreateNotificationTemplatePayload) => notificationApi.createTemplate(payload),
    onSuccess: () => {
      setTemplateFormSuccess('Notification template created successfully.')
      setTemplateFormError(null)
      setShowTemplateForm(false)
      queryClient.invalidateQueries({ queryKey: ['notification', 'templates'] })
    },
    onError: () => {
      setTemplateFormError('Failed to create notification template. Please check your inputs and try again.')
      setTemplateFormSuccess(null)
    },
  })

  const deleteTemplateMutation = useMutation({
    mutationFn: (id: number) => notificationApi.deleteTemplate(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notification', 'templates'] }),
  })

  const sendMutation = useMutation({
    mutationFn: (payload: SendNotificationPayload) => notificationApi.sendNotification(payload),
    onSuccess: () => {
      setSendFormSuccess('Notification sent successfully.')
      setSendFormError(null)
      setShowSendForm(false)
      queryClient.invalidateQueries({ queryKey: ['notification', 'logs'] })
    },
    onError: () => {
      setSendFormError('Failed to send notification. Please check your inputs and try again.')
      setSendFormSuccess(null)
    },
  })

  const handleTemplateSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setTemplateFormError(null)
    setTemplateFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreateNotificationTemplatePayload = {
      name: formData.get('name') as string,
      slug: formData.get('slug') as string,
      channel: formData.get('channel') as CreateNotificationTemplatePayload['channel'],
      subject: (formData.get('subject') as string) || undefined,
      body_template: formData.get('body_template') as string,
      is_active: (formData.get('is_active') as string) === 'on',
    }
    createTemplateMutation.mutate(payload)
  }

  const handleSendSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setSendFormError(null)
    setSendFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: SendNotificationPayload = {
      template_slug: formData.get('template_slug') as string,
      recipient_id: parseInt(formData.get('recipient_id') as string, 10),
    }
    sendMutation.mutate(payload)
  }

  const handleDeleteTemplate = (id: number) => {
    if (window.confirm('Are you sure you want to delete this template?')) {
      deleteTemplateMutation.mutate(id)
    }
  }

  const items = data?.data?.data ?? []
  const logs = logData?.data?.data ?? []

  return (
    <section aria-label="Notification Engine">
      <h1>Notification Engine</h1>
      <p>Multi-channel notification engine: email, SMS, push, and in-app.</p>

      <h2>Notification Templates</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowTemplateForm(!showTemplateForm)} aria-expanded={showTemplateForm}>
          {showTemplateForm ? 'Cancel' : 'Create Template'}
        </button>
        {' '}
        <button onClick={() => setShowSendForm(!showSendForm)} aria-expanded={showSendForm}>
          {showSendForm ? 'Cancel' : 'Send Notification'}
        </button>
      </div>

      {templateFormSuccess && <p role="alert" className="success">{templateFormSuccess}</p>}
      {templateFormError && <p role="alert" className="error">{templateFormError}</p>}
      {sendFormSuccess && <p role="alert" className="success">{sendFormSuccess}</p>}
      {sendFormError && <p role="alert" className="error">{sendFormError}</p>}

      {showTemplateForm && (
        <form onSubmit={handleTemplateSubmit} aria-label="Create notification template" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Welcome Email" />
          </label>
          <label>
            Slug
            <input name="slug" type="text" required placeholder="e.g. welcome-email" />
          </label>
          <label>
            Channel
            <select name="channel" required>
              <option value="email">Email</option>
              <option value="sms">SMS</option>
              <option value="push">Push</option>
              <option value="in_app">In-App</option>
            </select>
          </label>
          <label>
            Subject (optional)
            <input name="subject" type="text" placeholder="e.g. Welcome to our platform" />
          </label>
          <label>
            Body Template
            <textarea name="body_template" rows={4} required placeholder="Hello {{name}}, welcome!" />
          </label>
          <label>
            <input name="is_active" type="checkbox" defaultChecked />
            {' '}Active
          </label>
          <button type="submit" disabled={createTemplateMutation.isPending}>
            {createTemplateMutation.isPending ? 'Creating…' : 'Create Template'}
          </button>
        </form>
      )}

      {showSendForm && (
        <form onSubmit={handleSendSubmit} aria-label="Send notification" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Template Slug
            <input name="template_slug" type="text" required placeholder="e.g. welcome-email" />
          </label>
          <label>
            Recipient ID
            <input name="recipient_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <button type="submit" disabled={sendMutation.isPending}>
            {sendMutation.isPending ? 'Sending…' : 'Send Notification'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading notification templates…</p>}
      {isError && <p role="alert" className="error">Failed to load notification templates. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Notification templates">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Slug</th>
              <th scope="col">Channel</th>
              <th scope="col">Subject</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={7}>No notification templates found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.slug}</td>
                  <td>{item.channel}</td>
                  <td>{item.subject ?? '—'}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => handleDeleteTemplate(item.id)}
                      disabled={deleteTemplateMutation.isPending}
                      aria-label={`Delete template ${item.name}`}
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

      <h2 style={{ marginTop: '2rem' }}>Notification Logs</h2>

      {logLoading && <p role="status">Loading notification logs…</p>}
      {logError && <p role="alert" className="error">Failed to load notification logs. Please try again.</p>}

      {!logLoading && !logError && (
        <table aria-label="Notification logs">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Channel</th>
              <th scope="col">Recipient ID</th>
              <th scope="col">Status</th>
              <th scope="col">Sent At</th>
            </tr>
          </thead>
          <tbody>
            {logs.length === 0 ? (
              <tr>
                <td colSpan={5}>No notification logs found.</td>
              </tr>
            ) : (
              logs.map((log) => (
                <tr key={log.id}>
                  <td>{log.id}</td>
                  <td>{log.channel}</td>
                  <td>{log.recipient_id}</td>
                  <td>{log.status}</td>
                  <td>{log.sent_at ?? '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
