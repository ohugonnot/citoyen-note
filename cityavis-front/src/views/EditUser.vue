<template>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-xxl-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h2 class="mb-0">Modifier l'utilisateur #{{ form.id }}</h2>
          <Button
            icon="pi pi-arrow-left"
            label="Retour"
            class="p-button-outlined"
            @click="router.push('/admin/users')"
          />
        </div>

        <Card class="shadow">
          <template #content>
            <div v-if="loading" class="d-flex justify-content-center align-items-center py-5">
              <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
            </div>

            <form v-else @submit.prevent="submitForm">
              <!-- Informations de base -->
              <div class="field-group mb-4">
                <h3 class="h5 fw-semibold mb-3 text-primary">
                  <i class="pi pi-user me-2"></i>
                  Informations de base
                </h3>

                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label for="email" class="form-label fw-medium">Email *</label>
                    <InputText
                      id="email"
                      v-model="form.email"
                      type="email"
                      disabled
                      class="w-100"
                    />
                    <small class="form-text text-muted">L'email ne peut pas être modifié</small>
                  </div>

                  <div class="col-12 col-md-6 mb-3">
                    <label for="pseudo" class="form-label fw-medium">Pseudo *</label>
                    <InputText
                      id="pseudo"
                      v-model="form.pseudo"
                      type="text"
                      class="w-100"
                      :class="{ 'p-invalid': errors.pseudo }"
                    />
                    <small v-if="errors.pseudo" class="text-danger">{{ errors.pseudo }}</small>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label for="nom" class="form-label fw-medium">Nom</label>
                    <InputText id="nom" v-model="form.nom" type="text" class="w-100" />
                  </div>

                  <div class="col-12 col-md-6 mb-3">
                    <label for="prenom" class="form-label fw-medium">Prénom</label>
                    <InputText id="prenom" v-model="form.prenom" type="text" class="w-100" />
                  </div>
                </div>
              </div>

              <!-- Informations de contact -->
              <div class="field-group mb-4">
                <h3 class="h5 fw-semibold mb-3 text-primary">
                  <i class="pi pi-phone me-2"></i>
                  Contact & Naissance
                </h3>

                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label for="telephone" class="form-label fw-medium">Téléphone</label>
                    <InputText id="telephone" v-model="form.telephone" type="tel" class="w-100" />
                  </div>

                  <div class="col-12 col-md-6 mb-3">
                    <label for="dateNaissance" class="form-label fw-medium"
                      >Date de naissance</label
                    >
                    <DatePicker
                      id="dateNaissance"
                      v-model="form.dateNaissance"
                      date-format="dd/mm/yy"
                      show-icon
                      class="w-100"
                      :max-date="maxBirthDate"
                      placeholder="Sélectionner une date"
                      show-button-bar
                    />
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-3 mb-3">
                    <label for="codePostal" class="form-label fw-medium">Code postal</label>
                    <InputText
                      id="codePostal"
                      v-model="form.codePostal"
                      type="text"
                      class="w-100"
                      maxlength="5"
                    />
                  </div>

                  <div class="col-12 col-md-9 mb-3">
                    <label for="ville" class="form-label fw-medium">Ville</label>
                    <InputText id="ville" v-model="form.ville" type="text" class="w-100" />
                  </div>
                </div>
              </div>

              <!-- Administration -->
              <div class="field-group mb-4">
                <h3 class="h5 fw-semibold mb-3 text-primary">
                  <i class="pi pi-cog me-2"></i>
                  Administration
                </h3>

                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <label for="statut" class="form-label fw-medium">Statut *</label>
                    <Select
                      id="statut"
                      v-model="form.statut"
                      :options="statusOptions"
                      option-label="label"
                      option-value="value"
                      placeholder="Choisir un statut"
                      class="w-100"
                      :class="{ 'p-invalid': errors.statut }"
                    >
                      <template #option="{ option }">
                        <div class="d-flex align-items-center">
                          <Tag
                            :value="option.label"
                            :severity="getStatusSeverity(option.value)"
                            class="me-2"
                          />
                        </div>
                      </template>
                      <template #value="{ value }">
                        <div v-if="value" class="d-flex align-items-center">
                          <Tag
                            :value="getStatusLabel(value)"
                            :severity="getStatusSeverity(value)"
                            class="me-2"
                          />
                        </div>
                      </template>
                    </Select>
                    <small v-if="errors.statut" class="text-danger">{{ errors.statut }}</small>
                  </div>

                  <div class="col-12 col-md-6 mb-3">
                    <label for="roles" class="form-label fw-medium">Rôles *</label>
                    <MultiSelect
                      id="roles"
                      v-model="form.roles"
                      :options="rolesDisponibles"
                      option-label="label"
                      option-value="value"
                      display="chip"
                      placeholder="Choisir les rôles"
                      class="w-100"
                      :class="{ 'p-invalid': errors.roles }"
                      :max-selected-labels="3"
                    />
                    <small v-if="errors.roles" class="text-danger">{{ errors.roles }}</small>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                      <Checkbox id="verified" v-model="form.isVerified" binary />
                      <label for="verified" class="form-label fw-medium ms-2 mb-0"
                        >Email vérifié</label
                      >
                    </div>
                    <small class="form-text text-muted">
                      Indique si l'adresse email a été vérifiée
                    </small>
                  </div>

                  <div class="col-12 col-md-6 mb-3">
                    <label for="scoreFiabilite" class="form-label fw-medium"
                      >Score de fiabilité</label
                    >
                    <div class="input-group">
                      <InputNumber
                        id="scoreFiabilite"
                        v-model="form.scoreFiabilite"
                        :min="0"
                        :max="100"
                        suffix=" %"
                        class="flex-grow-1"
                      />
                      <Button
                        icon="pi pi-info-circle"
                        class="p-button-outlined p-button-secondary"
                        type="button"
                        @click="showScoreInfo = true"
                      />
                    </div>
                    <small class="form-text text-muted"> Score entre 0 et 100% </small>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="d-flex flex-wrap gap-2 justify-content-end pt-4 border-top">
                <Button
                  icon="pi pi-times"
                  label="Annuler"
                  class="p-button-outlined p-button-secondary"
                  type="button"
                  @click="router.push('/admin/users')"
                />
                <Button
                  type="submit"
                  icon="pi pi-check"
                  label="Enregistrer les modifications"
                  class="p-button-success"
                  :loading="saving"
                  :disabled="!isFormValid"
                />
              </div>
            </form>
          </template>
        </Card>

        <!-- Dialog d'information sur le score -->
        <Dialog
          v-model:visible="showScoreInfo"
          header="Score de fiabilité"
          :modal="true"
          style="width: 90%; max-width: 400px"
          class="mx-auto"
        >
          <p class="mb-3">Le score de fiabilité est calculé en fonction de plusieurs critères :</p>
          <ul class="list-unstyled">
            <li class="d-flex align-items-center mb-2">
              <i class="pi pi-check-circle text-success me-2"></i>
              Email vérifié (+20%)
            </li>
            <li class="d-flex align-items-center mb-2">
              <i class="pi pi-check-circle text-success me-2"></i>
              Profil complété (+30%)
            </li>
            <li class="d-flex align-items-center mb-2">
              <i class="pi pi-check-circle text-success me-2"></i>
              Activité régulière (+25%)
            </li>
            <li class="d-flex align-items-center">
              <i class="pi pi-check-circle text-success me-2"></i>
              Signalements (+25%)
            </li>
          </ul>
          <template #footer>
            <Button
              label="Fermer"
              icon="pi pi-times"
              class="p-button-text"
              @click="showScoreInfo = false"
            />
          </template>
        </Dialog>

        <!-- Toast pour les notifications -->
        <Toast />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import apiUser from '@/api/users'

// Composables
const route = useRoute()
const router = useRouter()
const toast = useToast()

// État réactif
const loading = ref(true)
const saving = ref(false)
const showScoreInfo = ref(false)
const errors = ref({})

const form = ref({
  id: null,
  email: '',
  pseudo: '',
  nom: '',
  prenom: '',
  telephone: '',
  dateNaissance: null,
  statut: null,
  roles: [],
  isVerified: false,
  scoreFiabilite: 0,
  codePostal: '',
  ville: '',
})

// Options
const statusOptions = [
  { label: 'Actif', value: 'actif' },
  { label: 'Suspendu', value: 'suspendu' },
  { label: 'Supprimé', value: 'supprime' },
]

const rolesDisponibles = [
  { label: 'Utilisateur', value: 'ROLE_USER' },
  { label: 'Modérateur', value: 'ROLE_MODERATOR' },
  { label: 'Admin', value: 'ROLE_ADMIN' },
  { label: 'Super Admin', value: 'ROLE_SUPER_ADMIN' },
]

// Computed
const maxBirthDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 13) // Âge minimum 13 ans
  return date
})

const isFormValid = computed(() => {
  return form.value.pseudo?.trim() && form.value.statut && form.value.roles?.length > 0
})

// Méthodes utilitaires
const getStatusSeverity = (status) => {
  const severityMap = {
    actif: 'success',
    suspendu: 'warn',
    supprime: 'danger',
  }
  return severityMap[status] || 'info'
}

const getStatusLabel = (status) => {
  const option = statusOptions.find((opt) => opt.value === status)
  return option?.label || status
}

const validateForm = () => {
  errors.value = {}

  if (!form.value.pseudo?.trim()) {
    errors.value.pseudo = 'Le pseudo est requis'
  }

  if (!form.value.statut) {
    errors.value.statut = 'Le statut est requis'
  }

  if (!form.value.roles?.length) {
    errors.value.roles = 'Au moins un rôle est requis'
  }

  return Object.keys(errors.value).length === 0
}

// Cycle de vie
onMounted(async () => {
  try {
    const user = await apiUser.getById(route.params.id)

    form.value = {
      id: user.id,
      email: user.email,
      pseudo: user.pseudo || '',
      nom: user.nom || '',
      prenom: user.prenom || '',
      telephone: user.telephone || '',
      dateNaissance: user.dateNaissance ? new Date(user.dateNaissance) : null,
      statut: user.statut || null,
      roles: user.roles || [],
      isVerified: user.isVerified || false,
      scoreFiabilite: user.scoreFiabilite || 0,
      codePostal: user.codePostal || '',
      ville: user.ville || '',
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: "Impossible de charger l'utilisateur: " + error.response.error || error.message,
      life: 5000,
    })
  } finally {
    loading.value = false
  }
})

// Gestionnaire de soumission
const submitForm = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Formulaire invalide',
      detail: 'Veuillez corriger les erreurs avant de continuer',
      life: 4000,
    })
    return
  }

  saving.value = true

  try {
    const payload = {
      ...form.value,
      dateNaissance: form.value.dateNaissance
        ? form.value.dateNaissance.toISOString().split('T')[0]
        : null,
    }

    await apiUser.update(form.value.id, payload)

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Utilisateur mis à jour avec succès',
      life: 3000,
    })

    // Redirection après un petit délai pour voir le toast
    setTimeout(() => {
      router.push('/admin/users')
    }, 1000)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.message || 'Erreur lors de la mise à jour',
      life: 5000,
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Force le fond blanc sur tous les éléments */
.container,
.field-group,
:deep(.p-card),
:deep(.p-card-content),
:deep(.p-inputtext),
:deep(.p-dropdown),
:deep(.p-multiselect),
:deep(.p-calendar),
:deep(.p-inputnumber-input),
:deep(.p-dialog),
:deep(.p-dialog-content) {
  background-color: white !important;
  color: #212529 !important;
}

/* Styles des groupes de champs */
.field-group {
  background-color: #f8f9fa !important;
  border-radius: 0.375rem;
  padding: 1.5rem;
  border-left: 4px solid #0d6efd;
}

.field-group h3 {
  margin-top: 0;
  color: #0d6efd !important;
}

/* Labels et textes */
.form-label,
.form-text,
.text-muted {
  color: #6c757d !important;
}

/* Force les inputs PrimeVue en blanc */
:deep(.p-inputtext:enabled:focus),
:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-dropdown:not(.p-disabled).p-focus),
:deep(.p-multiselect:not(.p-disabled):hover),
:deep(.p-multiselect:not(.p-disabled).p-focus) {
  background-color: white !important;
  color: #212529 !important;
}

/* Corrections pour les options des dropdowns */
:deep(.p-dropdown-panel),
:deep(.p-multiselect-panel) {
  background-color: white !important;
}

:deep(.p-dropdown-item),
:deep(.p-multiselect-item) {
  background-color: white !important;
  color: #212529 !important;
}

:deep(.p-dropdown-item:hover),
:deep(.p-multiselect-item:hover) {
  background-color: #f8f9fa !important;
  color: #212529 !important;
}

.gap-2 {
  gap: 0.5rem;
}

/* Responsive adjustments pour les très petits écrans */
@media (max-width: 576px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .field-group {
    padding: 1rem;
  }

  .d-flex.justify-content-end {
    flex-direction: column;
  }

  .d-flex.justify-content-end button {
    width: 100%;
    margin-bottom: 0.5rem;
  }
}
</style>
