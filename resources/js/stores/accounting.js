import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/composables/useApi';

export const useAccountingStore = defineStore('accounting', () => {
    const accounts = ref([]);
    const invoices = ref([]);
    const creditNotes = ref([]);
    const bankAccounts = ref([]);
    const bankTransactions = ref([]);
    const loading = ref(false);
    const creditNotesLoading = ref(false);
    const bankAccountsLoading = ref(false);
    const bankTransactionsLoading = ref(false);
    const error = ref(null);
    const accountsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const invoicesMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const creditNotesMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const bankAccountsMeta = ref({ current_page: 1, last_page: 1, total: 0 });
    const bankTransactionsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchAccounts(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/accounts', { params: { page } });
            accounts.value = data.data ?? data;
            if (data.meta) accountsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load accounts.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchInvoices(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/invoices', { params: { page } });
            invoices.value = data.data ?? data;
            if (data.meta) invoicesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load invoices.';
        } finally {
            loading.value = false;
        }
    }

    async function fetchBankAccounts(page = 1) {
        bankAccountsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/bank-accounts', { params: { page } });
            bankAccounts.value = data.data ?? data;
            if (data.meta) bankAccountsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load bank accounts.';
        } finally {
            bankAccountsLoading.value = false;
        }
    }

    async function fetchBankTransactions(page = 1, filters = {}) {
        bankTransactionsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/bank-transactions', { params: { page, ...filters } });
            bankTransactions.value = data.data ?? data;
            if (data.meta) bankTransactionsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load bank transactions.';
        } finally {
            bankTransactionsLoading.value = false;
        }
    }

    async function fetchCreditNotes(page = 1) {
        creditNotesLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/invoices', { params: { page, invoice_type: 'credit_note' } });
            creditNotes.value = data.data ?? data;
            if (data.meta) creditNotesMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load credit notes.';
        } finally {
            creditNotesLoading.value = false;
        }
    }

    const periods = ref([]);
    const periodsLoading = ref(false);
    const periodsMeta = ref({ current_page: 1, last_page: 1, total: 0 });

    async function fetchPeriods(page = 1) {
        periodsLoading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/api/v1/accounting/periods', { params: { page } });
            periods.value = data.data ?? data;
            if (data.meta) periodsMeta.value = data.meta;
        } catch (e) {
            error.value = e.response?.data?.message ?? 'Failed to load accounting periods.';
        } finally {
            periodsLoading.value = false;
        }
    }

    return {
        accounts, invoices, creditNotes, bankAccounts, bankTransactions, periods,
        loading, creditNotesLoading, bankAccountsLoading, bankTransactionsLoading, periodsLoading, error,
        accountsMeta, invoicesMeta, creditNotesMeta, bankAccountsMeta, bankTransactionsMeta, periodsMeta,
        fetchAccounts, fetchInvoices, fetchCreditNotes, fetchBankAccounts, fetchBankTransactions, fetchPeriods,
    };
});
