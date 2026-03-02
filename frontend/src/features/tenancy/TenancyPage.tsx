import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import tenancyApi, { type CreateTenantPayload } from '@/api/tenancy'

/**
 * TenancyPage — tenant management overview for platform administrators.
 *
 * Displays a paginated list of registered tenants with their slug, domain,
 * active status, and pharmaceutical compliance mode flag.
 */
export default function TenancyPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['tenancy', 'tenants'],
    queryFn: () => tenancyApi.listTenants({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: CreateTenantPayload) => tenancyApi.createTenant(payload),
    onSuccess: () => {
      setFormSuccess('Tenant created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['tenancy', 'tenants'] })
    },
    onError: () => {
      setFormError('Failed to create tenant. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => tenancyApi.deleteTenant(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tenancy', 'tenants'] })
    },
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateTenantPayload = {
      name: fd.get('name') as string,
      slug: fd.get('slug') as string,
      domain: (fd.get('domain') as string) || undefined,
      is_active: fd.get('is_active') === 'on',
      pharma_compliance_mode: fd.get('pharma_compliance_mode') === 'on',
    }
    createMutation.mutate(payload)
  }

  const handleDelete = (id: number) => {
    if (!window.confirm('Delete this tenant? This action cannot be undone.')) return
    deleteMutation.mutate(id)
  }

  const items = data?.data?.data ?? []

  return (
    <section aria-label="Tenant Management">
      <h1>Tenant Management</h1>
      <p>Platform-level multi-tenant registry. Each tenant owns all data beneath it via row-level isolation.</p>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Tenant'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create tenant" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Acme Inc." />
          </label>
          <label>
            Slug
            <input name="slug" type="text" required placeholder="e.g. acme-inc" />
          </label>
          <label>
            Domain (optional)
            <input name="domain" type="text" placeholder="e.g. acme.example.com" />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_active" type="checkbox" defaultChecked />
            Active
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="pharma_compliance_mode" type="checkbox" />
            Pharmaceutical Compliance Mode
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Tenant'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading tenants…</p>}
      {isError && <p role="alert" className="error">Failed to load tenants. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Tenants">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Slug</th>
              <th scope="col">Domain</th>
              <th scope="col">Active</th>
              <th scope="col">Pharma Mode</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={7}>No tenants found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.slug}</td>
                  <td>{item.domain ?? '—'}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>{item.pharma_compliance_mode ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => handleDelete(item.id)}
                      disabled={deleteMutation.isPending}
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
    </section>
  )
}
