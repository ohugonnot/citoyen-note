// src/stores/servicePublicStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  fetchServicesPublics,
  fetchServicePublicById,
  createServicePublic,
  updateServicePublic,
  deleteServicePublic,
  bulkDeleteServicesPublics,
  importServicesPublicsCSV,
  getServicesPublicsStats,
  getRecentServicesPublics,
} from '@/api/servicesPublics'

export const useServicePublicStore = defineStore('servicePublic', () => {
  // =============================================
  // CONSTANTES
  // =============================================
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

  const ERROR_MESSAGES = {
    FETCH_SERVICES: 'Impossible de charger les services publics',
    SERVICE_NOT_FOUND: 'Service public introuvable',
    CREATE_ERROR: 'Erreur lors de la création du service',
    UPDATE_ERROR: 'Erreur lors de la modification du service',
    DELETE_ERROR: 'Erreur lors de la suppression du service',
    BULK_DELETE_ERROR: 'Erreur lors de la suppression en masse',
    IMPORT_CSV_ERROR: "Erreur lors de l'import CSV",
    STATS_ERROR: 'Erreur lors du chargement des statistiques',
    RECENT_SERVICES_ERROR: 'Erreur lors du chargement des services récents',
  }

  const DEFAULT_RECENT_LIMIT = 10

  // =============================================
  // STATE
  // =============================================
  const services = ref([])
  const currentService = ref(null)
  const stats = ref({})
  const recentServices = ref([])
  const isLoading = ref(false)
  const isLoadingStats = ref(false)
  const error = ref(null)

  const filters = ref({ ...DEFAULT_FILTERS })
  const pagination = ref({ ...DEFAULT_PAGINATION })

  // =============================================
  // UTILITAIRES PRIVÉS
  // =============================================
  const handleError = (errorMessage, originalError) => {
    error.value = errorMessage
    console.error('Store error:', originalError)
    throw originalError
  }

  const executeAsyncAction = async (action, errorMessage) => {
    isLoading.value = true
    error.value = null

    try {
      return await action()
    } catch (err) {
      handleError(errorMessage, err)
    } finally {
      isLoading.value = false
    }
  }

  const updateFiltersFromResponse = (data) => {
    if (data.filters) {
      filters.value = { ...filters.value, ...data.filters }
    }
  }

  const updatePaginationFromResponse = (data) => {
    if (data.pagination) {
      pagination.value = { ...pagination.value, ...data.pagination }
      filters.value.page = data.pagination.page
      filters.value.limit = data.pagination.limit
    }
  }

  const updateServiceInList = (id, updatedService) => {
    const index = services.value.findIndex((s) => s.id === id)
    if (index !== -1) {
      services.value[index] = updatedService
    }
  }

  const updateCurrentServiceIfNeeded = (id, updatedService) => {
    if (currentService.value?.id === id) {
      currentService.value = updatedService
    }
  }

  const removeServiceFromList = (id) => {
    services.value = services.value.filter((s) => s.id !== id)
  }

  const removeServicesFromList = (ids) => {
    services.value = services.value.filter((s) => !ids.includes(s.id))
  }

  const clearCurrentServiceIfNeeded = (id) => {
    if (currentService.value?.id === id) {
      currentService.value = null
    }
  }

  const clearCurrentServiceIfInIds = (ids) => {
    if (currentService.value && ids.includes(currentService.value.id)) {
      currentService.value = null
    }
  }

  const updateTotal = (amount) => {
    pagination.value.total += amount
  }

  const isValidPage = (page) => {
    return page >= 1 && page <= pagination.value.totalPages
  }

  // =============================================
  // GETTERS
  // =============================================
  const servicesCount = computed(() => services.value.length)

  const servicesByStatut = computed(() => {
    return services.value.reduce((acc, service) => {
      const statut = service.statut || 'actif'
      acc[statut] = acc[statut] || []
      acc[statut].push(service)
      return acc
    }, {})
  })

  const servicesActifs = computed(() => {
    return services.value.filter((service) => service.statut === 'actif')
  })

  const servicesByCategorie = computed(() => {
    return services.value.reduce((acc, service) => {
      const categorieNom = service.categorie?.nom || 'Autres'
      acc[categorieNom] = acc[categorieNom] || []
      acc[categorieNom].push(service)
      return acc
    }, {})
  })

  const hasFilters = computed(() => {
    return Object.entries(filters.value).some(([key, value]) => {
      if (key === 'page' || key === 'limit') return false
      return value !== null && value !== '' && value !== undefined
    })
  })

  // =============================================
  // ACTIONS
  // =============================================
  const fetchServices = async (params = {}) => {
    return executeAsyncAction(async () => {
      const mergedParams = {
        ...filters.value,
        ...params,
        page: params.page ?? filters.value.page,
        limit: params.limit ?? filters.value.limit,
      }

      const data = await fetchServicesPublics(mergedParams)

      services.value = data.data || data
      updateFiltersFromResponse(data)
      updatePaginationFromResponse(data)

      return data
    }, ERROR_MESSAGES.FETCH_SERVICES)
  }

  const fetchServiceById = async (id, force = false) => {
    if (!force) {
      const cachedService = services.value.find((s) => s.id === id)
      if (cachedService) {
        currentService.value = cachedService
        return cachedService
      }
    }

    return executeAsyncAction(async () => {
      const service = await fetchServicePublicById(id)
      currentService.value = service
      updateServiceInList(id, service)
      return service
    }, ERROR_MESSAGES.SERVICE_NOT_FOUND)
  }

  const createService = async (serviceData) => {
    return executeAsyncAction(async () => {
      const newService = await createServicePublic(serviceData)
      services.value.unshift(newService)
      updateTotal(1)
      return newService
    }, ERROR_MESSAGES.CREATE_ERROR)
  }

  const updateService = async (id, serviceData) => {
    return executeAsyncAction(async () => {
      const updatedService = await updateServicePublic(id, serviceData)
      updateServiceInList(id, updatedService)
      updateCurrentServiceIfNeeded(id, updatedService)
      return updatedService
    }, ERROR_MESSAGES.UPDATE_ERROR)
  }

  const deleteService = async (id) => {
    return executeAsyncAction(async () => {
      await deleteServicePublic(id)
      removeServiceFromList(id)
      clearCurrentServiceIfNeeded(id)
      updateTotal(-1)
      return true
    }, ERROR_MESSAGES.DELETE_ERROR)
  }

  const bulkDeleteServices = async (ids) => {
    return executeAsyncAction(async () => {
      await bulkDeleteServicesPublics(ids)
      removeServicesFromList(ids)
      clearCurrentServiceIfInIds(ids)
      updateTotal(-ids.length)
      return true
    }, ERROR_MESSAGES.BULK_DELETE_ERROR)
  }

  const importCSV = async (file, clearExisting = false) => {
    return executeAsyncAction(async () => {
      const result = await importServicesPublicsCSV(file, clearExisting)
      await fetchServices()
      return result
    }, ERROR_MESSAGES.IMPORT_CSV_ERROR)
  }

  const fetchStats = async () => {
    isLoadingStats.value = true
    try {
      const statsData = await getServicesPublicsStats()
      stats.value = statsData
      return statsData
    } catch (err) {
      console.error(ERROR_MESSAGES.STATS_ERROR, err)
      throw err
    } finally {
      isLoadingStats.value = false
    }
  }

  const fetchRecentServices = async (limit = DEFAULT_RECENT_LIMIT) => {
    try {
      const recent = await getRecentServicesPublics(limit)
      recentServices.value = recent
      return recent
    } catch (err) {
      console.error(ERROR_MESSAGES.RECENT_SERVICES_ERROR, err)
      throw err
    }
  }

  // =============================================
  // ACTIONS DE NAVIGATION ET FILTRES
  // =============================================
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
    if (isValidPage(page)) {
      filters.value.page = page
      await fetchServices({ page })
    }
  }

  const nextPage = async () => {
    await goToPage(filters.value.page + 1)
  }

  const previousPage = async () => {
    await goToPage(filters.value.page - 1)
  }

  const changeItemsPerPage = async (newLimit) => {
    filters.value.limit = newLimit
    filters.value.page = 1
    await fetchServices({ page: 1, limit: newLimit })
  }

  // =============================================
  // ACTIONS UTILITAIRES
  // =============================================
  const resetError = () => {
    error.value = null
  }

  const clearCurrentService = () => {
    currentService.value = null
  }

  const refreshServices = async () => {
    await fetchServices({ force: true })
  }

  // =============================================
  // EXPORTS
  // =============================================
  return {
    // State
    services,
    currentService,
    stats,
    recentServices,
    isLoading,
    isLoadingStats,
    error,
    pagination,
    filters,

    // Getters
    servicesCount,
    servicesByStatut,
    servicesActifs,
    servicesByCategorie,
    hasFilters,

    // Actions
    fetchServices,
    fetchServiceById,
    createService,
    updateService,
    deleteService,
    bulkDeleteServices,
    importCSV,
    fetchStats,
    fetchRecentServices,
    updateFilters,
    resetFilters,
    applyFilters,
    goToPage,
    nextPage,
    previousPage,
    changeItemsPerPage,
    resetError,
    clearCurrentService,
    refreshServices,
  }
})
