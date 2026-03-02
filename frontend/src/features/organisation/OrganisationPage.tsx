import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import organisationApi, { type CreateOrganisationPayload, type CreateBranchPayload } from '@/api/organisation'

/**
 * OrganisationPage — organisation hierarchy management overview.
 *
 * Displays a paginated list of organisations within the tenant.
 * Reflects the Tenant → Organisation → Branch → Location → Department hierarchy.
 */
export default function OrganisationPage() {
  const queryClient = useQueryClient()

  // Organisation form state
  const [showOrgForm, setShowOrgForm] = useState(false)
  const [orgFormError, setOrgFormError] = useState<string | null>(null)
  const [orgFormSuccess, setOrgFormSuccess] = useState<string | null>(null)

  // Branch form state
  const [showBranchForm, setShowBranchForm] = useState(false)
  const [branchFormError, setBranchFormError] = useState<string | null>(null)
  const [branchFormSuccess, setBranchFormSuccess] = useState<string | null>(null)

  // Queries
  const { data, isLoading, isError } = useQuery({
    queryKey: ['organisation', 'organisations'],
    queryFn: () => organisationApi.listOrganisations({ per_page: 20 }),
  })

  // Mutations — Organisation
  const createOrgMutation = useMutation({
    mutationFn: (payload: CreateOrganisationPayload) => organisationApi.createOrganisation(payload),
    onSuccess: () => {
      setOrgFormSuccess('Organisation created successfully.')
      setOrgFormError(null)
      setShowOrgForm(false)
      queryClient.invalidateQueries({ queryKey: ['organisation', 'organisations'] })
    },
    onError: () => {
      setOrgFormError('Failed to create organisation. Please check your inputs and try again.')
      setOrgFormSuccess(null)
    },
  })

  const deleteOrgMutation = useMutation({
    mutationFn: (id: number) => organisationApi.deleteOrganisation(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['organisation', 'organisations'] })
    },
  })

  // Mutations — Branch
  const createBranchMutation = useMutation({
    mutationFn: ({ orgId, payload }: { orgId: number; payload: CreateBranchPayload }) =>
      organisationApi.createBranch(orgId, payload),
    onSuccess: () => {
      setBranchFormSuccess('Branch created successfully.')
      setBranchFormError(null)
      setShowBranchForm(false)
    },
    onError: () => {
      setBranchFormError('Failed to create branch. Please check your inputs and try again.')
      setBranchFormSuccess(null)
    },
  })

  // Handlers
  const handleOrgSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setOrgFormError(null)
    setOrgFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const payload: CreateOrganisationPayload = {
      name: fd.get('name') as string,
      description: (fd.get('description') as string) || undefined,
      is_active: fd.get('is_active') === 'on',
    }
    createOrgMutation.mutate(payload)
  }

  const handleDeleteOrg = (id: number) => {
    if (!window.confirm('Delete this organisation? This action cannot be undone.')) return
    deleteOrgMutation.mutate(id)
  }

  const handleBranchSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setBranchFormError(null)
    setBranchFormSuccess(null)
    const fd = new FormData(e.currentTarget)
    const orgId = parseInt(fd.get('org_id') as string, 10)
    const payload: CreateBranchPayload = {
      name: fd.get('branch_name') as string,
      address: (fd.get('address') as string) || undefined,
      is_active: fd.get('branch_is_active') === 'on',
    }
    createBranchMutation.mutate({ orgId, payload })
  }

  const items = data?.data?.data ?? []

  return (
    <section aria-label="Organisation Hierarchy">
      <h1>Organisation Hierarchy</h1>
      <p>Tenant → Organisation → Branch → Location → Department hierarchy.</p>

      {/* ── Organisations ── */}
      <h2>Organisations</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowOrgForm(!showOrgForm)} aria-expanded={showOrgForm}>
          {showOrgForm ? 'Cancel' : 'Create Organisation'}
        </button>
      </div>

      {orgFormSuccess && <p role="alert" className="success">{orgFormSuccess}</p>}
      {orgFormError && <p role="alert" className="error">{orgFormError}</p>}

      {showOrgForm && (
        <form onSubmit={handleOrgSubmit} aria-label="Create organisation" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Acme Corp" />
          </label>
          <label>
            Description (optional)
            <input name="description" type="text" placeholder="Brief description" />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="is_active" type="checkbox" defaultChecked />
            Active
          </label>
          <button type="submit" disabled={createOrgMutation.isPending}>
            {createOrgMutation.isPending ? 'Creating…' : 'Create Organisation'}
          </button>
        </form>
      )}

      {isLoading && <p role="status">Loading organisations…</p>}
      {isError && <p role="alert" className="error">Failed to load organisations. Please try again.</p>}

      {!isLoading && !isError && (
        <table aria-label="Organisations">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Description</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {items.length === 0 ? (
              <tr>
                <td colSpan={5}>No organisations found.</td>
              </tr>
            ) : (
              items.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.description ?? '—'}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => handleDeleteOrg(item.id)}
                      disabled={deleteOrgMutation.isPending}
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

      {/* ── Branches ── */}
      <h2 style={{ marginTop: '2rem' }}>Branches</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowBranchForm(!showBranchForm)} aria-expanded={showBranchForm}>
          {showBranchForm ? 'Cancel' : 'Create Branch'}
        </button>
      </div>

      {branchFormSuccess && <p role="alert" className="success">{branchFormSuccess}</p>}
      {branchFormError && <p role="alert" className="error">{branchFormError}</p>}

      {showBranchForm && (
        <form onSubmit={handleBranchSubmit} aria-label="Create branch" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Organisation ID
            <input name="org_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Branch Name
            <input name="branch_name" type="text" required placeholder="e.g. HQ Branch" />
          </label>
          <label>
            Address (optional)
            <input name="address" type="text" placeholder="e.g. 123 Main St" />
          </label>
          <label style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
            <input name="branch_is_active" type="checkbox" defaultChecked />
            Active
          </label>
          <button type="submit" disabled={createBranchMutation.isPending}>
            {createBranchMutation.isPending ? 'Creating…' : 'Create Branch'}
          </button>
        </form>
      )}
    </section>
  )
}
