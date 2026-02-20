<template>
  <div class="space-y-4">
    <PageHeader title="Invoices" subtitle="Billing and payment tracking" />

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="items.length === 0" icon="🧾" title="No invoices found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount Due</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="invoice in items" :key="invoice.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ invoice.invoice_number }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="invoice.status" /></td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ invoice.total_amount }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ invoice.amount_due }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ invoice.due_date?.substring(0, 10) ?? '—' }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="invoice.status === 'draft' && auth.hasPermission('invoice.update')"
                    class="text-xs text-blue-600 hover:underline"
                    :disabled="actionId === invoice.id"
                    @click="sendInvoice(invoice)"
                  >Send</button>
                  <button
                    v-if="['draft','sent'].includes(invoice.status) && auth.hasPermission('invoice.update')"
                    class="text-xs text-red-500 hover:underline"
                    :disabled="actionId === invoice.id"
                    @click="voidInvoice(invoice)"
                  >Void</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <AppPaginator
          :page="page"
          :last-page="lastPage"
          :per-page="perPage"
          :total="total"
          @prev="prevPage"
          @next="nextPage"
          @go-to="goToPage"
        />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useListPage } from '@/composables/useListPage';
import { invoiceService } from '@/services/invoices';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { Invoice } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppPaginator from '@/components/AppPaginator.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();
const actionId = ref<number | null>(null);

const { items, loading, error, page, perPage, total, lastPage, load, nextPage, prevPage, goToPage } =
  useListPage<Invoice>({ endpoint: '/invoices' });

void load();

async function sendInvoice(invoice: Invoice): Promise<void> {
  actionId.value = invoice.id;
  try {
    await invoiceService.send(invoice.id);
    notify.success('Invoice sent.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to send invoice.');
  } finally {
    actionId.value = null;
  }
}

async function voidInvoice(invoice: Invoice): Promise<void> {
  if (!confirm('Void this invoice? This cannot be undone.')) return;
  actionId.value = invoice.id;
  try {
    await invoiceService.void(invoice.id);
    notify.success('Invoice voided.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to void invoice.');
  } finally {
    actionId.value = null;
  }
}
</script>
