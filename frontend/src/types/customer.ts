export interface Customer {
  id: number
  uuid: string
  tenant_id?: number
  customer_code: string
  customer_type: 'individual' | 'business'
  first_name: string
  last_name: string
  company_name?: string
  email: string
  phone: string
  mobile?: string
  date_of_birth?: string
  id_number?: string
  address_line1?: string
  address_line2?: string
  city?: string
  state?: string
  postal_code?: string
  country: string
  tax_id?: string
  credit_limit: number
  payment_terms_days: number
  status: 'active' | 'inactive' | 'blocked'
  preferred_language: string
  preferences?: Record<string, any>
  metadata?: Record<string, any>
  last_service_date?: string
  lifetime_value: number
  total_services: number
  created_at: string
  updated_at: string
  deleted_at?: string
  full_name?: string
  display_name?: string
  vehicles?: any[]
}

export interface CustomerFilters {
  search?: string
  status?: 'active' | 'inactive' | 'blocked'
  customer_type?: 'individual' | 'business'
  per_page?: number
  page?: number
}

export interface CustomerFormData {
  customer_type: 'individual' | 'business'
  first_name: string
  last_name: string
  company_name?: string
  email: string
  phone: string
  mobile?: string
  date_of_birth?: string
  id_number?: string
  address_line1?: string
  address_line2?: string
  city?: string
  state?: string
  postal_code?: string
  country?: string
  tax_id?: string
  credit_limit?: number
  payment_terms_days?: number
  preferred_language?: string
}
