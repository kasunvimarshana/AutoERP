/**
 * Thin composable that returns the shared singleton Axios instance.
 * Use this instead of importing http directly so components stay
 * decoupled from the service layer implementation detail.
 */
import http from '@/services/http';
import type { AxiosInstance } from 'axios';

export function useApi(): AxiosInstance {
  return http;
}
