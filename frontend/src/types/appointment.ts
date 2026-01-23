// Appointment Management Types
export interface ServiceBay {
  id: number
  uuid: string
  tenant_id: number | null
  bay_number: string
  name: string
  description: string | null
  bay_type: 'general' | 'specialized' | 'diagnostic' | 'quick_service'
  status: 'available' | 'occupied' | 'maintenance' | 'inactive'
  capacity: number
  equipment: string[] | null
  specializations: string[] | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Appointment {
  id: number
  uuid: string
  tenant_id: number | null
  appointment_number: string
  customer_id: number
  vehicle_id: number
  service_bay_id: number | null
  scheduled_date: string
  scheduled_time: string
  estimated_duration: number
  appointment_type: 'routine_service' | 'repair' | 'inspection' | 'diagnostic' | 'custom'
  status: 'scheduled' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'no_show'
  priority: 'low' | 'normal' | 'high' | 'urgent'
  service_description: string | null
  customer_notes: string | null
  internal_notes: string | null
  assigned_to: number | null
  confirmed_at: string | null
  started_at: string | null
  completed_at: string | null
  cancelled_at: string | null
  cancellation_reason: string | null
  created_at: string
  updated_at: string
}
