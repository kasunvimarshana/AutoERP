/** Auth domain types */
export interface User {
  id: number
  tenant_id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
}

export interface LoginCredentials {
  email: string
  password: string
  tenant_slug?: string
}

export interface AuthTokens {
  access_token: string
  token_type: string
  expires_in: number
}
