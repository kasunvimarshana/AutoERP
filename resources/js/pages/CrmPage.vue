<template>
  <div class="space-y-4">
    <PageHeader title="CRM" subtitle="Contacts, leads, and opportunity pipeline">
      <template #actions>
        <div class="flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="switchTab(tab.key)"
            :class="activeTab === tab.key
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-100'"
            class="px-4 py-1.5 rounded-lg text-sm font-medium border transition-colors"
          >
            {{ tab.label }}
          </button>
        </div>
        <button
          v-if="auth.hasPermission('crm.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreate"
        >
          <span class="text-base leading-none">+</span> New
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <!-- Contacts -->
    <div v-else-if="activeTab === 'contacts'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="contacts.length === 0" icon="🤝" title="No contacts found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="contact in contacts" :key="contact.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ contact.name }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="contact.type" /></td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ contact.email ?? '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ contact.phone ?? '—' }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button v-if="auth.hasPermission('crm.update')" class="text-xs text-blue-600 hover:underline" @click="openEditContact(contact)">Edit</button>
                  <button v-if="auth.hasPermission('crm.delete')" class="text-xs text-red-500 hover:underline" @click="deleteContact(contact)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>

    <!-- Leads -->
    <div v-else-if="activeTab === 'leads'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="leads.length === 0" icon="🎯" title="No leads found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Est. Value</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ lead.title }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="lead.status" /></td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ lead.contact?.name ?? '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ lead.estimated_value ?? '—' }}</td>
              <td class="px-4 py-3 text-right">
                <button v-if="lead.status !== 'converted' && auth.hasPermission('crm.update')" class="text-xs text-green-600 hover:underline" @click="convertLead(lead.id)">Convert</button>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>

    <!-- Opportunities -->
    <div v-else-if="activeTab === 'opportunities'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="opportunities.length === 0" icon="💼" title="No opportunities found" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stage</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="opp in opportunities" :key="opp.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ opp.title }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ opp.stage }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ opp.contact?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ opp.amount ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Contact Form Modal -->
  <AppModal v-model="showContactForm" :title="editContactTarget ? 'Edit Contact' : 'New Contact'">
    <form id="contact-form" class="space-y-4" @submit.prevent="handleContactSubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
          <input v-model="contactForm.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
          <select v-model="contactForm.type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="person">Person</option>
            <option value="company">Company</option>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="contactForm.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
          <input v-model="contactForm.phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showContactForm = false">Cancel</button>
      <button type="submit" form="contact-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>

  <!-- Lead Form Modal -->
  <AppModal v-model="showLeadForm" title="New Lead">
    <form id="lead-form" class="space-y-4" @submit.prevent="handleLeadSubmit">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
        <input v-model="leadForm.title" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Value</label>
        <input v-model="leadForm.estimated_value" type="text" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showLeadForm = false">Cancel</button>
      <button type="submit" form="lead-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>

  <!-- Opportunity Form Modal -->
  <AppModal v-model="showOpportunityForm" title="New Opportunity">
    <form id="opp-form" class="space-y-4" @submit.prevent="handleOpportunitySubmit">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
        <input v-model="oppForm.title" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Stage</label>
          <select v-model="oppForm.stage" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="prospecting">Prospecting</option>
            <option value="qualification">Qualification</option>
            <option value="proposal">Proposal</option>
            <option value="negotiation">Negotiation</option>
            <option value="closed_won">Closed Won</option>
            <option value="closed_lost">Closed Lost</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <input v-model="oppForm.amount" type="text" placeholder="0.00" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showOpportunityForm = false">Cancel</button>
      <button type="submit" form="opp-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { crmService } from '@/services/crm';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { Contact, Lead, Opportunity } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

const tabs = [
  { key: 'contacts', label: 'Contacts' },
  { key: 'leads', label: 'Leads' },
  { key: 'opportunities', label: 'Opportunities' },
];

const activeTab = ref<string>('contacts');
const contacts = ref<Contact[]>([]);
const leads = ref<Lead[]>([]);
const opportunities = ref<Opportunity[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);

async function loadData(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    if (activeTab.value === 'contacts') {
      const { data } = await crmService.listContacts();
      contacts.value = Array.isArray(data) ? data : (data as { data: Contact[] }).data;
    } else if (activeTab.value === 'leads') {
      const { data } = await crmService.listLeads();
      leads.value = Array.isArray(data) ? data : (data as { data: Lead[] }).data;
    } else {
      const { data } = await crmService.listOpportunities();
      opportunities.value = Array.isArray(data) ? data : (data as { data: Opportunity[] }).data;
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load CRM data.';
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string): void {
  activeTab.value = key;
  void loadData();
}

onMounted(() => void loadData());

// ─── Contact Form ─────────────────────────────────────────────────────────────

const showContactForm = ref(false);
const editContactTarget = ref<Contact | null>(null);
const contactForm = ref({ name: '', type: 'person' as 'person' | 'company', email: '', phone: '' });

function openCreate(): void {
  formError.value = null;
  if (activeTab.value === 'contacts') {
    editContactTarget.value = null;
    contactForm.value = { name: '', type: 'person', email: '', phone: '' };
    showContactForm.value = true;
  } else if (activeTab.value === 'leads') {
    leadForm.value = { title: '', estimated_value: '' };
    showLeadForm.value = true;
  } else {
    oppForm.value = { title: '', stage: 'prospecting', amount: '' };
    showOpportunityForm.value = true;
  }
}

function openEditContact(contact: Contact): void {
  editContactTarget.value = contact;
  contactForm.value = {
    name: contact.name,
    type: contact.type,
    email: contact.email ?? '',
    phone: contact.phone ?? '',
  };
  formError.value = null;
  showContactForm.value = true;
}

async function handleContactSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    const payload = {
      name: contactForm.value.name,
      type: contactForm.value.type,
      email: contactForm.value.email || null,
      phone: contactForm.value.phone || null,
    };
    if (editContactTarget.value) {
      await crmService.updateContact(editContactTarget.value.id, payload);
      notify.success('Contact updated.');
    } else {
      await crmService.createContact(payload);
      notify.success('Contact created.');
    }
    showContactForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save contact.';
  } finally {
    saving.value = false;
  }
}

async function deleteContact(contact: Contact): Promise<void> {
  if (!confirm(`Delete contact "${contact.name}"?`)) return;
  try {
    await crmService.deleteContact(contact.id);
    notify.success('Contact deleted.');
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to delete contact.');
  }
}

// ─── Lead Form ────────────────────────────────────────────────────────────────

const showLeadForm = ref(false);
const leadForm = ref({ title: '', estimated_value: '' });

async function handleLeadSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    await crmService.createLead({
      title: leadForm.value.title,
      estimated_value: leadForm.value.estimated_value || null,
    });
    notify.success('Lead created.');
    showLeadForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save lead.';
  } finally {
    saving.value = false;
  }
}

async function convertLead(id: number): Promise<void> {
  if (!confirm('Convert this lead to an opportunity?')) return;
  try {
    await crmService.convertLead(id);
    notify.success('Lead converted to opportunity.');
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to convert lead.');
  }
}

// ─── Opportunity Form ─────────────────────────────────────────────────────────

const showOpportunityForm = ref(false);
const oppForm = ref({ title: '', stage: 'prospecting', amount: '' });

async function handleOpportunitySubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    await crmService.createOpportunity({
      title: oppForm.value.title,
      stage: oppForm.value.stage,
      amount: oppForm.value.amount || null,
    });
    notify.success('Opportunity created.');
    showOpportunityForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save opportunity.';
  } finally {
    saving.value = false;
  }
}
</script>
