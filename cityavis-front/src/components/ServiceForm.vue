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
              <div v-if="localFormData.latitude && localFormData.longitude" class="col-12">
                <div class="mt-3">
                  <label class="form-label fw-medium">
                    <i class="bi bi-map me-2"></i>
                    Aperçu de l'emplacement
                  </label>
                  <div
                    id="leaflet-map"
                    ref="mapContainer"
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
              <div class="col-md-6">
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

              <div class="col-md-6">
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

              <div class="col-md-6">
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
              <div class="row g-3">
                <div v-for="(jour, index) in joursOuverture" :key="index" class="col-md-6 col-lg-4">
                  <div class="card border">
                    <div class="card-header bg-light py-2">
                      <div class="d-flex align-items-center justify-content-between">
                        <label class="form-check-label fw-medium mb-0">
                          {{ jour.nom }}
                        </label>
                        <div class="form-check form-switch">
                          <input
                            v-model="jour.ouvert"
                            class="form-check-input"
                            type="checkbox"
                            @change="toggleJour(jour)"
                          />
                        </div>
                      </div>
                    </div>

                    <div v-if="jour.ouvert" class="card-body p-3">
                      <!-- Liste des créneaux -->
                      <div
                        v-for="(creneau, creneauIndex) in jour.creneaux"
                        :key="creneauIndex"
                        class="mb-2"
                      >
                        <div class="row g-2 align-items-center">
                          <div class="col-5">
                            <InputText
                              v-model="creneau.ouverture"
                              class="form-control form-control-sm"
                              placeholder="09:00"
                              type="time"
                            />
                          </div>
                          <div class="col-1 text-center">
                            <span class="text-muted">-</span>
                          </div>
                          <div class="col-5">
                            <InputText
                              v-model="creneau.fermeture"
                              class="form-control form-control-sm"
                              placeholder="17:00"
                              type="time"
                            />
                          </div>
                          <div class="col-1">
                            <Button
                              v-if="jour.creneaux.length > 1"
                              icon="pi pi-times"
                              severity="danger"
                              size="small"
                              text
                              class="p-1"
                              @click="supprimerCreneau(jour, creneauIndex)"
                            />
                          </div>
                        </div>
                      </div>

                      <!-- Bouton ajouter créneau -->
                      <div class="d-grid mt-2">
                        <Button
                          icon="pi pi-plus"
                          label="Ajouter un créneau"
                          severity="secondary"
                          size="small"
                          outlined
                          @click="ajouterCreneau(jour)"
                        />
                      </div>

                      <!-- Aperçu des horaires -->
                      <div
                        v-if="jour.creneaux.some((c) => c.ouverture && c.fermeture)"
                        class="mt-2 p-2 bg-light rounded"
                      >
                        <small class="text-muted">
                          <i class="bi bi-clock me-1"></i>
                          {{ formatApercu(jour.creneaux) }}
                        </small>
                      </div>
                    </div>

                    <div v-else class="card-body p-3 text-center text-muted">
                      <i class="bi bi-x-circle me-1"></i>
                      <small>Fermé</small>
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
                    id="accessibilite"
                    v-model="localFormData.accessibilite_pmr"
                    class="form-check-input"
                    type="checkbox"
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

const statutsDisponibles = ref([
  { label: 'Actif', value: 'actif' },
  { label: 'Fermé temporairement', value: 'ferme' },
  { label: 'En travaux', value: 'travaux' },
])

const joursOuverture = ref([
  { nom: 'Lundi', code: 'lundi', ouvert: false, creneaux: [{ ouverture: '', fermeture: '' }] },
  { nom: 'Mardi', code: 'mardi', ouvert: false, creneaux: [{ ouverture: '', fermeture: '' }] },
  {
    nom: 'Mercredi',
    code: 'mercredi',
    ouvert: false,
    creneaux: [{ ouverture: '', fermeture: '' }],
  },
  { nom: 'Jeudi', code: 'jeudi', ouvert: false, creneaux: [{ ouverture: '', fermeture: '' }] },
  {
    nom: 'Vendredi',
    code: 'vendredi',
    ouvert: false,
    creneaux: [{ ouverture: '', fermeture: '' }],
  },
  { nom: 'Samedi', code: 'samedi', ouvert: false, creneaux: [{ ouverture: '', fermeture: '' }] },
  {
    nom: 'Dimanche',
    code: 'dimanche',
    ouvert: false,
    creneaux: [{ ouverture: '', fermeture: '' }],
  },
])

const ajouterCreneau = (jour) => {
  jour.creneaux.push({ ouverture: '', fermeture: '' })
}

const supprimerCreneau = (jour, index) => {
  if (jour.creneaux.length > 1) {
    jour.creneaux.splice(index, 1)
  }
}

const toggleJour = (jour) => {
  if (!jour.ouvert) {
    jour.creneaux = [{ ouverture: '', fermeture: '' }]
  }
}

const formatApercu = (creneaux) => {
  return creneaux
    .filter((c) => c.ouverture && c.fermeture)
    .map((c) => `${c.ouverture}-${c.fermeture}`)
    .join(', ')
}

const construireHoraires = () => {
  const horaires = {}
  joursOuverture.value.forEach((jour) => {
    if (jour.ouvert) {
      const creneauxValides = jour.creneaux.filter((c) => c.ouverture && c.fermeture)
      if (creneauxValides.length > 0) {
        horaires[jour.code] = {
          ouvert: true,
          creneaux: creneauxValides,
        }
      } else {
        horaires[jour.code] = { ouvert: false }
      }
    } else {
      horaires[jour.code] = { ouvert: false }
    }
  })
  return horaires
}

const peuplerJoursOuvertureDepuisHoraires = (horaires) => {
  if (!horaires) return

  joursOuverture.value.forEach((jour) => {
    const horaireJour = horaires[jour.code]
    if (horaireJour && horaireJour.ouvert) {
      jour.ouvert = true
      if (horaireJour.creneaux && horaireJour.creneaux.length > 0) {
        jour.creneaux = horaireJour.creneaux.map((c) => ({
          ouverture: c.ouverture || '',
          fermeture: c.fermeture || '',
        }))
      } else if (horaireJour.ouverture && horaireJour.fermeture) {
        jour.creneaux = [
          {
            ouverture: horaireJour.ouverture,
            fermeture: horaireJour.fermeture,
          },
        ]
      } else {
        jour.creneaux = [{ ouverture: '', fermeture: '' }]
      }
    } else {
      jour.ouvert = false
      jour.creneaux = [{ ouverture: '', fermeture: '' }]
    }
  })
}

const emit = defineEmits(['submit', 'update-coordonnees', 'update-form-data'])

const localFormData = reactive({
  ...props.formData,
})

const syncHoraires = debounce(() => {
  const horaires = construireHoraires()
  localFormData.horaires = horaires

  emit('update-form-data', { ...localFormData })
}, 300)

watch(
  joursOuverture,
  () => {
    syncHoraires()
  },
  { deep: true },
)

onMounted(() => {
  if (props.formData.horaires) {
    peuplerJoursOuvertureDepuisHoraires(props.formData.horaires)
  }

  localFormData.horaires = construireHoraires()
})

// 🎯 **Synchronisation des autres champs (pas les horaires)**
watch(
  () => props.formData,
  (newFormData) => {
    // Copier tout sauf les horaires
    const { horaires, ...autresChamps } = newFormData
    Object.assign(localFormData, autresChamps)

    // Gérer les horaires séparément
    if (horaires && JSON.stringify(horaires) !== JSON.stringify(localFormData.horaires)) {
      peuplerJoursOuvertureDepuisHoraires(horaires)
    }
  },
  { deep: true, immediate: true },
)

// 🎯 **Synchronisation vers le parent pour les autres champs**
watch(
  () => {
    const { horaires, ...autresChamps } = localFormData
    return autresChamps
  },
  (newData) => {
    emit('update-form-data', { ...newData, horaires: localFormData.horaires })
  },
  { deep: true },
)

const mapContainer = ref(null)
let map = null
let marker = null

const onSubmit = () => {
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
      console.log(data)
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
    toast.add({
      severity: 'error',
      summary: 'Erreur de géolocalisation',
      detail: 'Une erreur est survenue lors de la recherche: ' + error,
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
