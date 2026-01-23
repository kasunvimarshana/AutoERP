// Job Card Management Types
export interface JobCard {
  id: number
  uuid: string
  tenant_id: number | null
  job_card_number: string
  appointment_id: number | null
  customer_id: number
  vehicle_id: number
  service_bay_id: number | null
  status: 'draft' | 'open' | 'in_progress' | 'on_hold' | 'completed' | 'invoiced' | 'cancelled'
  priority: 'low' | 'normal' | 'high' | 'urgent'
  opened_at: string | null
  started_at: string | null
  completed_at: string | null
  invoiced_at: string | null
  opened_by: number | null
  assigned_to: number | null
  current_mileage: number | null
  estimated_hours: number | null
  actual_hours: number | null
  customer_complaint: string | null
  diagnosis: string | null
  work_performed: string | null
  internal_notes: string | null
  estimated_cost: number | null
  actual_cost: number | null
  created_at: string
  updated_at: string
}

export interface JobCardTask {
  id: number
  uuid: string
  job_card_id: number
  task_name: string
  task_description: string | null
  task_type: 'service' | 'repair' | 'inspection' | 'diagnostic' | 'custom'
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled'
  sequence_order: number
  assigned_to: number | null
  estimated_minutes: number | null
  actual_minutes: number | null
  started_at: string | null
  completed_at: string | null
  completion_notes: string | null
  created_at: string
  updated_at: string
}

export interface DigitalInspection {
  id: number
  uuid: string
  job_card_id: number
  vehicle_id: number
  inspector_id: number
  inspection_type: string
  inspection_data: Record<string, any> | null
  photos: string[] | null
  overall_notes: string | null
  overall_status: 'excellent' | 'good' | 'fair' | 'poor' | null
  inspected_at: string
  created_at: string
  updated_at: string
}
