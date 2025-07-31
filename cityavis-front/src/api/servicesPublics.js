import apiClient from '@/axios'

const baseUrl = '/api/admin/services-publics'
const basePublicUrl = '/public/services'

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
}
