import { createI18n } from 'vue-i18n';

const messages = {
  en: {
    common: {
      save: 'Save',
      cancel: 'Cancel',
      delete: 'Delete',
      edit: 'Edit',
      create: 'Create',
      search: 'Search',
      filter: 'Filter',
      export: 'Export',
      import: 'Import',
      actions: 'Actions',
      loading: 'Loading...',
      noData: 'No data available',
      confirm: 'Confirm',
      success: 'Success',
      error: 'Error',
      warning: 'Warning',
    },
    auth: {
      login: 'Login',
      logout: 'Logout',
      register: 'Register',
      email: 'Email',
      password: 'Password',
      remember: 'Remember me',
      forgot: 'Forgot password?',
      noAccount: "Don't have an account?",
      haveAccount: 'Already have an account?',
    },
    nav: {
      dashboard: 'Dashboard',
      inventory: 'Inventory',
      products: 'Products',
      stock: 'Stock Management',
      crm: 'CRM',
      customers: 'Customers',
      masterData: 'Master Data',
      units: 'Units',
      currencies: 'Currencies',
      taxRates: 'Tax Rates',
    },
  },
};

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages,
});

export default i18n;
