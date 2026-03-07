import api from './axios'

export const usersApi = {
  /**
   * Fetch a paginated / filtered list of users.
   * @param {Object} params - Query parameters (page, per_page, search, role, etc.)
   */
  getUsers: (params = {}) => api.get('/users', { params }),

  /**
   * Fetch a single user by ID.
   * @param {number|string} id
   */
  getUserById: (id) => api.get(`/users/${id}`),

  /**
   * Create a new user.
   * @param {Object} data - User payload (name, email, password, role, etc.)
   */
  createUser: (data) => api.post('/users', data),

  /**
   * Update an existing user.
   * @param {number|string} id
   * @param {Object} data - Fields to update
   */
  updateUser: (id, data) => api.put(`/users/${id}`, data),

  /**
   * Delete a user by ID.
   * @param {number|string} id
   */
  deleteUser: (id) => api.delete(`/users/${id}`),
}
