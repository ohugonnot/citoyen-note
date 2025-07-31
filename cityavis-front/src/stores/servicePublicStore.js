'' // src/stores/servicePublicStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/servicesPublics'
import servicesPublics from '@/api/servicesPublics'

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

  const DEFAULT_RECENT_LIMIT = 10

  const services = ref([])
  const servicesPublic = ref([])
  const currentService = ref(null)
  const stats = ref({})
  const recentServices = ref([])
  const isLoading = ref(false)
  const isLoadingStats = ref(false)
  const error = ref(null)

  const filters = ref({ ...DEFAULT_FILTERS })
  const pagination = ref({ ...DEFAULT_PAGINATION })

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

  const servicesCount = computed(() => services.value.length)

  const servicesByStatut = computed(() => {
    return services.value.reduce((acc, s) => {
      const statut = s.statut || 'actif'
      acc[statut] = acc[statut] || []
      acc[statut].push(s)
      return acc
    }, {})
  })

  const servicesActifs = computed(() => services.value.filter((s) => s.statut === 'actif'))

  const servicesByCategorie = computed(() => {
    return services.value.reduce((acc, s) => {
      const c = s.categorie?.nom || 'Autres'
      acc[c] = acc[c] || []
      acc[c].push(s)
      return acc
    }, {})
  })

  const hasFilters = computed(() => {
    return Object.entries(filters.value).some(
      ([k, v]) => !['page', 'limit'].includes(k) && v != null && v !== '',
    )
  })

  const fetchServices = async (params = {}) => {
    return execute(async () => {
      const merged = {
        ...filters.value,
        ...params,
        page: params.page ?? filters.value.page,
        limit: params.limit ?? filters.value.limit,
      }
      const data = await api.getAll(merged)
      services.value = data.data || data
      updateFiltersFromResponse(data)
      updatePaginationFromResponse(data)
      return data
    })
  }

  const fetchServiceById = async (id, force = false) => {
    if (!force) {
      const found = services.value.find((s) => s.id === id)
      if (found) {
        currentService.value = found
        return found
      }
    }
    return execute(async () => {
      const s = await api.getOne(id)
      currentService.value = s
      updateInList(id, s)
      return s
    })
  }

  const createService = async (data) => {
    return execute(async () => {
      const created = await api.create(data)
      services.value.unshift(created)
      pagination.value.total += 1
      return created
    })
  }

  const updateService = async (id, data) => {
    return execute(async () => {
      const updated = await api.update(id, data)
      updateInList(id, updated)
      if (currentService.value?.id === id) currentService.value = updated
      return updated
    })
  }

  const deleteService = async (id) => {
    return execute(async () => {
      await api.delete(id)
      removeFromList(id)
      if (currentService.value?.id === id) currentService.value = null
      pagination.value.total -= 1
    })
  }

  const bulkDeleteServices = async (ids) => {
    return execute(async () => {
      await api.bulkDelete(ids)
      removeFromListBulk(ids)
      if (currentService.value && ids.includes(currentService.value.id)) currentService.value = null
      pagination.value.total -= ids.length
    })
  }

  const importCSV = async (file, clearExisting = false) => {
    return execute(async () => {
      const res = await api.importCSV(file, clearExisting)
      await fetchServices()
      return res
    })
  }

  const fetchStats = async () => {
    isLoadingStats.value = true
    try {
      const res = await api.getStats()
      stats.value = res
      return res
    } finally {
      isLoadingStats.value = false
    }
  }

  const fetchRecentServices = async (limit = DEFAULT_RECENT_LIMIT) => {
    const res = await api.getRecent(limit)
    recentServices.value = res
    return res
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
    await goToPage(filters.value.page + 1)
  }

  const previousPage = async () => {
    await goToPage(filters.value.page - 1)
  }

  const changeItemsPerPage = async (limit) => {
    filters.value.limit = limit
    filters.value.page = 1
    await fetchServices({ page: 1, limit })
  }

  const resetError = () => {
    error.value = null
  }

  const refreshServices = async () => {
    await fetchServices({ force: true })
  }

  // Méthode pour récupérer un service par slug (public)
  const fetchServicesPublic = async (params) => {
    const res = await api.getAllPublic(params)
    servicesPublic.value = res
    return res
  }

  const fetchServiceBySlug = async (slug) => {
    return execute(async () => {
      const service = await api.getOneBySlug(slug)
      currentService.value = service
      return service
    })
  }

  return {
    services,
    servicesPublic,
    currentService,
    stats,
    recentServices,
    isLoading,
    isLoadingStats,
    error,
    pagination,
    filters,

    servicesCount,
    servicesByStatut,
    servicesActifs,
    servicesByCategorie,
    hasFilters,

    fetchServices,
    fetchServicesPublic,
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
    refreshServices,
    fetchServiceBySlug,
  }
})
