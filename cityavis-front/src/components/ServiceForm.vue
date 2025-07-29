<template>
  <form @submit.prevent="onSubmit">
    <div class="row g-4">
      <!-- Informations générales -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">
              <i class="bi bi-info-circle-fill text-primary me-2"></i>
              Informations générales
            </h5>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <!-- Nom -->
              <div class="col-md-8">
                <label for="nom" class="form-label fw-medium">
                  Nom du service <span class="text-danger">*</span>
                </label>
                <InputText
                  id="nom"
                  v-model="localFormData.nom"
                  :class="['form-control', { 'is-invalid': errors.nom }]"
                  required
                />
                <div v-if="errors.nom" class="invalid-feedback">
                  {{ errors.nom }}
                </div>
              </div>

              <!-- Catégorie -->
              <div class="col-md-4">
                <label for="categorie" class="form-label fw-medium">
                  Catégorie <span class="text-danger">*</span>
                </label>
                <Select
                  id="categorie"
                  v-model="localFormData.categorie"
                  :options="categorieStore.categories"
                  option-label="nom"
                  option-value="id"
                  placeholder="Sélectionner..."
                  :class="['form-control p-0', { 'is-invalid': categorieStore.error }]"
                  :loading="categorieStore.isLoading"
                />
                <div v-if="categorieStore.error" class="invalid-feedback">
                  {{ categorieStore.error }}
                </div>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label for="description" class="form-label fw-medium"> Description </label>
                <Textarea
                  id="description"
                  v-model="localFormData.description"
                  :class="['form-control', { 'is-invalid': errors.description }]"
                  rows="4"
                />
                <div v-if="errors.description" class="invalid-feedback">
                  {{ errors.description }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Localisation -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">
              <i class="bi bi-geo-alt-fill text-danger me-2"></i>
              Localisation
            </h5>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <!-- Adresse complète -->
              <div class="col-12">
                <label for="adresse" class="form-label fw-medium">
                  Adresse complète <span class="text-danger">*</span>
                </label>
                <InputText
                  id="adresse"
                  v-model="localFormData.adresse"
                  :class="['form-control', { 'is-invalid': errors.adresse }]"
                  required
                  @change="rechercherCoordonnees"
                />
                <div v-if="errors.adresse" class="invalid-feedback">
                  {{ errors.adresse }}
                </div>
                <small class="form-text text-muted">
                  <i class="bi bi-info-circle me-1"></i>
                  Les coordonnées GPS seront automatiquement récupérées
                </small>
              </div>

              <!-- Code postal et Ville -->
              <div class="col-md-4">
                <label for="code_postal" class="form-label fw-medium">
                  Code postal <span class="text-danger">*</span>
                </label>
                <InputText
                  id="code_postal"
                  v-model="localFormData.code_postal"
                  :class="['form-control', { 'is-invalid': errors.code_postal }]"
                  maxlength="5"
                  pattern="[0-9]{5}"
                  required
                  @change="rechercherCoordonnees"
                />
                <div v-if="errors.code_postal" class="invalid-feedback">
                  {{ errors.code_postal }}
                </div>
              </div>

              <div class="col-md-8">
                <label for="ville" class="form-label fw-medium">
                  Ville <span class="text-danger">*</span>
                </label>
                <InputText
                  id="ville"
                  v-model="localFormData.ville"
                  :class="['form-control', { 'is-invalid': errors.ville }]"
                  required
                  @change="rechercherCoordonnees"
                />
                <div v-if="errors.ville" class="invalid-feedback">
                  {{ errors.ville }}
                </div>
              </div>

              <!-- Coordonnées GPS (lecture seule) -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Latitude</label>
                <InputText
                  v-model="localFormData.latitude"
                  class="form-control"
                  readonly
                  placeholder="Automatique"
                />
              </div>

              <div class="col-md-6">
                <label class="form-label fw-medium">Longitude</label>
                <InputText
                  v-model="localFormData.longitude"
                  class="form-control"
                  readonly
                  placeholder="Automatique"
                />
              </div>

              <!-- 🗺️ Carte Leaflet -->
              <div class="col-12" v-if="localFormData.latitude && localFormData.longitude">
                <div class="mt-3">
                  <label class="form-label fw-medium">
                    <i class="bi bi-map me-2"></i>
                    Aperçu de l'emplacement
                  </label>
                  <div
                    ref="mapContainer"
                    id="leaflet-map"
                    class="border rounded"
                    style="height: 300px; width: 100%"
                  ></div>
                  <small class="form-text text-muted mt-2 d-block">
                    <i class="bi bi-info-circle me-1"></i>
                    Vous pouvez déplacer le marqueur pour ajuster précisément la position
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">
              <i class="bi bi-telephone-fill text-success me-2"></i>
              Informations de contact
            </h5>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label for="telephone" class="form-label fw-medium"> Téléphone </label>
                <InputText
                  id="telephone"
                  v-model="localFormData.telephone"
                  :class="['form-control', { 'is-invalid': errors.telephone }]"
                  type="tel"
                />
                <div v-if="errors.telephone" class="invalid-feedback">
                  {{ errors.telephone }}
                </div>
              </div>

              <div class="col-md-4">
                <label for="email" class="form-label fw-medium"> Email </label>
                <InputText
                  id="email"
                  v-model="localFormData.email"
                  :class="['form-control', { 'is-invalid': errors.email }]"
                  type="email"
                />
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email }}
                </div>
              </div>

              <div class="col-md-4">
                <label for="site_web" class="form-label fw-medium"> Site web </label>
                <InputText
                  id="site_web"
                  v-model="localFormData.site_web"
                  :class="['form-control', { 'is-invalid': errors.site_web }]"
                  type="url"
                />
                <div v-if="errors.site_web" class="invalid-feedback">
                  {{ errors.site_web }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Horaires et Accessibilité -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-light border-0 py-3">
            <h5 class="card-title mb-0 fw-semibold">
              <i class="bi bi-clock-fill text-warning me-2"></i>
              Horaires et accessibilité
            </h5>
          </div>
          <div class="card-body p-4">
            <!-- Horaires -->
            <div class="mb-4">
              <label class="form-label fw-medium">Horaires d'ouverture</label>
              <div class="row g-2">
                <div v-for="(jour, index) in joursOuverture" :key="index" class="col-md-6 col-lg-4">
                  <div class="card border">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-check-label fw-medium">
                          {{ jour.nom }}
                        </label>
                        <div class="form-check form-switch">
                          <input v-model="jour.ouvert" class="form-check-input" type="checkbox" />
                        </div>
                      </div>
                      <div v-if="jour.ouvert" class="row g-2">
                        <div class="col-6">
                          <InputText
                            v-model="jour.heureOuverture"
                            class="form-control form-control-sm"
                            placeholder="09:00"
                            type="time"
                          />
                        </div>
                        <div class="col-6">
                          <InputText
                            v-model="jour.heureFermeture"
                            class="form-control form-control-sm"
                            placeholder="17:00"
                            type="time"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Accessibilité et Statut -->
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input
                    v-model="localFormData.accessibilite_pmr"
                    class="form-check-input"
                    type="checkbox"
                    id="accessibilite"
                  />
                  <label class="form-check-label fw-medium" for="accessibilite">
                    <i class="bi bi-universal-access me-2"></i>
                    Accessible aux personnes à mobilité réduite
                  </label>
                </div>
              </div>

              <div class="col-md-6">
                <label for="statut" class="form-label fw-medium"> Statut du service </label>
                <Select
                  id="statut"
                  v-model="localFormData.statut"
                  :options="statutsDisponibles"
                  option-label="label"
                  option-value="value"
                  placeholder="Sélectionner le statut"
                  class="form-control p-0"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <slot name="actions"></slot>
    </div>
    <Toast />
  </form>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, onUnmounted, reactive } from 'vue'
import L from 'leaflet'
import { debounce } from 'lodash'
import { useCategorieStore } from '@/stores/categorieServiceStore'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

const props = defineProps({
  formData: Object,
  errors: Object,
  loading: Boolean,
})

const categorieStore = useCategorieStore()
onMounted(async () => {
  await categorieStore.fetchCategories()
})

// Options pour les statuts
const statutsDisponibles = ref([
  { label: 'Actif', value: 'actif' },
  { label: 'Fermé temporairement', value: 'ferme' },
  { label: 'En travaux', value: 'travaux' },
])

// Jours de la semaine pour les horaires
const joursOuverture = ref([
  { nom: 'Lundi', code: 'lundi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Mardi', code: 'mardi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Mercredi', code: 'mercredi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Jeudi', code: 'jeudi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Vendredi', code: 'vendredi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Samedi', code: 'samedi', ouvert: false, heureOuverture: '', heureFermeture: '' },
  { nom: 'Dimanche', code: 'dimanche', ouvert: false, heureOuverture: '', heureFermeture: '' },
])

// 🎯 Construction des horaires depuis joursOuverture
const construireHoraires = () => {
  const horaires = {}

  joursOuverture.value.forEach((jour) => {
    if (jour.ouvert && jour.heureOuverture && jour.heureFermeture) {
      horaires[jour.code] = {
        ouvert: true,
        ouverture: jour.heureOuverture,
        fermeture: jour.heureFermeture,
      }
    } else {
      horaires[jour.code] = {
        ouvert: false,
      }
    }
  })

  return horaires
}

// 🎯 Peuplement de joursOuverture depuis horaires (pour l'édition)
const peuplerJoursOuvertureDepuisHoraires = (horaires) => {
  if (!horaires) return

  joursOuverture.value.forEach((jour) => {
    const horaireJour = horaires[jour.code]
    if (horaireJour) {
      jour.ouvert = horaireJour.ouvert || false
      jour.heureOuverture = horaireJour.ouverture || ''
      jour.heureFermeture = horaireJour.fermeture || ''
    } else {
      jour.ouvert = false
      jour.heureOuverture = ''
      jour.heureFermeture = ''
    }
  })
}

const emit = defineEmits(['submit', 'update-coordonnees', 'update-form-data'])

// 🎯 Initialisation avec horaires construits
const localFormData = reactive({
  ...props.formData,
  horaires: construireHoraires(),
})

// 🎯 Initialisation pour l'édition au montage
onMounted(() => {
  // Si on a des horaires dans les props (mode édition), on peuple joursOuverture
  if (props.formData.horaires) {
    peuplerJoursOuvertureDepuisHoraires(props.formData.horaires)
  }
})

// 🎯 Synchronisation props → local (pour l'édition)
watch(
  () => props.formData,
  (newFormData) => {
    Object.assign(localFormData, newFormData)

    // 🎯 Si on reçoit des horaires, on met à jour joursOuverture
    if (newFormData.horaires) {
      peuplerJoursOuvertureDepuisHoraires(newFormData.horaires)
    }
  },
  { deep: true, immediate: true },
)

// 🎯 Watch sur joursOuverture pour mettre à jour les horaires
watch(
  joursOuverture,
  () => {
    localFormData.horaires = construireHoraires()
  },
  { deep: true },
)

// 🎯 Synchronisation local → parent
watch(
  localFormData,
  (newData) => {
    emit('update-form-data', { ...newData })
  },
  { deep: true },
)

const mapContainer = ref(null)
let map = null
let marker = null

const onSubmit = () => {
  // 🎯 S'assurer que les horaires sont à jour avant submit
  localFormData.horaires = construireHoraires()
  emit('submit')
}

const updateMarkerPopup = () => {
  if (marker && map) {
    const popupContent = `
      <div class="text-center p-2" style="min-width: 200px;">
        <div class="mb-2">
          <i class="bi bi-building text-primary me-2"></i>
          <strong>${localFormData.nom || 'Service Public'}</strong>
        </div>
        ${
          localFormData.adresse
            ? `<div class="mb-1">
                <i class="bi bi-geo-alt text-danger me-2"></i>
                <small>${localFormData.adresse}</small>
              </div>`
            : ''
        }
        <div class="mb-1">
          <i class="bi bi-mailbox text-info me-2"></i>
          <small>${localFormData.code_postal} ${localFormData.ville}</small>
        </div>
        <div class="mt-2 text-muted">
          <small>📍 ${parseFloat(localFormData.latitude).toFixed(4)}, ${parseFloat(localFormData.longitude).toFixed(4)}</small>
        </div>
      </div>
    `
    if (!marker.getPopup()) {
      marker
        .bindPopup(popupContent, {
          autoClose: false,
          closeOnClick: false,
        })
        .openPopup()
    } else {
      marker.setPopupContent(popupContent)
      if (!marker.isPopupOpen()) marker.openPopup()
    }
  }
}

const updateMarkerPosition = () => {
  if (marker && map && localFormData.latitude && localFormData.longitude) {
    const newLatLng = [parseFloat(localFormData.latitude), parseFloat(localFormData.longitude)]
    marker.setLatLng(newLatLng)
    map.setView(newLatLng, map.getZoom())
  }
}

const initMap = async () => {
  if (!localFormData.latitude || !localFormData.longitude || !mapContainer.value) return
  await nextTick()

  if (map) {
    map.remove()
    map = null
    marker = null
  }

  map = L.map('leaflet-map', {
    scrollWheelZoom: false,
    zoomControl: true,
  }).setView([localFormData.latitude, localFormData.longitude], 15)

  map.on('click', () => map.scrollWheelZoom.enable())

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '',
  }).addTo(map)

  const customIcon = L.divIcon({
    html: '<i class="bi bi-geo-alt-fill text-danger fs-3"></i>',
    iconSize: [30, 30],
    className: 'custom-div-icon',
  })

  marker = L.marker([localFormData.latitude, localFormData.longitude], {
    draggable: true,
    icon: customIcon,
  }).addTo(map)

  updateMarkerPopup()
  marker.openPopup()

  // 🎯 Mutation des coordonnées via le marker
  marker.on('dragend', function (e) {
    const position = e.target.getLatLng()
    localFormData.latitude = position.lat.toFixed(6)
    localFormData.longitude = position.lng.toFixed(6)

    toast.add({
      severity: 'info',
      summary: 'Position ajustée',
      detail: 'Les coordonnées ont été mises à jour manuellement',
      life: 3000,
    })
  })

  setTimeout(() => {
    if (map) map.invalidateSize()
  }, 100)
}

const handleFormDataChanges = debounce(() => {
  if (marker) updateMarkerPopup()
  if (localFormData.latitude && localFormData.longitude) {
    if (marker && map) {
      updateMarkerPosition()
    } else if (mapContainer.value) {
      setTimeout(() => initMap(), 100)
    }
  }
}, 300)

// 🎯 Watch sur les données locales
watch(
  [
    () => localFormData.nom,
    () => localFormData.adresse,
    () => localFormData.ville,
    () => localFormData.code_postal,
    () => localFormData.latitude,
    () => localFormData.longitude,
  ],
  handleFormDataChanges,
  { immediate: true, deep: true },
)

const rechercherCoordonnees = debounce(async () => {
  if (
    !localFormData.adresse.trim() ||
    !localFormData.ville.trim() ||
    !localFormData.code_postal.trim()
  ) {
    localFormData.latitude = null
    localFormData.longitude = null

    // 🗺️ Détruire la carte si pas d'adresse complète
    if (map) {
      map.remove()
      map = null
      marker = null
    }
    return
  }

  try {
    const adresse = `${localFormData.adresse}, ${localFormData.code_postal} ${localFormData.ville}`
    const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(adresse)}&limit=1`

    const response = await fetch(url)
    const data = await response.json()

    if (data.features && data.features.length > 0) {
      const feature = data.features[0]
      localFormData.latitude = feature.geometry.coordinates[1]
      localFormData.longitude = feature.geometry.coordinates[0]

      // 🗺️ Initialiser la carte avec les nouvelles coordonnées
      setTimeout(() => {
        initMap()
      }, 200)
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Adresse non trouvée',
        detail: 'Impossible de géolocaliser cette adresse',
        life: 4000,
      })
    }
  } catch (error) {
    console.error('Erreur géocodage:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur de géolocalisation',
      detail: 'Une erreur est survenue lors de la recherche',
      life: 4000,
    })
  }
}, 1000)

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
})
</script>

<style scoped>
#leaflet-map {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.custom-div-icon {
  background: transparent;
  border: none;
}
</style>
