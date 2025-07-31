import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/evaluations'

export const useEvaluationStore = defineStore('evaluation', () => {
  // =========================
  // CONSTANTES
  // =========================
  const DEFAULT_FILTERS = {
    search: '',
    service_id: null,
    user_id: null,
    est_verifie: null,
    est_anonyme: null,
    page: 1,
    limit: 25,
    sortField: 'createdAt',
    sortOrder: 'desc',
  }

  const DEFAULT_PAGINATION = {
    page: 1,
    totalPages: 1,
    total: 0,
    limit: 25,
    hasNext: false,
    hasPrev: false,
  }

  const ERROR_MESSAGES = {
    FETCH: 'Impossible de charger les évaluations',
    NOT_FOUND: 'Évaluation introuvable',
    CREATE: 'Erreur lors de la création',
    UPDATE: 'Erreur lors de la modification',
    DELETE: 'Erreur lors de la suppression',
    BULK_DELETE: 'Erreur lors de la suppression en masse',
  }

  // =========================
  // STATE
  // =========================
  const evaluations = ref([])
  const currentEvaluation = ref(null)
  const filters = ref({ ...DEFAULT_FILTERS })
  const pagination = ref({ ...DEFAULT_PAGINATION })
  const isLoading = ref(false)
  const error = ref(null)

  // =========================
  // UTILS
  // =========================
  const setError = (msg, err) => {
    error.value = msg
    console.error('[EvaluationStore]', err)
    throw err
  }

  const updatePagination = (data) => {
    if (data.pagination) {
      pagination.value = { ...pagination.value, ...data.pagination }
    }
  }

  const updateFilters = (data) => {
    if (data.filters) {
      filters.value = { ...filters.value, ...data.filters }
    }
  }

  const updateList = (uuid, updated) => {
    const i = evaluations.value.findIndex((e) => e.uuid === uuid)
    if (i !== -1) evaluations.value[i] = updated
  }

  const removeFromList = (uuid) => {
    evaluations.value = evaluations.value.filter((e) => e.uuid !== uuid)
  }

  const removeFromListBulk = (uuids) => {
    evaluations.value = evaluations.value.filter((e) => !uuids.includes(e.uuid))
  }

  // =========================
  // GETTERS
  // =========================
  const count = computed(() => evaluations.value.length)

  const hasFilters = computed(() =>
    Object.entries(filters.value).some(
      ([k, v]) => !['page', 'limit'].includes(k) && v !== null && v !== '',
    ),
  )

  // =========================
  // ACTIONS
  // =========================
  const fetchAll = async (params = {}) => {
    isLoading.value = true
    error.value = null
    try {
      const data = await api.getAll({ ...filters.value, ...params })
      evaluations.value = data.data || data
      updatePagination(data)
      updateFilters(data)
    } catch (err) {
      setError(ERROR_MESSAGES.FETCH, err)
    } finally {
      isLoading.value = false
    }
  }

  const fetchOne = async (uuid) => {
    isLoading.value = true
    error.value = null
    try {
      const data = await api.getOne(uuid)
      currentEvaluation.value = data
      updateList(uuid, data)
      return data
    } catch (err) {
      setError(ERROR_MESSAGES.NOT_FOUND, err)
    } finally {
      isLoading.value = false
    }
  }

  const create = async (payload) => {
    try {
      const data = await api.create(payload)
      evaluations.value.unshift(data)
      pagination.value.total += 1
      return data
    } catch (err) {
      setError(ERROR_MESSAGES.CREATE, err)
    }
  }

  const update = async (uuid, payload) => {
    try {
      const data = await api.update(uuid, payload)
      updateList(uuid, data)
      if (currentEvaluation.value?.uuid === uuid) currentEvaluation.value = data
      return data
    } catch (err) {
      setError(ERROR_MESSAGES.UPDATE, err)
    }
  }

  const remove = async (uuid) => {
    try {
      await api.delete(uuid)
      removeFromList(uuid)
      if (currentEvaluation.value?.uuid === uuid) currentEvaluation.value = null
      pagination.value.total -= 1
    } catch (err) {
      setError(ERROR_MESSAGES.DELETE, err)
    }
  }

  const bulkDelete = async (uuids) => {
    try {
      await api.bulkDelete(uuids)
      removeFromListBulk(uuids)
      pagination.value.total -= uuids.length
    } catch (err) {
      setError(ERROR_MESSAGES.BULK_DELETE, err)
    }
  }

  const goToPage = async (page) => {
    filters.value.page = page
    await fetchAll({ page })
  }

  const resetFilters = async () => {
    filters.value = { ...DEFAULT_FILTERS }
    await fetchAll()
  }

  return {
    // State
    evaluations,
    currentEvaluation,
    filters,
    pagination,
    isLoading,
    error,

    // Getters
    count,
    hasFilters,

    // Actions
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    bulkDelete,
    goToPage,
    resetFilters,
  }
})
