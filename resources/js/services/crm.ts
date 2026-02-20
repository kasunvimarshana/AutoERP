import http from '@/services/http';
import type { Contact, Lead, Opportunity, PaginatedResponse } from '@/types/index';

export interface ContactPayload {
  name: string;
  email?: string | null;
  phone?: string | null;
  type: 'person' | 'company';
  company?: string | null;
  notes?: string | null;
}

export interface LeadPayload {
  title: string;
  contact_id?: number | null;
  estimated_value?: string | null;
  status?: string;
  notes?: string | null;
}

export interface OpportunityPayload {
  title: string;
  contact_id?: number | null;
  stage?: string;
  amount?: string | null;
  expected_close_date?: string | null;
  notes?: string | null;
}

export const crmService = {
  // Contacts
  listContacts(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Contact> | Contact[]>('/crm/contacts', { params });
  },
  createContact(payload: ContactPayload) {
    return http.post<Contact>('/crm/contacts', payload);
  },
  updateContact(id: number, payload: Partial<ContactPayload>) {
    return http.put<Contact>(`/crm/contacts/${id}`, payload);
  },
  deleteContact(id: number) {
    return http.delete(`/crm/contacts/${id}`);
  },

  // Leads
  listLeads(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Lead> | Lead[]>('/crm/leads', { params });
  },
  createLead(payload: LeadPayload) {
    return http.post<Lead>('/crm/leads', payload);
  },
  convertLead(id: number) {
    return http.patch<Lead>(`/crm/leads/${id}/convert`);
  },

  // Opportunities
  listOpportunities(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Opportunity> | Opportunity[]>('/crm/opportunities', { params });
  },
  createOpportunity(payload: OpportunityPayload) {
    return http.post<Opportunity>('/crm/opportunities', payload);
  },
};
