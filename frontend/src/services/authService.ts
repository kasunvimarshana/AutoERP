import apiClient from './apiClient';

export interface LoginPayload {
  email:    string;
  password: string;
}

export interface LoginResponse {
  data: {
    token:      string;
    token_type: string;
    expires_in: number;
  };
}

export interface MeResponse {
  data: {
    id:        string;
    name:      string;
    email:     string;
    tenant_id: string;
  };
}

/**
 * Authentication API calls routed through the API Gateway.
 */
export const authService = {
  async login(payload: LoginPayload): Promise<LoginResponse> {
    const response = await apiClient.post<LoginResponse>('/auth/login', payload);
    return response.data;
  },

  async me(): Promise<MeResponse> {
    const response = await apiClient.get<MeResponse>('/auth/me');
    return response.data;
  },

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout');
  },
};
