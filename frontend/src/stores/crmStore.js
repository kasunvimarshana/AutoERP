import { defineStore } from 'pinia'
import { ref } from 'vue'
import crmService from '../services/crmService'
import { useNotificationStore } from './notificationStore'

export const useCRMStore = defineStore('crm', () => {
  const customers = ref([])
  const contacts = ref([])
  const loading = ref(false)
  
  const notificationStore = useNotificationStore()
  
  const fetchCustomers = async (params = {}) => {
    loading.value = true
    try {
      const response = await crmService.getCustomers(params)
      customers.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch customers')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createCustomer = async (data) => {
    try {
      const response = await crmService.createCustomer(data)
      customers.value.unshift(response.data)
      notificationStore.success('Customer created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create customer')
      throw error
    }
  }
  
  const updateCustomer = async (id, data) => {
    try {
      const response = await crmService.updateCustomer(id, data)
      const index = customers.value.findIndex(c => c.id === id)
      if (index !== -1) {
        customers.value[index] = response.data
      }
      notificationStore.success('Customer updated successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to update customer')
      throw error
    }
  }
  
  const deleteCustomer = async (id) => {
    try {
      await crmService.deleteCustomer(id)
      customers.value = customers.value.filter(c => c.id !== id)
      notificationStore.success('Customer deleted successfully')
    } catch (error) {
      notificationStore.error('Failed to delete customer')
      throw error
    }
  }
  
  const fetchContacts = async (params = {}) => {
    loading.value = true
    try {
      const response = await crmService.getContacts(params)
      contacts.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch contacts')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createContact = async (data) => {
    try {
      const response = await crmService.createContact(data)
      contacts.value.unshift(response.data)
      notificationStore.success('Contact created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create contact')
      throw error
    }
  }
  
  return {
    customers,
    contacts,
    loading,
    fetchCustomers,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    fetchContacts,
    createContact
  }
})
