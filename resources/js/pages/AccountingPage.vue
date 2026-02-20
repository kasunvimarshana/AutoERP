<template>
  <div class="space-y-4">
    <PageHeader title="Accounting" subtitle="Chart of accounts and journal entries">
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
          v-if="activeTab === 'journal' && auth.hasPermission('accounting.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreateEntry"
        >
          <span class="text-base leading-none">+</span> New Entry
        </button>
        <button
          v-if="activeTab === 'accounts' && auth.hasPermission('accounting.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreateAccount"
        >
          <span class="text-base leading-none">+</span> New Account
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <!-- Chart of Accounts -->
    <div v-else-if="activeTab === 'accounts'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="accounts.length === 0" icon="📊" title="No accounts found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Active</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="account in accounts" :key="account.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ account.code }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ account.name }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="account.type" /></td>
              <td class="px-4 py-3 text-center">
                <StatusBadge :status="account.is_active ? 'active' : 'suspended'" />
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="auth.hasPermission('accounting.update')"
                  class="text-xs text-blue-600 hover:underline"
                  @click="openEditAccount(account)"
                >Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>

    <!-- Journal Entries -->
    <div v-else-if="activeTab === 'journal'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="journalEntries.length === 0" icon="📒" title="No journal entries found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="entry in journalEntries" :key="entry.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ entry.reference }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ entry.description ?? '—' }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="entry.status" /></td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ entry.total_debit }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ entry.total_credit }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ entry.created_at?.substring(0, 10) }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="entry.status === 'draft' && auth.hasPermission('accounting.update')"
                  class="text-xs text-green-600 hover:underline"
                  :disabled="actionId === entry.id"
                  @click="postEntry(entry)"
                >Post</button>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>
  </div>

  <!-- Account Form Modal -->
  <AppModal v-model="showAccountForm" :title="editAccountTarget ? 'Edit Account' : 'New Account'">
    <form id="account-form" class="space-y-4" @submit.prevent="handleAccountSubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
          <input v-model="accountForm.code" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
          <select v-model="accountForm.type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="asset">Asset</option>
            <option value="liability">Liability</option>
            <option value="equity">Equity</option>
            <option value="revenue">Revenue</option>
            <option value="expense">Expense</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
        <input v-model="accountForm.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div class="flex items-center gap-2">
        <input id="acc-active" v-model="accountForm.is_active" type="checkbox" class="rounded" />
        <label for="acc-active" class="text-sm text-gray-700">Active</label>
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showAccountForm = false">Cancel</button>
      <button type="submit" form="account-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>

  <!-- Journal Entry Form Modal -->
  <AppModal v-model="showEntryForm" title="New Journal Entry" size="lg">
    <form id="entry-form" class="space-y-4" @submit.prevent="handleEntrySubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
          <input v-model="entryForm.reference" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="JE-001" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Entry Date</label>
          <input v-model="entryForm.entry_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <input v-model="entryForm.description" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="block text-sm font-medium text-gray-700">Lines <span class="text-red-500">*</span></label>
          <button type="button" class="text-xs text-blue-600 hover:underline" @click="addEntryLine">+ Add line</button>
        </div>
        <div v-for="(line, idx) in entryForm.lines" :key="idx" class="grid grid-cols-4 gap-2 mb-2">
          <input v-model="line.account_id" type="number" placeholder="Account ID" class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
          <input v-model="line.debit" type="text" placeholder="Debit" class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
          <input v-model="line.credit" type="text" placeholder="Credit" class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
          <button type="button" class="text-xs text-red-500 hover:underline" @click="removeEntryLine(idx)">Remove</button>
        </div>
      </div>

      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showEntryForm = false">Cancel</button>
      <button type="submit" form="entry-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { accountingService } from '@/services/accounting';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { ChartOfAccount, JournalEntry } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

const tabs = [
  { key: 'accounts', label: 'Chart of Accounts' },
  { key: 'journal', label: 'Journal Entries' },
];

const activeTab = ref<string>('accounts');
const accounts = ref<ChartOfAccount[]>([]);
const journalEntries = ref<JournalEntry[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);
const actionId = ref<number | null>(null);

async function loadData(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    if (activeTab.value === 'accounts') {
      const { data } = await accountingService.listAccounts();
      accounts.value = Array.isArray(data) ? data : (data as { data: ChartOfAccount[] }).data;
    } else {
      const { data } = await accountingService.listJournalEntries();
      journalEntries.value = Array.isArray(data) ? data : (data as { data: JournalEntry[] }).data;
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load accounting data.';
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string): void {
  activeTab.value = key;
  void loadData();
}

onMounted(() => void loadData());

// ─── Post Journal Entry ───────────────────────────────────────────────────────

async function postEntry(entry: JournalEntry): Promise<void> {
  actionId.value = entry.id;
  try {
    await accountingService.postJournalEntry(entry.id);
    notify.success('Journal entry posted.');
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to post entry.');
  } finally {
    actionId.value = null;
  }
}

// ─── Account Form ─────────────────────────────────────────────────────────────

const showAccountForm = ref(false);
const editAccountTarget = ref<ChartOfAccount | null>(null);
const accountForm = ref({ code: '', name: '', type: 'asset' as ChartOfAccount['type'], is_active: true });

function openCreateAccount(): void {
  editAccountTarget.value = null;
  accountForm.value = { code: '', name: '', type: 'asset', is_active: true };
  formError.value = null;
  showAccountForm.value = true;
}

function openEditAccount(account: ChartOfAccount): void {
  editAccountTarget.value = account;
  accountForm.value = { code: account.code, name: account.name, type: account.type, is_active: account.is_active };
  formError.value = null;
  showAccountForm.value = true;
}

async function handleAccountSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    if (editAccountTarget.value) {
      await accountingService.updateAccount(editAccountTarget.value.id, accountForm.value);
      notify.success('Account updated.');
    } else {
      await accountingService.createAccount(accountForm.value);
      notify.success('Account created.');
    }
    showAccountForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save account.';
  } finally {
    saving.value = false;
  }
}

// ─── Journal Entry Form ───────────────────────────────────────────────────────

const showEntryForm = ref(false);
const entryForm = ref({
  reference: '',
  description: '',
  entry_date: new Date().toISOString().substring(0, 10),
  lines: [{ account_id: null as number | null, debit: '', credit: '' }],
});

function openCreateEntry(): void {
  entryForm.value = {
    reference: '',
    description: '',
    entry_date: new Date().toISOString().substring(0, 10),
    lines: [{ account_id: null, debit: '', credit: '' }],
  };
  formError.value = null;
  showEntryForm.value = true;
}

function addEntryLine(): void {
  entryForm.value.lines.push({ account_id: null, debit: '', credit: '' });
}

function removeEntryLine(idx: number): void {
  if (entryForm.value.lines.length > 1) {
    entryForm.value.lines.splice(idx, 1);
  }
}

async function handleEntrySubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    const lines = entryForm.value.lines
      .filter((l) => l.account_id)
      .map((l) => ({
        account_id: l.account_id as number,
        debit: l.debit || undefined,
        credit: l.credit || undefined,
      }));

    await accountingService.createJournalEntry({
      reference: entryForm.value.reference || undefined,
      description: entryForm.value.description || null,
      entry_date: entryForm.value.entry_date,
      lines,
    });
    notify.success('Journal entry created.');
    showEntryForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to create journal entry.';
  } finally {
    saving.value = false;
  }
}
</script>
