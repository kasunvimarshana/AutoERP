import apiClient from './api'

export default {
  // Accounts
  getAccounts(params = {}) {
    return apiClient.get('/finance/accounts', { params })
  },
  getAccount(id) {
    return apiClient.get(`/finance/accounts/${id}`)
  },
  createAccount(data) {
    return apiClient.post('/finance/accounts', data)
  },
  updateAccount(id, data) {
    return apiClient.put(`/finance/accounts/${id}`, data)
  },
  deleteAccount(id) {
    return apiClient.delete(`/finance/accounts/${id}`)
  },
  
  // Journal Entries
  getJournalEntries(params = {}) {
    return apiClient.get('/finance/journal-entries', { params })
  },
  getJournalEntry(id) {
    return apiClient.get(`/finance/journal-entries/${id}`)
  },
  createJournalEntry(data) {
    return apiClient.post('/finance/journal-entries', data)
  },
  updateJournalEntry(id, data) {
    return apiClient.put(`/finance/journal-entries/${id}`, data)
  },
  deleteJournalEntry(id) {
    return apiClient.delete(`/finance/journal-entries/${id}`)
  },
  postJournalEntry(id) {
    return apiClient.post(`/finance/journal-entries/${id}/post`)
  },
  approveJournalEntry(id) {
    return apiClient.post(`/finance/journal-entries/${id}/approve`)
  },
  reverseJournalEntry(id) {
    return apiClient.post(`/finance/journal-entries/${id}/reverse`)
  },
  
  // Reports
  getFinancialReports(params = {}) {
    return apiClient.get('/finance/reports', { params })
  },
  getBalanceSheet(params = {}) {
    return apiClient.get('/finance/reports/balance-sheet', { params })
  },
  getIncomeStatement(params = {}) {
    return apiClient.get('/finance/reports/income-statement', { params })
  },
  getTrialBalance(params = {}) {
    return apiClient.get('/finance/reports/trial-balance', { params })
  },
  getCashFlowStatement(params = {}) {
    return apiClient.get('/finance/reports/cash-flow', { params })
  },
  getGeneralLedger(params = {}) {
    return apiClient.get('/finance/reports/general-ledger', { params })
  },
  getAccountAging(params = {}) {
    return apiClient.get('/finance/reports/account-aging', { params })
  }
}
