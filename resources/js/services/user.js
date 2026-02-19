import api from './api';

/**
 * User Service
 * Handles all user management API calls
 */
const userService = {
  /**
   * Get paginated list of users
   * @param {Object} params - Query parameters (page, per_page, search, etc.)
   * @returns {Promise}
   */
  async getUsers(params = {}) {
    const response = await api.get('/users', { params });
    return response.data;
  },

  /**
   * Get a single user by ID
   * @param {string|number} id - User ID
   * @returns {Promise}
   */
  async getUser(id) {
    const response = await api.get(`/users/${id}`);
    return response.data;
  },

  /**
   * Create a new user
   * @param {Object} data - User data
   * @returns {Promise}
   */
  async createUser(data) {
    const response = await api.post('/users', data);
    return response.data;
  },

  /**
   * Update an existing user
   * @param {string|number} id - User ID
   * @param {Object} data - Updated user data
   * @returns {Promise}
   */
  async updateUser(id, data) {
    const response = await api.put(`/users/${id}`, data);
    return response.data;
  },

  /**
   * Delete a user
   * @param {string|number} id - User ID
   * @returns {Promise}
   */
  async deleteUser(id) {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  },

  /**
   * Assign a role to a user
   * @param {string|number} id - User ID
   * @param {string} role - Role name
   * @returns {Promise}
   */
  async assignRole(id, role) {
    const response = await api.post(`/users/${id}/assign-role`, { role });
    return response.data;
  },

  /**
   * Revoke a role from a user
   * @param {string|number} id - User ID
   * @param {string} role - Role name
   * @returns {Promise}
   */
  async revokeRole(id, role) {
    const response = await api.post(`/users/${id}/revoke-role`, { role });
    return response.data;
  },
};

export default userService;
