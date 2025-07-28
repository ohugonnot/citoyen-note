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
              :loading="loading"
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
        <div class="row g-3" v-if="stats">
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-building text-primary fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ stats.total || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Total</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-check-circle text-success fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ stats.actifs || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Actifs</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-star text-warning fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ stats.note_moyenne?.toFixed(1) || 'N/A' }}</h5>
                <p class="card-text text-muted small mb-0">Note moy.</p>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="card text-center h-100">
              <div class="card-body py-3">
                <i class="pi pi-map-marker text-info fs-3 mb-2"></i>
                <h5 class="card-title mb-1">{{ stats.villes || 0 }}</h5>
                <p class="card-text text-muted small mb-0">Villes</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <!-- Toggle filtres sur mobile -->
            <div class="d-md-none mb-3">
              <Button
                @click="showFilters = !showFilters"
                :icon="showFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"
                :label="showFilters ? 'Masquer les filtres' : 'Afficher les filtres'"
                severity="secondary"
                text
                size="small"
                class="w-100"
              />
            </div>

            <!-- Filtres -->
            <div :class="['row g-3', { 'd-none d-md-flex': !showFilters }]">
              <!-- Recherche -->
              <div class="col-12 col-md-4">
                <IconField>
                  <InputIcon class="pi pi-search" />
                  <InputText
                    v-model="filters.search"
                    placeholder="Rechercher un service..."
                    class="w-100"
                    @input="debouncedSearch"
                  />
                </IconField>
              </div>

              <!-- Statut -->
              <div class="col-6 col-md-2">
                <Select
                  v-model="filters.statut"
                  :options="statutOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Tous les statuts"
                  showClear
                  @change="debouncedSearch"
                  class="w-100"
                />
              </div>

              <!-- Ville -->
              <div class="col-6 col-md-2">
                <InputText
                  v-model="filters.ville"
                  @input="debouncedSearch"
                  placeholder="Ville"
                  class="w-100"
                />
              </div>

              <div class="col-12 col-md-3">
                <div style="max-width: 200px; overflow: hidden">
                  <InputNumber
                    v-model="filters.note_min"
                    @input="debouncedSearch"
                    placeholder="Note min."
                    :min="0"
                    :max="5"
                    :step="0.1"
                    :maxFractionDigits="1"
                    showButtons
                    buttonLayout="horizontal"
                    class="w-100 compact-input-number"
                  />
                </div>
              </div>

              <!-- Actions -->
              <div class="col-12 col-md-1">
                <div class="d-flex justify-content-end">
                  <Button
                    @click="confirmBulkDelete"
                    :disabled="selectedServices.length === 0"
                    :class="'bt-remove'"
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
        </div>
      </div>
    </div>

    <!-- Table des services -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <DataTable
            v-model:selection="selectedServices"
            :value="services"
            :loading="loading"
            :paginator="true"
            :rows="pagination.limit"
            :totalRecords="pagination.total"
            :lazy="true"
            :first="(pagination.page - 1) * pagination.limit"
            :paginatorTemplate="paginatorTemplate"
            :rowsPerPageOptions="[10, 25, 50, 100]"
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

            <!-- Statut -->
            <Column field="statut" header="Statut" sortable :style="{ minWidth: '100px' }">
              <template #body="{ data }">
                <Badge :class="getStatutBadgeClass(data.statut)" class="px-2 py-1">
                  <i :class="getStatutIcon(data.statut)" class="me-1"></i>
                  {{ getStatutLabel(data.statut) }}
                </Badge>
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

            <!-- Dernière modification -->
            <Column field="updatedAt" header="Modifié" sortable :style="{ minWidth: '100px' }">
              <template #body="{ data }">
                <small class="text-muted">
                  {{
                    new Date(data.updatedAt).toLocaleDateString('fr-FR', {
                      day: '2-digit',
                      month: '2-digit',
                      year: '2-digit',
                    })
                  }}
                </small>
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
                    Object.values(filters).some((v) => v !== null && v !== '')
                      ? 'Aucun service ne correspond à vos critères de recherche.'
                      : 'Commencez par créer votre premier service public.'
                  }}
                </p>
                <Button
                  v-if="!Object.values(filters).some((v) => v !== null && v !== '')"
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
import { ref, onMounted, computed } from 'vue'
import { debounce } from 'lodash'
import router from '@/router'
import { useToast } from 'primevue/usetoast'
import {
  fetchServicesPublics,
  deleteServicePublic,
  bulkDeleteServicesPublics,
  getServicesPublicsStats,
} from '@/api/servicesPublics'

const toast = useToast()

// Responsive breakpoints
const $screen = computed(() => {
  if (typeof window === 'undefined') return {}
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

// État réactif
const services = ref([])
const selectedServices = ref([])
const loading = ref(true)
const error = ref(null)
const stats = ref(null)
const showFilters = ref(false)
const showImportModal = ref(false)

// Modales
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const serviceToDelete = ref(null)

// Options pour les dropdowns
const statutOptions = [
  { label: 'Actif', value: 'actif' },
  { label: 'Fermé', value: 'ferme' },
  { label: 'Travaux', value: 'travaux' },
]

// Filtres et pagination
const filters = ref({
  search: '',
  statut: null,
  ville: '',
  note_min: null,
})

const pagination = ref({
  page: 1,
  limit: 25,
  total: 0,
  totalPages: 0,
})

const sorting = ref({
  field: 'nom',
  order: 'asc',
})

// Gestion de la sélection
const onSelectAll = (event) => {
  selectedServices.value = [...event.data]
}

const onUnselectAll = () => {
  selectedServices.value = []
}

// Template de pagination plus explicite
const paginatorTemplate = computed(() => {
  if ($screen.value.mdAndUp) {
    return 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport'
  } else {
    return 'PrevPageLink PageLinks NextPageLink'
  }
})

// Fonctions principales
const fetchServices = async () => {
  loading.value = true

  try {
    const params = {
      page: pagination.value.page,
      limit: pagination.value.limit,
      sortField: sorting.value.field,
      sortOrder: sorting.value.order,
      search: filters.value.search || undefined,
      statut: filters.value.statut || undefined,
      ville: filters.value.ville || undefined,
      categorie: filters.value.categorie || undefined,
      source: filters.value.source || undefined,
    }

    // Nettoyer les paramètres undefined
    Object.keys(params).forEach((key) => {
      if (params[key] === undefined || params[key] === null || params[key] === '') {
        delete params[key]
      }
    })

    const apiResponse = await fetchServicesPublics(params)

    services.value = apiResponse.data || []
    if (apiResponse.pagination) {
      pagination.value = {
        page: apiResponse.pagination.page,
        limit: apiResponse.pagination.limit,
        total: apiResponse.pagination.total,
        totalPages: apiResponse.pagination.totalPages,
      }
    }
  } catch (err) {
    console.error('[fetchServices] Erreur:', err)
    error.value = err?.response?.data?.message || err?.message || 'Erreur lors du chargement'

    // Reset en cas d'erreur
    services.value = []
    pagination.value.total = 0

    // Afficher le toast d'erreur
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.value,
      life: 5000,
    })
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    stats.value = await getServicesPublicsStats()
  } catch (err) {
    console.warn('[fetchStats] Erreur:', err)
  }
}

// Gestion des événements DataTable
const onPageChange = (event) => {
  pagination.value.page = event.page + 1 // PrimeVue utilise un index basé sur 0
  pagination.value.limit = event.rows
  fetchServices()
}

const onSort = (event) => {
  sorting.value.field = event.sortField
  sorting.value.order = event.sortOrder === 1 ? 'asc' : 'desc'
  pagination.value.page = 1 // Reset à la première page lors du tri
  fetchServices()
}

// Recherche avec débounce
const debouncedSearch = debounce(() => {
  pagination.value.page = 1
  fetchServices()
}, 300)

// Actualisation
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([fetchServices(), fetchStats()])
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: "Impossible d'actualiser les données",
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

// Actions sur les services
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
    await deleteServicePublic(serviceToDelete.value.id)
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Service supprimé avec succès',
      life: 3000,
    })

    // Supprimer de la liste locale
    services.value = services.value.filter((s) => s.id !== serviceToDelete.value.id)
    selectedServices.value = selectedServices.value.filter((s) => s.id !== serviceToDelete.value.id)

    // Actualiser si la page devient vide
    if (services.value.length === 0 && pagination.value.page > 1) {
      pagination.value.page--
      await fetchServices()
    }

    showDeleteModal.value = false
    serviceToDelete.value = null
  } catch (error) {
    console.error('[deleteService] Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error?.response?.data?.message || 'Erreur lors de la suppression',
      life: 5000,
    })
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
    await bulkDeleteServicesPublics(ids)

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `${selectedServices.value.length} service(s) supprimé(s)`,
      life: 3000,
    })

    selectedServices.value = []
    showBulkDeleteModal.value = false
    await fetchServices()
  } catch (error) {
    console.error('[bulkDelete] Erreur:', error)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Erreur lors de la suppression',
      life: 5000,
    })
  }
}

// Fonctions utilitaires pour l'affichage
const getStatutBadgeClass = (statut) => {
  const classes = {
    actif: 'bg-success text-white',
    ferme: 'bg-danger text-white',
    travaux: 'bg-warning text-dark',
  }
  return classes[statut] || 'bg-secondary text-white'
}

const getStatutLabel = (statut) => {
  const labels = {
    actif: 'Actif',
    ferme: 'Fermé',
    travaux: 'Travaux',
  }
  return labels[statut] || statut
}

const getStatutIcon = (statut) => {
  const icons = {
    actif: 'pi pi-check-circle',
    ferme: 'pi pi-times-circle',
    travaux: 'pi pi-wrench',
  }
  return icons[statut] || 'pi pi-circle'
}

// Lifecycle
onMounted(() => {
  refreshData()
})
</script>

<style scoped>
.stats-card {
  transition: transform 0.2s ease;
}

.stats-card:hover {
  transform: translateY(-2px);
}

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
