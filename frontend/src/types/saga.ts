export type SagaStepStatus =
  | 'pending'
  | 'running'
  | 'completed'
  | 'failed'
  | 'compensating'
  | 'compensated'
  | 'skipped';

export type SagaStatus =
  | 'pending'
  | 'running'
  | 'completed'
  | 'failed'
  | 'compensating'
  | 'compensated';

export interface SagaStep {
  name: string;
  label: string;
  status: SagaStepStatus;
  started_at: string | null;
  completed_at: string | null;
  error: string | null;
  compensation_error: string | null;
}

export interface SagaExecution {
  id: string;
  saga_type: string;
  status: SagaStatus;
  payload: Record<string, unknown>;
  steps: SagaStep[];
  error: string | null;
  started_at: string;
  completed_at: string | null;
  created_at: string;
  updated_at: string;
}

export const ORDER_SAGA_STEPS: { name: string; label: string }[] = [
  { name: 'reserve_stock', label: 'Reserve Stock' },
  { name: 'process_payment', label: 'Process Payment' },
  { name: 'create_order', label: 'Create Order' },
  { name: 'send_notification', label: 'Send Notification' },
];
