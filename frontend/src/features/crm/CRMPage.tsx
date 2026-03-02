import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import crmApi, { type CrmLead } from '@/api/crm'

/**
 * CRMPage — CRM pipeline overview: Leads and Opportunities.
 *
 * Displays the lead funnel and opportunity pipeline following the
 * Lead → Opportunity → Proposal → Closed Won / Closed Lost flow.
 *
 * Includes Create Lead, Delete Lead, Convert Lead, and Close Won / Close Lost actions.
 */
export default function CRMPage() {
  const queryClient = useQueryClient()
  const [showLeadForm, setShowLeadForm] = useState(false)
  const [convertingLeadId, setConvertingLeadId] = useState<number | null>(null)
  const [convertName, setConvertName] = useState('')
  const [formError, setFormError] = useState<string | null>(null)
  const [formSuccess, setFormSuccess] = useState<string | null>(null)

  const leadsQuery = useQuery({
    queryKey: ['crm', 'leads'],
    queryFn: () => crmApi.listLeads({ per_page: 20 }),
  })

  const opportunitiesQuery = useQuery({
    queryKey: ['crm', 'opportunities'],
    queryFn: () => crmApi.listOpportunities({ per_page: 20 }),
  })

  const createLeadMutation = useMutation({
    mutationFn: (payload: Partial<CrmLead>) => crmApi.createLead(payload),
    onSuccess: () => {
      setFormSuccess('Lead created successfully.')
      setFormError(null)
      setShowLeadForm(false)
      queryClient.invalidateQueries({ queryKey: ['crm', 'leads'] })
    },
    onError: () => {
      setFormError('Failed to create lead. Please check your inputs and try again.')
      setFormSuccess(null)
    },
  })

  const deleteLeadMutation = useMutation({
    mutationFn: (id: number) => crmApi.deleteLead(id),
    onSuccess: () => {
      setFormSuccess('Lead deleted successfully.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['crm', 'leads'] })
    },
    onError: () => {
      setFormError('Failed to delete lead.')
      setFormSuccess(null)
    },
  })

  const convertLeadMutation = useMutation({
    mutationFn: ({ id, name }: { id: number; name: string }) =>
      crmApi.convertLead(id, { name }),
    onSuccess: () => {
      setFormSuccess('Lead converted to opportunity successfully.')
      setFormError(null)
      setConvertingLeadId(null)
      setConvertName('')
      queryClient.invalidateQueries({ queryKey: ['crm', 'leads'] })
      queryClient.invalidateQueries({ queryKey: ['crm', 'opportunities'] })
    },
    onError: () => {
      setFormError('Failed to convert lead.')
      setFormSuccess(null)
    },
  })

  const closeWonMutation = useMutation({
    mutationFn: (id: number) => crmApi.closeWon(id),
    onSuccess: () => {
      setFormSuccess('Opportunity marked as Closed Won.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['crm', 'opportunities'] })
    },
    onError: () => {
      setFormError('Failed to close opportunity as won.')
      setFormSuccess(null)
    },
  })

  const closeLostMutation = useMutation({
    mutationFn: (id: number) => crmApi.closeLost(id),
    onSuccess: () => {
      setFormSuccess('Opportunity marked as Closed Lost.')
      setFormError(null)
      queryClient.invalidateQueries({ queryKey: ['crm', 'opportunities'] })
    },
    onError: () => {
      setFormError('Failed to close opportunity as lost.')
      setFormSuccess(null)
    },
  })

  const handleCreateLead = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setFormError(null)
    setFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const estimatedValue = formData.get('estimated_value') as string
    const payload: Partial<CrmLead> = {
      name: formData.get('name') as string,
      email: (formData.get('email') as string) || undefined,
      phone: (formData.get('phone') as string) || undefined,
      company: (formData.get('company') as string) || undefined,
      source: (formData.get('source') as string) || undefined,
      estimated_value: estimatedValue || undefined,
    }
    createLeadMutation.mutate(payload)
  }

  const handleDeleteLead = (lead: CrmLead) => {
    if (!window.confirm(`Delete lead "${lead.name}"? This cannot be undone.`)) return
    setFormError(null)
    setFormSuccess(null)
    deleteLeadMutation.mutate(lead.id)
  }

  const handleConvertSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!convertingLeadId || !convertName.trim()) return
    setFormError(null)
    setFormSuccess(null)
    convertLeadMutation.mutate({ id: convertingLeadId, name: convertName.trim() })
  }

  const leads = leadsQuery.data?.data?.data ?? []
  const opportunities = opportunitiesQuery.data?.data?.data ?? []

  return (
    <section aria-label="CRM">
      <h1>CRM</h1>
      <p>Manage the full customer relationship lifecycle: Lead → Opportunity → Proposal → Closed Won / Closed Lost.</p>

      {formSuccess && <p role="alert" className="success">{formSuccess}</p>}
      {formError && <p role="alert" className="error">{formError}</p>}

      <h2>Leads</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => { setShowLeadForm(!showLeadForm); setConvertingLeadId(null) }} aria-expanded={showLeadForm}>
          {showLeadForm ? 'Cancel' : 'Create Lead'}
        </button>
      </div>

      {showLeadForm && (
        <form onSubmit={handleCreateLead} aria-label="Create lead" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. John Smith" />
          </label>
          <label>
            Email
            <input name="email" type="email" placeholder="e.g. john@example.com" />
          </label>
          <label>
            Phone
            <input name="phone" type="text" placeholder="e.g. +1 555 000 0000" />
          </label>
          <label>
            Company
            <input name="company" type="text" placeholder="e.g. Acme Corp" />
          </label>
          <label>
            Source
            <input name="source" type="text" placeholder="e.g. website, referral" />
          </label>
          <label>
            Estimated Value
            <input name="estimated_value" type="text" placeholder="e.g. 5000.00" pattern="^\d+(\.\d+)?$" />
          </label>
          <button type="submit" disabled={createLeadMutation.isPending}>
            {createLeadMutation.isPending ? 'Creating…' : 'Create Lead'}
          </button>
        </form>
      )}

      {leadsQuery.isLoading && <p role="status">Loading leads…</p>}
      {leadsQuery.isError && <p role="alert" className="error">Failed to load leads.</p>}
      {!leadsQuery.isLoading && !leadsQuery.isError && (
        <table aria-label="CRM leads">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Company</th>
              <th scope="col">Email</th>
              <th scope="col">Status</th>
              <th scope="col">Est. Value</th>
              <th scope="col">Source</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {leads.length === 0 ? (
              <tr><td colSpan={7}>No leads found.</td></tr>
            ) : (
              leads.map((lead) => (
                <tr key={lead.id}>
                  <td>{lead.name}</td>
                  <td>{lead.company ?? '—'}</td>
                  <td>{lead.email ?? '—'}</td>
                  <td>{lead.status}</td>
                  <td>{lead.estimated_value}</td>
                  <td>{lead.source ?? '—'}</td>
                  <td>
                    {lead.status !== 'converted' && (
                      <>
                        <button
                          onClick={() => { setConvertingLeadId(lead.id); setConvertName(''); setShowLeadForm(false) }}
                          aria-label={`Convert lead ${lead.name} to opportunity`}
                        >
                          Convert
                        </button>
                        {' '}
                      </>
                    )}
                    <button
                      onClick={() => handleDeleteLead(lead)}
                      disabled={deleteLeadMutation.isPending}
                      aria-label={`Delete lead ${lead.name}`}
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

      {convertingLeadId !== null && (
        <form onSubmit={handleConvertSubmit} aria-label="Convert lead to opportunity" style={{ marginTop: '1rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <h3>Convert Lead to Opportunity</h3>
          <label>
            Opportunity Name
            <input
              type="text"
              required
              value={convertName}
              onChange={(e) => setConvertName(e.target.value)}
              placeholder="e.g. Enterprise Deal Q3"
            />
          </label>
          <div style={{ display: 'flex', gap: '0.5rem' }}>
            <button type="submit" disabled={convertLeadMutation.isPending}>
              {convertLeadMutation.isPending ? 'Converting…' : 'Convert'}
            </button>
            <button type="button" onClick={() => setConvertingLeadId(null)}>Cancel</button>
          </div>
        </form>
      )}

      <h2>Opportunities</h2>
      {opportunitiesQuery.isLoading && <p role="status">Loading opportunities…</p>}
      {opportunitiesQuery.isError && <p role="alert" className="error">Failed to load opportunities.</p>}
      {!opportunitiesQuery.isLoading && !opportunitiesQuery.isError && (
        <table aria-label="CRM opportunities">
          <thead>
            <tr>
              <th scope="col">Name</th>
              <th scope="col">Status</th>
              <th scope="col">Est. Value</th>
              <th scope="col">Probability</th>
              <th scope="col">Expected Close</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {opportunities.length === 0 ? (
              <tr><td colSpan={6}>No opportunities found.</td></tr>
            ) : (
              opportunities.map((opp) => (
                <tr key={opp.id}>
                  <td>{opp.name}</td>
                  <td>{opp.status}</td>
                  <td>{opp.estimated_value}</td>
                  <td>{opp.probability}%</td>
                  <td>{opp.expected_close_date ?? '—'}</td>
                  <td>
                    {opp.status === 'open' && (
                      <>
                        <button
                          onClick={() => closeWonMutation.mutate(opp.id)}
                          disabled={closeWonMutation.isPending}
                          aria-label={`Mark opportunity ${opp.name} as closed won`}
                        >
                          Close Won
                        </button>
                        {' '}
                        <button
                          onClick={() => closeLostMutation.mutate(opp.id)}
                          disabled={closeLostMutation.isPending}
                          aria-label={`Mark opportunity ${opp.name} as closed lost`}
                        >
                          Close Lost
                        </button>
                      </>
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
