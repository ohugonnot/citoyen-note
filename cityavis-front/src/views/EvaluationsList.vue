<template>
  <div class="container min-vh-100 py-3">
    <!-- Header -->
    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 mb-2">Évaluations</h1>
          <p class="text-muted mb-0">Gestion des évaluations utilisateurs</p>
        </div>
        <div class="d-flex gap-2">
          <Button
            label="Nouvelle évaluation"
            icon="pi pi-plus"
            size="small"
            @click="openCreateModal"
          />
          <Button
            label="Actualiser"
            icon="pi pi-refresh"
            severity="secondary"
            size="small"
            :loading="store.isLoading"
            @click="handleRefresh"
          />
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <Card class="mb-4">
      <template #content>
        <div class="row g-3">
          <div class="col-md-4">
            <InputText
              v-model="store.filters.search"
              placeholder="Rechercher..."
              class="w-100"
              @input="debounceSearch"
            />
          </div>
          <div class="col-md-3">
            <Select
              v-model="store.filters.est_verifie"
              :options="verificationOptions"
              option-label="label"
              option-value="value"
              placeholder="Statut vérification"
              class="w-100"
              show-clear
              @change="handleFilterChange"
            />
          </div>
          <div class="col-md-3">
            <Select
              v-model="store.filters.est_anonyme"
              :options="anonymeOptions"
              option-label="label"
              option-value="value"
              placeholder="Type d'évaluation"
              class="w-100"
              show-clear
              @change="handleFilterChange"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Sélection en masse -->
    <div v-if="selectedEvaluations.length > 0" class="mb-3">
      <div class="bg-light p-3 rounded">
        <div class="d-flex justify-content-between align-items-center">
          <span>{{ selectedEvaluations.length }} évaluation(s) sélectionnée(s)</span>
          <div class="d-flex gap-2">
            <Button
              label="Valider la sélection"
              icon="pi pi-check"
              severity="success"
              size="small"
              @click="confirmBulkValidate"
            />
            <Button
              label="Supprimer la sélection"
              icon="pi pi-trash"
              severity="danger"
              size="small"
              @click="confirmBulkDelete"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Tableau -->
    <DataTable
      v-model:selection="selectedEvaluations"
      :value="store.evaluations"
      :loading="store.isLoading"
      :lazy="true"
      :paginator="true"
      :rows="store.filters.limit"
      :total-records="store.pagination.total"
      :first="(store.filters.page - 1) * store.filters.limit"
      data-key="id"
      responsive-layout="scroll"
      striped-rows
      class="p-datatable-sm"
      paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
      current-page-report-template="Affichage de {first} à {last} sur {totalRecords} évaluations"
      :rows-per-page-options="[10, 25, 50, 100]"
      empty-message="Aucune évaluation trouvée"
      @page="handlePageChange"
      @sort="handleSort"
      @row-click="handleRowClick"
    >
      <!-- Checkbox selection -->
      <Column selection-mode="multiple" header-style="width: 3rem" />

      <!-- Utilisateur -->
      <Column field="user.nom" header="Utilisateur" sortable>
        <template #body="{ data }">
          <div>
            <div class="fw-bold">
              {{ data.pseudo }}
            </div>
            <small v-if="!data.est_anonyme" class="text-muted">
              {{ data.user?.email }}
            </small>
            <div class="mt-1">
              <Tag v-if="data.est_verifie" value="Vérifié" severity="success" size="small" />
              <Tag v-if="data.est_anonyme" value="Anonyme" severity="info" size="small" />
            </div>
          </div>
        </template>
      </Column>

      <!-- Service -->
      <Column field="service.nom" header="Service évalué" sortable>
        <template #body="{ data }">
          <div class="fw-medium">{{ data.service.nom }}</div>
          <small v-if="data.service_description" class="text-muted">
            {{ truncateText(data.service_description, 50) }}
          </small>
        </template>
      </Column>

      <!-- Note -->
      <Column field="note" header="Note" sortable>
        <template #body="{ data }">
          <div class="d-flex align-items-center gap-2">
            <Rating :model-value="data.note" readonly :cancel="false" />
            <span class="fw-bold">{{ data.note }}/5</span>
          </div>
        </template>
      </Column>

      <!-- Commentaire -->
      <Column field="commentaire" header="Commentaire">
        <template #body="{ data }">
          <div v-if="data.commentaire">
            <p class="mb-1 small">{{ truncateText(data.commentaire, 100) }}</p>
            <Button
              v-if="data.commentaire.length > 100"
              label="Lire plus"
              text
              size="small"
              @click="showCommentModal(data)"
            />
          </div>
          <span v-else class="text-muted fst-italic">Aucun commentaire</span>
        </template>
      </Column>

      <!-- Date -->
      <Column field="createdAt" header="Date" sortable>
        <template #body="{ data }">
          <div>
            <div class="fw-medium">{{ formatDate(data.updatedAt) }}</div>
            <small class="text-muted">{{ formatTime(data.updatedAt) }}</small>
          </div>
        </template>
      </Column>

      <!-- Actions -->
      <Column header="Actions" :exportable="false" style="width: 120px">
        <template #body="{ data }">
          <div class="d-flex gap-1 align-items-center">
            <Button
              icon="pi pi-pencil"
              severity="info"
              text
              size="small"
              title="Modifier"
              @click="openEditModal(data)"
            />

            <!-- Switch pour validation -->
            <ToggleButton
              v-model="data.est_verifie"
              on-icon="pi pi-check"
              off-icon="pi pi-eye-slash"
              :on-label="data.est_verifie ? 'Vérifié' : ''"
              :off-label="data.est_verifie ? '' : 'Non vérifié'"
              class="p-button-sm"
              :class="data.est_verifie ? 'p-button-success' : 'p-button-warning'"
              @change="toggleValidation(data)"
              @click.stop
            />

            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              size="small"
              title="Supprimer"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- Modal de suppression -->
    <Dialog v-model:visible="deleteModal.show" header="Confirmation de suppression" modal>
      <div class="d-flex align-items-center gap-3 mb-3">
        <i class="pi pi-exclamation-triangle text-warning" style="font-size: 2rem"></i>
        <div>
          <p class="mb-1">
            Êtes-vous sûr de vouloir supprimer l'évaluation de
            <strong>{{ deleteModal.item?.user?.nom || 'cet utilisateur' }}</strong> ?
          </p>
          <p class="text-muted mb-0 small">Cette action est irréversible.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Annuler" severity="secondary" outlined @click="deleteModal.show = false" />
        <Button
          label="Supprimer"
          icon="pi pi-trash"
          severity="danger"
          :loading="deleteModal.loading"
          @click="handleDelete"
        />
      </template>
    </Dialog>

    <!-- Modal de suppression en masse -->
    <Dialog v-model:visible="bulkDeleteModal.show" header="Suppression en masse" modal>
      <div class="d-flex align-items-center gap-3 mb-3">
        <i class="pi pi-exclamation-triangle text-warning" style="font-size: 2rem"></i>
        <div>
          <p class="mb-1">
            Supprimer <strong>{{ selectedEvaluations.length }}</strong> évaluation(s) ?
          </p>
          <p class="text-muted mb-0 small">Cette action est irréversible.</p>
        </div>
      </div>
      <template #footer>
        <Button
          label="Annuler"
          severity="secondary"
          outlined
          @click="bulkDeleteModal.show = false"
        />
        <Button
          label="Supprimer tout"
          icon="pi pi-trash"
          severity="danger"
          :loading="bulkDeleteModal.loading"
          @click="handleBulkDelete"
        />
      </template>
    </Dialog>

    <!-- Modal de validation en masse -->
    <Dialog v-model:visible="bulkValidateModal.show" header="Validation en masse" modal>
      <div class="d-flex align-items-center gap-3 mb-3">
        <i class="pi pi-check-circle text-success" style="font-size: 2rem"></i>
        <div>
          <p class="mb-1">
            Valider <strong>{{ selectedEvaluations.length }}</strong> évaluation(s) ?
          </p>
          <p class="text-muted mb-0 small">
            Cette action marquera toutes les évaluations sélectionnées comme vérifiées.
          </p>
        </div>
      </div>
      <template #footer>
        <Button
          label="Annuler"
          severity="secondary"
          outlined
          @click="bulkValidateModal.show = false"
        />
        <Button
          label="Valider tout"
          icon="pi pi-check"
          severity="success"
          :loading="bulkValidateModal.loading"
          @click="handleBulkValidate"
        />
      </template>
    </Dialog>

    <!-- Modal de commentaire complet -->
    <Dialog
      v-model:visible="commentModal.show"
      :header="`Commentaire de ${commentModal.evaluation?.user?.nom || 'Anonyme'}`"
      :style="{ 'max-width': '800px' }"
      modal
    >
      <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <Rating :model-value="commentModal.evaluation?.note" readonly :cancel="false" />
          <span class="fw-bold">{{ commentModal.evaluation?.note }}/5</span>
        </div>
        <p class="mb-0">{{ commentModal.evaluation?.commentaire }}</p>
      </div>
      <template #footer>
        <Button label="Fermer" @click="commentModal.show = false" />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="evaluationModal.show"
      :header="evaluationModal.isEdit ? 'Modifier l\'évaluation' : 'Nouvelle évaluation'"
      modal
      style="width: 600px"
      :closable="!evaluationModal.loading"
    >
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <!-- Service -->
        <div class="mb-3">
          <label class="form-label">Service <span class="text-danger">*</span></label>
          <Select
            v-model="evaluationForm.service_id"
            :options="serviceOptions"
            option-label="nom"
            option-value="id"
            placeholder="Sélectionner un service"
            class="w-100"
            :class="{ 'p-invalid': errors.service_id }"
            :disabled="evaluationModal.loading"
            filter
            show-clear
            @change="errors.service_id = ''"
          />
          <small v-if="errors.service_id" class="text-danger">{{ errors.service_id }}</small>
        </div>

        <!-- Utilisateur -->
        <div v-if="!evaluationForm.est_anonyme" class="mb-3">
          <label class="form-label">Utilisateur <span class="text-danger">*</span></label>
          <AutoComplete
            v-model="selectedUserObj"
            :suggestions="userOptions"
            option-label="displayName"
            placeholder="Rechercher un utilisateur..."
            class="w-100"
            :class="{ 'p-invalid': errors.user_id }"
            :disabled="evaluationModal.loading"
            :loading="loadingUsers"
            :min-length="2"
            :delay="300"
            show-clear
            fluid
            @complete="handleUserSearch"
            @item-select="handleUserSelect"
            @clear="handleUserClear"
          >
            <!-- Template pour les suggestions dans la popup -->
            <template #option="{ option }">
              <div class="flex align-items-center gap-3 w-full p-2">
                <div class="font-medium text-gray-900">{{ option.pseudo }} {{ option.email }}</div>
              </div>
            </template>
          </AutoComplete>
          <small v-if="errors.user_id" class="text-danger">{{ errors.user_id }}</small>
        </div>
        <div v-else class="mb-3">
          <label class="form-label">Pseudo anonyme</label>
          <InputText
            v-model="evaluationForm.pseudo_anonyme"
            placeholder="Ex: Client satisfait"
            class="w-100"
            :class="{ 'p-invalid': errors.pseudo_anonyme }"
            :disabled="evaluationModal.loading"
            maxlength="50"
            fluid
          />
          <small class="form-text text-muted">Laissez vide pour "Utilisateur anonyme"</small>
          <small v-if="errors.pseudo_anonyme" class="text-danger">{{
            errors.pseudo_anonyme
          }}</small>
        </div>

        <!-- Note -->
        <div class="mb-3">
          <label class="form-label">Note <span class="text-danger">*</span></label>
          <div class="mt-2">
            <Rating
              v-model="evaluationForm.note"
              :class="{ 'p-invalid': errors.note }"
              :disabled="evaluationModal.loading"
              @change="errors.note = ''"
            />
          </div>
          <small v-if="errors.note" class="text-danger">{{ errors.note }}</small>
        </div>

        <!-- Commentaire -->
        <div class="mb-3">
          <label class="form-label">Commentaire</label>
          <Textarea
            v-model="evaluationForm.commentaire"
            rows="4"
            class="w-100"
            placeholder="Commentaire optionnel..."
            :disabled="evaluationModal.loading"
            maxlength="500"
          />
          <small class="text-muted"
            >{{ evaluationForm.commentaire?.length || 0 }}/500 caractères</small
          >
        </div>

        <!-- Options -->
        <div class="row">
          <div class="col-6">
            <div class="form-check">
              <Checkbox
                v-model="evaluationForm.est_verifie"
                input-id="est_verifie"
                :binary="true"
                :disabled="evaluationModal.loading"
              />
              <label for="est_verifie" class="form-check-label ms-2"> Évaluation vérifiée </label>
            </div>
          </div>
          <div class="col-6">
            <div class="form-check">
              <Checkbox
                v-model="evaluationForm.est_anonyme"
                input-id="est_anonyme"
                :binary="true"
                :disabled="evaluationModal.loading"
              />
              <label for="est_anonyme" class="form-check-label ms-2"> Évaluation anonyme </label>
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <Button label="Annuler" text :disabled="evaluationModal.loading" @click="closeModal" />
        <Button
          :label="evaluationModal.isEdit ? 'Modifier' : 'Créer'"
          :icon="evaluationModal.isEdit ? 'pi pi-check' : 'pi pi-plus'"
          :loading="evaluationModal.loading"
          @click="handleSubmit"
        />
      </template>
    </Dialog>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useEvaluationStore } from '@/stores/evaluationStore'
import { debounce } from 'lodash'
import servicesApi from '@/api/servicesPublics'
import { useUserSearch } from '@/composables/useUserSearch'

// ===========================
// STORES & COMPOSABLES
// ===========================
const store = useEvaluationStore()
const toast = useToast()
const { userOptions, loadingUsers, searchUsers, loadInitialUsers } = useUserSearch()

// ===========================
// REACTIVE DATA
// ===========================
const selectedEvaluations = ref([])

const deleteModal = ref({
  show: false,
  item: null,
  loading: false,
})

const bulkDeleteModal = ref({
  show: false,
  loading: false,
})

const bulkValidateModal = ref({
  show: false,
  loading: false,
})

const commentModal = ref({
  show: false,
  evaluation: null,
})

// Options pour les filtres
const verificationOptions = [
  { label: 'Vérifié', value: true },
  { label: 'Non vérifié', value: false },
]

const anonymeOptions = [
  { label: 'Anonyme', value: true },
  { label: 'Identifié', value: false },
]

// ===========================
// METHODS
// ===========================
const handleRefresh = async () => {
  try {
    await store.fetchAll()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: store.error || 'Erreur lors du chargement',
      life: 5000,
    })
  }
}

const handlePageChange = async (event) => {
  try {
    await store.goToPage(event.page + 1)
    store.filters.limit = event.rows
    selectedEvaluations.value = []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors du changement de page',
      life: 5000,
    })
  }
}

const handleSort = async (event) => {
  try {
    store.filters.sortField = event.sortField
    store.filters.sortOrder = event.sortOrder === 1 ? 'asc' : 'desc'
    await store.fetchAll()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors du tri',
      life: 5000,
    })
  }
}

const handleFilterChange = async () => {
  try {
    store.filters.page = 1
    await store.fetchAll()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors du filtrage',
      life: 5000,
    })
  }
}

const debounceSearch = debounce(handleFilterChange, 300)

const confirmDelete = (evaluation) => {
  deleteModal.value.item = evaluation
  deleteModal.value.show = true
}

const handleDelete = async () => {
  deleteModal.value.loading = true
  try {
    await store.remove(deleteModal.value.item.uuid)
    toast.add({
      severity: 'success',
      summary: 'Supprimé',
      detail: 'Évaluation supprimée avec succès',
      life: 5000,
    })
    deleteModal.value.show = false
    selectedEvaluations.value = []
    handleRefresh()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: store.error || 'Erreur lors de la suppression',
      life: 5000,
    })
  } finally {
    deleteModal.value.loading = false
  }
}

const confirmBulkDelete = () => {
  bulkDeleteModal.value.show = true
}

const handleBulkDelete = async () => {
  bulkDeleteModal.value.loading = true
  try {
    const ids = selectedEvaluations.value.map((e) => e.uuid)
    await store.bulkDelete(ids)
    toast.add({
      severity: 'success',
      summary: 'Supprimé',
      detail: `${ids.length} évaluation(s) supprimée(s)`,
      life: 5000,
    })
    bulkDeleteModal.value.show = false
    selectedEvaluations.value = []
    handleRefresh()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: store.error || 'Erreur lors de la suppression en masse',
      life: 5000,
    })
  } finally {
    bulkDeleteModal.value.loading = false
  }
}

// ===========================
// NOUVELLES MÉTHODES POUR LA VALIDATION
// ===========================
const confirmBulkValidate = () => {
  bulkValidateModal.value.show = true
}

const handleBulkValidate = async () => {
  bulkValidateModal.value.loading = true
  try {
    const ids = selectedEvaluations.value.map((e) => e.uuid)
    await store.bulkValidate(ids)
    toast.add({
      severity: 'success',
      summary: 'Validé',
      detail: `${ids.length} évaluation(s) validée(s)`,
      life: 5000,
    })
    bulkValidateModal.value.show = false
    selectedEvaluations.value = []
    handleRefresh()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: store.error || 'Erreur lors de la validation en masse',
      life: 5000,
    })
  } finally {
    bulkValidateModal.value.loading = false
  }
}

const toggleValidation = async (evaluation) => {
  try {
    await store.toggleValidation(evaluation.uuid, evaluation.est_verifie)

    toast.add({
      severity: 'success',
      summary: evaluation.est_verifie ? 'Validé' : 'Invalidé',
      detail: `Évaluation ${evaluation.est_verifie ? 'validée' : 'invalidée'} avec succès`,
      life: 3000,
    })
  } catch (error) {
    // Rollback en cas d'erreur
    evaluation.est_verifie = !evaluation.est_verifie

    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors du changement de statut',
      life: 5000,
    })
  }
}

const showCommentModal = (evaluation) => {
  commentModal.value.evaluation = evaluation
  commentModal.value.show = true
}

// ===========================
// UTILS
// ===========================
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const evaluationModal = ref({
  show: false,
  isEdit: false,
  loading: false,
})

const defaultForm = {
  service_id: null,
  user_id: null,
  note: null,
  commentaire: '',
  est_verifie: false,
  est_anonyme: false,
  pseudo: null,
}

const evaluationForm = ref({ ...defaultForm })
const errors = ref({})

// Options pour les dropdowns
const serviceOptions = ref([])
const selectedUserObj = ref(null)

// ===========================
// MODAL ACTIONS
// ===========================
const openCreateModal = async () => {
  evaluationModal.value = { show: true, isEdit: false, loading: false }
  evaluationForm.value = { ...defaultForm }
  selectedUserObj.value = null
  errors.value = {}
  await loadOptions()
}

const openEditModal = async (evaluation) => {
  evaluationModal.value = { show: true, isEdit: true, loading: false }
  evaluationForm.value = {
    service_id: evaluation.service.id,
    user_id: evaluation.user?.id || null,
    note: evaluation.note,
    commentaire: evaluation.commentaire || '',
    est_verifie: evaluation.est_verifie || false,
    est_anonyme: evaluation.est_anonyme || false,
    pseudo_anonyme: evaluation.pseudo || null,
  }

  if (evaluation.user) {
    selectedUserObj.value = {
      ...evaluation.user,
      displayName: `${evaluation.user.pseudo} (${evaluation.user.email})`,
    }
  } else {
    selectedUserObj.value = null
  }

  errors.value = {}
  store.currentEvaluation = evaluation
  await loadOptions()
}

const closeModal = () => {
  evaluationModal.value.show = false
  evaluationForm.value = { ...defaultForm }
  errors.value = {}
  store.currentEvaluation = null
}

// ===========================
// CHARGEMENT DES OPTIONS
// ===========================
const loadOptions = async () => {
  try {
    // Charger les services
    const servicesData = await servicesApi.getAll({ limit: 1000 })
    serviceOptions.value = servicesData.data || servicesData

    // Charger quelques utilisateurs initiaux si pas anonyme
    if (!evaluationForm.value.est_anonyme) {
      await loadInitialUsers(20)
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les options',
      life: 5000,
    })
  }
}

const handleUserSearch = async (event) => {
  await searchUsers(event.query)
}

const handleUserSelect = (event) => {
  evaluationForm.value.user_id = event.value.id
  errors.value.user_id = ''
}

const handleUserClear = () => {
  evaluationForm.value.user_id = null
  selectedUserObj.value = null
}

// ===========================
// VALIDATION
// ===========================
const validateForm = () => {
  errors.value = {}

  if (!evaluationForm.value.service_id) {
    errors.value.service_id = 'Le service est requis'
  }

  if (!evaluationForm.value.user_id && !evaluationForm.value.est_anonyme) {
    errors.value.user_id = "L'utilisateur est requis"
  }

  if (
    !evaluationForm.value.note ||
    evaluationForm.value.note < 1 ||
    evaluationForm.value.note > 5
  ) {
    errors.value.note = 'La note doit être entre 1 et 5'
  }

  return Object.keys(errors.value).length === 0
}

// ===========================
// SOUMISSION
// ===========================
const handleSubmit = async () => {
  if (!validateForm()) return

  evaluationModal.value.loading = true

  try {
    const payload = { ...evaluationForm.value }

    if (evaluationModal.value.isEdit) {
      await store.update(store.currentEvaluation.uuid, payload)
      toast.add({
        severity: 'success',
        summary: 'Modifié',
        detail: 'Évaluation modifiée avec succès',
        life: 5000,
      })
    } else {
      await store.create(payload)
      toast.add({
        severity: 'success',
        summary: 'Créé',
        detail: 'Évaluation créée avec succès',
        life: 5000,
      })
    }

    closeModal()
    await handleRefresh()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: store.error || 'Une erreur est survenue',
      life: 5000,
    })
  } finally {
    evaluationModal.value.loading = false
  }
}

const handleRowClick = (evaluation) => {
  openEditModal(evaluation.data)
}

// ===========================
// LIFECYCLE
// ===========================
onMounted(() => {
  handleRefresh()
})
</script>

<style scoped>
.container {
  max-width: 1400px;
}

.p-datatable .p-datatable-tbody > tr > td {
  padding: 0.75rem;
}

.p-rating .p-rating-item {
  margin-right: 0.1rem;
}

.p-togglebutton {
  min-width: auto;
}

.p-togglebutton.p-button-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
