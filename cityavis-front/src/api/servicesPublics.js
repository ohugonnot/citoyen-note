import apiClient from '@/axios'

const baseUrl = '/api/admin/services-publics'
const basePublicUrl = '/api/public/services'

export default {
  async getAll(params = {}) {
    const { data } = await apiClient.get(baseUrl, { params })
    return data
  },

  async getAllPublic(params = {}) {
    const { data } = await apiClient.get(basePublicUrl, { params })
    return data
  },

  async getOne(id) {
    const { data } = await apiClient.get(`${baseUrl}/${id}`)
    return data
  },

  async getOneBySlug(slug) {
    const { data } = await apiClient.get(`${basePublicUrl}/${slug}`)
    return data
  },

  async create(payload) {
    const { data } = await apiClient.post(baseUrl, payload)
    return data
  },

  async update(id, payload) {
    const { data } = await apiClient.put(`${baseUrl}/${id}`, payload)
    return data
  },

  async delete(id) {
    await apiClient.delete(`${baseUrl}/${id}`)
    return true
  },

  async bulkDelete(ids) {
    await apiClient.delete(`${baseUrl}/bulk`, { data: { ids } })
    return true
  },

  async importCSV(file, clearExisting = false) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('clearExisting', clearExisting)

    const { data } = await apiClient.post(`${baseUrl}/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return data
  },

  async getStats() {
    const { data } = await apiClient.get(`${baseUrl}/stats`)
    return data
  },

  async getRecent(limit = 10) {
    const { data } = await apiClient.get(`${baseUrl}/recent?limit=${limit}`)
    return data
  },

  // 🆕 NOUVELLES MÉTHODES POUR LES ÉVALUATIONS

  // Soumettre une évaluation (version publique)
  async submitEvaluation(serviceId, evaluationData) {
    const { data } = await apiClient.post(
      `${basePublicUrl}/${serviceId}/evaluations`,
      evaluationData,
    )
    return data
  },

  // Récupérer les évaluations d'un service (version publique)
  async getEvaluations(serviceId, params = {}) {
    const { data } = await apiClient.get(`${basePublicUrl}/${serviceId}/evaluations`, { params })
    return data
  },

  // Récupérer les statistiques d'évaluations d'un service
  async getEvaluationStats(serviceId) {
    const { data } = await apiClient.get(`${basePublicUrl}/${serviceId}/evaluations/stats`)
    return data
  },

  // 📊 OPTIONNEL - Méthodes admin pour gérer les évaluations

  // Admin : Récupérer toutes les évaluations avec filtres
  async getEvaluationsAdmin(params = {}) {
    const { data } = await apiClient.get(`${baseUrl}/evaluations`, { params })
    return data
  },

  // Admin : Modérer une évaluation
  async moderateEvaluation(evaluationId, action) {
    const { data } = await apiClient.patch(
      `${baseUrl}/evaluations/${evaluationId}/moderate`,
      { action }, // 'approve', 'reject', 'flag'
    )
    return data
  },

  // Admin : Supprimer une évaluation
  async deleteEvaluation(evaluationId) {
    await apiClient.delete(`${baseUrl}/evaluations/${evaluationId}`)
    return true
  },
}
