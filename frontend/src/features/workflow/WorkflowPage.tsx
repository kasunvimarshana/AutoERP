import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import workflowApi, { type CreateWorkflowPayload, type CreateWorkflowInstancePayload } from '@/api/workflow'

/**
 * WorkflowPage — workflow engine management overview.
 *
 * Displays workflow definitions and instances.
 * Workflows follow the State → Event → Transition → Guard → Action model.
 */
export default function WorkflowPage() {
  const queryClient = useQueryClient()
  const [showWorkflowForm, setShowWorkflowForm] = useState(false)
  const [showInstanceForm, setShowInstanceForm] = useState(false)
  const [workflowFormError, setWorkflowFormError] = useState<string | null>(null)
  const [workflowFormSuccess, setWorkflowFormSuccess] = useState<string | null>(null)
  const [instanceFormError, setInstanceFormError] = useState<string | null>(null)
  const [instanceFormSuccess, setInstanceFormSuccess] = useState<string | null>(null)

  const { data: workflowData, isLoading: workflowLoading, isError: workflowError } = useQuery({
    queryKey: ['workflow', 'workflows'],
    queryFn: () => workflowApi.listWorkflows({ per_page: 20 }),
  })

  const { data: instanceData, isLoading: instanceLoading, isError: instanceError } = useQuery({
    queryKey: ['workflow', 'instances'],
    queryFn: () => workflowApi.listInstances({ per_page: 20 }),
  })

  const createWorkflowMutation = useMutation({
    mutationFn: (payload: CreateWorkflowPayload) => workflowApi.createWorkflow(payload),
    onSuccess: () => {
      setWorkflowFormSuccess('Workflow created successfully.')
      setWorkflowFormError(null)
      setShowWorkflowForm(false)
      queryClient.invalidateQueries({ queryKey: ['workflow', 'workflows'] })
    },
    onError: () => {
      setWorkflowFormError('Failed to create workflow. Please check your inputs and try again.')
      setWorkflowFormSuccess(null)
    },
  })

  const deleteWorkflowMutation = useMutation({
    mutationFn: (id: number) => workflowApi.deleteWorkflow(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['workflow', 'workflows'] }),
  })

  const createInstanceMutation = useMutation({
    mutationFn: (payload: CreateWorkflowInstancePayload) => workflowApi.createInstance(payload),
    onSuccess: () => {
      setInstanceFormSuccess('Workflow instance created successfully.')
      setInstanceFormError(null)
      setShowInstanceForm(false)
      queryClient.invalidateQueries({ queryKey: ['workflow', 'instances'] })
    },
    onError: () => {
      setInstanceFormError('Failed to create workflow instance. Please check your inputs and try again.')
      setInstanceFormSuccess(null)
    },
  })

  const handleWorkflowSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setWorkflowFormError(null)
    setWorkflowFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreateWorkflowPayload = {
      name: formData.get('name') as string,
      entity_type: formData.get('entity_type') as string,
      initial_state: formData.get('initial_state') as string,
      is_active: (formData.get('is_active') as string) === 'on',
    }
    createWorkflowMutation.mutate(payload)
  }

  const handleInstanceSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setInstanceFormError(null)
    setInstanceFormSuccess(null)
    const formData = new FormData(e.currentTarget)
    const payload: CreateWorkflowInstancePayload = {
      workflow_definition_id: parseInt(formData.get('workflow_definition_id') as string, 10),
      entity_id: parseInt(formData.get('entity_id') as string, 10),
      entity_type: formData.get('entity_type') as string,
    }
    createInstanceMutation.mutate(payload)
  }

  const handleDeleteWorkflow = (id: number) => {
    if (window.confirm('Are you sure you want to delete this workflow?')) {
      deleteWorkflowMutation.mutate(id)
    }
  }

  const workflows = workflowData?.data?.data ?? []
  const instances = instanceData?.data?.data ?? []

  return (
    <section aria-label="Workflow Engine">
      <h1>Workflow Engine</h1>
      <p>State machine flows, approval chains, escalation rules, and SLA enforcement.</p>

      <h2>Workflow Definitions</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowWorkflowForm(!showWorkflowForm)} aria-expanded={showWorkflowForm}>
          {showWorkflowForm ? 'Cancel' : 'Create Workflow'}
        </button>
      </div>

      {workflowFormSuccess && <p role="alert" className="success">{workflowFormSuccess}</p>}
      {workflowFormError && <p role="alert" className="error">{workflowFormError}</p>}

      {showWorkflowForm && (
        <form onSubmit={handleWorkflowSubmit} aria-label="Create workflow" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Name
            <input name="name" type="text" required placeholder="e.g. Order Approval" />
          </label>
          <label>
            Entity Type
            <input name="entity_type" type="text" required placeholder="e.g. sales_order" />
          </label>
          <label>
            Initial State
            <input name="initial_state" type="text" required placeholder="e.g. draft" />
          </label>
          <label>
            <input name="is_active" type="checkbox" defaultChecked />
            {' '}Active
          </label>
          <button type="submit" disabled={createWorkflowMutation.isPending}>
            {createWorkflowMutation.isPending ? 'Creating…' : 'Create Workflow'}
          </button>
        </form>
      )}

      {workflowLoading && <p role="status">Loading workflows…</p>}
      {workflowError && <p role="alert" className="error">Failed to load workflows. Please try again.</p>}

      {!workflowLoading && !workflowError && (
        <table aria-label="Workflow definitions">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Name</th>
              <th scope="col">Entity Type</th>
              <th scope="col">Initial State</th>
              <th scope="col">Active</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            {workflows.length === 0 ? (
              <tr>
                <td colSpan={6}>No workflows found.</td>
              </tr>
            ) : (
              workflows.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.name}</td>
                  <td>{item.entity_type}</td>
                  <td>{item.initial_state}</td>
                  <td>{item.is_active ? 'Yes' : 'No'}</td>
                  <td>
                    <button
                      onClick={() => handleDeleteWorkflow(item.id)}
                      disabled={deleteWorkflowMutation.isPending}
                      aria-label={`Delete workflow ${item.name}`}
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

      <h2 style={{ marginTop: '2rem' }}>Workflow Instances</h2>

      <div style={{ marginBottom: '1rem' }}>
        <button onClick={() => setShowInstanceForm(!showInstanceForm)} aria-expanded={showInstanceForm}>
          {showInstanceForm ? 'Cancel' : 'Create Instance'}
        </button>
      </div>

      {instanceFormSuccess && <p role="alert" className="success">{instanceFormSuccess}</p>}
      {instanceFormError && <p role="alert" className="error">{instanceFormError}</p>}

      {showInstanceForm && (
        <form onSubmit={handleInstanceSubmit} aria-label="Create workflow instance" style={{ marginBottom: '1.5rem', display: 'grid', gap: '0.5rem', maxWidth: '480px' }}>
          <label>
            Workflow Definition ID
            <input name="workflow_definition_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Entity ID
            <input name="entity_id" type="number" min="1" required placeholder="e.g. 1" />
          </label>
          <label>
            Entity Type
            <input name="entity_type" type="text" required placeholder="e.g. sales_order" />
          </label>
          <button type="submit" disabled={createInstanceMutation.isPending}>
            {createInstanceMutation.isPending ? 'Creating…' : 'Create Instance'}
          </button>
        </form>
      )}

      {instanceLoading && <p role="status">Loading workflow instances…</p>}
      {instanceError && <p role="alert" className="error">Failed to load workflow instances. Please try again.</p>}

      {!instanceLoading && !instanceError && (
        <table aria-label="Workflow instances">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Workflow Definition ID</th>
              <th scope="col">Entity ID</th>
              <th scope="col">Entity Type</th>
              <th scope="col">Current State</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            {instances.length === 0 ? (
              <tr>
                <td colSpan={6}>No workflow instances found.</td>
              </tr>
            ) : (
              instances.map((item) => (
                <tr key={item.id}>
                  <td>{item.id}</td>
                  <td>{item.workflow_definition_id}</td>
                  <td>{item.entity_id}</td>
                  <td>{item.entity_type}</td>
                  <td>{item.current_state}</td>
                  <td>{item.status}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      )}
    </section>
  )
}
