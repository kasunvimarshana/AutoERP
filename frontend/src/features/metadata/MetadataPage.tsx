import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import metadataApi, { type CreateMetadataFieldPayload } from '@/api/metadata'

/**
 * MetadataPage — custom fields and dynamic forms management overview.
 *
 * Displays a paginated list of metadata field definitions.
 * All configurable logic is database-driven and runtime-resolvable.
 */
export default function MetadataPage() {
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['metadata', 'fields'],
    queryFn: () => metadataApi.listFields({ per_page: 20 }),
  })

  const createMutation = useMutation({
    mutationFn: (payload: CreateMetadataFieldPayload) => metadataApi.createField(payload),
    onSuccess: () => {
      setFormSuccess('Custom field created successfully.')
      setFormError(null)
      setShowForm(false)
      queryClient.invalidateQueries({ queryKey: ['metadata', 'fields'] })
    },
    onError: () => {
      setFormError('Failed to create custom field. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => metadataApi.deleteField(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['metadata', 'fields'] })
    },
  })

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateMetadataFieldPayload = {
      entity_type: fd.get('entity_type') as string,
      field_name: fd.get('field_name') as string,
      field_type: fd.get('field_type') as string,
      is_required: fd.get('is_required') === 'on',
      default_value: (fd.get('default_value') as string) || undefined,
    }
    createMutation.mutate(payload)
  }

  const handleDelete = (id: number) => {
    if (!window.confirm('Delete this custom field? This action cannot be undone.')) return
    deleteMutation.mutate(id)
  }

  const items = data?.data?.data ?? []

  return (
    <section aria-label="Metadata &amp; Custom Fields">
      <h1>Metadata &amp; Custom Fields</h1>
      <p>Custom fields, dynamic forms, and runtime-configurable validation rules.</p>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowForm(!showForm)} aria-expanded={showForm}>
          {showForm ? 'Cancel' : 'Create Custom Field'}
        </button>
      </div>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      {showForm && (
        <form onSubmit={handleSubmit} aria-label="Create custom field" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Entity Type
            <input name="entity_type" type="text" required placeholder="e.g. product" />
          </label>
          <label>
            Field Name
            <input name="field_name" type="text" required placeholder="e.g. sku_suffix" />
          </label>
          <label>
            Field Type
            <select name="field_type" required>
              <option value="text">text</option>
              <option value="number">number</option>
              <option value="date">date</option>
              <option value="boolean">boolean</option>
              <option value="select">select</option>
              <option value="textarea">textarea</option>
            </select>
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_required" type="checkbox" />
            Required
          </label>
          <label>
            Default Value (optional)
            <input name="default_value" type="text" placeholder="e.g. N/A" />
          </label>
          <button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Creating…' : 'Create Custom Field'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading custom fields…</p>}
      {isError && <p role="alert" className="error">Failed to load custom fields. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Custom fields">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Entity Type</th>
              <th scope="col">Field Name</th>
              <th scope="col">Field Type</th>
              <th scope="col">Required</th>
              <th scope="col">Default Value</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={7}>No custom fields found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.entity_type}</td>
                  <td>{item.field_name}</td>
                  <td>{item.field_type}</td>
                  <td>{item.is_required ? 'Yes' : 'No'}</td>
                  <td>{item.default_value ?? '—'}</td>
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
