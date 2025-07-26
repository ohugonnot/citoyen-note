<template>
  <section class="section py-5">
    <div class="container">
      <h1 class="title mb-4">Liste des utilisateurs</h1>

      <DataTable
        :value="users"
        :loading="loading"
        paginator
        :rows="10"
        responsiveLayout="scroll"
        rowHover
        dataKey="id"
        class="p-datatable-sm shadow-sm"
      >
        <Column field="id" header="ID" style="width: 50px" />
        <Column field="email" header="Email" />
        <Column field="pseudo" header="Pseudo" />
        <Column header="Rôles">
          <template #body="slotProps">
            <Tag v-for="role in slotProps.data.roles" :key="role" severity="info" class="me-1">
              {{ role }}
            </Tag>
          </template>
        </Column>
        <Column header="Statut">
          <template #body="slotProps">
            <Tag :severity="slotProps.data.statut === 'ACTIF' ? 'success' : 'danger'">
              {{ slotProps.data.statut }}
            </Tag>
          </template>
        </Column>
        <Column header="Actions" style="width: 140px">
          <template #body="slotProps">
            <Button
              icon="pi pi-pencil"
              class="p-button-rounded p-button-sm p-button-primary me-2"
              @click="editUser(slotProps.data)"
            />
            <Button
              icon="pi pi-trash"
              class="p-button-rounded p-button-sm p-button-danger"
              @click="confirmDelete(slotProps.data)"
            />
          </template>
        </Column>
      </DataTable>

      <Dialog v-model:visible="showDeleteModal" modal header="Confirmation" :style="{ width: '350px' }">
        <p>Supprimer l’utilisateur <strong>{{ userToDelete?.email }}</strong> ?</p>
        <template #footer>
          <Button label="Annuler" icon="pi pi-times" class="p-button-text" @click="showDeleteModal = false" />
          <Button label="Supprimer" icon="pi pi-check" class="p-button-danger" @click="deleteUser" />
        </template>
      </Dialog>

      <Message v-if="error" severity="error" class="mt-4">{{ error }}</Message>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import router from '@/router'
import { useNotifications } from '@/composables/useNotifications'
import {
  fetchUsers as fetchAllUsers,
  deleteUser as deleteUserById,
} from '@/api/users'

const users = ref([])
const loading = ref(true)
const error = ref(null)
const showDeleteModal = ref(false)
const userToDelete = ref(null)

const { notify } = useNotifications()

const fetchUsers = async () => {
  loading.value = true
  error.value = null
  try {
    users.value = await fetchAllUsers()
  } catch (err) {
    console.error('[fetchUsers] Erreur:', err)
    error.value = err?.response?.data?.message || 'Erreur lors du chargement'
  } finally {
    loading.value = false
  }
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
    notify.success('Utilisateur supprimé')
    users.value = users.value.filter(u => u.id !== userToDelete.value.id)
  } catch (err) {
    console.error('[deleteUser] Erreur:', err)
    notify.error('Échec de la suppression')
  } finally {
    showDeleteModal.value = false
    userToDelete.value = null
  }
}

onMounted(fetchUsers)
</script>
