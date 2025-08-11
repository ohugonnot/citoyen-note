<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue'
import StarRating from '@/components/StarRating.vue'
import { useRouter } from 'vue-router'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import { useCategorieStore } from '@/stores/categorieServiceStore'
import { storeToRefs } from 'pinia'
import { debounce } from 'lodash'
import L from 'leaflet'

// Composables
const router = useRouter()
const serviceStore = useServicePublicStore()
const categorieStore = useCategorieStore()

// Store refs
const { servicesPublic, isLoading } = storeToRefs(serviceStore)
const { categoriesOptions, isLoading: categoriesLoading } = storeToRefs(categorieStore)

// Computed pour un accès plus facile
const services = computed(() => servicesPublic.value?.data || [])
const pagination = computed(() => servicesPublic.value?.pagination || {})

// Filters
const searchTerm = ref('')
const villeFilter = ref([])
const categorieFilter = ref(null)

const radiusOptions = [
  { label: '5 km', value: 5 },
  { label: '10 km', value: 10 },
  { label: '20 km', value: 20 },
  { label: '30 km', value: 30 },
  { label: '50 km', value: 50 },
]
const selectedRadius = ref(5)

const sortOptions = [
  { label: 'Nom (A→Z)', value: { tri: 'nom', order: 'ASC' } },
  { label: 'Nom (Z→A)', value: { tri: 'nom', order: 'DESC' } },
  { label: 'Distance (proche→loin)', value: { tri: 'distance', order: 'ASC' } },
  { label: 'Note (meilleure→pire)', value: { tri: 'note', order: 'DESC' } },
]
const selectedSort = ref(sortOptions[0].value) // nom ASC par défaut

// Feuille cercle de rayon sur la carte
const radiusCircle = ref(null)
const villeSuggestions = ref([])
const villeLoading = ref(false)
const selectedVille = ref(null)
const cityCache = new Map()
const CACHE_DURATION = 30 * 60 * 1000

const getStoredViewMode = () => {
  try {
    return localStorage.getItem('viewMode') || 'grid'
  } catch (error) {
    return 'grid'
  }
}

const startPage = computed(() => {
  const maxVisible = 5
  let start = Math.max(1, pagination.value.page - Math.floor(maxVisible / 2))
  let end = Math.min(pagination.value.pages, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  return start
})

const endPage = computed(() => {
  const maxVisible = 5
  let start = Math.max(1, pagination.value.page - Math.floor(maxVisible / 2))
  let end = Math.min(pagination.value.pages, start + maxVisible - 1)

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }

  return end
})

const visiblePages = computed(() => {
  const pages = []
  for (let i = startPage.value; i <= endPage.value; i++) {
    pages.push(i)
  }
  return pages
})

// UI State
const viewMode = ref(getStoredViewMode())
const map = ref(null)
const mapContainer = ref(null)
const markers = ref([])

// Fonction utilitaire pour valider les coordonnées
const isValidCoordinate = (lat, lng) => {
  return (
    typeof lat === 'number' &&
    typeof lng === 'number' &&
    !isNaN(lat) &&
    !isNaN(lng) &&
    lat >= -90 &&
    lat <= 90 &&
    lng >= -180 &&
    lng <= 180
  )
}

const applyFilters = async () => {
  try {
    const hasVille = hasCoordsForDistance.value
    const payload = {
      search: searchTerm.value,
      categorie: categorieFilter.value,
      page: 1,
      tri: selectedSort.value.tri,
      order: selectedSort.value.order,
      ...(hasVille ? { ville: buildVilleParam(), rayon: selectedRadius.value } : {}), // ✅
    }

    await serviceStore.fetchServicesPublic(payload)
  } catch (error) {
    console.error("Erreur lors de l'application des filtres:", error)
  }
}

const getCenterFromVille = () => {
  const v = buildVilleParam()
  if (v && isValidCoordinate(Number(v.latitude), Number(v.longitude))) {
    return [Number(v.latitude), Number(v.longitude)]
  }
  return null
}

const updateRadiusCircle = (fit = false) => {
  console.log('updateRadiusCircle')
  if (!map.value) return
  const center = getCenterFromVille()
  if (radiusCircle.value && map.value.hasLayer(radiusCircle.value)) {
    map.value.removeLayer(radiusCircle.value)
  }
  radiusCircle.value = null
  if (!center || !selectedRadius.value) return

  radiusCircle.value = L.circle(center, {
    radius: selectedRadius.value * 1000,
    color: '#0d6efd',
    weight: 1,
    fillColor: '#0d6efd',
    fillOpacity: 0.08,
  }).addTo(map.value)

  if (fit) {
    try {
      const b = radiusCircle.value.getBounds()
      if (b.isValid()) map.value.fitBounds(b.pad(0.2))
    } catch {}
  }
}

watch(
  services,
  async () => {
    if (viewMode.value === 'map' && map.value) {
      await nextTick()
      setTimeout(() => {
        updateMapMarkers()
        if (hasCoordsForDistance.value) {
          updateRadiusCircle(true)
        } else {
          clearRadiusCircle()
        }
      }, 100)
    }
  },
  { immediate: false },
)

// Debounce la recherche
const debouncedSearch = debounce(() => applyFilters().catch(() => {}), 500)

const onSearchChange = () => {
  debouncedSearch()
}

const onFiltersChange = () => {
  applyFilters()
}

const onPageChange = async (event) => {
  const page = Math.floor(event.first / event.rows) + 1
  const hasVille = hasCoordsForDistance.value
  const payload = {
    search: searchTerm.value,
    categorie: categorieFilter.value,
    page,
    tri: selectedSort.value.tri,
    order: selectedSort.value.order,
    ...(hasVille ? { ville: buildVilleParam(), rayon: selectedRadius.value } : {}), // ✅
  }

  await serviceStore.fetchServicesPublic(payload)

  if (viewMode.value === 'map') {
    setTimeout(() => {
      updateMapMarkers()
      if (!hasVille) clearRadiusCircle()
    }, 100)
  }
}

const goToService = (slug) => {
  router.push({ name: 'ServicePublicDetail', params: { slug } })
}

const truncateText = (text, maxLength) => {
  if (!text || text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const switchToGrid = () => {
  viewMode.value = 'grid'
}

const switchToMap = async () => {
  viewMode.value = 'map'
  await nextTick()
  await nextTick()
  await new Promise((resolve) => setTimeout(resolve, 50))

  if (!map.value && mapContainer.value) {
    initMap()
  } else if (map.value) {
    setTimeout(() => {
      map.value.invalidateSize()
      updateMapMarkers()
      if (!hasCoordsForDistance.value) clearRadiusCircle()
    }, 100)
  }
}

watch(selectedRadius, () => {
  applyFilters().catch(() => {})
})

watch(selectedVille, () => {
  applyFilters().catch(() => {})
  if (viewMode.value === 'map') {
    if (hasCoordsForDistance.value)
      updateRadiusCircle(false) // <= PAS de fit ici
    else clearRadiusCircle()
  }
})

const initMap = () => {
  if (!mapContainer.value) return

  // S'assurer qu'il n'y a pas déjà une carte
  if (map.value) {
    try {
      map.value.remove()
    } catch (e) {
      console.warn("Erreur lors de la suppression de l'ancienne carte:", e)
    }
    map.value = null
  }

  // Vérifier que le container a des dimensions
  const rect = mapContainer.value.getBoundingClientRect()
  if (rect.width === 0 || rect.height === 0) {
    console.warn("Container de carte sans dimensions, report de l'initialisation")
    setTimeout(() => initMap(), 200)
    return
  }

  try {
    // Désactiver toutes les animations pour éviter les erreurs
    map.value = L.map(mapContainer.value, {
      zoomAnimation: false, // ❌ Désactiver
      fadeAnimation: true, // ❌ Désactiver
      markerZoomAnimation: false, // ❌ Désactiver
      zoomAnimationThreshold: 1000, // Seuil très élevé
      preferCanvas: true, // Utiliser Canvas au lieu de SVG
    }).setView([46.603354, 1.888334], 6)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '',
      maxZoom: 18,
      minZoom: 2,
    }).addTo(map.value)

    const bounds = L.latLngBounds(
      [FRANCE_BOUNDS.south, FRANCE_BOUNDS.west],
      [FRANCE_BOUNDS.north, FRANCE_BOUNDS.east],
    )
    map.value.fitBounds(bounds)

    // Attendre que la carte soit complètement chargée
    map.value.whenReady(() => {
      setTimeout(() => {
        updateMapMarkers()
        if (hasCoordsForDistance.value) updateRadiusCircle(true)
        else clearRadiusCircle()
      }, 100)
    })

    // Désactiver le zoom molette qui cause souvent l'erreur
    map.value.scrollWheelZoom.disable()

    // Ajouter des contrôles de zoom manuels
    setTimeout(() => {
      if (map.value) {
        map.value.scrollWheelZoom.enable()
      }
    }, 1000)
  } catch (error) {
    console.error("Erreur lors de l'initialisation de la carte:", error)
    map.value = null
  }
}

const updateMapMarkers = () => {
  console.log('updateMapMarkers')
  if (!map.value) return

  try {
    // Nettoyer les anciens markers de façon sécurisée
    markers.value.forEach((marker) => {
      if (marker && map.value.hasLayer(marker)) {
        map.value.removeLayer(marker)
      }
    })
    markers.value = []

    if (!services.value || services.value.length === 0) {
      map.value.setView([46.603354, 1.888334], 6)
      return
    }

    const validMarkers = []

    services.value.forEach((service) => {
      const lat = service?.coordinates?.latitude
      const lng = service?.coordinates?.longitude
      if (
        isValidCoordinate(lat, lng) &&
        (selectedVille.value || categorieFilter.value || isInFranceBounds(lat, lng))
      ) {
        try {
          const customIcon = L.divIcon({
            html: createCustomMarkerHTML(service),
            className: 'custom-marker',
            iconSize: [50, 50],
            iconAnchor: [25, 25],
            popupAnchor: [-7, -25],
          })

          const marker = L.marker([lat, lng], {
            icon: customIcon,
          }).bindPopup(`
            <div class="marker-popup">
              <h6>${service.nom}</h6>
              <p class="mb-0"><small>${service.adresse}</small></p>
              <p class="mb-1 mt-0"><small>${service.ville} ${service.code_postal}</small></p>
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rating-stars">
                  ${generateStarsHTML(service.note_moyenne || 0)}
                </div>
                <small class="text-muted">${service.note_moyenne?.toFixed(1) || '0.0'} (${service.nombre_evaluations || 0} avis)</small>
              </div>
              <button class="btn btn-primary btn-sm" onclick="window.goToServiceFromMap('${service.slug}')">
                Voir détails
              </button>
            </div>
          `)

          marker.addTo(map.value)
          validMarkers.push(marker)
        } catch (markerError) {
          console.warn('Erreur lors de la création du marker:', markerError, service)
        }
      }
    })

    markers.value = validMarkers

    // Ajuster la vue seulement si on a des markers valides
    if (!hasCoordsForDistance.value && validMarkers.length > 0) {
      try {
        const group = new L.featureGroup(validMarkers)
        const bounds = group.getBounds()
        if (bounds.isValid()) map.value.fitBounds(bounds.pad(0.1))
      } catch (boundsError) {
        console.warn('Erreur lors du calcul des bounds:', boundsError)
        map.value.setView([46.603354, 1.888334], 6)
      }
    } else if (!hasCoordsForDistance.value && validMarkers.length === 0) {
      // seulement si pas de ville ET pas de markers
      map.value.setView([46.603354, 1.888334], 6)
    }
  } catch (error) {
    console.error('Erreur dans updateMapMarkers:', error)
  }
}

const createCustomMarkerHTML = (service) => {
  const iconeCategorie = service.categorie?.icone || 'bi-building'
  const couleurCategorie = service.categorie?.couleur || '#0d6efd'

  return `
    <div class="custom-marker-container">
      <div class="marker-icon" style="background-color: ${couleurCategorie};">
        <i class="bi ${iconeCategorie} text-white"></i>
      </div>
      <div class="marker-shadow"></div>
    </div>
  `
}

const generateStarsHTML = (rating) => {
  const maxStars = 5
  let starsHTML = `<div style="display: inline-flex; gap: 1px;">`

  for (let i = 1; i <= maxStars; i++) {
    const fillPercentage = Math.max(0, Math.min(100, (rating - (i - 1)) * 100))

    starsHTML += `
      <div style="position: relative; font-size: 14px; line-height: 1;">
        <span style="color: #e9ecef;">★</span>
        <span style="
          position: absolute;
          top: 0;
          left: 0;
          width: ${fillPercentage}%;
          overflow: hidden;
          color: #ffc107;
        ">★</span>
      </div>
    `
  }

  starsHTML += `</div>`
  return starsHTML
}

const loadServiceDetails = async (slug) => {
  try {
    await serviceStore.fetchServiceBySlug(slug)
  } catch (error) {
    router.push({ name: 'ServicePublicDetail', params: { slug } })
  }
}

const getCategoryBadgeClass = (categorie) => {
  const classes = {
    administration: 'bg-primary',
    education: 'bg-success',
    sante: 'bg-danger',
    transport: 'bg-info',
  }
  return classes[categorie] || 'bg-secondary'
}

// Global function pour les markers
window.goToServiceFromMap = (slug) => {
  router.push({ name: 'ServicePublicDetail', params: { slug } })
}

const hasInitiallyLoaded = ref(false)

watch(
  viewMode,
  (newMode) => {
    try {
      localStorage.setItem('viewMode', newMode)
    } catch (error) {
      console.warn('Impossible de sauvegarder viewMode:', error)
    }
  },
  { immediate: true },
)

const searchCities = async (event) => {
  const query = event.query.toLowerCase()
  const cacheKey = query

  const cached = cityCache.get(cacheKey)
  if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
    villeSuggestions.value = cached.data
    return
  }

  if (!query || query.length < 2) {
    villeSuggestions.value = []
    return
  }

  villeLoading.value = true

  try {
    const response = await fetch(
      `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&boost=population&limit=10&fields=nom,code,codesPostaux,departement,centre`,
    )

    const cities = await response.json()

    const formattedCities = cities.map((city) => ({
      nom: city.nom,
      code: city.code,
      codePostal: city.codesPostaux?.[0] || '',
      departement: city.departement?.nom || '',
      population: city.population || 0,
      displayName: `${city.nom} (${city.departement?.nom || ''}) - ${city.codesPostaux?.[0] || ''}`,
      longitude: city.centre.coordinates[0] || null,
      latitude: city.centre.coordinates[1] || null,
    }))

    cityCache.set(cacheKey, {
      data: formattedCities,
      timestamp: Date.now(),
    })

    villeSuggestions.value = formattedCities
  } catch (error) {
    console.error('Erreur API communes:', error)
    villeSuggestions.value = []
  } finally {
    villeLoading.value = false
  }
}

const onVilleClear = () => {
  selectedVille.value = null
  villeFilter.value = []
}

const formatPopulation = (pop) => {
  if (!pop || pop === 0) return ''
  return `${pop.toLocaleString()} hab.`
}

const onVilleSelect = (event) => {
  selectedVille.value = event.value
}

// Gestionnaire d'erreur global pour Leaflet - VERSION PLUS AGRESSIVE
window.addEventListener('error', (event) => {
  if (
    event.error &&
    event.error.message &&
    (event.error.message.includes('_latLngToNewLayerPoint') ||
      event.error.message.includes('_animateZoom') ||
      event.error.message.includes('Cannot read properties of null'))
  ) {
    console.warn('Erreur Leaflet interceptée et bloquée:', event.error.message)
    event.preventDefault() // Empêche l'erreur de remonter
    event.stopPropagation()

    // Solution drastique: recréer la carte complètement
    if (map.value && viewMode.value === 'map') {
      console.warn("Recréation de la carte suite à l'erreur")
      setTimeout(() => {
        try {
          // Nettoyer complètement
          markers.value.forEach((marker) => {
            if (marker && map.value && map.value.hasLayer(marker)) {
              map.value.removeLayer(marker)
            }
          })
          markers.value = []

          if (map.value) {
            map.value.remove()
            map.value = null
          }

          // Recréer après un délai
          setTimeout(() => {
            if (mapContainer.value && viewMode.value === 'map') {
              initMap()
            }
          }, 500)
        } catch (e) {
          console.warn('Impossible de récréer la carte:', e)
        }
      }, 100)
    }

    return false
  }
})

const FRANCE_BOUNDS = {
  north: 51.2, // Nord de la métropole
  south: 42.3, // Pas en dessous de Marseille
  west: -4.8, // Bretagne / Finistère
  east: 7.5, // Alsace / frontière allemande
}
const isInFranceBounds = (lat, lng) => {
  return (
    lat >= FRANCE_BOUNDS.south &&
    lat <= FRANCE_BOUNDS.north &&
    lng >= FRANCE_BOUNDS.west &&
    lng <= FRANCE_BOUNDS.east
  )
}

const buildVilleParam = () => {
  const v = selectedVille.value
  if (v && isValidCoordinate(Number(v?.latitude), Number(v?.longitude))) {
    return {
      nom: v.nom ?? '',
      codePostal: v.codePostal ?? '',
      latitude: Number(v.latitude),
      longitude: Number(v.longitude),
    }
  }
  return null
}

const hasCoordsForDistance = computed(() => {
  const v = buildVilleParam()
  return !!(v && isValidCoordinate(v.latitude, v.longitude))
})

const clearRadiusCircle = () => {
  if (map.value && radiusCircle.value && map.value.hasLayer(radiusCircle.value)) {
    map.value.removeLayer(radiusCircle.value)
  }
  radiusCircle.value = null
}

// Lifecycle
onMounted(async () => {
  try {
    await Promise.all([serviceStore.fetchServicesPublic(), categorieStore.fetchCategoriesActives()])

    hasInitiallyLoaded.value = true

    await nextTick()
    if (viewMode.value === 'map') {
      switchToMap()
    }
  } catch (error) {
    console.error('Erreur:', error)
    hasInitiallyLoaded.value = true
  }
})

// Nettoyage lors de la destruction
onBeforeUnmount(() => {
  if (map.value) {
    try {
      // Nettoyer les markers
      markers.value.forEach((marker) => {
        if (marker && map.value.hasLayer(marker)) {
          map.value.removeLayer(marker)
        }
      })

      // Détruire la carte
      map.value.remove()
      map.value = null
    } catch (error) {
      console.warn('Erreur lors du nettoyage de la carte:', error)
    }
  }
})
</script>

<template>
  <div v-cloak class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
      <div class="col-12">
        <!-- Titre et description -->
        <div class="mb-3">
          <h1 class="display-6 fw-bold text-primary mb-2">
            <i class="bi bi-building me-3"></i>Services Publiques
          </h1>
          <p class="lead text-muted">
            Découvrez et évaluez tous les services publiques de votre région
          </p>
        </div>

        <!-- Toggle vue -->
        <div class="d-flex justify-content-center">
          <div class="btn-group" role="group" style="max-width: 300px; width: 100%">
            <button
              type="button"
              class="btn w-50"
              :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
              @click="switchToGrid"
            >
              <i class="bi bi-grid-3x3-gap me-1"></i>Liste
            </button>
            <button
              type="button"
              class="btn w-50"
              :class="viewMode === 'map' ? 'btn-primary' : 'btn-outline-primary'"
              @click="switchToMap"
            >
              <i class="bi bi-geo-alt me-1"></i>Carte
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="row g-3">
              <!-- Recherche avec icône intégrée -->
              <div class="col-md-4">
                <IconField icon-position="left">
                  <InputIcon class="bi bi-search"></InputIcon>
                  <InputText
                    id="search"
                    v-model="searchTerm"
                    placeholder="Nom du service, description..."
                    class="w-100"
                    @input="onSearchChange"
                  />
                </IconField>
              </div>

              <!-- Ville -->
              <div class="col-md-4">
                <IconField icon-position="left">
                  <InputIcon class="bi bi-geo-alt"></InputIcon>
                  <AutoComplete
                    v-model="villeFilter"
                    :suggestions="villeSuggestions"
                    option-label="displayName"
                    placeholder="Nom de la ville"
                    :min-length="2"
                    :delay="300"
                    fluid
                    class="w-100"
                    :loading="villeLoading"
                    empty-message="Aucune ville trouvée"
                    @complete="searchCities"
                    @item-select="onVilleSelect"
                    @clear="onVilleClear"
                  >
                    <template #item="slotProps">
                      <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                          <strong>{{ slotProps.item.nom }}</strong>
                          <small class="text-muted ms-2">({{ slotProps.item.departement }})</small>
                        </div>
                        <small class="text-muted">{{
                          formatPopulation(slotProps.item.population)
                        }}</small>
                      </div>
                    </template>
                  </AutoComplete>
                </IconField>
              </div>

              <!-- Catégorie -->
              <div class="col-md-4">
                <Select
                  v-model="categorieFilter"
                  :loading="categoriesLoading"
                  :options="categoriesOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Choisir une catégorie"
                  class="w-100"
                  show-clear
                  filter
                  filter-placeholder="Rechercher une catégorie"
                  @change="onFiltersChange"
                >
                  <template #option="slotProps">
                    <div class="d-flex align-items-center">
                      <i :class="slotProps.option.icon || 'bi bi-tag'" class="me-2"></i>
                      {{ slotProps.option.label }}
                    </div>
                  </template>
                </Select>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <!-- Rayon -->
              <div v-if="hasCoordsForDistance" class="col-md-4">
                <Select
                  v-model="selectedRadius"
                  :options="radiusOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Rayon autour de la ville"
                  class="w-100"
                />
              </div>

              <!-- Tri -->
              <div class="col-md-4">
                <Select
                  v-model="selectedSort"
                  :options="sortOptions"
                  option-label="label"
                  option-value="value"
                  class="w-100"
                  :disabled="!hasCoordsForDistance && selectedSort?.tri === 'distance'"
                  placeholder="Trier par"
                  @change="onFiltersChange"
                />
              </div>

              <!-- (Optionnel) bouton reset -->
              <div class="col-md-4 d-flex align-items-center">
                <button
                  class="btn btn-outline-secondary w-100"
                  @click="
                    () => {
                      selectedRadius = 5
                      selectedSort = sortOptions[0].value
                      onVilleClear()
                      searchTerm = ''
                      categorieFilter = null
                      applyFilters().catch(() => {})
                    }
                  "
                >
                  Réinitialiser les filtres
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="row">
      <div class="col-12">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
          <p class="mt-3 text-muted">Chargement des services...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="hasInitiallyLoaded && services.length === 0" class="text-center py-5">
          <i class="bi bi-search display-1 text-muted"></i>
          <h3 class="mt-3">Aucun service trouvé</h3>
          <p class="text-muted">Essayez de modifier vos critères de recherche</p>
        </div>

        <!-- Vue Grille -->
        <div v-else-if="viewMode === 'grid'">
          <div class="row g-4">
            <div v-for="service in services" :key="service.id" class="col-lg-4 col-md-6">
              <div
                class="card h-100 shadow-sm border-0 service-card"
                @click="goToService(service.slug)"
              >
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="card-title text-primary mb-0 flex-grow-1">
                      {{ service.nom }}
                    </h5>
                    <span
                      v-if="service.categorie"
                      class="badge rounded-pill ms-2"
                      :class="getCategoryBadgeClass(service.categorie.nom)"
                    >
                      {{ service.categorie.nom }}
                    </span>
                  </div>

                  <div class="mb-3">
                    <div v-if="service.ville" class="d-flex align-items-center mb-2">
                      <i class="bi bi-geo-alt text-muted me-2"></i>
                      <small class="text-muted">
                        {{ service.adresse }}<br />
                        {{ service.ville }} {{ service.code_postal }}
                      </small>
                    </div>

                    <div class="d-flex align-items-center">
                      <StarRating
                        :rating="service.note_moyenne || 0"
                        :show-value="true"
                        class="me-2"
                      />
                      <small class="text-muted"> {{ service.nombre_evaluations || 0 }} avis </small>
                    </div>
                  </div>

                  <p v-if="service.description" class="card-text text-muted flex-grow-1">
                    {{ truncateText(service.description, 120) }}
                  </p>

                  <div class="mt-auto pt-2">
                    <router-link
                      :to="{ name: 'ServicePublicDetail', params: { slug: service.slug } }"
                      class="btn btn-outline-primary btn-sm"
                      @click="loadServiceDetails(service.slug)"
                    >
                      <i class="bi bi-eye me-1"></i> Voir détails
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <nav v-if="pagination.pages > 1" class="mt-5">
            <ul class="pagination justify-content-center">
              <!-- Bouton Précédent -->
              <li class="page-item" :class="{ disabled: pagination.page === 1 }">
                <button
                  class="page-link"
                  :disabled="pagination.page === 1"
                  @click="
                    onPageChange({
                      first: (pagination.page - 2) * pagination.limit,
                      rows: pagination.limit,
                    })
                  "
                >
                  Précédent
                </button>
              </li>

              <!-- Première page si on n'est pas au début -->
              <li v-if="startPage > 1" class="page-item">
                <button
                  class="page-link"
                  @click="onPageChange({ first: 0, rows: pagination.limit })"
                >
                  1
                </button>
              </li>

              <!-- Points de suspension si on est loin du début -->
              <li v-if="startPage > 2" class="page-item disabled">
                <span class="page-link">...</span>
              </li>

              <!-- Pages visibles autour de la page courante -->
              <li
                v-for="page in visiblePages"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.page }"
              >
                <button
                  class="page-link"
                  @click="
                    onPageChange({ first: (page - 1) * pagination.limit, rows: pagination.limit })
                  "
                >
                  {{ page }}
                </button>
              </li>

              <!-- Points de suspension si on est loin de la fin -->
              <li v-if="endPage < pagination.pages - 1" class="page-item disabled">
                <span class="page-link">...</span>
              </li>

              <!-- Dernière page si on n'est pas à la fin -->
              <li v-if="endPage < pagination.pages" class="page-item">
                <button
                  class="page-link"
                  @click="
                    onPageChange({
                      first: (pagination.pages - 1) * pagination.limit,
                      rows: pagination.limit,
                    })
                  "
                >
                  {{ pagination.pages }}
                </button>
              </li>

              <!-- Bouton Suivant -->
              <li class="page-item" :class="{ disabled: pagination.page === pagination.pages }">
                <button
                  class="page-link"
                  :disabled="pagination.page === pagination.pages"
                  @click="
                    onPageChange({
                      first: pagination.page * pagination.limit,
                      rows: pagination.limit,
                    })
                  "
                >
                  Suivant
                </button>
              </li>
            </ul>
          </nav>
        </div>

        <!-- Vue Carte -->
        <div v-show="viewMode === 'map' && services.length !== 0" class="map-container">
          <div
            ref="mapContainer"
            class="leaflet-map rounded shadow-sm"
            style="height: 600px; width: 100%"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.service-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.service-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
  border-left-color: var(--bs-primary);
}

.rating-stars i {
  font-size: 0.9rem;
}

.leaflet-map {
  z-index: 1;
}

.marker-popup {
  min-width: 200px;
}

.marker-popup h6 {
  color: var(--bs-primary);
  margin-bottom: 0.5rem;
}

.marker-popup .rating-stars i {
  font-size: 0.8rem;
}

.btn-group .btn {
  border-radius: 0;
}

.btn-group .btn:first-child {
  border-top-left-radius: 0.375rem;
  border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
  border-top-right-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
}

@media (max-width: 768px) {
  .display-6 {
    font-size: 1.5rem;
  }

  .leaflet-map {
    height: 400px !important;
  }
}

/* 🎯 STYLES POUR LES MARKERS PERSONNALISÉS */
:deep(.custom-marker) {
  background: transparent !important;
  border: none !important;
}

:deep(.custom-marker-container) {
  position: relative;
  width: 40px;
  height: 40px;
}

:deep(.marker-icon) {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  position: relative;
  z-index: 100;
  font-size: 14px;
  cursor: pointer;
  transition: transform 0.2s ease;
}

:deep(.marker-icon:hover) {
  transform: scale(1.1);
}

:deep(.marker-shadow) {
  position: absolute;
  bottom: -2px;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 8px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 50%;
  z-index: 99;
}

/* Style pour la popup */
:deep(.marker-popup) {
  min-width: 250px;
}

:deep(.marker-popup h6) {
  color: #333;
  margin-bottom: 0.5rem;
}

:deep(.rating-stars) {
  display: inline-block;
}
</style>
