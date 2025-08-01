// src/stores/categorieStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  fetchCategoriesServices,
  createCategorieService,
  updateCategorieService,
  deleteCategorieService,
} from '@/api/categoriesServices'

export const useCategorieStore = defineStore('categorie', () => {
  // State
  const categories = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const lastFetch = ref(null)

  // Getters
  const categoriesActives = computed(() => {
    return categories.value.filter((cat) => cat.actif === true)
  })

  const categoriesOptions = computed(() => [
    { label: 'Toutes les catégories', value: null },
    ...categoriesActives.value.map((cat) => ({
      label: cat.nom,
      value: cat.id,
      data: cat, // Garder l'objet complet si besoin
    })),
  ])

  const getCategorieById = computed(() => {
    return (id) => categories.value.find((cat) => cat.id === id)
  })

  const categoriesCount = computed(() => categories.value.length)

  const categoriesActivesCount = computed(() => categoriesActives.value.length)

  // Actions
  const fetchCategories = async (params = {}) => {
    // Cache simple : ne refetch que si > 5 minutes ou force
    const force = params.force || false
    if (!force && lastFetch.value && Date.now() - lastFetch.value < 300000) {
      return { data: categories.value }
    }

    if (isLoading.value) return { data: categories.value }

    isLoading.value = true
    error.value = null

    try {
      const data = await fetchCategoriesServices(params)
      categories.value = data.data || data // Selon votre structure de réponse
      lastFetch.value = Date.now()
      return data
    } catch (err) {
      error.value = 'Impossible de charger les catégories'
      console.error('Store error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const createCategorie = async (categorieData) => {
    isLoading.value = true
    error.value = null

    try {
      const newCategorie = await createCategorieService(categorieData)
      categories.value.unshift(newCategorie)
      return newCategorie
    } catch (err) {
      error.value = 'Erreur lors de la création'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const updateCategorie = async (id, categorieData) => {
    isLoading.value = true
    error.value = null

    try {
      const updatedCategorie = await updateCategorieService(id, categorieData)

      const index = categories.value.findIndex((cat) => cat.id === id)
      if (index !== -1) {
        categories.value[index] = updatedCategorie
      }

      return updatedCategorie
    } catch (err) {
      error.value = 'Erreur lors de la modification'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const deleteCategorie = async (id) => {
    isLoading.value = true
    error.value = null

    try {
      await deleteCategorieService(id)
      categories.value = categories.value.filter((cat) => cat.id !== id)

      return true
    } catch (err) {
      error.value = 'Erreur lors de la suppression'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const refreshCategories = async () => {
    return await fetchCategories({ force: true })
  }

  const fetchCategoriesActives = async (force = false) => {
    await fetchCategories({ force })
    return categoriesActives.value
  }

  const clearCache = () => {
    lastFetch.value = null
  }

  const resetError = () => {
    error.value = null
  }

  const resetState = () => {
    categories.value = []
    error.value = null
    lastFetch.value = null
  }

  return {
    // State
    categories,
    isLoading,
    error,
    lastFetch,

    // Getters (computed)
    categoriesActives,
    categoriesOptions,
    getCategorieById,
    categoriesCount,
    categoriesActivesCount,

    // Actions
    fetchCategories,
    fetchCategoriesActives,
    createCategorie,
    updateCategorie,
    deleteCategorie,
    refreshCategories,
    clearCache,
    resetError,
    resetState,
  }
})
