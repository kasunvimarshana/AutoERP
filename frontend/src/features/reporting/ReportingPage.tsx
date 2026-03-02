import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import reportingApi, { type CreateReportDefinitionPayload, type ScheduleReportPayload } from '@/api/reporting'

/**
 * ReportingPage — Report definitions and export overview.
 *
 * Displays available report definitions and allows triggering
 * report generation in CSV or PDF format.
 */
export default function ReportingPage() {
  const queryClient = useQueryClient()
  const [showDefForm, setShowDefForm] = useState(false)
  const [showScheduleForm, setShowScheduleForm] = useState(false)
  const [defFormError, setDefFormError] = useState<string | null>(null)
  const [defFormSuccess, setDefFormSuccess] = useState<string | null>(null)
  const [scheduleFormError, setScheduleFormError] = useState<string | null>(null)
  const [scheduleFormSuccess, setScheduleFormSuccess] = useState<string | null>(null)
  const [generateSuccess, setGenerateSuccess] = useState<string | null>(null)

  const { data: defData, isLoading: defLoading, isError: defError } = useQuery({
    queryKey: ['reporting', 'definitions'],
    queryFn: () => reportingApi.listDefinitions({ per_page: 20 }),
  })

  const { data: exportData, isLoading: exportLoading, isError: exportError } = useQuery({
    queryKey: ['reporting', 'exports'],
    queryFn: () => reportingApi.listExports({ per_page: 20 }),
  })

  const { data: scheduleData, isLoading: scheduleLoading, isError: scheduleError } = useQuery({
    queryKey: ['reporting', 'schedules'],
    queryFn: () => reportingApi.listSchedules({ per_page: 20 }),
  })

  const createDefMutation = useMutation({
    mutationFn: (payload: CreateReportDefinitionPayload) => reportingApi.createDefinition(payload),
    onSuccess: () => {
      setDefFormSuccess('Report definition created successfully.')
      setDefFormError(null)
      setShowDefForm(false)
      queryClient.invalidateQueries({ queryKey: ['reporting', 'definitions'] })
    },
    onError: () => {
      setDefFormError('Failed to create report definition. Please check your inputs and try again.')
      setDefFormSuccess(null)
    },
  })

  const deleteDefMutation = useMutation({
    mutationFn: (id: number) => reportingApi.deleteDefinition(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['reporting', 'definitions'] }),
  })

  const generateMutation = useMutation({
    mutationFn: (id: number) => reportingApi.generateReport({ report_definition_id: id, format: 'csv' }),
    onSuccess: () => {
      setGenerateSuccess('Report generation started successfully.')
      queryClient.invalidateQueries({ queryKey: ['reporting', 'exports'] })
    },
  })

  const scheduleMutation = useMutation({
    mutationFn: (payload: ScheduleReportPayload) => reportingApi.scheduleReport(payload),
    onSuccess: () => {
      setScheduleFormSuccess('Report scheduled successfully.')
      setScheduleFormError(null)
      setShowScheduleForm(false)
      queryClient.invalidateQueries({ queryKey: ['reporting', 'schedules'] })
    },
    onError: () => {
      setScheduleFormError('Failed to schedule report. Please check your inputs and try again.')
      setScheduleFormSuccess(null)
    },
  })

  const handleDefSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setDefFormError(null)
    setDefFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreateReportDefinitionPayload = {
      name: formData.get('name') as string,
      slug: formData.get('slug') as string,
      report_type: formData.get('report_type') as string,
      description: (formData.get('description') as string) || undefined,
      is_active: (formData.get('is_active') as string) === 'on',
    }
    createDefMutation.mutate(payload)
  }

  const handleScheduleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setScheduleFormError(null)
    setScheduleFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: ScheduleReportPayload = {
      report_definition_id: parseInt(formData.get('report_definition_id') as string, 10),
      frequency: formData.get('frequency') as string,
    }
    scheduleMutation.mutate(payload)
  }

  const definitions = defData?.data?.data ?? []
  const exports = exportData?.data?.data ?? []
  const schedules = scheduleData?.data?.data ?? []

  return (
    <section aria-label="Reporting">
      <h1>Reporting &amp; Analytics</h1>
      <p>Generate financial statements, inventory reports, and custom exports in CSV or PDF format.</p>

      <h2>Report Definitions</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowDefForm(!showDefForm)} aria-expanded={showDefForm}>
          {showDefForm ? 'Cancel' : 'Create Report Definition'}
        </button>
      </div>

      {defFormSuccess && <p role="alert" className="success">{defFormSuccess}</p>}
      {defFormError && <p role="alert" className="error">{defFormError}</p>}
      {generateSuccess && <p role="alert" className="success">{generateSuccess}</p>}

      {showDefForm && (
        <form onSubmit={handleDefSubmit} aria-label="Create report definition" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Monthly Sales Report" />
          </label>
          <label>
            Slug
            <input name="slug" type="text" required placeholder="e.g. monthly-sales-report" />
          </label>
          <label>
            Report Type
            <input name="report_type" type="text" required placeholder="e.g. sales" />
          </label>
          <label>
            Description (optional)
            <input name="description" type="text" placeholder="Optional description" />
          </label>
          <label>
            <input name="is_active" type="checkbox" defaultChecked />
            {' '}Active
          </label>
          <button type="submit" disabled={createDefMutation.isPending}>
            {createDefMutation.isPending ? 'Creating…' : 'Create Definition'}
          </button>
        </form>
      )}

      {defLoading && <p role="status">Loading report definitions…</p>}
      {defError && <p role="alert" className="error">Failed to load report definitions. Please try again.</p>}

      {!defLoading && !defError && (
        <table aria-label="Report definitions">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Type</th>
              <th scope="col">Slug</th>
              <th scope="col">Description</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {definitions.length === 0 ? (
              <tr>
                <td colSpan={7}>No report definitions found.</td>
              </tr>
            ) : (
              definitions.map((def) => (
                <tr key={def.id}>
                  <td>{def.id}</td>
                  <td>{def.name}</td>
                  <td>{def.report_type}</td>
                  <td>{def.slug}</td>
                  <td>{def.description ?? '—'}</td>
                  <td>{def.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => generateMutation.mutate(def.id)}
                      disabled={generateMutation.isPending}
                      aria-label={`Generate report ${def.name}`}
                    >
                      Generate
                    </button>
                    {' '}
                    <button
                      onClick={() => deleteDefMutation.mutate(def.id)}
                      disabled={deleteDefMutation.isPending}
                      aria-label={`Delete report definition ${def.name}`}
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

      <h2 style={{ marginTop: '2rem' }}>Exports</h2>

      {exportLoading && <p role="status">Loading exports…</p>}
      {exportError && <p role="alert" className="error">Failed to load exports. Please try again.</p>}

      {!exportLoading && !exportError && (
        <table aria-label="Report exports">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Definition ID</th>
              <th scope="col">Status</th>
              <th scope="col">Format</th>
              <th scope="col">Generated At</th>
            </tr>
          </thead>
          <tbody>
            {exports.length === 0 ? (
              <tr>
                <td colSpan={5}>No exports found.</td>
              </tr>
            ) : (
              exports.map((exp) => (
                <tr key={exp.id}>
                  <td>{exp.id}</td>
                  <td>{exp.report_definition_id}</td>
                  <td>{exp.status}</td>
                  <td>{exp.format}</td>
                  <td>{exp.generated_at ?? '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}

      <h2 style={{ marginTop: '2rem' }}>Schedules</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowScheduleForm(!showScheduleForm)} aria-expanded={showScheduleForm}>
          {showScheduleForm ? 'Cancel' : 'Schedule Report'}
        </button>
      </div>

      {scheduleFormSuccess && <p role="alert" className="success">{scheduleFormSuccess}</p>}
      {scheduleFormError && <p role="alert" className="error">{scheduleFormError}</p>}

      {showScheduleForm && (
        <form onSubmit={handleScheduleSubmit} aria-label="Schedule report" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Report Definition ID
            <input name="report_definition_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Frequency
            <select name="frequency" required>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </label>
          <button type="submit" disabled={scheduleMutation.isPending}>
            {scheduleMutation.isPending ? 'Scheduling…' : 'Schedule Report'}
          </button>
        </form>
      )}

      {scheduleLoading && <p role="status">Loading schedules…</p>}
      {scheduleError && <p role="alert" className="error">Failed to load schedules. Please try again.</p>}

      {!scheduleLoading && !scheduleError && (
        <table aria-label="Report schedules">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Definition ID</th>
              <th scope="col">Frequency</th>
              <th scope="col">Active</th>
              <th scope="col">Next Run At</th>
            </tr>
          </thead>
          <tbody>
            {schedules.length === 0 ? (
              <tr>
                <td colSpan={5}>No schedules found.</td>
              </tr>
            ) : (
              schedules.map((schedule) => (
                <tr key={schedule.id}>
                  <td>{schedule.id}</td>
                  <td>{schedule.report_definition_id}</td>
                  <td>{schedule.frequency}</td>
                  <td>{schedule.is_active ? 'Yes' : 'No'}</td>
                  <td>{schedule.next_run_at ?? '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
