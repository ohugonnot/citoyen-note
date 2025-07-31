import apiClient from '@/axios'

const baseUrl = '/api/admin/users'

export default {
  async getAll(params = {}) {
    const searchParams = new URLSearchParams()

    if (params.search) searchParams.append('search', params.search)
    if (params.role) searchParams.append('role', params.role)
    if (params.statut) searchParams.append('statut', params.statut)
    if (params.page) searchParams.append('page', params.page)
    if (params.limit) searchParams.append('limit', params.limit)
    if (params.sortField) searchParams.append('sortField', params.sortField)
    if (params.sortOrder) searchParams.append('sortOrder', params.sortOrder)

    const { data } = await apiClient.get(`${baseUrl}?${searchParams.toString()}`)
    return data
  },

  async getById(id) {
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
  },

  async bulkDelete(ids) {
    const { data } = await apiClient.delete(`${baseUrl}/bulk/delete`, {
      data: { ids },
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
