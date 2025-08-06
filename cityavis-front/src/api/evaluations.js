import apiClient from '@/axios'

const baseUrl = '/api/admin/evaluations'

export default {
  async getAll(params = {}) {
    const { data } = await apiClient.get(baseUrl, { params })
    return data
  },

  async getOne(uuid) {
    const { data } = await apiClient.get(`${baseUrl}/${uuid}`)
    return data
  },

  async create(payload) {
    const { data } = await apiClient.post(baseUrl, payload)
    return data
  },

  async update(uuid, payload) {
    const { data } = await apiClient.put(`${baseUrl}/${uuid}`, payload)
    return data
  },

  async delete(uuid) {
    await apiClient.delete(`${baseUrl}/${uuid}`)
  },

  async bulkDelete(uuids) {
    await apiClient.delete(`${baseUrl}/bulk`, {
      data: { ids: uuids },
    })
  },

  async bulkValidate(uuids, estVerifie = true) {
    const { data } = await apiClient.post(`${baseUrl}/bulk-validate`, {
      ids: uuids,
      est_verifie: estVerifie,
    })
    return data
  },

  async toggleValidation(uuid, estVerifie) {
    const { data } = await apiClient.patch(`${baseUrl}/${uuid}/toggle-validation`, {
      est_verifie: estVerifie,
    })
    return data
  },
}
