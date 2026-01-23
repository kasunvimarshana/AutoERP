import api from './api';

/**
 * Authentication Service
 * Handles all authentication-related API calls
 */
const authService = {
  /**
   * Register a new user
   * @param {Object} credentials - User registration data
   * @returns {Promise}
   */
  async register(credentials) {
    const response = await api.post('/auth/register', credentials);
    return response.data;
  },

  /**
   * Login user
   * @param {Object} credentials - Login credentials
   * @returns {Promise}
   */
  async login(credentials) {
    const response = await api.post('/auth/login', credentials);
    return response.data;
  },

  /**
   * Logout from current device
   * @returns {Promise}
   */
  async logout() {
    const response = await api.post('/auth/logout');
    return response.data;
  },

  /**
   * Logout from all devices
   * @returns {Promise}
   */
  async logoutAll() {
    const response = await api.post('/auth/logout-all');
    return response.data;
  },

  /**
   * Get current user profile
   * @returns {Promise}
   */
  async me() {
    const response = await api.get('/auth/me');
    return response.data;
  },

  /**
   * Refresh authentication token
   * @returns {Promise}
   */
  async refresh() {
    const response = await api.post('/auth/refresh');
    return response.data;
  },

  /**
   * Request password reset
   * @param {string} email - User email
   * @returns {Promise}
   */
  async forgotPassword(email) {
    const response = await api.post('/auth/forgot-password', { email });
    return response.data;
  },

  /**
   * Reset password with token
   * @param {Object} data - Password reset data
   * @returns {Promise}
   */
  async resetPassword(data) {
    const response = await api.post('/auth/reset-password', data);
    return response.data;
  },

  /**
   * Verify email address
   * @param {string|number} id - User ID
   * @param {string} hash - Verification hash
   * @param {Object} queryParams - Query parameters (expires, signature)
   * @returns {Promise}
   */
  async verifyEmail(id, hash, queryParams) {
    const response = await api.get(`/auth/verify-email/${id}/${hash}`, {
      params: queryParams,
    });
    return response.data;
  },

  /**
   * Resend email verification
   * @returns {Promise}
   */
  async resendVerification() {
    const response = await api.post('/auth/resend-verification');
    return response.data;
  },
};

export default authService;
