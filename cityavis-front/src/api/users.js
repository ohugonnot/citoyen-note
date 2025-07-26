import apiClient from '@/axios'

/**
 * Récupère tous les utilisateurs
 * @returns {Promise<Array>}
 */
export async function fetchUsers() {
  const { data } = await apiClient.get('/api/admin/users')
  return data
}

/**
 * Récupère un utilisateur par son ID
 * @param {number|string} id
 * @returns {Promise<Object>}
 */
export async function fetchUserById(id) {
  const { data } = await apiClient.get(`/api/admin/users/${id}`)
  return data
}

/**
 * Crée un nouvel utilisateur
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
export async function createUser(payload) {
  const { data } = await apiClient.post('/api/admin/users', payload)
  return data
}

/**
 * Met à jour un utilisateur
 * @param {number|string} id
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
export async function updateUser(id, payload) {
  const { data } = await apiClient.put(`/api/admin/users/${id}`, payload)
  return data
}

/**
 * Supprime un utilisateur
 * @param {number|string} id
 * @returns {Promise<void>}
 */
export async function deleteUser(id) {
  await apiClient.delete(`/api/admin/users/${id}`)
}
