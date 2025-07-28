import apiClient from '@/axios'

// Récupérer tous les services publics avec filtres
export const fetchServicesPublics = async (params = {}) => {
  try {
    const response = await apiClient.get('/api/admin/services-publics', { params })
    return response.data
  } catch (error) {
    console.error('Erreur fetchServicesPublics:', error)
    throw error
  }
}

// Récupérer un service public par ID
export const fetchServicePublicById = async (id) => {
  try {
    const response = await apiClient.get(`/api/admin/services-publics/${id}`)
    return response.data
  } catch (error) {
    console.error('Erreur fetchServicePublicById:', error)
    throw error
  }
}

// Créer un nouveau service public
export const createServicePublic = async (data) => {
  try {
    const response = await apiClient.post('/api/admin/services-publics', data)
    return response.data
  } catch (error) {
    console.error('Erreur createServicePublic:', error)
    throw error
  }
}

// Modifier un service public
export const updateServicePublic = async (id, data) => {
  try {
    const response = await apiClient.put(`/api/admin/services-publics/${id}`, data)
    return response.data
  } catch (error) {
    console.error('Erreur updateServicePublic:', error)
    throw error
  }
}

// Supprimer un service public
export const deleteServicePublic = async (id) => {
  try {
    await apiClient.delete(`/api/admin/services-publics/${id}`)
    return true
  } catch (error) {
    console.error('Erreur deleteServicePublic:', error)
    throw error
  }
}

// Suppression en masse
export const bulkDeleteServicesPublics = async (ids) => {
  try {
    await apiClient.delete('/api/admin/services-publics/bulk', { data: { ids } })
    return true
  } catch (error) {
    console.error('Erreur bulkDeleteServicesPublics:', error)
    throw error
  }
}

// Import CSV
export const importServicesPublicsCSV = async (file, clearExisting = false) => {
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('clearExisting', clearExisting)

    const response = await apiClient.post('/api/admin/services-publics/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  } catch (error) {
    console.error('Erreur importServicesPublicsCSV:', error)
    throw error
  }
}

// Statistiques
export const getServicesPublicsStats = async () => {
  try {
    const response = await apiClient.get('/api/admin/services-publics/stats')
    return response.data
  } catch (error) {
    console.error('Erreur getServicesPublicsStats:', error)
    throw error
  }
}

// Services récents
export const getRecentServicesPublics = async (limit = 10) => {
  try {
    const response = await apiClient.get(`/api/admin/services-publics/recent?limit=${limit}`)
    return response.data
  } catch (error) {
    console.error('Erreur getRecentServicesPublics:', error)
    throw error
  }
}
