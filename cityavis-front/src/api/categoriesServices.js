import apiClient from '@/axios'

const BASE_PATH = '/api/admin/categories'
const BASE_PUBLIC_PATH = '/api/public/categories'

export const fetchCategoriesServices = async (params = {}) => {
  const response = await apiClient.get(BASE_PUBLIC_PATH, { params })
  return response.data
}

export const fetchCategorieServiceById = async (id) => {
  const response = await apiClient.get(`${BASE_PUBLIC_PATH}/${id}`)
  return response.data
}

export const createCategorieService = async (data) => {
  const response = await apiClient.post(BASE_PATH, data)
  return response.data
}

export const updateCategorieService = async (id, data) => {
  const response = await apiClient.put(`${BASE_PATH}/${id}`, data)
  return response.data
}

export const deleteCategorieService = async (id) => {
  await apiClient.delete(`${BASE_PATH}/${id}`)
  return true
}
