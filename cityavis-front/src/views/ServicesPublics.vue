<script setup>
import { ref, onMounted, nextTick, computed, watch } from 'vue'
import StarRating from '@/components/StarRating.vue'
import { useRouter } from 'vue-router'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import { storeToRefs } from 'pinia'
import { debounce } from 'lodash'
import L from 'leaflet'

// Composables
const router = useRouter()
const serviceStore = useServicePublicStore()

// Store refs
const { servicesPublic, isLoading } = storeToRefs(serviceStore)

// Computed pour un accès plus facile
const services = computed(() => servicesPublic.value?.data || [])
const pagination = computed(() => servicesPublic.value?.pagination || {})
const categories = computed(() => servicesPublic.value?.categories || [])

// Filters
const searchTerm = ref('')
const villeFilter = ref([])
const categorieFilter = ref(null)

const getStoredViewMode = () => {
  try {
    return localStorage.getItem('viewMode') || 'grid'
  } catch (error) {
    return 'grid' // fallback si localStorage pas dispo
  }
}
// UI State
const viewMode = ref(getStoredViewMode())
const map = ref(null)
const mapContainer = ref(null)
const markers = ref([])

// Options
const categorieOptions = computed(() => {
  const defaultOption = { label: 'Toutes les catégories', value: null }

  const categoriesFromApi = categories.value.map((cat) => ({
    label: cat.nom,
    value: cat.nom,
  }))

  return [defaultOption, ...categoriesFromApi]
})

// Methods
const applyFilters = async () => {
  try {
    await serviceStore.fetchServicesPublic({
      search: searchTerm.value,
      ville: villeFilter.value || [],
      categorie: categorieFilter.value,
      page: 1,
    })

    // ✅ Toujours mettre à jour la carte après une recherche
    if (viewMode.value === 'map') {
      await nextTick()
      updateMapMarkers()
    }
  } catch (error) {
    console.error("Erreur lors de l'application des filtres:", error)
  }
}

watch(
  services,
  async () => {
    if (viewMode.value === 'map' && map.value) {
      await nextTick()
      updateMapMarkers()
    }
  },
  { immediate: false },
)

// Debounce la recherche avec Lodash
const debouncedSearch = debounce(applyFilters, 300)

const onSearchChange = () => {
  debouncedSearch()
}

const onFiltersChange = () => {
  applyFilters()
}

const onPageChange = async (event) => {
  const page = Math.floor(event.first / event.rows) + 1
  await serviceStore.fetchServicesPublic({
    page,
  })

  if (viewMode.value === 'map') {
    updateMapMarkers()
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
    }, 100)
  }
}

const initMap = () => {
  if (!mapContainer.value || map.value) return

  map.value = L.map(mapContainer.value).setView([46.603354, 1.888334], 6)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '',
  }).addTo(map.value)

  updateMapMarkers()
}

const updateMapMarkers = () => {
  if (!map.value) return

  // Supprimer les anciens markers
  markers.value.forEach((marker) => map.value.removeLayer(marker))
  markers.value = []

  if (!services.value || services.value.length === 0) {
    map.value.setView([46.603354, 1.888334], 6)
    return
  }

  // Ajouter les nouveaux markers
  services.value.forEach((service) => {
    if (service?.coordinates?.latitude && service?.coordinates?.longitude) {
      // 🌟 CRÉER L'ICÔNE PERSONNALISÉE
      const customIcon = L.divIcon({
        html: createCustomMarkerHTML(service),
        className: 'custom-marker',
        iconSize: [50, 50],
        iconAnchor: [25, 25],
        popupAnchor: [-7, -25],
      })

      // Utiliser l'icône personnalisée au lieu du marker par défaut
      const marker = L.marker([service?.coordinates?.latitude, service?.coordinates?.longitude], {
        icon: customIcon,
      }).bindPopup(`
          <div class="marker-popup">
            <h6>${service.nom}</h6>
            <p class="mb-1"><small>${service.ville} ${service.code_postal}</small></p>
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
      markers.value.push(marker)
    }
  })

  // Ajuster la vue pour inclure tous les markers
  if (markers.value.length > 0) {
    const group = new L.featureGroup(markers.value)
    map.value.fitBounds(group.getBounds().pad(0.1))
  } else {
    // ✅ Si aucun marker avec coordonnées, revenir à la vue France
    map.value.setView([46.603354, 1.888334], 6)
  }
}

// 🎨 FONCTION POUR CRÉER L'HTML DU MARKER
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

// 🎯 Recherche des villes (ton code existant)
const villeSuggestions = ref([])
const villeLoading = ref(false)
const selectedVille = ref(null)

const cityCache = new Map()
const CACHE_DURATION = 30 * 60 * 1000

const searchCities = async (event) => {
  const query = event.query.toLowerCase()
  const cacheKey = query

  // 🎯 Vérifier le cache
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
      `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&boost=population&limit=10&fields=nom,code,codesPostaux,departement,population`,
    )

    const cities = await response.json()

    const formattedCities = cities.map((city) => ({
      nom: city.nom,
      code: city.code,
      codePostal: city.codesPostaux?.[0] || '',
      departement: city.departement?.nom || '',
      population: city.population || 0,
      displayName: `${city.nom} (${city.departement?.nom || ''}) - ${city.codesPostaux?.[0] || ''}`,
    }))

    // 🎯 Mettre en cache
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
  onFiltersChange()
}

const formatPopulation = (pop) => {
  if (!pop || pop === 0) return ''
  return `${pop.toLocaleString()} hab.`
}

const onVilleSelect = (event) => {
  selectedVille.value = event.value
  onFiltersChange()
}

// Lifecycle
onMounted(async () => {
  try {
    await serviceStore.fetchServicesPublic()
    hasInitiallyLoaded.value = true

    await nextTick()
    if (viewMode.value === 'map' && mapContainer.value && !map.value) {
      initMap()
    }
  } catch (error) {
    console.error('Erreur:', error)
    hasInitiallyLoaded.value = true
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

              <!-- Ville (pareil) -->
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

              <!-- Catégorie avec filtre et groupes -->
              <div class="col-md-4">
                <Select
                  v-model="categorieFilter"
                  :options="categorieOptions"
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

              <li
                v-for="page in Math.min(pagination.pages, 10)"
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
