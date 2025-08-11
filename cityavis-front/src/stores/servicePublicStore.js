// src/stores/servicePublicStore.js - Version finale complète
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/servicesPublics'
import { useAuthStore } from '@/stores/authStore' // 🆕 Import ajouté

export const useServicePublicStore = defineStore('servicePublic', () => {
  const DEFAULT_FILTERS = {
    search: '',
    categorie: null,
    ville: '',
    statut: null,
    codePostal: '',
    accessibilitePmr: null,
    sortField: 'nom',
    sortOrder: 'asc',
    page: 1,
    limit: 25,
    source: null,
  }

  const DEFAULT_PAGINATION = {
    page: 1,
    totalPages: 1,
    total: 0,
    limit: 25,
    hasNext: false,
    hasPrev: false,
  }

  const services = ref([])
  const servicesPublic = ref([])
  const currentService = ref(null)
  const evaluations = ref([])
  const evaluationStats = ref({})
  const stats = ref({})
  const recentServices = ref([])

  const isLoading = ref(false)
  const isLoadingStats = ref(false)
  const isLoadingEvaluations = ref(false)
  const error = ref(null)

  const filters = ref({ ...DEFAULT_FILTERS })
  const pagination = ref({ ...DEFAULT_PAGINATION })

  const servicesCount = computed(() => services.value.length)
  const servicesActifs = computed(() => services.value.filter((s) => s.statut === 'actif'))
  const servicesByStatut = computed(() => {
    const grouped = {}
    services.value.forEach((service) => {
      const statut = service.statut || 'inconnu'
      grouped[statut] = (grouped[statut] || 0) + 1
    })
    return grouped
  })

  const servicesByCategorie = computed(() => {
    const grouped = {}
    services.value.forEach((service) => {
      const cat = service.categorie?.nom || 'Non catégorisé'
      grouped[cat] = (grouped[cat] || 0) + 1
    })
    return grouped
  })

  const hasFilters = computed(() => {
    return Object.entries(filters.value).some(([key, value]) => {
      const defaultValue = DEFAULT_FILTERS[key]
      return value !== defaultValue && value !== '' && value !== null
    })
  })

  const execute = async (action) => {
    isLoading.value = true
    error.value = null
    try {
      return await action()
    } catch (err) {
      error.value = err.message || 'Erreur'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const updateFromResponse = (data) => {
    if (data.pagination) {
      pagination.value = { ...pagination.value, ...data.pagination }
      filters.value.page = data.pagination.page
      filters.value.limit = data.pagination.limit
    }
  }

  const updateInList = (id, updated) => {
    const i = services.value.findIndex((s) => s.id === id)
    if (i !== -1) services.value[i] = updated
  }

  const removeFromList = (id) => {
    services.value = services.value.filter((s) => s.id !== id)
  }

  const removeFromListBulk = (ids) => {
    services.value = services.value.filter((s) => !ids.includes(s.id))
  }

  const fetchServicesPublic = async (params = {}) => {
    try {
      const res = await api.getAllPublic(params)

      const payload = res && res.data && (res.data.data || res.data.pagination) ? res.data : res

      servicesPublic.value = payload ?? {
        success: true,
        data: [],
        pagination: { page: 1, pages: 1, total: 0, limit: 25, hasNext: false, hasPrev: false },
      }
      return servicesPublic.value
    } catch (e) {
      // Annulations & erreurs réseau → on ne remonte pas d'exception
      const isCanceled =
        e?.code === 'ERR_CANCELED' || e?.name === 'CanceledError' || e?.name === 'AbortError'
      if (!isCanceled) {
        console.warn('fetchServicesPublic failed:', e)
      }
      // Assure un état cohérent pour la vue
      servicesPublic.value = {
        success: false,
        data: [],
        pagination: { page: 1, pages: 1, total: 0, limit: 25, hasNext: false, hasPrev: false },
        error: isCanceled ? 'canceled' : (e?.message ?? 'error'),
      }
      return servicesPublic.value
    }
  }

  const fetchServiceBySlug = async (slug) => {
    return execute(async () => {
      const service = await api.getOneBySlug(slug)
      currentService.value = service
      return service
    })
  }

  const submitEvaluation = async (evaluationData) => {
    return execute(async () => {
      const authStore = useAuthStore()
      const payload = {
        note: evaluationData.note,
        commentaire: evaluationData.commentaire,
      }

      if (authStore.isPublicAuthenticated) {
        payload.nom = authStore.currentPublicUser.nom
        payload.email = authStore.currentPublicUser.email
      } else {
        payload.nom_anonyme = evaluationData.nomAnonyme || 'Utilisateur anonyme'
        payload.anonyme = true
      }

      const result = await api.submitEvaluation(evaluationData.serviceId, payload)

      if (currentService.value?.id === evaluationData.serviceId) {
        await Promise.all([
          fetchServiceBySlug(currentService.value.slug),
          fetchEvaluationStats(evaluationData.serviceId),
        ])
      }

      return result
    })
  }

  const fetchEvaluations = async (serviceId, params = {}) => {
    isLoadingEvaluations.value = true
    try {
      const result = await api.getEvaluations(serviceId, params)
      evaluations.value = result.data || result
      return result
    } finally {
      isLoadingEvaluations.value = false
    }
  }

  const fetchEvaluationStats = async (serviceId) => {
    try {
      const result = await api.getEvaluationStats(serviceId)
      evaluationStats.value = result
      return result
    } catch (error) {
      console.warn('Erreur stats évaluations:', error)
      return {}
    }
  }

  const fetchServices = async (params = {}, force = false) => {
    return execute(async () => {
      const queryParams = {
        ...filters.value,
        ...params,
      }

      if (force) queryParams._t = Date.now()

      const result = await api.getAll(queryParams)

      services.value = result.data || result.services || result
      updateFromResponse(result)

      return result
    })
  }

  const fetchServiceById = async (id, force = false) => {
    return execute(async () => {
      if (!force && currentService.value?.id === id) {
        return currentService.value
      }
      const service = await api.getOne(id)
      currentService.value = service
      return service
    })
  }

  const createService = async (payload) => {
    return execute(async () => {
      const service = await api.create(payload)
      services.value.unshift(service)
      return service
    })
  }

  const updateService = async (id, payload) => {
    return execute(async () => {
      const updated = await api.update(id, payload)
      updateInList(id, updated)
      if (currentService.value?.id === id) {
        currentService.value = updated
      }
      return updated
    })
  }

  const deleteService = async (id) => {
    return execute(async () => {
      await api.delete(id)
      removeFromList(id)
      if (currentService.value?.id === id) {
        currentService.value = null
      }
      return true
    })
  }

  const bulkDeleteServices = async (ids) => {
    return execute(async () => {
      await api.bulkDelete(ids)
      removeFromListBulk(ids)
      return true
    })
  }

  const importCSV = async (file, clearExisting = false) => {
    return execute(async () => {
      const result = await api.importCSV(file, clearExisting)
      await fetchServices()
      return result
    })
  }

  const fetchStats = async () => {
    isLoadingStats.value = true
    try {
      const result = await api.getStats()
      stats.value = result
      return result
    } finally {
      isLoadingStats.value = false
    }
  }

  const fetchRecentServices = async (limit = 10) => {
    const result = await api.getRecent(limit)
    recentServices.value = result
    return result
  }

  const updateFilters = (newFilters) => {
    Object.assign(filters.value, newFilters)
  }

  const resetFilters = () => {
    filters.value = { ...DEFAULT_FILTERS }
  }

  const applyFilters = async () => {
    filters.value.page = 1
    await fetchServices()
  }

  const goToPage = async (page) => {
    filters.value.page = page
    await fetchServices({ page })
  }

  const nextPage = async () => {
    if (pagination.value.hasNext) await goToPage(filters.value.page + 1)
  }

  const previousPage = async () => {
    if (pagination.value.hasPrev) await goToPage(filters.value.page - 1)
  }

  const changeItemsPerPage = async (limit) => {
    filters.value.limit = limit
    filters.value.page = 1
    await fetchServices({ page: 1, limit })
  }

  const resetError = () => {
    error.value = null
  }

  const refreshServices = async () => await fetchServices({ force: true })

  return {
    // État
    services,
    servicesPublic,
    currentService,
    evaluations,
    evaluationStats,
    stats,
    recentServices,

    // Loading states
    isLoading,
    isLoadingStats,
    isLoadingEvaluations,
    error,

    // Filtres & pagination
    filters,
    pagination,

    // Computed
    servicesCount,
    servicesActifs,
    servicesByStatut,
    servicesByCategorie,
    hasFilters,

    // Actions publiques
    fetchServicesPublic,
    fetchServiceBySlug,

    // Évaluations
    submitEvaluation,
    fetchEvaluations,
    fetchEvaluationStats,

    // Actions admin
    fetchServices,
    fetchServiceById,
    createService,
    updateService,
    deleteService,
    bulkDeleteServices,
    importCSV,

    // Stats & données
    fetchStats,
    fetchRecentServices,

    // Navigation & filtres
    updateFilters,
    resetFilters,
    applyFilters,
    goToPage,
    nextPage,
    previousPage,
    changeItemsPerPage,

    // Utilitaires
    resetError,
    refreshServices,
  }
})
