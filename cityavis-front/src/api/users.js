import apiClient from '@/axios'

/**
 * Récupère tous les utilisateurs avec filtres et pagination
 * @param {Object} params - Paramètres de recherche et pagination
 * @returns {Promise<Object>}
 */
export async function fetchUsers(params = {}) {
  const searchParams = new URLSearchParams()

  // Paramètres de recherche
  if (params.search) searchParams.append('search', params.search)
  if (params.role) searchParams.append('role', params.role)
  if (params.statut) searchParams.append('statut', params.statut)

  // Paramètres de pagination et tri
  if (params.page) searchParams.append('page', params.page)
  if (params.limit) searchParams.append('limit', params.limit)
  if (params.sortField) searchParams.append('sortField', params.sortField)
  if (params.sortOrder) searchParams.append('sortOrder', params.sortOrder)

  const { data } = await apiClient.get(`/api/admin/users?${searchParams.toString()}`)
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

/**
 * Suppression en masse d'utilisateurs
 * @param {Array<number>} ids
 * @returns {Promise<Object>}
 */
export async function bulkDeleteUsers(ids) {
  const { data } = await apiClient.delete('/api/admin/users/bulk/delete', {
    data: { ids }
  })
  return data
}

/**
 * Recherche rapide d'utilisateurs
 * @param {string} searchTerm
 * @param {number} limit
 * @returns {Promise<Array>}
 */
export async function searchUsers(searchTerm, limit = 20) {
  const { data } = await fetchUsers({
    search: searchTerm,
    limit
  })
  return data.data
}

/**
 * Récupère les statistiques des utilisateurs
 * @returns {Promise<Object>}
 */
export async function getUserStats() {
  const { data } = await apiClient.get('/api/admin/users/stats')
  return data
}

/**
 * Récupère les utilisateurs récents
 * @param {number} limit
 * @returns {Promise<Array>}
 */
export async function getRecentUsers(limit = 10) {
  const { data } = await apiClient.get(`/api/admin/users/recent?limit=${limit}`)
  return data
}
