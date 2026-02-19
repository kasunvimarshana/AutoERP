import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import userService from '@/services/user';

export const useUserStore = defineStore('user', () => {
  // State
  const users = ref([]);
  const currentUser = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const pagination = ref({
    currentPage: 1,
    perPage: 15,
    total: 0,
    lastPage: 1,
  });

  // Getters
  const hasUsers = computed(() => users.value.length > 0);
  const totalUsers = computed(() => pagination.value.total);

  // Actions

  /**
   * Fetch paginated users
   */
  async function fetchUsers(params = {}) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.getUsers({
        page: pagination.value.currentPage,
        per_page: pagination.value.perPage,
        ...params,
      });

      if (response.success && response.data) {
        users.value = response.data.data || response.data;
        
        // Update pagination if available
        if (response.data.meta) {
          pagination.value = {
            currentPage: response.data.meta.current_page,
            perPage: response.data.meta.per_page,
            total: response.data.meta.total,
            lastPage: response.data.meta.last_page,
          };
        }
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch users';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Fetch a single user by ID
   */
  async function fetchUser(id) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.getUser(id);

      if (response.success && response.data) {
        currentUser.value = response.data;
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch user';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Create a new user
   */
  async function createUser(data) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.createUser(data);

      if (response.success && response.data) {
        // Refresh the user list
        await fetchUsers();
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create user';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Update an existing user
   */
  async function updateUser(id, data) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.updateUser(id, data);

      if (response.success && response.data) {
        // Update the user in the list
        const index = users.value.findIndex(u => u.id === id);
        if (index !== -1) {
          users.value[index] = response.data;
        }
        
        // Update current user if it's the same
        if (currentUser.value?.id === id) {
          currentUser.value = response.data;
        }
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update user';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Delete a user
   */
  async function deleteUser(id) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.deleteUser(id);

      if (response.success) {
        // Remove from the list
        users.value = users.value.filter(u => u.id !== id);
        
        // Clear current user if it's the deleted one
        if (currentUser.value?.id === id) {
          currentUser.value = null;
        }
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete user';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Assign a role to a user
   */
  async function assignRole(userId, role) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.assignRole(userId, role);

      if (response.success) {
        // Refresh the user to get updated roles
        await fetchUser(userId);
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to assign role';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Revoke a role from a user
   */
  async function revokeRole(userId, role) {
    loading.value = true;
    error.value = null;

    try {
      const response = await userService.revokeRole(userId, role);

      if (response.success) {
        // Refresh the user to get updated roles
        await fetchUser(userId);
      }

      return response;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to revoke role';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Set current page for pagination
   */
  function setPage(page) {
    pagination.value.currentPage = page;
  }

  /**
   * Set items per page
   */
  function setPerPage(perPage) {
    pagination.value.perPage = perPage;
    pagination.value.currentPage = 1; // Reset to first page
  }

  return {
    // State
    users,
    currentUser,
    loading,
    error,
    pagination,
    // Getters
    hasUsers,
    totalUsers,
    // Actions
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    assignRole,
    revokeRole,
    setPage,
    setPerPage,
  };
});
