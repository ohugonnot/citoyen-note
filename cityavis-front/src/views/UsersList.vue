<template>
  <div class="bg-light min-vh-100 py-3">
    <div class="container">
      <!-- En-tête avec statistiques -->
      <div class="row mb-4">
        <div class="col-12">
          <div
            class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-3"
          >
            <h1 class="h2 fw-bold text-dark mb-2 mb-lg-0">Gestion des utilisateurs</h1>
            <Button
              icon="pi pi-plus"
              label="Nouvel utilisateur"
              class="p-button-success d-md-inline-flex"
              @click="createUser"
            />
          </div>

          <!-- Statistiques responsive -->
          <div v-if="stats" class="row g-3">
            <div class="col-6 col-md-3">
              <div class="card text-center h-100">
                <div class="card-body py-3">
                  <i class="pi pi-users text-primary fs-3 mb-2"></i>
                  <h5 class="card-title mb-1">{{ stats.total || 0 }}</h5>
                  <p class="card-text text-muted small mb-0">Total</p>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-3">
              <div class="card text-center h-100">
                <div class="card-body py-3">
                  <i class="pi pi-check-circle text-success fs-3 mb-2"></i>
                  <h5 class="card-title mb-1">{{ stats.active || 0 }}</h5>
                  <p class="card-text text-muted small mb-0">Actifs</p>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-3">
              <div class="card text-center h-100">
                <div class="card-body py-3">
                  <i class="pi pi-shield text-info fs-3 mb-2"></i>
                  <h5 class="card-title mb-1">{{ stats.verified || 0 }}</h5>
                  <p class="card-text text-muted small mb-0">Vérifiés</p>
                </div>
              </div>
            </div>

            <div class="col-6 col-md-3">
              <div class="card text-center h-100">
                <div class="card-body py-3">
                  <i class="pi pi-calendar text-warning fs-3 mb-2"></i>
                  <h5 class="card-title mb-1">{{ stats.thisMonth || 0 }}</h5>
                  <p class="card-text text-muted small mb-0">Ce mois</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Barre d'actions principale -->
      <div class="row g-3 mb-3">
        <!-- Recherche -->
        <div class="col-lg-4 col-md-6">
          <label class="form-label fw-semibold text-dark mb-2">
            <i class="pi pi-search me-1"></i>
            Recherche
          </label>
          <div class="input-group">
            <input
              v-model="filters.search"
              type="text"
              spellcheck="false"
              autocorrect="off"
              autocapitalize="off"
              autocomplete="off"
              class="form-control"
              placeholder="Email, pseudo, nom..."
              @input="debouncedSearch"
            />
            <button
              type="button"
              class="btn btn-outline-primary"
              :disabled="loading"
              @click="fetchUsers"
            >
              <i class="pi pi-search"></i>
            </button>
          </div>
        </div>

        <!-- Actions rapides -->
        <div class="col-lg-4 col-md-6">
          <label class="form-label fw-semibold text-dark mb-2 invisible"> Actions </label>
          <div class="d-flex gap-2">
            <button
              type="button"
              class="btn btn-outline-secondary"
              title="Actualiser"
              :disabled="loading"
              @click="refreshData"
            >
              <i class="pi pi-refresh" :class="{ 'pi-spin': loading }"></i>
              <span class="d-none d-sm-inline ms-2">Actualiser</span>
            </button>

            <button
              type="button"
              class="btn btn-outline-info"
              title="Filtres avancés"
              :class="{ active: showFilters }"
              @click="showFilters = !showFilters"
            >
              <i class="pi pi-filter"></i>
              <span class="d-none d-lg-inline ms-2">Filtres</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Filtres avancés (collapsible) -->
      <Transition name="slide-down">
        <div v-show="showFilters" class="filters-container">
          <div class="row g-3">
            <div class="col-md-4 col-sm-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-users me-1"></i>
                Rôle
              </label>
              <select
                v-model="filters.role"
                class="form-select"
                @change="
                  () => {
                    pagination.page = 1
                    fetchUsers()
                  }
                "
              >
                <option value="">Tous les rôles</option>
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
            </div>

            <div class="col-md-4 col-sm-6">
              <label class="form-label fw-semibold text-dark">
                <i class="pi pi-flag me-1"></i>
                Statut
              </label>
              <select
                v-model="filters.statut"
                class="form-select"
                @change="
                  () => {
                    pagination.page = 1
                    fetchUsers()
                  }
                "
              >
                <option value="">Tous les statuts</option>
                <option v-for="statut in statutOptions" :key="statut.value" :value="statut.value">
                  {{ statut.label }}
                </option>
              </select>
            </div>

            <div class="col-md-4 col-sm-12 d-flex align-items-end">
              <button type="button" class="btn btn-outline-warning me-2" @click="resetFilters">
                <i class="pi pi-refresh me-1"></i>
                Réinitialiser
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Actions de sélection multiple optimisées -->
      <Transition name="fade">
        <div v-if="selectedUsers.length > 0" class="sticky-selection-bar">
          <div class="alert alert-primary mb-3 shadow-sm">
            <div
              class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2"
            >
              <div class="d-flex align-items-center">
                <i class="pi pi-info-circle me-2"></i>
                <strong>{{ selectedUsers.length }}</strong> utilisateur(s) sélectionné(s)
              </div>
              <div class="d-flex gap-2">
                <Button
                  icon="pi pi-times"
                  label="Désélectionner"
                  class="p-button-text p-button-sm"
                  @click="selectedUsers = []"
                />
                <Button
                  icon="pi pi-trash"
                  :label="$screen.smAndUp ? 'Supprimer la sélection' : 'Supprimer'"
                  class="p-button-danger p-button-sm"
                  @click="confirmBulkDelete"
                />
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Tableau responsive optimisé -->
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <!-- Version desktop -->
          <div class="d-none d-lg-block">
            <DataTable
              v-model:selection="selectedUsers"
              :value="users"
              :loading="loading"
              :paginator="true"
              responsive-layout="scroll"
              row-hover
              data-key="id"
              class="custom-datatable"
              :first="(pagination.page - 1) * pagination.limit"
              :lazy="true"
              column-resize-mode="expand"
              :scrollable="true"
              :template="paginatorTemplate"
              :resizable-columns="true"
              :paginator-template="paginatorTemplate"
              :rows-per-page-options="limitOptions"
              striped-rows
              :total-records="pagination.total"
              :rows="pagination.limit"
              @sort="onSort"
              @page="onPageChange"
              @row-click="onRowClick"
            >
              <Column selection-mode="multiple" header-style="width: 3rem" />

              <Column field="id" header="ID" sortable style="width: 80px">
                <template #body="slotProps">
                  <span class="badge bg-secondary">#{{ slotProps.data.id }}</span>
                </template>
              </Column>

              <Column field="email" header="Utilisateur" sortable class="min-width-250">
                <template #body="slotProps">
                  <div class="d-flex align-items-center">
                    <div class="me-3">
                      <div
                        class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center"
                      >
                        {{ getInitials(slotProps.data) }}
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold text-dark">{{ slotProps.data.email }}</div>
                      <div
                        v-if="slotProps.data.nom || slotProps.data.prenom"
                        class="text-muted small"
                      >
                        {{ slotProps.data.prenom }} {{ slotProps.data.nom }}
                      </div>
                      <div v-if="slotProps.data.pseudo" class="text-muted small">
                        @{{ slotProps.data.pseudo }}
                      </div>
                    </div>
                    <div v-if="slotProps.data.isVerified" class="ms-2">
                      <i
                        class="pi pi-verified text-success"
                        title="Compte vérifié"
                        style="font-size: 1.1rem"
                      ></i>
                    </div>
                  </div>
                </template>
              </Column>

              <Column header="Rôles" style="width: 140px">
                <template #body="slotProps">
                  <div class="d-flex flex-wrap gap-1">
                    <span
                      v-for="role in slotProps.data.roles?.slice(0, 2)"
                      :key="role"
                      :class="getRoleBadgeClass(role)"
                      class="badge small"
                    >
                      {{ getRoleLabel(role) }}
                    </span>
                    <span
                      v-if="slotProps.data.roles?.length > 2"
                      class="badge bg-light text-dark small"
                    >
                      +{{ slotProps.data.roles.length - 2 }}
                    </span>
                  </div>
                </template>
              </Column>

              <Column field="statut" header="Statut" sortable style="width: 120px">
                <template #body="slotProps">
                  <span :class="getStatutBadgeClass(slotProps.data.statut)" class="badge">
                    <i :class="getStatutIcon(slotProps.data.statut)" class="me-1"></i>
                    {{ getStatutLabel(slotProps.data.statut) }}
                  </span>
                </template>
              </Column>

              <Column header="Contact" style="width: 140px">
                <template #body="slotProps">
                  <div class="text-center">
                    <div v-if="slotProps.data.telephone" class="small text-muted mb-1">
                      <i class="pi pi-phone me-1"></i>
                      {{ formatPhone(slotProps.data.telephone) }}
                    </div>
                    <div v-if="slotProps.data.dateNaissance" class="small text-muted">
                      <i class="pi pi-calendar me-1"></i>
                      {{ calculateAge(slotProps.data.dateNaissance) }} ans
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="scoreFiabilite" header="Fiabilité" sortable style="width: 140px">
                <template #body="slotProps">
                  <div v-if="slotProps.data.scoreFiabilite !== null" class="text-center">
                    <div class="progress mb-1" style="height: 8px">
                      <div
                        class="progress-bar"
                        :class="getScoreProgressClass(slotProps.data.scoreFiabilite)"
                        :style="{ width: slotProps.data.scoreFiabilite + '%' }"
                      ></div>
                    </div>
                    <small class="text-muted">{{ slotProps.data.scoreFiabilite }}%</small>
                  </div>
                  <span v-else class="text-muted">-</span>
                </template>
              </Column>

              <Column header="Actions" style="width: 120px">
                <template #body="slotProps">
                  <div class="d-flex gap-1 justify-content-center">
                    <Button
                      icon="pi pi-pencil"
                      class="p-button-rounded p-button-sm p-button-primary"
                      title="Modifier"
                      @click="editUser(slotProps.data)"
                    />
                    <Button
                      icon="pi pi-trash"
                      class="p-button-rounded p-button-sm p-button-danger"
                      title="Supprimer"
                      @click="confirmDelete(slotProps.data)"
                    />
                  </div>
                </template>
              </Column>

              <template #empty>
                <div class="text-center py-5">
                  <i class="pi pi-users" style="font-size: 3rem; color: #6c757d"></i>
                  <h5 class="text-muted mt-3">Aucun utilisateur trouvé</h5>
                  <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                </div>
              </template>

              <template #loading>
                <div class="text-center py-5">
                  <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
                  <p class="text-muted mt-3">Chargement des utilisateurs...</p>
                </div>
              </template>
            </DataTable>
          </div>

          <!-- Version mobile/tablette avec cartes -->
          <div class="d-lg-none">
            <div v-if="loading" class="text-center py-5">
              <ProgressSpinner style="width: 50px; height: 50px" stroke-width="4" />
              <p class="text-muted mt-3">Chargement...</p>
            </div>

            <div v-else-if="users.length === 0" class="text-center py-5">
              <i class="pi pi-users" style="font-size: 3rem; color: #6c757d"></i>
              <h5 class="text-muted mt-3">Aucun utilisateur trouvé</h5>
            </div>

            <div v-else class="mobile-users-list">
              <div v-for="user in users" :key="user.id" class="user-card p-3 border-bottom">
                <div class="d-flex align-items-start">
                  <!-- Checkbox de sélection -->
                  <div class="form-check me-3 mt-1">
                    <input
                      :id="'user-' + user.id"
                      v-model="selectedUsers"
                      :value="user"
                      type="checkbox"
                      class="form-check-input"
                    />
                  </div>

                  <!-- Avatar et infos principales -->
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-start mb-2">
                      <div class="avatar-circle-mobile bg-primary text-white me-3 flex-shrink-0">
                        {{ getInitials(user) }}
                      </div>
                      <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center mb-1">
                          <h6 class="mb-0 fw-semibold text-truncate me-2">{{ user.email }}</h6>
                          <i v-if="user.isVerified" class="pi pi-verified text-success small"></i>
                        </div>
                        <div v-if="user.nom || user.prenom" class="text-muted small mb-1">
                          {{ user.prenom }} {{ user.nom }}
                        </div>
                        <div v-if="user.pseudo" class="text-muted small">@{{ user.pseudo }}</div>
                      </div>
                      <span class="badge bg-secondary small">#{{ user.id }}</span>
                    </div>

                    <!-- Métadonnées et Actions (responsive) -->
                    <div class="user-meta-actions">
                      <div class="user-metadata">
                        <div class="d-flex flex-wrap gap-2 small mb-3 mb-md-0">
                          <span :class="getStatutBadgeClass(user.statut)" class="badge badge-sm">
                            <i :class="getStatutIcon(user.statut)" class="me-1"></i>
                            {{ getStatutLabel(user.statut) }}
                          </span>
                          <span
                            v-for="role in user.roles.slice(0, 1)"
                            v-if="user.roles?.length"
                            :key="role"
                            :class="getRoleBadgeClass(role)"
                            class="badge badge-sm"
                          >
                            {{ getRoleLabel(role) }}
                          </span>
                          <span v-if="user.scoreFiabilite !== null" class="text-muted">
                            <i class="pi pi-star-fill me-1"></i>
                            {{ user.scoreFiabilite }}%
                          </span>
                        </div>
                      </div>

                      <!-- Actions -->
                      <div class="user-actions">
                        <div class="d-flex gap-2">
                          <Button
                            icon="pi pi-pencil"
                            class="p-button-sm p-button-primary p-button-rounded"
                            @click="editUser(user)"
                          />
                          <Button
                            icon="pi pi-trash"
                            class="p-button-sm p-button-danger p-button-rounded"
                            @click="confirmDelete(user)"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modales -->
      <!-- Modal de suppression simple optimisée -->
      <Dialog
        v-model:visible="showDeleteModal"
        modal
        header="Confirmation de suppression"
        :style="{ width: '95%', maxWidth: '450px' }"
        class="mx-auto"
        :dismissable-mask="false"
      >
        <div class="d-flex align-items-start mb-3">
          <div class="text-warning me-3 flex-shrink-0">
            <i class="pi pi-exclamation-triangle" style="font-size: 2rem"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="fw-semibold mb-2">Supprimer l'utilisateur</h6>
            <p class="mb-2">Êtes-vous sûr de vouloir supprimer :</p>
            <div class="bg-light p-3 rounded mb-2">
              <div class="fw-semibold">{{ userToDelete?.email }}</div>
              <small v-if="userToDelete?.nom || userToDelete?.prenom" class="text-muted">
                {{ userToDelete?.prenom }} {{ userToDelete?.nom }}
              </small>
            </div>
            <div class="alert alert-warning py-2 mb-0">
              <i class="pi pi-exclamation-circle me-1"></i>
              <small>Cette action est irréversible.</small>
            </div>
          </div>
        </div>
        <template #footer>
          <div class="d-flex gap-2 justify-content-end">
            <Button
              label="Annuler"
              icon="pi pi-times"
              class="p-button-outlined p-button-secondary"
              @click="showDeleteModal = false"
            />
            <Button
              label="Supprimer"
              icon="pi pi-trash"
              class="p-button-danger"
              @click="deleteUser"
            />
          </div>
        </template>
      </Dialog>

      <!-- Modal de suppression en masse optimisée -->
      <Dialog
        v-model:visible="showBulkDeleteModal"
        modal
        header="Suppression en masse"
        :style="{ width: '95%', maxWidth: '500px' }"
        class="mx-auto"
        :dismissable-mask="false"
      >
        <div class="d-flex align-items-start mb-3">
          <div class="text-warning me-3 flex-shrink-0">
            <i class="pi pi-exclamation-triangle" style="font-size: 2rem"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="fw-semibold mb-2">
              Suppression de {{ selectedUsers.length }} utilisateur(s)
            </h6>
            <p class="text-muted mb-3">
              Les utilisateurs suivants seront définitivement supprimés :
            </p>
          </div>
        </div>

        <div class="bg-light p-3 rounded mb-3" style="max-height: 250px; overflow-y: auto">
          <div
            v-for="user in selectedUsers.slice(0, 10)"
            :key="user.id"
            class="d-flex align-items-center mb-3"
          >
            <div class="avatar-circle-sm bg-secondary text-white me-3 flex-shrink-0">
              {{ getInitials(user) }}
            </div>
            <div class="flex-grow-1 min-width-0">
              <div class="fw-semibold text-truncate">{{ user.email }}</div>
              <div v-if="user.nom || user.prenom" class="text-muted small">
                {{ user.prenom }} {{ user.nom }}
              </div>
            </div>
          </div>
          <div
            v-if="selectedUsers.length > 10"
            class="text-muted text-center small border-top pt-2"
          >
            ... et {{ selectedUsers.length - 10 }} autre(s)
          </div>
        </div>

        <div class="alert alert-danger py-2">
          <i class="pi pi-exclamation-triangle me-2"></i>
          <strong>Attention :</strong> Cette action est irréversible.
        </div>

        <template #footer>
          <div class="d-flex gap-2 justify-content-end">
            <Button
              label="Annuler"
              icon="pi pi-times"
              class="p-button-outlined p-button-secondary"
              @click="showBulkDeleteModal = false"
            />
            <Button
              label="Supprimer tout"
              icon="pi pi-trash"
              class="p-button-danger"
              @click="bulkDelete"
            />
          </div>
        </template>
      </Dialog>

      <!-- Toast pour les notifications -->
      <Toast position="top-center" />
    </div>
    <CreateUserModal v-model:visible="showCreateUserModal" @user-created="onUserCreated" />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { debounce } from 'lodash'
import { useToast } from 'primevue/usetoast'
import {
  fetchUsers as fetchAllUsers,
  deleteUser as deleteUserById,
  bulkDeleteUsers,
  getUserStats,
} from '@/api/users'
import { useRouter } from 'vue-router'

import CreateUserModal from '@/views/CreateUser.vue'

const router = useRouter()

// Composables
const toast = useToast()

// Responsive breakpoints (vous pouvez utiliser une librairie ou créer un composable)
const $screen = computed(() => {
  if (typeof window === 'undefined') return {}
  const width = window.innerWidth
  return {
    xs: width < 576,
    sm: width >= 576,
    md: width >= 768,
    lg: width >= 992,
    xl: width >= 1200,
    smAndUp: width >= 576,
    mdAndUp: width >= 768,
    lgAndUp: width >= 992,
  }
})

// État réactif
const users = ref([])
const selectedUsers = ref([])
const loading = ref(true)
const error = ref(null)
const stats = ref(null)
const showFilters = ref(false)

// Modales
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const userToDelete = ref(null)

// Filtres et pagination
const filters = ref({
  search: '',
  role: null,
  statut: null,
})

const pagination = ref({
  page: 1,
  limit: 25,
  total: 0,
  totalPages: 0,
})

const sorting = ref({
  field: 'id',
  order: 'asc',
})

// Template de pagination responsive
const paginatorTemplate = computed(() => {
  if ($screen.value.mdAndUp) {
    return 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport'
  } else {
    return 'PrevPageLink PageLinks NextPageLink'
  }
})

// Options pour les dropdowns
const roleOptions = [
  { label: 'Utilisateur', value: 'ROLE_USER' },
  { label: 'Modérateur', value: 'ROLE_MODERATOR' },
  { label: 'Administrateur', value: 'ROLE_ADMIN' },
  { label: 'Super Admin', value: 'ROLE_SUPER_ADMIN' },
]

const statutOptions = [
  { label: 'Actif', value: 'actif' },
  { label: 'Suspendu', value: 'suspendu' },
  { label: 'Supprimé', value: 'supprime' },
]

const limitOptions = ref([10, 25, 50, 100])

// Fonctions utilitaires pour l'affichage
const getInitials = (user) => {
  if (user.prenom && user.nom) {
    return (user.prenom.charAt(0) + user.nom.charAt(0)).toUpperCase()
  }
  if (user.pseudo) {
    return user.pseudo.substring(0, 2).toUpperCase()
  }
  return user.email.substring(0, 2).toUpperCase()
}

const getRoleBadgeClass = (role) => {
  const classes = {
    ROLE_SUPER_ADMIN: 'bg-danger text-white',
    ROLE_ADMIN: 'bg-warning text-dark',
    ROLE_MODERATOR: 'bg-info text-white',
    ROLE_USER: 'bg-secondary text-white',
  }
  return classes[role] || 'bg-light text-dark'
}

const getRoleLabel = (role) => {
  const labels = {
    ROLE_SUPER_ADMIN: 'Super',
    ROLE_ADMIN: 'Admin',
    ROLE_MODERATOR: 'Mod',
    ROLE_USER: 'User',
  }
  return labels[role] || role
}

const getStatutBadgeClass = (statut) => {
  const classes = {
    actif: 'bg-success text-white',
    suspendu: 'bg-warning text-dark',
    supprime: 'bg-danger text-white',
  }
  return classes[statut] || 'bg-secondary text-white'
}

const getStatutLabel = (statut) => {
  const labels = {
    actif: 'Actif',
    suspendu: 'Suspendu',
    supprime: 'Supprimé',
  }
  return labels[statut] || statut
}

const getStatutIcon = (statut) => {
  const icons = {
    actif: 'pi pi-check-circle',
    suspendu: 'pi pi-pause-circle',
    supprime: 'pi pi-times-circle',
  }
  return icons[statut] || 'pi pi-circle'
}

const getScoreProgressClass = (score) => {
  if (score >= 80) return 'bg-success'
  if (score >= 60) return 'bg-warning'
  if (score >= 40) return 'bg-info'
  return 'bg-danger'
}

const formatPhone = (phone) => {
  if (!phone) return ''
  // Format français : 06.12.34.56.78
  return phone.replace(/(\d{2})(?=\d)/g, '$1.')
}

const calculateAge = (birthDate) => {
  if (!birthDate) return ''
  const today = new Date()
  const birth = new Date(birthDate)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age
}

// Fonctions de données
const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.page,
      limit: pagination.value.limit,
      sortField: sorting.value.field,
      sortOrder: sorting.value.order,
      ...filters.value,
    }

    // Nettoyer les paramètres vides
    Object.keys(params).forEach((key) => {
      if (params[key] === null || params[key] === '') {
        delete params[key]
      }
    })

    const response = await fetchAllUsers(params)

    users.value = response.data || []
    pagination.value = {
      ...pagination.value,
      ...response.pagination,
    }
  } catch (err) {
    error.value = err?.response?.data?.message || 'Erreur lors du chargement des utilisateurs'
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
    stats.value = await getUserStats()
  } catch (err) {
    console.warn('[fetchStats] Erreur:', err)
  }
}

const resetFilters = () => {
  filters.value = {
    search: '',
    role: null,
    statut: null,
  }
  pagination.value.page = 1
  fetchUsers()
}

// Recherche avec débounce
// Fonction de recherche améliorée
const debouncedSearch = debounce(() => {
  // Réinitialiser la page lors d'une nouvelle recherche
  pagination.value.page = 1
  fetchUsers()
}, 300) // Délai réduit pour une meilleure réactivité

// Fonction d'actualisation avec feedback visuel
const refreshData = async () => {
  loading.value = true
  try {
    await Promise.all([fetchUsers(), fetchStats()])
  } catch (error) {
    console.error('[refreshData] Erreur:', error)
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

// Gestion des événements
const onPageChange = (event) => {
  pagination.value.page = event.page + 1
  pagination.value.limit = event.rows
  fetchUsers()
}

const onSort = (event) => {
  pagination.value.page = 1
  pagination.value.limit = event.rows
  sorting.value.field = event.sortField
  sorting.value.order = event.sortOrder === 1 ? 'asc' : 'desc'
  fetchUsers()
}

const onRowClick = (event) => {
  editUser(event.data)
}

const editUser = (user) => {
  router.push(`/admin/users/${user.id}/edit`)
}

const confirmDelete = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const deleteUser = async () => {
  if (!userToDelete.value) return

  try {
    await deleteUserById(userToDelete.value.id)
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: 'Utilisateur supprimé avec succès',
      life: 3000,
    })

    // Supprimer de la liste locale
    users.value = users.value.filter((u) => u.id !== userToDelete.value.id)
    selectedUsers.value = selectedUsers.value.filter((u) => u.id !== userToDelete.value.id)

    // Mettre à jour les stats
    await fetchStats()
  } catch (err) {
    console.error('[deleteUser] Erreur:', err)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: err?.response?.data?.message || 'Échec de la suppression',
      life: 5000,
    })
  } finally {
    showDeleteModal.value = false
    userToDelete.value = null
  }
}

const confirmBulkDelete = () => {
  if (selectedUsers.value.length === 0) return
  showBulkDeleteModal.value = true
}

const bulkDelete = async () => {
  if (selectedUsers.value.length === 0) return

  try {
    const ids = selectedUsers.value.map((user) => user.id)
    const result = await bulkDeleteUsers(ids)

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `${result.deletedCount} utilisateur(s) supprimé(s)`,
      life: 3000,
    })

    // Recharger les données
    await fetchUsers()
    await fetchStats()

    selectedUsers.value = []
  } catch (err) {
    console.error('[bulkDelete] Erreur:', err)
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: err?.response?.data?.message || 'Échec de la suppression en masse',
      life: 5000,
    })
  } finally {
    showBulkDeleteModal.value = false
  }
}

const showCreateUserModal = ref(false)

// Fonction pour ouvrir la modal
const createUser = () => {
  showCreateUserModal.value = true
}

// Fonction appelée après création
const onUserCreated = () => {
  fetchUsers() // Recharger la liste
  fetchStats() // Recharger les stats
}

// Cycle de vie
onMounted(async () => {
  await Promise.all([fetchUsers(), fetchStats()])
})
</script>

<style scoped>
/* Variables CSS personnalisées */
:root {
  --primary-color: #0d6efd;
  --success-color: #198754;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #0dcaf0;
  --dark-color: #212529;
  --light-color: #f8f9fa;
  --border-radius: 8px;
  --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  --transition: all 0.15s ease-in-out;
}

/* Conteneur principal */
.container-fluid {
  max-width: 1400px;
  background-color: white;
}

/* Navigation sticky pour la sélection */
.sticky-selection-bar {
  position: sticky;
  top: 0;
  z-index: 10;
  background-color: white;
}

/* Avatars */
.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  font-size: 0.875rem;
  font-weight: 600;
  flex-shrink: 0;
}

.avatar-circle-mobile {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-circle-sm {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Liste mobile */
.mobile-users-list {
  max-height: 70vh;
  overflow-y: auto;
  background-color: white;
}

.user-card {
  transition: var(--transition);
  cursor: pointer;
  background-color: white;
}

.user-card:hover {
  background-color: #f8f9fa;
}

/* Badges personnalisés */
.badge-sm {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

/* Transitions et animations */
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

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Progress bars personnalisées */
.progress {
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar {
  transition: width 0.3s ease;
}

.card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

/* Responsive utilities */
.min-width-0 {
  min-width: 0;
}

.min-width-250 {
  min-width: 250px;
}

/* Customisation PrimeVue */
:deep(.p-datatable) {
  border-radius: var(--border-radius);
  overflow: hidden;
  background-color: white;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f1f3f4;
  border-color: #dee2e6;
  font-weight: 600;
  color: #495057;
  padding: 1rem 0.75rem;
  border-bottom: 2px solid #e9ecef;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  transition: var(--transition);
  border-bottom: 1px solid #f1f3f4;
  background-color: white;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f8f9fa;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  background-color: inherit;
}

:deep(.p-datatable .p-selection-column .p-checkbox) {
  margin: 0;
}

:deep(.p-paginator) {
  background: white;
  border: none;
  padding: 0.5rem 0;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page) {
  margin: 0 2px;
  border-radius: var(--border-radius);
  min-width: 2.5rem;
}

:deep(.p-button) {
  border-radius: var(--border-radius);
  transition: var(--transition);
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}

:deep(.p-dialog) {
  border-radius: var(--border-radius);
  overflow: hidden;
  background-color: white;
}

:deep(.p-dialog .p-dialog-header) {
  border-bottom: 1px solid #e9ecef;
  padding: 1.5rem;
  background-color: white;
}

:deep(.p-dialog .p-dialog-content) {
  padding: 1.5rem;
  background-color: white;
}

:deep(.p-dialog .p-dialog-footer) {
  border-top: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
  background-color: #f8f9fa;
}

:deep(.p-toast) {
  z-index: 9999;
}

:deep(.p-inputtext) {
  border-radius: var(--border-radius);
  transition: var(--transition);
  background-color: white;
}

:deep(.p-inputtext:focus) {
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

:deep(.p-dropdown) {
  border-radius: var(--border-radius);
  background-color: white;
}

:deep(.p-dropdown-panel) {
  background-color: white;
}

:deep(.p-dropdown-item) {
  background-color: white;
}

:deep(.p-progressspinner) {
  width: 50px;
  height: 50px;
}

/* Media queries pour le responsive */
@media (max-width: 575.98px) {
  .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
    background-color: white;
  }

  .user-card {
    padding: 1rem !important;
    background-color: white;
  }
}

@media (min-width: 576px) and (max-width: 767.98px) {
  .stats-card {
    padding: 1rem !important;
    background-color: white;
  }
}

@media (min-width: 1200px) {
  .container-fluid {
    max-width: 1400px;
  }
}

/* Optimisations pour l'accessibilité */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* SUPPRIMÉ : Section mode sombre qui causait le problème */

/* Classes utilitaires supplémentaires */
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cursor-pointer {
  cursor: pointer;
}

.hover-shadow {
  transition: var(--transition);
}

.hover-shadow:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Améliorations pour les performances */
.user-card,
.stats-card,
.card {
  transform: translateZ(0);
  backface-visibility: hidden;
  perspective: 1000px;
}

/* Styles améliorés pour les contrôles de recherche */
.form-control:focus {
  border-color: #86b7fe;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  background-color: white;
}

/* Désactiver l'autocomplétion visuelle */
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 1000px white inset !important;
  -webkit-text-fill-color: #212529 !important;
}

.form-control {
  background-color: white;
}

/* Amélioration du bouton d'actualisation avec animation */
.pi-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Alignement des boutons */
.btn-group .btn {
  display: inline-flex;
  align-items: center;
}

.input-group .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
}

/* Animation pour les filtres */
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
  max-height: 200px;
  transform: translateY(0);
}

/* Amélioration de l'alerte de sélection */
.alert {
  border-radius: 8px;
  border: none;
  background-color: rgba(13, 202, 240, 0.1);
}

.alert-info {
  background-color: rgba(13, 202, 240, 0.1);
  border-left: 4px solid #0dcaf0;
}

/* Dropdown amélioré */
.dropdown-menu {
  border-radius: 8px;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  background-color: white;
}

.dropdown-item {
  transition: all 0.15s ease-in-out;
  background-color: white;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  transform: translateX(2px);
}

.dropdown-item.active {
  background-color: #0d6efd;
  color: white;
}

/* Responsive pour les contrôles */
@media (max-width: 768px) {
  .btn-group {
    width: 100%;
  }

  .btn-group .btn {
    flex: 1;
  }

  .input-group {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .col-lg-4.d-flex {
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn-group {
    display: flex;
    width: 100%;
  }
}

/* Amélioration des labels */
.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-label i {
  color: #6c757d;
}

/* États des boutons */
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
  color: white;
}

.tablet-actions-container {
  justify-content: flex-start;
}

/* Alignement pour tablettes */
@media (min-width: 768px) and (max-width: 991.98px) {
  .tablet-actions-container {
    justify-content: flex-end;
  }
}

/* Force le fond blanc sur tous les éléments */
.container-fluid,
.card,
.bg-light,
.user-card,
.mobile-users-list,
.form-control {
  background-color: white !important;
}

.filters-container {
  padding: 1rem 0 1rem 0;
  border-top: 1px solid #e9ecef;
  background-color: white;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-in-out;
  overflow: hidden;
}

.slide-down-enter-from {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 150px;
}

.user-meta-actions {
  display: flex;
  flex-direction: column;
}

.user-metadata {
  flex-grow: 1;
}

.user-actions {
  margin-top: 0.75rem;
}

/* Tablette : actions à droite des métadonnées */
@media (min-width: 768px) and (max-width: 991.98px) {
  .user-meta-actions {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .user-actions {
    margin-top: 0;
    margin-left: 1rem;
  }
}

/* Mobile : garder en colonne */
@media (max-width: 767.98px) {
  .user-meta-actions {
    flex-direction: column;
  }
}
</style>
