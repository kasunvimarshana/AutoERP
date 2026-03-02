import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import pluginApi, { type InstallPluginPayload } from '@/api/plugin'

/**
 * PluginPage — plugin marketplace management overview.
 *
 * Displays a paginated list of registered plugins with their
 * version, description, and activation status.
 */
export default function PluginPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['plugin', 'plugins'],
    queryFn: () => pluginApi.listPlugins({ per_page: 20 }),
  })

  const installMutation = useMutation({
    mutationFn: (payload: InstallPluginPayload) => pluginApi.installPlugin(payload),
    onSuccess: () => {
      setFormSuccess('Plugin installed successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['plugin', 'plugins'] })
    },
    onError: () => {
      setFormError('Failed to install plugin. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const uninstallMutation = useMutation({
    mutationFn: (id: number) => pluginApi.uninstallPlugin(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['plugin', 'plugins'] }),
  })

  const enableMutation = useMutation({
    mutationFn: (id: number) => pluginApi.enablePlugin(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['plugin', 'plugins'] }),
  })

  const disableMutation = useMutation({
    mutationFn: (id: number) => pluginApi.disablePlugin(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['plugin', 'plugins'] }),
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: InstallPluginPayload = {
      name: formData.get('name') as string,
      alias: formData.get('alias') as string,
      version: formData.get('version') as string,
      description: (formData.get('description') as string) || undefined,
    }
    installMutation.mutate(payload)
  }

  const handleUninstall = (id: number) => {
    if (window.confirm('Are you sure you want to uninstall this plugin?')) {
      uninstallMutation.mutate(id)
    }
  }

  const items = data?.data?.data ?? []

  return (
    <section aria-label="Plugin Marketplace">
      <h1>Plugin Marketplace</h1>
      <p>Marketplace-ready plugin registry with dependency validation and tenant enablement.</p>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Install Plugin'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Install plugin" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. My Plugin" />
          </label>
          <label>
            Alias
            <input name="alias" type="text" required placeholder="e.g. my-plugin" />
          </label>
          <label>
            Version
            <input name="version" type="text" required placeholder="e.g. 1.0.0" />
          </label>
          <label>
            Description (optional)
            <input name="description" type="text" placeholder="Optional description" />
          </label>
          <button type="submit" disabled={installMutation.isPending}>
            {installMutation.isPending ? 'Installing…' : 'Install Plugin'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading plugins…</p>}
      {isError && <p role="alert" className="error">Failed to load plugins. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Plugins">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Alias</th>
              <th scope="col">Version</th>
              <th scope="col">Description</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={7}>No plugins found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.alias}</td>
                  <td>{item.version}</td>
                  <td>{item.description ?? '—'}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    {!item.is_active && (
                      <button
                        onClick={() => enableMutation.mutate(item.id)}
                        disabled={enableMutation.isPending}
                        aria-label={`Enable plugin ${item.name}`}
                      >
                        Enable
                      </button>
                    )}
                    {item.is_active && (
                      <button
                        onClick={() => disableMutation.mutate(item.id)}
                        disabled={disableMutation.isPending}
                        aria-label={`Disable plugin ${item.name}`}
                      >
                        Disable
                      </button>
                    )}
                    {' '}
                    <button
                      onClick={() => handleUninstall(item.id)}
                      disabled={uninstallMutation.isPending}
                      aria-label={`Uninstall plugin ${item.name}`}
                    >
                      Uninstall
                    </button>
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
