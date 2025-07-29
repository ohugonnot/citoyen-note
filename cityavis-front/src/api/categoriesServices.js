import apiClient from '@/axios'

// Récupérer toutes les catégories de services avec filtres
export const fetchCategoriesServices = async (params = {}) => {
  try {
    const response = await apiClient.get('/api/admin/categories', { params })
    return response.data
  } catch (error) {
    console.error('Erreur fetchCategoriesServices:', error)
    throw error
  }
}

// Récupérer une catégorie de service par ID
export const fetchCategorieServiceById = async (id) => {
  try {
    const response = await apiClient.get(`/api/admin/categories/${id}`)
    return response.data
  } catch (error) {
    console.error('Erreur fetchCategorieServiceById:', error)
    throw error
  }
}

// Récupérer une catégorie de service par slug
export const fetchCategorieServiceBySlug = async (slug) => {
  try {
    const response = await apiClient.get(`/api/admin/categories/slug/${slug}`)
    return response.data
  } catch (error) {
    console.error('Erreur fetchCategorieServiceBySlug:', error)
    throw error
  }
}

// Créer une nouvelle catégorie de service
export const createCategorieService = async (data) => {
  try {
    const response = await apiClient.post('/api/admin/categories', data)
    return response.data
  } catch (error) {
    console.error('Erreur createCategorieService:', error)
    throw error
  }
}

// Modifier une catégorie de service
export const updateCategorieService = async (id, data) => {
  try {
    const response = await apiClient.put(`/api/admin/categories/${id}`, data)
    return response.data
  } catch (error) {
    console.error('Erreur updateCategorieService:', error)
    throw error
  }
}

// Supprimer une catégorie de service
export const deleteCategorieService = async (id) => {
  try {
    await apiClient.delete(`/api/admin/categories/${id}`)
    return true
  } catch (error) {
    console.error('Erreur deleteCategorieService:', error)
    throw error
  }
}

// Suppression en masse
export const bulkDeleteCategoriesServices = async (ids) => {
  try {
    await apiClient.delete('/api/admin/categories/bulk', { data: { ids } })
    return true
  } catch (error) {
    console.error('Erreur bulkDeleteCategoriesServices:', error)
    throw error
  }
}

// Réorganiser l'ordre des catégories (drag & drop)
export const reorderCategoriesServices = async (orderedIds) => {
  try {
    const response = await apiClient.patch('/api/admin/categories/reorder', {
      orderedIds,
    })
    return response.data
  } catch (error) {
    console.error('Erreur reorderCategoriesServices:', error)
    throw error
  }
}

// Changer le statut d'une catégorie (actif/inactif)
export const toggleCategorieServiceStatus = async (id) => {
  try {
    const response = await apiClient.patch(`/api/admin/categories/${id}/toggle-status`)
    return response.data
  } catch (error) {
    console.error('Erreur toggleCategorieServiceStatus:', error)
    throw error
  }
}

// Import CSV des catégories
export const importCategoriesServicesCSV = async (file, clearExisting = false) => {
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('clearExisting', clearExisting)

    const response = await apiClient.post('/api/admin/categories/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  } catch (error) {
    console.error('Erreur importCategoriesServicesCSV:', error)
    throw error
  }
}

// Export CSV des catégories
export const exportCategoriesServicesCSV = async (params = {}) => {
  try {
    const response = await apiClient.get('/api/admin/categories/export', {
      params,
      responseType: 'blob',
    })

    // Créer un lien de téléchargement
    const blob = new Blob([response.data], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `categories-${new Date().toISOString().split('T')[0]}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    return true
  } catch (error) {
    console.error('Erreur exportCategoriesServicesCSV:', error)
    throw error
  }
}

// Statistiques des catégories
export const getCategoriesServicesStats = async () => {
  try {
    const response = await apiClient.get('/api/admin/categories/stats')
    return response.data
  } catch (error) {
    console.error('Erreur getCategoriesServicesStats:', error)
    throw error
  }
}

// Catégories avec compteur de services
export const getCategoriesServicesWithCount = async () => {
  try {
    const response = await apiClient.get('/api/admin/categories/with-count')
    return response.data
  } catch (error) {
    console.error('Erreur getCategoriesServicesWithCount:', error)
    throw error
  }
}

// Catégories récentes
export const getRecentCategoriesServices = async (limit = 10) => {
  try {
    const response = await apiClient.get(`/api/admin/categories/recent?limit=${limit}`)
    return response.data
  } catch (error) {
    console.error('Erreur getRecentCategoriesServices:', error)
    throw error
  }
}

// Recherche de catégories
export const searchCategoriesServices = async (query) => {
  try {
    const response = await apiClient.get('/api/admin/categories/search', {
      params: { q: query },
    })
    return response.data
  } catch (error) {
    console.error('Erreur searchCategoriesServices:', error)
    throw error
  }
}

// Catégories hiérarchiques (si vous avez des sous-catégories)
export const getCategoriesServicesHierarchy = async () => {
  try {
    const response = await apiClient.get('/api/admin/categories/hierarchy')
    return response.data
  } catch (error) {
    console.error('Erreur getCategoriesServicesHierarchy:', error)
    throw error
  }
}

// Dupliquer une catégorie
export const duplicateCategorieService = async (id) => {
  try {
    const response = await apiClient.post(`/api/admin/categories/${id}/duplicate`)
    return response.data
  } catch (error) {
    console.error('Erreur duplicateCategorieService:', error)
    throw error
  }
}

// Archiver une catégorie (soft delete)
export const archiveCategorieService = async (id) => {
  try {
    const response = await apiClient.patch(`/api/admin/categories/${id}/archive`)
    return response.data
  } catch (error) {
    console.error('Erreur archiveCategorieService:', error)
    throw error
  }
}

// Restaurer une catégorie archivée
export const restoreCategorieService = async (id) => {
  try {
    const response = await apiClient.patch(`/api/admin/categories/${id}/restore`)
    return response.data
  } catch (error) {
    console.error('Erreur restoreCategorieService:', error)
    throw error
  }
}
