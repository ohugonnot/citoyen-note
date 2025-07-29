<template>
  <div class="container-fluid">
    <!-- Header avec statistiques -->
    <div class="row mb-4">
      <div class="col-12">
        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3"
        >
          <div>
            <h1 class="h3 mb-2 mb-md-0">Services Publics</h1>
            <p class="text-muted mb-0">Gestion des services publics référencés</p>
          </div>
          <div class="d-flex flex-column flex-md-row gap-2">
            <Button
              @click="refreshData"
              :loading="serviceStore.isLoading"
              icon="pi pi-refresh"
              label="Actualiser"
              severity="secondary"
              size="small"
              class="order-2 order-md-1"
            />
            <Button
              @click="showImportModal = true"
              icon="pi pi-upload"
              label="Importer"
              severity="info"
              size="small"
              class="order-1 order-md-2"
            />
            <Button
              @click="createService"
              icon="pi pi-plus"
              label="Nouveau service"
              severity="primary"
              size="small"
              class="order-0 order-md-3"
            />
          </div>
        </div>

        <!-- Statistiques -->
        <div class="row g-3" v-if="serviceStore.stats">
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-building text-primary fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ serviceStore.stats.total || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Total</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-check-circle text-success fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ serviceStore.stats.actifs || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Actifs</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-star text-warning fs-3 mb-2"></i>
                <h5 class="card-title mb-1">
                  {{ serviceStore.stats.note_moyenne?.toFixed(1) || 'N/A' }}
                </h5>
                <p class="card-text text-muted small mb-0">Note moy.</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-map-marker text-info fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ serviceStore.stats.villes || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Villes</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="row g-3 mb-3">
      <!-- Recherche -->
      <div class="col-lg-4 col-md-6">
        <div class="input-group">
          <InputText
            v-model="localFilters.search"
            placeholder="Rechercher un service..."
            class="form-control"
            @input="onSearchChange"
            autocomplete="off"
            autocapitalize="off"
            autocorrect="off"
            spellcheck="false"
          />
          <button type="button" class="btn btn-outline-primary" @input="debouncedSearch">
            <i class="pi pi-search"></i>
          </button>
        </div>
      </div>

      <!-- Filtres compacts -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex gap-2">
          <!-- Statut -->
          <Select
            v-model="localFilters.statut"
            :options="STATUT_OPTIONS"
            optionLabel="label"
            optionValue="value"
            placeholder="Statut"
            showClear
            @change="onFilterChange"
            class="flex-fill"
            style="min-width: 120px"
          />

          <!-- Catégorie -->
          <Select
            v-model="localFilters.categorie"
            :options="categorieStore.categoriesOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Catégorie"
            showClear
            @change="onFilterChange"
            class="flex-fill"
            style="min-width: 120px"
          />
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="col-lg-4 col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <!-- Note minimum -->
          <div style="max-width: 175px">
            <InputNumber
              v-model="localFilters.note_min"
              @input="onFilterChange"
              placeholder="Note min."
              :min="0"
              :max="5"
              :step="0.1"
              :maxFractionDigits="1"
              showButtons
              buttonLayout="horizontal"
              class="compact-input-number w-100"
              size="small"
            />
          </div>

          <!-- Actions à droite -->
          <div class="d-flex gap-2 ms-auto">
            <!-- Options d'affichage -->
            <div class="dropdown">
              <button
                type="button"
                class="btn btn-outline-secondary dropdown-toggle"
                data-bs-toggle="dropdown"
                title="Options d'affichage"
              >
                <i class="pi pi-cog"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <h6 class="dropdown-header">Éléments par page</h6>
                </li>
                <li v-for="option in limitOptionsFormatted" :key="option.value">
                  <button
                    type="button"
                    class="dropdown-item"
                    :class="{ active: serviceStore.pagination.limit === option.value }"
                    @click="
                      () => {
                        serviceStore.changeItemsPerPage(option.value)
                      }
                    "
                  >
                    {{ option.label }}
                  </button>
                </li>
              </ul>
            </div>

            <!-- Suppression en lot -->
            <Button
              @click="confirmBulkDelete"
              :disabled="selectedServices.length === 0"
              icon="pi pi-trash"
              severity="danger"
              size="small"
              :title="
                selectedServices.length === 0
                  ? 'Sélectionnez des services'
                  : `Supprimer ${selectedServices.length} service(s)`
              "
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Table des services -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <DataTable
            v-model:selection="selectedServices"
            :value="serviceStore.services"
            :loading="serviceStore.isLoading"
            :paginator="true"
            :rows="serviceStore.pagination.limit"
            :totalRecords="serviceStore.pagination.total"
            :lazy="true"
            :first="(serviceStore.pagination.page - 1) * serviceStore.pagination.limit"
            :paginatorTemplate="paginatorTemplate"
            :rowsPerPageOptions="limitOptions"
            :sortField="sorting.field"
            :sortOrder="sorting.order === 'asc' ? 1 : -1"
            :scrollable="true"
            :resizableColumns="true"
            columnResizeMode="expand"
            selectionMode="multiple"
            dataKey="id"
            responsiveLayout="scroll"
            stripedRows
            @page="onPageChange"
            @sort="onSort"
            @row-select-all="onSelectAll"
            @row-unselect-all="onUnselectAll"
            class="p-datatable-sm"
          >
            <!-- Header de sélection -->
            <Column selectionMode="multiple" :style="{ width: '3rem' }" :exportable="false" />

            <!-- Nom du service -->
            <Column field="nom" header="Service" sortable :style="{ minWidth: '200px' }">
              <template #body="{ data }">
                <div class="d-flex flex-column">
                  <strong class="text-truncate" style="max-width: 200px" :title="data.nom">
                    {{ data.nom }}
                  </strong>
                  <small
                    class="text-muted text-truncate"
                    style="max-width: 200px"
                    :title="data.description"
                  >
                    {{ data.description || 'Aucune description' }}
                  </small>
                </div>
              </template>
            </Column>

            <!-- Localisation -->
            <Column field="ville" header="Localisation" sortable :style="{ minWidth: '150px' }">
              <template #body="{ data }">
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ data.ville }}</span>
                  <small class="text-muted">{{ data.code_postal }}</small>
                </div>
              </template>
            </Column>

            <Column
              field="categorie_nom"
              header="Catégorie"
              :sortable="true"
              :sortField="'categorie.nom'"
              :style="{ minWidth: '150px' }"
            >
              <template #body="{ data }">
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ data.categorie_nom }}</span>
                </div>
              </template>
            </Column>

            <!-- Statut -->
            <Column field="statut" header="Statut" sortable :style="{ minWidth: '100px' }">
              <template #body="{ data }">
                <Badge :class="getStatutBadgeClass(data.statut)" class="px-2 py-1">
                  <i :class="getStatutIcon(data.statut)" class="me-1"></i>
                  {{ getStatutLabel(data.statut) }}
                </Badge>
                <br />
                <small class="text-muted"> Modifié le {{ formatDate(data.updatedAt) }} </small>
              </template>
            </Column>

            <!-- Évaluation -->
            <Column
              field="note_moyenne"
              header="Évaluation"
              sortable
              :style="{ minWidth: '120px' }"
            >
              <template #body="{ data }">
                <div class="d-flex flex-column align-items-center">
                  <div class="d-flex align-items-center mb-1">
                    <Rating
                      :modelValue="data.note_moyenne || 0"
                      readonly
                      :cancel="false"
                      :stars="5"
                    />
                  </div>
                  <small class="text-muted">
                    {{ data.note_moyenne?.toFixed(1) || 'N/A' }}
                    ({{ data.nombre_evaluations || 0 }})
                  </small>
                </div>
              </template>
            </Column>

            <!-- Contact -->
            <Column header="Contact" :style="{ minWidth: '140px' }">
              <template #body="{ data }">
                <div class="d-flex flex-column gap-1">
                  <div v-if="data.telephone" class="d-flex align-items-center">
                    <i class="pi pi-phone text-muted me-1"></i>
                    <small>{{ data.telephone }}</small>
                  </div>
                  <div v-if="data.email" class="d-flex align-items-center">
                    <i class="pi pi-envelope text-muted me-1"></i>
                    <small class="text-truncate" style="max-width: 120px" :title="data.email">
                      {{ data.email }}
                    </small>
                  </div>
                  <div v-if="data.site_web" class="d-flex align-items-center">
                    <i class="pi pi-globe text-muted me-1"></i>
                    <a
                      :href="data.site_web"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="small"
                    >
                      Site web
                    </a>
                  </div>
                </div>
              </template>
            </Column>

            <!-- Actions -->
            <Column header="Actions" :exportable="false" :style="{ minWidth: '120px' }">
              <template #body="{ data }">
                <div class="d-flex gap-1">
                  <Button
                    @click="viewService(data)"
                    icon="pi pi-eye"
                    severity="info"
                    text
                    size="small"
                    title="Voir"
                  />
                  <Button
                    @click="editService(data)"
                    icon="pi pi-pencil"
                    severity="secondary"
                    text
                    size="small"
                    title="Modifier"
                  />
                  <Button
                    @click="confirmDelete(data)"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    size="small"
                    title="Supprimer"
                  />
                </div>
              </template>
            </Column>

            <!-- Message quand aucun service -->
            <template #empty>
              <div class="text-center py-5">
                <i class="pi pi-building text-muted" style="font-size: 3rem"></i>
                <h5 class="mt-3 mb-2">Aucun service trouvé</h5>
                <p class="text-muted mb-3">
                  {{
                    hasActiveFilters
                      ? 'Aucun service ne correspond à vos critères de recherche.'
                      : 'Commencez par créer votre premier service public.'
                  }}
                </p>
                <Button
                  v-if="!hasActiveFilters"
                  @click="createService"
                  label="Créer un service"
                  icon="pi pi-plus"
                  severity="primary"
                />
              </div>
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <!-- Modal de suppression simple -->
    <Dialog
      v-model:visible="showDeleteModal"
      modal
      :header="`Supprimer ${serviceToDelete?.nom || 'ce service'}`"
      :style="{ width: $screen.mdAndUp ? '650px' : '95vw' }"
    >
      <div class="text-center">
        <i class="pi pi-exclamation-triangle text-warning" style="font-size: 3rem"></i>
        <h5 class="mt-3 mb-3">Êtes-vous sûr ?</h5>
        <p class="text-muted mb-4">
          Cette action supprimera définitivement le service
          <strong>{{ serviceToDelete?.nom }}</strong>
          ainsi que toutes ses évaluations.
        </p>
      </div>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end">
          <Button @click="showDeleteModal = false" label="Annuler" severity="secondary" text />
          <Button @click="deleteService" label="Supprimer" severity="danger" icon="pi pi-trash" />
        </div>
      </template>
    </Dialog>

    <!-- Modal de suppression en masse -->
    <Dialog
      v-model:visible="showBulkDeleteModal"
      modal
      header="Suppression en masse"
      :style="{ width: $screen.mdAndUp ? '500px' : '95vw' }"
    >
      <div class="text-center">
        <i class="pi pi-exclamation-triangle text-warning" style="font-size: 3rem"></i>
        <h5 class="mt-3 mb-3">Supprimer {{ selectedServices.length }} service(s) ?</h5>
        <p class="text-muted mb-4">
          Cette action supprimera définitivement les {{ selectedServices.length }} services
          sélectionnés ainsi que toutes leurs évaluations. Cette action est irréversible.
        </p>

        <!-- Liste des services à supprimer -->
        <div class="text-start">
          <h6 class="mb-2">Services concernés :</h6>
          <ul class="list-unstyled mb-0" style="max-height: 200px; overflow-y: auto">
            <li
              v-for="service in selectedServices"
              :key="service.id"
              class="d-flex align-items-center py-1 px-2 bg-light rounded mb-1"
            >
              <i class="pi pi-building text-muted me-2"></i>
              <span class="flex-grow-1">{{ service.nom }}</span>
              <Badge :class="getStatutBadgeClass(service.statut)" class="ms-2">
                {{ getStatutLabel(service.statut) }}
              </Badge>
            </li>
          </ul>
        </div>
      </div>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end">
          <Button @click="showBulkDeleteModal = false" label="Annuler" severity="secondary" text />
          <Button
            @click="bulkDelete"
            :label="`Supprimer ${selectedServices.length} service(s)`"
            severity="danger"
            icon="pi pi-trash"
          />
        </div>
      </template>
    </Dialog>

    <!-- Modal d'import (placeholder) -->
    <Dialog
      v-model:visible="showImportModal"
      modal
      header="Importer des services"
      :style="{ width: $screen.mdAndUp ? '600px' : '95vw' }"
    >
      <div class="text-center py-4">
        <i class="pi pi-upload text-primary" style="font-size: 3rem"></i>
        <h5 class="mt-3 mb-2">Import de services</h5>
        <p class="text-muted mb-4">Fonctionnalité d'import à implémenter</p>
      </div>

      <template #footer>
        <div class="d-flex gap-2 justify-content-end">
          <Button @click="showImportModal = false" label="Fermer" severity="secondary" />
        </div>
      </template>
    </Dialog>
    <Toast />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { debounce } from 'lodash'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import { useCategorieStore } from '@/stores/categorieServiceStore'

const router = useRouter()

// =============================================
// CONSTANTES
// =============================================
const TOAST_CONFIG = {
  SUCCESS: { severity: 'success', life: 3000 },
  ERROR: { severity: 'error', life: 5000 },
}

const STATUT_CONFIG = {
  actif: {
    label: 'Actif',
    icon: 'pi pi-check-circle',
    class: 'bg-success text-white',
  },
  ferme: {
    label: 'Fermé',
    icon: 'pi pi-times-circle',
    class: 'bg-danger text-white',
  },
  travaux: {
    label: 'Travaux',
    icon: 'pi pi-wrench',
    class: 'bg-warning text-dark',
  },
}

const STATUT_OPTIONS = [
  { label: 'Tous les statuts', value: null },
  ...Object.entries(STATUT_CONFIG).map(([value, config]) => ({
    label: config.label,
    value,
  })),
]

const DEBOUNCE_DELAY = 300
const DEFAULT_SORT = { field: 'nom', order: 'asc' }

// =============================================
// COMPOSABLES
// =============================================
const toast = useToast()
const serviceStore = useServicePublicStore()
const categorieStore = useCategorieStore()
// =============================================
// ÉTAT LOCAL UI
// =============================================
const selectedServices = ref([])
const showImportModal = ref(false)
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const serviceToDelete = ref(null)
const sorting = ref({ ...DEFAULT_SORT })

const limitOptions = ref([10, 25, 50, 100])

const limitOptionsFormatted = computed(() => {
  return limitOptions.value.map((value) => ({
    label: `${value} par page`,
    value: value,
  }))
})

// Filtres locaux synchronisés avec le store
const localFilters = ref({
  search: '',
  statut: null,
  categorie: null,
  note_min: null,
})

// =============================================
// COMPUTED
// =============================================
const $screen = computed(() => {
  if (typeof window === 'undefined') return { mdAndUp: true }
  const width = window.innerWidth
  return {
    xs: width < 576,
    sm: width >= 576,
    md: width >= 768,
    lg: width >= 992,
    xl: width >= 1200,
    mdAndUp: width >= 768,
  }
})

const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(
    (value) => value !== null && value !== '' && value !== undefined,
  )
})

const paginatorTemplate = computed(() => {
  if ($screen.value.mdAndUp) {
    return 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport'
  }
  return 'PrevPageLink PageLinks NextPageLink'
})

// =============================================
// FONCTIONS UTILITAIRES
// =============================================
const showToast = (type, summary, detail) => {
  const config = TOAST_CONFIG[type] || TOAST_CONFIG.ERROR
  toast.add({ ...config, summary, detail })
}

const getStatutConfig = (statut) => {
  return (
    STATUT_CONFIG[statut] || {
      label: statut,
      icon: 'pi pi-circle',
      class: 'bg-secondary text-white',
    }
  )
}

const getStatutBadgeClass = (statut) => getStatutConfig(statut).class
const getStatutLabel = (statut) => getStatutConfig(statut).label
const getStatutIcon = (statut) => getStatutConfig(statut).icon

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
  })
}

// =============================================
// GESTION DES FILTRES ET SYNCHRONISATION
// =============================================
const syncFiltersToStore = () => {
  const cleanFilters = Object.fromEntries(
    Object.entries(localFilters.value).filter(([_, value]) => value !== undefined),
  )
  serviceStore.updateFilters(cleanFilters)
}

const debouncedSearch = debounce(async () => {
  try {
    syncFiltersToStore()

    const searchParams = {
      ...serviceStore.filters,
      sortField: sorting.value.field,
      sortOrder: sorting.value.order,
      page: 1, // Reset à la page 1 lors de la recherche
    }

    await serviceStore.fetchServices(searchParams)
  } catch (error) {
    handleError('Erreur lors de la recherche', error)
  }
}, DEBOUNCE_DELAY)

// =============================================
// GESTIONNAIRES D'ÉVÉNEMENTS
// =============================================
const onSearchChange = () => debouncedSearch()
const onFilterChange = () => debouncedSearch()

const onSelectAll = (event) => {
  selectedServices.value = [...event.data]
}

const onUnselectAll = () => {
  selectedServices.value = []
}

const onPageChange = async (event) => {
  try {
    await serviceStore.fetchServices({
      page: event.page + 1, // PrimeVue commence à 0, votre API à 1
      limit: event.rows,
      sortField: sorting.value.field,
      sortOrder: sorting.value.order,
    })
  } catch (error) {
    handleError('Erreur lors du changement de page', error)
  }
}

const onSort = async (event) => {
  try {
    sorting.value = {
      field: event.sortField,
      order: event.sortOrder === 1 ? 'asc' : 'desc',
    }

    await serviceStore.fetchServices({
      sortField: sorting.value.field,
      sortOrder: sorting.value.order,
    })
  } catch (error) {
    handleError('Erreur lors du tri', error)
  }
}

// =============================================
// ACTIONS CRUD
// =============================================
const createService = () => {
  router.push('/admin/services-publiques/create')
}

const viewService = (service) => {
  router.push(`/admin/services-publiques/${service.id}`)
}

const editService = (service) => {
  router.push(`/admin/services-publiques/${service.id}/edit`)
}

const confirmDelete = (service) => {
  serviceToDelete.value = service
  showDeleteModal.value = true
}

const deleteService = async () => {
  if (!serviceToDelete.value) return

  try {
    await serviceStore.deleteService(serviceToDelete.value.id)

    selectedServices.value = selectedServices.value.filter((s) => s.id !== serviceToDelete.value.id)
    refreshData()
    showToast('SUCCESS', 'Succès', 'Service supprimé avec succès')
    resetDeleteModal()
  } catch (error) {
    handleError('Erreur lors de la suppression', error)
  }
}

const confirmBulkDelete = () => {
  if (selectedServices.value.length === 0) return
  showBulkDeleteModal.value = true
}

const bulkDelete = async () => {
  if (selectedServices.value.length === 0) return

  try {
    const ids = selectedServices.value.map((service) => service.id)
    await serviceStore.bulkDeleteServices(ids)

    const count = selectedServices.value.length
    selectedServices.value = []
    showBulkDeleteModal.value = false
    refreshData()
    showToast('SUCCESS', 'Succès', `${count} service(s) supprimé(s)`)
  } catch (error) {
    handleError('Erreur lors de la suppression en masse', error)
  }
}

const refreshData = async () => {
  try {
    await Promise.all([serviceStore.refreshServices(), serviceStore.fetchStats()])
  } catch (error) {
    handleError("Impossible d'actualiser les données", error)
  }
}

// =============================================
// GESTION D'ERREUR
// =============================================
const handleError = (message, error) => {
  console.error(message, error)
  const errorMessage = serviceStore.error || error?.message || message
  showToast('ERROR', 'Erreur', errorMessage)
}

const resetDeleteModal = () => {
  showDeleteModal.value = false
  serviceToDelete.value = null
}

// =============================================
// WATCHERS
// =============================================
watch(
  () => serviceStore.filters,
  (newFilters) => {
    // Synchroniser les filtres du store vers les filtres locaux
    Object.assign(localFilters.value, {
      search: newFilters.search || '',
      statut: newFilters.statut || null,
      categorie: newFilters.categorie || null,
      note_min: newFilters.note_min || null,
    })
  },
  { deep: true },
)

// =============================================
// INITIALISATION
// =============================================
onMounted(async () => {
  try {
    await Promise.all([
      categorieStore.fetchCategories(),
      serviceStore.fetchServices({
        ...DEFAULT_SORT,
        sortField: sorting.value.field,
        sortOrder: sorting.value.order,
      }),
      serviceStore.fetchStats(),
    ])
  } catch (error) {
    handleError('Erreur lors du chargement initial', error)
  }
})
</script>

<style scoped>
.rating-stars {
  font-size: 0.8rem;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Alignement parfait des éléments de filtres */
.row.g-3 > div {
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}

/* Correction icône de recherche */
.position-relative .pi-search {
  pointer-events: none;
  z-index: 1;
}

/* Alignement du bouton de suppression */
.d-flex.justify-content-end {
  height: 100%;
  align-items: flex-end;
}

/* Uniformisation des hauteurs d'inputs */
.p-inputtext,
.p-select,
.p-inputnumber {
  height: 40px;
}

.p-button-sm.bt-remove {
  height: 40px;
  width: 40px;
}

.compact-input-number :deep(.p-inputnumber-input) {
  max-width: 80x !important;
  min-width: 80px !important;
  text-align: center !important;
}

.compact-input-number :deep(.p-inputnumber-button) {
  width: 30px !important;
  min-width: 30px !important;
}
</style>
