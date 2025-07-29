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
                  <i class="bi bi-pencil-fill me-2"></i>
                  Modifier le service public
                </h1>
                <p class="text-muted mb-0">
                  {{ formData.nom || 'Chargement...' }}
                </p>
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

        <!-- Loading state -->
        <div v-if="loadingService" class="text-center py-5">
          <ProgressSpinner />
          <p class="mt-3 text-muted">Chargement du service...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="errorLoading" class="card border-0 shadow-sm">
          <div class="card-body text-center py-5">
            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem"></i>
            <h3 class="mt-3">Service non trouvé</h3>
            <p class="text-muted">Le service demandé n'existe pas ou n'est plus disponible.</p>
            <Button
              label="Retour à la liste"
              icon="bi bi-arrow-left"
              @click="$router.push('/admin/services-publiques')"
            />
          </div>
        </div>

        <!-- Form -->
        <ServiceForm
          v-else
          :form-data="formData"
          :errors="errors"
          :loading="loading"
          @update-form-data="handleUpdateFormData"
          @submit="modifierService"
        >
          <template #actions>
            <div class="d-flex gap-2">
              <Button
                label="Annuler"
                icon="bi bi-x-circle"
                severity="secondary"
                outlined
                @click="$router.push('/admin/services-publiques')"
              />
              <Button
                label="Sauvegarder"
                icon="bi bi-check-circle"
                type="submit"
                :loading="loading"
                :disabled="!formulaireValide"
              />
            </div>
          </template>
        </ServiceForm>
      </div>
    </div>
    <Toast />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import ServiceForm from '@/components/ServiceForm.vue'

// Composables
const router = useRouter()
const route = useRoute()
const toast = useToast()
const serviceStore = useServicePublicStore()

// État réactif
const loading = ref(false)
const loadingService = ref(true)
const errorLoading = ref(false)
const errors = ref({})

// ID du service à modifier
const serviceId = route.params.id

// Données du formulaire
const formData = reactive({
  nom: '',
  description: '',
  adresse: '',
  code_postal: '',
  ville: '',
  latitude: null,
  longitude: null,
  telephone: '',
  email: '',
  site_web: '',
  categorie: null,
  accessibilite_pmr: false,
  statut: 'actif',
  horaires: {},
})

// 🎯 Validation du formulaire
const formulaireValide = computed(() => {
  return (
    formData.nom?.trim() !== '' &&
    formData.adresse?.trim() !== '' &&
    formData.code_postal?.trim() !== '' &&
    formData.ville?.trim() !== '' &&
    formData.categorie !== null
  )
})

// 🚀 Handler pour la mise à jour des données
const handleUpdateFormData = (newData) => {
  console.log(newData)
  Object.keys(newData).forEach((key) => {
    formData[key] = newData[key]
  })
}

// 🎯 Chargement du service existant
const chargerService = async () => {
  if (!serviceId) return

  loadingService.value = true
  errorLoading.value = false

  try {
    // ✅ Appel direct de la méthode async
    const service = await serviceStore.fetchServiceById(serviceId, true)

    if (!service) {
      errorLoading.value = true
      return
    }

    // Hydratation du formulaire
    Object.assign(formData, {
      nom: service.nom || '',
      description: service.description || '',
      adresse: service.adresse || '',
      code_postal: service.code_postal || '',
      ville: service.ville || '',
      latitude: service.latitude || null,
      longitude: service.longitude || null,
      telephone: service.telephone || '',
      email: service.email || '',
      site_web: service.site_web || '',
      categorie: service.categorie || null,
      accessibilite_pmr: service.accessibilite_pmr || false,
      statut: service.statut || 'actif',
      horaires: service.horaires_ouverture || {},
    })
  } catch (error) {
    console.error('Erreur lors du chargement:', error)
    errorLoading.value = true
    toast.add({
      severity: 'error',
      summary: 'Erreur de chargement',
      detail: 'Impossible de charger le service public',
      life: 5000,
    })
  } finally {
    loadingService.value = false
  }
}

// 🎯 Validation du formulaire
const validerFormulaire = () => {
  errors.value = {}
  let valide = true

  // Validation nom
  if (!formData.nom?.trim()) {
    errors.value.nom = 'Le nom est obligatoire'
    valide = false
  } else if (formData.nom.length < 3) {
    errors.value.nom = 'Le nom doit contenir au moins 3 caractères'
    valide = false
  }

  // Validation adresse
  if (!formData.adresse?.trim()) {
    errors.value.adresse = "L'adresse est obligatoire"
    valide = false
  }

  // Validation code postal
  if (!formData.code_postal?.trim()) {
    errors.value.code_postal = 'Le code postal est obligatoire'
    valide = false
  } else if (!/^\d{5}$/.test(formData.code_postal)) {
    errors.value.code_postal = 'Code postal invalide (5 chiffres requis)'
    valide = false
  }

  // Validation ville
  if (!formData.ville?.trim()) {
    errors.value.ville = 'La ville est obligatoire'
    valide = false
  }

  // Validation catégorie
  if (!formData.categorie) {
    errors.value.categorie = 'La catégorie est obligatoire'
    valide = false
  }

  // Validation email (si fourni)
  if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    errors.value.email = "Format d'email invalide"
    valide = false
  }

  // Validation URL (si fournie)
  if (formData.site_web && !formData.site_web.startsWith('http')) {
    errors.value.site_web = "L'URL doit commencer par http:// ou https://"
    valide = false
  }

  return valide
}

// 🚀 Modification du service
const modifierService = async () => {
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
    const payload = { ...formData }
    await serviceStore.updateService(serviceId, payload)

    toast.add({
      severity: 'success',
      summary: 'Service modifié',
      detail: 'Le service public a été modifié avec succès',
      life: 4000,
    })

    setTimeout(() => {
      router.push('/admin/services-publiques')
    }, 2000)
  } catch (error) {
    console.error('Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail:
        error?.response?.data?.error ||
        error.message ||
        'Erreur lors de la modification du service',
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

// 🎯 Chargement initial
onMounted(() => {
  if (!serviceId) {
    router.push('/admin/services-publiques')
    return
  }
  chargerService()
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

/* Styles pour la carte */
#leaflet-map {
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.custom-div-icon {
  background: transparent;
  border: none;
}

/* Responsive */
@media (max-width: 768px) {
  .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .card-body {
    padding: 1rem !important;
  }

  #leaflet-map {
    height: 250px !important;
  }
}
</style>
