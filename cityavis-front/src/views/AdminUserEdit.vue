<template>
  <div class="container py-4">
    <h2 class="mb-4">Modifier l’utilisateur #{{ form.id }}</h2>

    <div v-if="loading" class="text-center py-5">
      <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
    </div>

    <div v-else>
      <form @submit.prevent="submitForm">
        <div class="row mb-3">
          <div class="col-md-6">
            <label>Email</label>
            <input v-model="form.email" type="email" class="form-control" disabled />
          </div>
          <div class="col-md-6">
            <label>Pseudo</label>
            <input v-model="form.pseudo" type="text" class="form-control" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Nom</label>
            <input v-model="form.nom" type="text" class="form-control" />
          </div>
          <div class="col-md-6">
            <label>Prénom</label>
            <input v-model="form.prenom" type="text" class="form-control" />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Téléphone</label>
            <input v-model="form.telephone" type="text" class="form-control" />
          </div>
          <div class="col-md-6">
            <label>Date de naissance</label>
            <DatePicker v-model="form.dateNaissance" class="w-100" dateFormat="yy-mm-dd" showIcon />
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label>Statut</label>
            <Select
              v-model="form.statut"
              :options="status"
              optionLabel="label"
              optionValue="value"
              placeholder="Choisir"
              class="w-100"
            />
          </div>

          <div class="col-md-6">
            <label>Rôles</label>
            <MultiSelect
              v-model="form.roles"
              :options="rolesDisponibles"
              optionLabel="label"
              optionValue="value"
              display="chip"
              placeholder="Choisir les rôles"
              class="w-100"
            />
          </div>
        </div>

        <div class="mb-3 form-check">
          <Checkbox v-model="form.isVerified" binary inputId="verified" />
          <label for="verified" class="form-check-label ms-2">Email vérifié</label>
        </div>

        <div class="mb-4">
          <label>Score de fiabilité</label>
          <InputNumber v-model="form.scoreFiabilite" class="form-control" />
        </div>

        <div class="d-flex gap-2">
            <Button type="submit" label="Enregistrer" icon="pi pi-check" class="p-button-success" :loading="saving" />
            <RouterLink to="/admin/users" class="btn btn-link">Retour</RouterLink>
          </div>
        </form>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { fetchUserById, updateUser } from '@/api/users'
  import { useNotifications } from '@/composables/useNotifications'

  const route = useRoute()
  const router = useRouter()
  const { notify } = useNotifications()

  const loading = ref(true)
  const saving = ref(false)
  const form = ref({
    id: null,
    email: '',
    pseudo: '',
  nom: '',
  prenom: '',
  telephone: '',
  dateNaissance: '',
  statut: null,
  roles: [],
  isVerified: false,
  scoreFiabilite: 0,
})

  const status = [
    { label: 'Actif', value: 'actif' },
    { label: 'Suspendu', value: 'suspendu' },
    { label: 'Supprimé', value: 'supprime' }
  ]


const rolesDisponibles = [
  { label: 'Utilisateur', value: 'ROLE_USER' },
  { label: 'Admin', value: 'ROLE_ADMIN' },
  { label: 'Super Admin', value: 'ROLE_SUPER_ADMIN' },
]

onMounted(async () => {
  try {
    const user = await fetchUserById(route.params.id)

    form.value = {
      id: user.id,
      email: user.email,
      pseudo: user.pseudo || '',
      nom: user.nom || '',
      prenom: user.prenom || '',
      telephone: user.telephone || '',
      dateNaissance: user.dateNaissance || '',
      statut: user.statut || null,
      roles: user.roles || [],
      isVerified: user.isVerified || false,
      scoreFiabilite: user.scoreFiabilite || 0,
    }
  } catch (error) {
    notify.error('Impossible de charger l’utilisateur')
  } finally {
    loading.value = false
  }
})

const submitForm = async () => {
  saving.value = true
  try {
    await updateUser(form.value.id, form.value)
    notify.success('Utilisateur mis à jour')
    router.push('/admin/users')
  } catch (error) {
    notify.error('Erreur lors de la mise à jour')
    console.error('[submitForm] error:', error)
  } finally {
    saving.value = false
  }
}
</script>
