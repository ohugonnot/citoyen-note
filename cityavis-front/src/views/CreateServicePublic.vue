<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-xl-8 col-lg-10">
        <!-- Header -->
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h1 class="h3 mb-1 fw-bold text-primary">
                  <i class="bi bi-plus-circle-fill me-2"></i>
                  Nouveau service publique
                </h1>
                <p class="text-muted mb-0">Ajoutez un nouveau service publique à la plateforme</p>
              </div>
              <Button
                label="Retour"
                icon="bi bi-arrow-left"
                severity="secondary"
                outlined
                @click="$router.push('/admin/services-publiques')"
              />
            </div>
          </div>
        </div>

        <!-- Formulaire principal -->
        <form @submit.prevent="creerService">
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
                        v-model="formData.nom"
                        :class="['form-control', { 'is-invalid': errors.nom }]"
                        placeholder="Ex: Mairie de Bordeaux"
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
                        v-model="formData.categorieId"
                        :options="categories"
                        option-label="nom"
                        option-value="id"
                        placeholder="Sélectionner..."
                        :class="['form-control p-0', { 'is-invalid': errors.categorieId }]"
                        :loading="loadingCategories"
                      />
                      <div v-if="errors.categorieId" class="invalid-feedback">
                        {{ errors.categorieId }}
                      </div>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                      <label for="description" class="form-label fw-medium"> Description </label>
                      <Textarea
                        id="description"
                        v-model="formData.description"
                        :class="['form-control', { 'is-invalid': errors.description }]"
                        rows="4"
                        placeholder="Description détaillée du service..."
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
                        v-model="formData.adresseComplete"
                        :class="['form-control', { 'is-invalid': errors.adresseComplete }]"
                        required
                        @blur="rechercherCoordonnees"
                      />
                      <div v-if="errors.adresseComplete" class="invalid-feedback">
                        {{ errors.adresseComplete }}
                      </div>
                      <small class="form-text text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Les coordonnées GPS seront automatiquement récupérées
                      </small>
                    </div>

                    <!-- Code postal et Ville -->
                    <div class="col-md-4">
                      <label for="codePostal" class="form-label fw-medium">
                        Code postal <span class="text-danger">*</span>
                      </label>
                      <InputText
                        id="codePostal"
                        v-model="formData.codePostal"
                        :class="['form-control', { 'is-invalid': errors.codePostal }]"
                        maxlength="5"
                        pattern="[0-9]{5}"
                        required
                        @blur="rechercherCoordonnees"
                      />
                      <div v-if="errors.codePostal" class="invalid-feedback">
                        {{ errors.codePostal }}
                      </div>
                    </div>

                    <div class="col-md-8">
                      <label for="ville" class="form-label fw-medium">
                        Ville <span class="text-danger">*</span>
                      </label>
                      <InputText
                        id="ville"
                        v-model="formData.ville"
                        :class="['form-control', { 'is-invalid': errors.ville }]"
                        required
                        @blur="rechercherCoordonnees"
                      />
                      <div v-if="errors.ville" class="invalid-feedback">
                        {{ errors.ville }}
                      </div>
                    </div>

                    <!-- Coordonnées GPS (lecture seule) -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Latitude</label>
                      <InputText
                        v-model="formData.latitude"
                        class="form-control"
                        readonly
                        placeholder="Automatique"
                      />
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-medium">Longitude</label>
                      <InputText
                        v-model="formData.longitude"
                        class="form-control"
                        readonly
                        placeholder="Automatique"
                      />
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
                        v-model="formData.telephone"
                        :class="['form-control', { 'is-invalid': errors.telephone }]"
                        placeholder="05.56.10.20.30"
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
                        v-model="formData.email"
                        :class="['form-control', { 'is-invalid': errors.email }]"
                        placeholder="contact@service.fr"
                        type="email"
                      />
                      <div v-if="errors.email" class="invalid-feedback">
                        {{ errors.email }}
                      </div>
                    </div>

                    <div class="col-md-4">
                      <label for="siteWeb" class="form-label fw-medium"> Site web </label>
                      <InputText
                        id="siteWeb"
                        v-model="formData.siteWeb"
                        :class="['form-control', { 'is-invalid': errors.siteWeb }]"
                        placeholder="https://www.service.fr"
                        type="url"
                      />
                      <div v-if="errors.siteWeb" class="invalid-feedback">
                        {{ errors.siteWeb }}
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
                      <div
                        v-for="(jour, index) in joursOuverture"
                        :key="index"
                        class="col-md-6 col-lg-4"
                      >
                        <div class="card border">
                          <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <label class="form-check-label fw-medium">
                                {{ jour.nom }}
                              </label>
                              <div class="form-check form-switch">
                                <input
                                  v-model="jour.ouvert"
                                  class="form-check-input"
                                  type="checkbox"
                                />
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
                          v-model="formData.accessibilitePmr"
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
                        v-model="formData.statut"
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
            <div class="col-12">
              <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-end gap-2">
                    <Button
                      label="Annuler"
                      icon="bi bi-x-circle"
                      severity="secondary"
                      outlined
                      @click="$router.push('/admin/services')"
                      :disabled="loading"
                    />
                    <Button
                      type="submit"
                      label="Créer le service"
                      icon="bi bi-check-circle"
                      :loading="loading"
                      :disabled="!formulaireValide"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { fetchCategoriesServices } from '@/api/categoriesServices'

// Composables
const router = useRouter()
const toast = useToast()

// État réactif
const loading = ref(false)
const loadingCategories = ref(false)
const categories = ref([])
const errors = ref({})

// Données du formulaire
const formData = reactive({
  nom: '',
  description: '',
  adresseComplete: '',
  codePostal: '',
  ville: '',
  latitude: null,
  longitude: null,
  telephone: '',
  email: '',
  siteWeb: '',
  categorieId: null,
  accessibilitePmr: false,
  statut: 'actif',
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

// Validation du formulaire
const formulaireValide = computed(() => {
  return (
    formData.nom.trim() !== '' &&
    formData.adresseComplete.trim() !== '' &&
    formData.codePostal.trim() !== '' &&
    formData.ville.trim() !== '' &&
    formData.categorieId !== null
  )
})

// Méthodes
const chargerCategories = async () => {
  loadingCategories.value = true
  try {
    const data = await fetchCategoriesServices()

    // Si votre API retourne une structure avec success/data
    if (data.success) {
      categories.value = data.data
    } else {
      throw new Error(data.message || 'Erreur lors du chargement des catégories')
    }

    // Si votre API retourne directement les données
    // categories.value = data
  } catch (error) {
    console.error('Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message || 'Impossible de charger les catégories',
      life: 5000,
    })
  } finally {
    loadingCategories.value = false
  }
}

const rechercherCoordonnees = async () => {
  if (!formData.adresseComplete.trim() || !formData.ville.trim() || !formData.codePostal.trim())
    return

  try {
    const adresseComplete = `${formData.adresseComplete}, ${formData.codePostal} ${formData.ville}`
    const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(adresseComplete)}&limit=1`

    const response = await fetch(url)
    const data = await response.json()

    if (data.features && data.features.length > 0) {
      const feature = data.features[0]
      formData.latitude = feature.geometry.coordinates[1]
      formData.longitude = feature.geometry.coordinates[0]

      toast.add({
        severity: 'success',
        summary: 'Coordonnées trouvées',
        detail: 'Les coordonnées GPS ont été automatiquement renseignées',
        life: 3000,
      })
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
  }
}

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

const validerFormulaire = () => {
  errors.value = {}
  let valide = true

  // Validation nom
  if (!formData.nom.trim()) {
    errors.value.nom = 'Le nom est obligatoire'
    valide = false
  } else if (formData.nom.length < 3) {
    errors.value.nom = 'Le nom doit contenir au moins 3 caractères'
    valide = false
  }

  // Validation adresse
  if (!formData.adresseComplete.trim()) {
    errors.value.adresseComplete = "L'adresse est obligatoire"
    valide = false
  }

  // Validation code postal
  if (!formData.codePostal.trim()) {
    errors.value.codePostal = 'Le code postal est obligatoire'
    valide = false
  } else if (!/^\d{5}$/.test(formData.codePostal)) {
    errors.value.codePostal = 'Code postal invalide (5 chiffres requis)'
    valide = false
  }

  // Validation ville
  if (!formData.ville.trim()) {
    errors.value.ville = 'La ville est obligatoire'
    valide = false
  }

  // Validation catégorie
  if (!formData.categorieId) {
    errors.value.categorieId = 'La catégorie est obligatoire'
    valide = false
  }

  // Validation email (si fourni)
  if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.value.email = "Format d'email invalide"
    valide = false
  }

  // Validation URL (si fournie)
  if (formData.siteWeb && !formData.siteWeb.startsWith('http')) {
    errors.value.siteWeb = "L'URL doit commencer par http:// ou https://"
    valide = false
  }

  return valide
}

const creerService = async () => {
  if (!validerFormulaire()) {
    toast.add({
      severity: 'error',
      summary: 'Erreur de validation',
      detail: 'Veuillez corriger les champs en erreur',
      life: 5000,
    })
    return
  }

  loading.value = true

  try {
    const payload = {
      ...formData,
      horairesOuverture: construireHoraires(),
    }

    const response = await fetch('/api/services', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data = await response.json()

    if (data.success) {
      toast.add({
        severity: 'success',
        summary: 'Service créé',
        detail: 'Le service publique a été créé avec succès',
        life: 4000,
      })

      // Redirection vers la liste des services
      setTimeout(() => {
        router.push('/admin/services')
      }, 2000)
    } else {
      throw new Error(data.message || 'Erreur lors de la création')
    }
  } catch (error) {
    console.error('Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message || 'Erreur lors de la création du service',
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  chargerCategories()
})
</script>

<style scoped>
.card {
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
}

.form-control:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.invalid-feedback {
  display: block;
}

.form-check-input:checked {
  background-color: var(--bs-success);
  border-color: var(--bs-success);
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

@media (max-width: 768px) {
  .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .card-body {
    padding: 1rem !important;
  }
}
</style>
