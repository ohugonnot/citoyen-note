<template>
  <!-- Modal de création d'utilisateur -->
  <Dialog
    v-model:visible="showCreateModal"
    :modal="true"
    :closable="true"
    :dismissable-mask="false"
    :draggable="false"
    class="create-user-modal"
    :style="{ width: '90vw', maxWidth: '800px' }"
    :breakpoints="{ '960px': '95vw', '640px': '100vw' }"
  >
    <template #header>
      <div class="d-flex align-items-center">
        <div class="me-3">
          <div
            class="modal-icon bg-success text-white d-flex align-items-center justify-content-center"
          >
            <i class="pi pi-user-plus fs-4"></i>
          </div>
        </div>
        <div>
          <h4 class="mb-1 fw-bold text-dark">Créer un nouvel utilisateur</h4>
          <p class="text-muted mb-0 small">Remplissez les informations ci-dessous</p>
        </div>
      </div>
    </template>

    <div class="create-user-content">
      <form novalidate @submit.prevent="submitCreateUser">
        <!-- Informations de base -->
        <div class="section-card mb-4">
          <div class="section-header d-flex align-items-center mb-3">
            <i class="pi pi-user text-primary me-2"></i>
            <h5 class="mb-0 fw-semibold">Informations personnelles</h5>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-envelope me-1"></i>
                Email <span class="text-danger">*</span>
              </label>
              <input
                v-model="formData.email"
                type="email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                autocomplete="email"
                required
              />
              <div v-if="errors.email" class="invalid-feedback">
                {{ errors.email }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-at me-1"></i>
                Pseudo
              </label>
              <input
                v-model="formData.pseudo"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.pseudo }"
                autocomplete="username"
              />
              <div v-if="errors.pseudo" class="invalid-feedback">
                {{ errors.pseudo }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-user me-1"></i>
                Prénom
              </label>
              <input
                v-model="formData.prenom"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.prenom }"
                autocomplete="given-name"
              />
              <div v-if="errors.prenom" class="invalid-feedback">
                {{ errors.prenom }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-user me-1"></i>
                Nom
              </label>
              <input
                v-model="formData.nom"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.nom }"
                autocomplete="family-name"
              />
              <div v-if="errors.nom" class="invalid-feedback">
                {{ errors.nom }}
              </div>
            </div>
          </div>
        </div>

        <!-- Sécurité -->
        <div class="section-card mb-4">
          <div class="section-header d-flex align-items-center mb-3">
            <i class="pi pi-shield text-warning me-2"></i>
            <h5 class="mb-0 fw-semibold">Sécurité et accès</h5>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-lock me-1"></i>
                Mot de passe <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input
                  v-model="formData.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': errors.password }"
                  autocomplete="new-password"
                  required
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  tabindex="-1"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
              <div v-if="errors.password" class="invalid-feedback">
                {{ errors.password }}
              </div>
              <div class="form-text">
                <small class="text-muted">
                  <i class="pi pi-info-circle me-1"></i>
                  Minimum 8 caractères, avec majuscules, minuscules et chiffres
                </small>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-lock me-1"></i>
                Confirmer le mot de passe <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input
                  v-model="formData.confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': errors.confirmPassword }"
                  autocomplete="new-password"
                  required
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  tabindex="-1"
                  @click="showConfirmPassword = !showConfirmPassword"
                >
                  <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
              <div v-if="errors.confirmPassword" class="invalid-feedback">
                {{ errors.confirmPassword }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-users me-1"></i>
                Rôles <span class="text-danger">*</span>
              </label>
              <MultiSelect
                v-model="formData.roles"
                :options="roleOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionner les rôles"
                :max-selected-labels="2"
                class="w-100"
                :class="{ 'p-invalid': errors.roles }"
              />
              <div v-if="errors.roles" class="invalid-feedback d-block">
                {{ errors.roles }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-flag me-1"></i>
                Statut initial
              </label>
              <Select
                id="statut"
                v-model="formData.statut"
                :options="statutOptions"
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
              <div v-if="errors.statut" class="invalid-feedback">
                {{ errors.statut }}
              </div>
            </div>
          </div>
        </div>

        <!-- Contact (optionnel) -->
        <div class="section-card mb-4">
          <div class="section-header d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="pi pi-phone text-info me-2"></i>
              <h5 class="mb-0 fw-semibold">Contact</h5>
              <span class="badge bg-light text-muted ms-2">Optionnel</span>
            </div>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="showContactSection = !showContactSection"
            >
              <i :class="showContactSection ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
            </button>
          </div>

          <Transition name="slide-down">
            <div v-show="showContactSection" class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="pi pi-phone me-1"></i>
                  Téléphone
                </label>
                <input
                  v-model="formData.telephone"
                  type="tel"
                  class="form-control"
                  :class="{ 'is-invalid': errors.telephone }"
                  autocomplete="tel"
                />
                <div v-if="errors.telephone" class="invalid-feedback">
                  {{ errors.telephone }}
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="pi pi-calendar me-1"></i>
                  Date de naissance
                </label>
                <input
                  v-model="formData.dateNaissance"
                  type="date"
                  class="form-control"
                  :class="{ 'is-invalid': errors.dateNaissance }"
                  :max="new Date().toISOString().split('T')[0]"
                />
                <div v-if="errors.dateNaissance" class="invalid-feedback">
                  {{ errors.dateNaissance }}
                </div>
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold text-dark">
                  <i class="pi pi-map-marker me-1"></i>
                  Code postal
                </label>
                <input
                  v-model="formData.codePostal"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.codePostal }"
                  pattern="[0-9]{5}"
                  maxlength="5"
                  autocomplete="postal-code"
                />
                <div v-if="errors.codePostal" class="invalid-feedback">
                  {{ errors.codePostal }}
                </div>
              </div>

              <div class="col-md-9">
                <label class="form-label fw-semibold text-dark">
                  <i class="pi pi-home me-1"></i>
                  Ville
                </label>
                <input
                  v-model="formData.ville"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.ville }"
                  autocomplete="address-level2"
                />
                <div v-if="errors.ville" class="invalid-feedback">
                  {{ errors.ville }}
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Options avancées -->
        <div class="section-card">
          <div class="section-header d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
              <i class="pi pi-cog text-secondary me-2"></i>
              <h5 class="mb-0 fw-semibold">Options avancées</h5>
            </div>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="showAdvancedSection = !showAdvancedSection"
            >
              <i :class="showAdvancedSection ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
            </button>
          </div>

          <Transition name="slide-down">
            <div v-show="showAdvancedSection" class="row g-3">
              <div class="col-12">
                <div class="form-check form-switch">
                  <input
                    id="emailVerified"
                    v-model="formData.isVerified"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                  />
                  <label class="form-check-label fw-semibold" for="emailVerified">
                    <i class="pi pi-verified me-1"></i>
                    Marquer comme vérifié
                  </label>
                  <div class="form-text">
                    <small class="text-muted">
                      L'utilisateur n'aura pas besoin de vérifier son email
                    </small>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input
                    id="sendWelcomeEmail"
                    v-model="formData.sendWelcomeEmail"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                  />
                  <label class="form-check-label fw-semibold" for="sendWelcomeEmail">
                    <i class="pi pi-send me-1"></i>
                    Envoyer un email de bienvenue
                  </label>
                  <div class="form-text">
                    <small class="text-muted">
                      Notifie l'utilisateur de la création de son compte
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </form>
    </div>

    <template #footer>
      <div class="d-flex justify-content-between align-items-center w-100">
        <div class="text-muted small">
          <i class="pi pi-info-circle me-1"></i>
          Les champs marqués d'un <span class="text-danger">*</span> sont obligatoires
        </div>
        <div class="d-flex gap-2">
          <Button
            label="Annuler"
            icon="pi pi-times"
            class="p-button-text p-button-secondary"
            :disabled="createLoading"
            @click="closeCreateModal"
          />
          <Button
            label="Créer l'utilisateur"
            icon="pi pi-check"
            class="p-button-success"
            :disabled="!isFormValid"
            :loading="createLoading"
            @click="submitCreateUser"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import MultiSelect from 'primevue/multiselect'
import { useToast } from 'primevue/usetoast'
import apiClient from '@/axios'

// Props et émissions
const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:visible', 'user-created'])

// Composables
const toast = useToast()

// État réactif
const showCreateModal = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
})

const createLoading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const showContactSection = ref(false)
const showAdvancedSection = ref(false)

// Données du formulaire
const formData = ref({
  email: '',
  pseudo: '',
  prenom: '',
  nom: '',
  password: '',
  confirmPassword: '',
  roles: ['ROLE_USER'],
  statut: 'actif',
  telephone: '',
  dateNaissance: '',
  codePostal: '',
  ville: '',
  isVerified: false,
  sendWelcomeEmail: true,
})

const errors = ref({})

// Options
const roleOptions = [
  { label: 'Utilisateur', value: 'ROLE_USER' },
  { label: 'Modérateur', value: 'ROLE_MODERATOR' },
  { label: 'Administrateur', value: 'ROLE_ADMIN' },
  { label: 'Super Admin', value: 'ROLE_SUPER_ADMIN' },
]

const statutOptions = [
  { label: 'Actif', value: 'actif' },
  { label: 'Suspendu', value: 'suspendu' },
]

const getStatusSeverity = (status) => {
  const severityMap = {
    actif: 'success',
    suspendu: 'warn',
    supprime: 'danger',
  }
  return severityMap[status] || 'info'
}

const getStatusLabel = (status) => {
  const option = statutOptions.find((opt) => opt.value === status)
  return option?.label || status
}

// Validation
const validateForm = () => {
  const newErrors = {}

  // Email obligatoire et format valide
  if (!formData.value.email) {
    newErrors.email = "L'email est obligatoire"
  } else if (!/\S+@\S+\.\S+/.test(formData.value.email)) {
    newErrors.email = "Format d'email invalide"
  }

  // Mot de passe obligatoire et complexité
  if (!formData.value.password) {
    newErrors.password = 'Le mot de passe est obligatoire'
  } else if (formData.value.password.length < 8) {
    newErrors.password = 'Le mot de passe doit contenir au moins 8 caractères'
  } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(formData.value.password)) {
    newErrors.password =
      'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre'
  }

  // Confirmation du mot de passe
  if (formData.value.password !== formData.value.confirmPassword) {
    newErrors.confirmPassword = 'Les mots de passe ne correspondent pas'
  }

  // Rôles obligatoires
  if (!formData.value.roles || formData.value.roles.length === 0) {
    newErrors.roles = 'Au moins un rôle doit être sélectionné'
  }

  // Validation du pseudo si renseigné
  if (formData.value.pseudo && formData.value.pseudo.length < 3) {
    newErrors.pseudo = 'Le pseudo doit contenir au moins 3 caractères'
  }

  // Validation du téléphone si renseigné
  if (
    formData.value.telephone &&
    !/^(?:\+33|0)[1-9](?:[0-9]{8})$/.test(formData.value.telephone.replace(/\D/g, ''))
  ) {
    newErrors.telephone = 'Format de téléphone invalide'
  }

  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

const isFormValid = computed(() => {
  return (
    formData.value.email &&
    formData.value.password &&
    formData.value.confirmPassword &&
    formData.value.password === formData.value.confirmPassword &&
    formData.value.roles &&
    formData.value.roles.length > 0
  )
})

// Actions
const submitCreateUser = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'warn',
      summary: 'Attention',
      detail: 'Veuillez corriger les erreurs dans le formulaire',
      life: 3000,
    })
    return
  }

  createLoading.value = true

  try {
    // Préparer les données utilisateur
    const userData = { ...formData.value }
    delete userData.confirmPassword

    // Appel à votre API (à décommenter et adapter)
    const newUser = await apiClient.create(userData)

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `Utilisateur "${formData.value.email}" créé avec succès`,
      life: 5000,
    })

    emit('user-created', newUser) // Passer les données du nouvel utilisateur
    closeCreateModal()
  } catch (error) {
    let detail = "Erreur lors de la création de l'utilisateur"
    let errorMessage
    if (error?.response?.status === 409) {
      errorMessage = 'Un utilisateur avec cet email existe déjà'
    } else {
      errorMessage =
        error?.response?.data?.error || error?.response?.data?.message || 'Données invalides'
    }

    toast.add({
      severity: 'error',
      summary: detail,
      detail: errorMessage,
      life: 5000,
    })
  } finally {
    createLoading.value = false
  }
}

const closeCreateModal = () => {
  resetForm()
  showCreateModal.value = false
}

const resetForm = () => {
  formData.value = {
    email: '',
    pseudo: '',
    prenom: '',
    nom: '',
    password: '',
    confirmPassword: '',
    roles: ['ROLE_USER'],
    statut: 'actif',
    telephone: '',
    dateNaissance: '',
    codePostal: '',
    ville: '',
    isVerified: false,
    sendWelcomeEmail: true,
  }
  errors.value = {}
  showPassword.value = false
  showConfirmPassword.value = false
  showContactSection.value = false
  showAdvancedSection.value = false
}

// Watch pour réinitialiser quand la modal se ferme
watch(
  () => props.visible,
  (newVal) => {
    if (!newVal) {
      resetForm()
    }
  },
)
</script>

<style scoped>
/* Force le mode clair sur tous les éléments */
.create-user-modal,
.create-user-content,
.modal-icon,
:deep(.p-dialog),
:deep(.p-dialog-header),
:deep(.p-dialog-content),
:deep(.p-dialog-footer) {
  background-color: white !important;
  color: #212529 !important;
}

.modal-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  font-size: 1.2rem;
}

.section-card {
  background: #f8f9fa !important;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.section-card:hover {
  border-color: #dee2e6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.section-header {
  border-bottom: 1px solid #e9ecef;
  padding-bottom: 0.75rem;
}

.section-header h5 {
  color: #212529 !important;
}

/* Force les labels et textes en noir */
.form-label,
.form-check-label,
.form-text,
.text-muted,
.text-dark,
small {
  color: #212529 !important;
}

.text-muted,
.form-text small {
  color: #6c757d !important;
}

/* Force les inputs en blanc */
.form-control,
.form-select,
:deep(.p-multiselect),
:deep(.p-multiselect .p-multiselect-label),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-item) {
  background-color: white !important;
  color: #212529 !important;
  border: 1px solid #ced4da !important;
}

.form-control,
.form-select {
  border-radius: 8px;
  transition: all 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus,
:deep(.p-multiselect.p-focus) {
  border-color: #86b7fe !important;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
  background-color: white !important;
  color: #212529 !important;
}

.form-control.is-invalid,
.form-select.is-invalid,
:deep(.p-multiselect.p-invalid) {
  border-color: #dc3545 !important;
  box-shadow: none;
}

.form-control.is-invalid:focus,
.form-select.is-invalid:focus,
:deep(.p-multiselect.p-invalid.p-focus) {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
}

/* Force les checkbox et switch */
.form-check-input {
  border-radius: 6px;
  background-color: white !important;
  border-color: #ced4da !important;
}

.form-check-input:focus {
  border-color: #86b7fe !important;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

.form-check-input:checked {
  background-color: #0d6efd !important;
  border-color: #0d6efd !important;
}

.input-group .btn {
  border-radius: 0 8px 8px 0;
  background-color: white !important;
  border-color: #ced4da !important;
  color: #212529 !important;
}

.input-group .btn:hover {
  background-color: #f8f9fa !important;
  color: #212529 !important;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}

/* Animation pour les sections collapsibles */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-in-out;
  overflow: hidden;
}

.slide-down-enter-from {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 500px;
  transform: translateY(0);
}

/* Mobile - plein écran */
@media (max-width: 640px) {
  :deep(.p-dialog.create-user-modal) {
    width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh !important;
    margin: 0 !important;
    border-radius: 0 !important;
    top: 0 !important;
    left: 0 !important;
    transform: none !important;
    position: fixed !important;
  }

  :deep(.p-dialog .p-dialog-content) {
    height: calc(100vh - 140px) !important;
    max-height: none !important;
    overflow-y: auto !important;
    padding: 1rem !important;
  }

  :deep(.p-dialog .p-dialog-header) {
    padding: 1rem !important;
    border-radius: 0 !important;
  }

  :deep(.p-dialog .p-dialog-footer) {
    padding: 1rem !important;
    border-radius: 0 !important;
  }

  .create-user-content {
    padding: 0;
  }

  .section-card {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
  }

  .modal-icon {
    width: 40px;
    height: 40px;
  }

  .section-header h5 {
    font-size: 1rem;
  }

  /* Footer sur mobile */
  :deep(.p-dialog .p-dialog-footer) {
    flex-direction: column !important;
    gap: 0.5rem;
  }

  :deep(.p-dialog .p-dialog-footer .d-flex) {
    flex-direction: column;
    gap: 0.75rem;
  }

  :deep(.p-dialog .p-dialog-footer .d-flex:last-child) {
    flex-direction: row;
    justify-content: stretch;
  }

  :deep(.p-dialog .p-dialog-footer .d-flex:last-child .p-button) {
    flex: 1;
  }
}

/* Très petits écrans */
@media (max-width: 480px) {
  :deep(.p-dialog .p-dialog-header) {
    padding: 0.75rem !important;
  }

  :deep(.p-dialog .p-dialog-content) {
    padding: 0.75rem !important;
    height: calc(100vh - 120px) !important;
  }

  :deep(.p-dialog .p-dialog-footer) {
    padding: 0.75rem !important;
  }

  .section-card {
    padding: 0.75rem;
  }
}

/* Responsive tablette */
@media (min-width: 641px) and (max-width: 768px) {
  .create-user-content {
    padding: 0;
  }

  .section-card {
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .modal-icon {
    width: 40px;
    height: 40px;
  }

  .section-header h5 {
    font-size: 1rem;
  }
}

/* PrimeVue overrides avec force */
:deep(.p-dialog .p-dialog-header) {
  border-bottom: 1px solid #e9ecef !important;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
  color: #212529 !important;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
  max-height: 70vh;
  overflow-y: auto;
  background-color: white !important;
  color: #212529 !important;
}

:deep(.p-dialog .p-dialog-footer) {
  border-top: 1px solid #e9ecef !important;
  background-color: #f8f9fa !important;
  padding: 1rem 1.5rem;
  color: #212529 !important;
}

:deep(.p-multiselect) {
  border-radius: 8px;
}

:deep(.p-button) {
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button:not(:disabled):hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Force les panels des dropdowns */
:deep(.p-multiselect-panel) {
  background-color: white !important;
  border: 1px solid #ced4da !important;
}

:deep(.p-multiselect-item) {
  color: #212529 !important;
}

:deep(.p-multiselect-item:hover) {
  background-color: #f8f9fa !important;
  color: #212529 !important;
}
</style>

<style>
@media (max-width: 640px) {
  .p-dialog.create-user-modal {
    width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh !important;
    max-width: 100vw !important;
    margin: 0 !important;
    border-radius: 0 !important;
    top: 0 !important;
    left: 0 !important;
    transform: none !important;
    position: fixed !important;
    border: none !important;
    display: flex !important;
    flex-direction: column !important;
  }

  .p-dialog.create-user-modal .p-dialog-content {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 1rem !important;
  }

  .p-dialog.create-user-modal .p-dialog-footer {
    flex-shrink: 0 !important;
    padding: 1rem !important;
    border-top: 1px solid #ced4da !important;
  }

  /* Footer responsive */
  .p-dialog.create-user-modal .p-dialog-footer .d-flex.justify-content-between {
    flex-direction: column !important;
    gap: 1rem !important;
    align-items: center !important;
  }

  .p-dialog.create-user-modal .p-dialog-footer .text-muted {
    text-align: center !important;
    order: 1 !important;
  }

  .p-dialog.create-user-modal .p-dialog-footer .d-flex.gap-2 {
    order: 0 !important;
    width: 100% !important;
    justify-content: center !important;
  }

  /* Texte plus court pour le bouton */
  .p-dialog.create-user-modal .p-dialog-footer .p-button-success .p-button-label {
    display: none !important;
  }

  .p-dialog.create-user-modal .p-dialog-footer .p-button-success::after {
    content: 'Créer' !important;
  }
}
</style>
