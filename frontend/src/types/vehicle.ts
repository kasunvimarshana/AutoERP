export interface Vehicle {
  id: number
  uuid: string
  tenant_id?: number
  current_customer_id: number
  vin: string
  registration_number: string
  make: string
  model: string
  year: number
  color?: string
  engine_number?: string
  chassis_number?: string
  vehicle_type: 'car' | 'truck' | 'motorcycle' | 'suv' | 'van' | 'bus' | 'other'
  fuel_type?: string
  transmission?: string
  engine_capacity?: number
  current_mileage: number
  mileage_unit: 'km' | 'miles'
  last_service_mileage?: number
  next_service_mileage?: number
  last_service_date?: string
  next_service_date?: string
  service_interval_days: number
  service_interval_mileage: number
  insurance_provider?: string
  insurance_policy_number?: string
  insurance_expiry_date?: string
  registration_expiry_date?: string
  specifications?: Record<string, any>
  metadata?: Record<string, any>
  notes?: string
  status: 'active' | 'inactive' | 'sold' | 'written_off'
  ownership_start_date?: string
  total_services: number
  created_at: string
  updated_at: string
  deleted_at?: string
  full_name?: string
  current_customer?: any
  ownership_history?: any[]
}

export interface VehicleFilters {
  search?: string
  status?: 'active' | 'inactive' | 'sold' | 'written_off'
  customer_id?: number
  service_due?: boolean
  per_page?: number
  page?: number
}

export interface VehicleFormData {
  current_customer_id: number
  vin: string
  registration_number: string
  make: string
  model: string
  year: number
  color?: string
  engine_number?: string
  chassis_number?: string
  vehicle_type: 'car' | 'truck' | 'motorcycle' | 'suv' | 'van' | 'bus' | 'other'
  fuel_type?: string
  transmission?: string
  engine_capacity?: number
  current_mileage?: number
  mileage_unit?: 'km' | 'miles'
  service_interval_days?: number
  service_interval_mileage?: number
  insurance_provider?: string
  insurance_policy_number?: string
  insurance_expiry_date?: string
  registration_expiry_date?: string
  notes?: string
}
