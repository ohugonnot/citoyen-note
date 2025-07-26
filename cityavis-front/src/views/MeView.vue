<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-xl-6">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-gradient-primary text-white text-center py-4">
            <div class="profile-avatar mb-3">
              <div class="avatar-circle d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-person-fill" style="font-size: 3rem;"></i>
              </div>
            </div>
            <h2 class="mb-1">{{ user.pseudo || user.prenom || 'Utilisateur' }}</h2>
            <p class="mb-2 text-white-50">{{ user.nom }} {{ user.prenom }}</p>
            <span class="badge bg-light text-primary px-3 py-2 rounded-pill">
              <i class="bi bi-circle-fill me-1" :class="statusClass" style="font-size: 0.6rem;"></i>
              {{ statusText }}
            </span>
          </div>

          <div class="card-body p-4">
            <!-- Informations personnelles -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary mb-3">
                  <i class="bi bi-person-circle me-2"></i>
                  Informations personnelles
                </h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="info-item mb-3">
                      <label class="form-label text-muted small fw-bold text-uppercase">Prénom</label>
                      <div class="d-flex align-items-center w-100">
                        <i class="bi bi-person me-2 text-primary"></i>
                        <span v-if="!isEditing" class="fs-6">{{ user.prenom || 'Non renseigné' }}</span>
                        <input v-else v-model="user.prenom" type="text" class="form-control" />
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-item mb-3">
                      <label class="form-label text-muted small fw-bold text-uppercase">Nom</label>
                      <div class="d-flex align-items-center w-100">
                        <i class="bi bi-person me-2 text-primary"></i>
                        <span v-if="!isEditing" class="fs-6">{{ user.nom || 'Non renseigné' }}</span>
                        <input v-else v-model="user.nom" type="text" class="form-control" />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="info-item mb-3">
                  <label class="form-label text-muted small fw-bold text-uppercase">Pseudo</label>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-at me-2 text-primary"></i>
                    <span v-if="!isEditing" class="fs-6">{{ user.pseudo || 'Non défini' }}</span>
                    <input v-else v-model="user.pseudo" type="text" class="form-control" />
                  </div>
                </div>

                <div class="info-item mb-3">
                  <label class="form-label text-muted small fw-bold text-uppercase">Date de naissance</label>
                  <div class="d-flex align-items-center w-100">
                    <i class="bi bi-calendar-event me-2 text-primary"></i>
                    <template v-if="!isEditing">
                      <span class="fs-6">{{ formatDateNaissance(user.dateNaissance) }}</span>
                      <span v-if="getAge(user.dateNaissance)" class="badge bg-info ms-2">
                        {{ getAge(user.dateNaissance) }} ans
                      </span>
                    </template>
                    <input v-else v-model="user.dateNaissance" type="date" class="form-control" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary mb-3">
                  <i class="bi bi-telephone me-2"></i>
                  Contact
                </h5>

                <div class="info-item mb-3">
                  <label class="form-label text-muted small fw-bold text-uppercase">Email</label>
                  <div class="d-flex align-items-center">
                    <i class="bi bi-envelope me-2 text-primary"></i>
                    <span class="fs-6">{{ user.email }}</span>
                    <span v-if="!user.isVerified" class="badge bg-warning ms-2">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      Non vérifié
                    </span>
                    <span v-else class="badge bg-success ms-2">
                      <i class="bi bi-check-circle me-1"></i>
                      Vérifié
                    </span>
                  </div>
                </div>

                <div class="info-item mb-3">
                  <label class="form-label text-muted small fw-bold text-uppercase">Téléphone</label>
                  <div class="d-flex align-items-center w-100">
                    <i class="bi bi-phone me-2 text-primary"></i>
                    <span v-if="!isEditing" class="fs-6">{{ user.telephone || 'Non renseigné' }}</span>
                    <input v-else v-model="user.telephone" type="text" class="form-control" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Informations système -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="text-primary mb-3">
                  <i class="bi bi-gear me-2"></i>
                  Informations système
                </h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="info-item mb-3">
                      <label class="form-label text-muted small fw-bold text-uppercase">Identifiant</label>
                      <div class="d-flex align-items-center">
                        <i class="bi bi-hash text-primary me-2"></i>
                        <span class="fs-6">{{ user.id }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-item mb-3">
                      <label class="form-label text-muted small fw-bold text-uppercase">Rôles</label>
                      <div class="d-flex align-items-center flex-wrap">
                        <i class="bi bi-person-badge text-primary me-2"></i>
                        <span v-for="role in user.roles" :key="role"
                              class="badge bg-primary me-1 mb-1">
                          {{ formatRole(role) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Score -->
            <div class="mb-4">
              <h5 class="text-primary mb-3">
                <i class="bi bi-star me-2"></i>
                Score de fiabilité
              </h5>
              <div class="progress mb-2" style="height: 10px;">
                <div class="progress-bar bg-gradient-primary"
                     :style="{ width: fiabilityPercentage + '%' }"
                     role="progressbar">
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">{{ user.scoreFiabilite }} / 100</span>
                <span class="badge" :class="fiabilityBadgeClass">
                  {{ fiabilityLevel }}
                </span>
              </div>
            </div>

            <!-- Bouton Modifier / Enregistrer -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
              <button
                v-if="!isEditing"
                class="btn btn-outline-primary me-md-2"
                type="button"
                @click="startEditing"
              >
                <i class="bi bi-pencil me-2"></i>
                Modifier le profil
              </button>
              <button
                v-else
                class="btn btn-primary"
                type="button"
                @click="saveProfile"
              >
                <i class="bi bi-check-lg me-2"></i>
                Enregistrer
              </button>
            </div>
          </div>

          <!-- Pied de carte -->
          <div class="card-footer bg-light text-center text-muted small">
            <i class="bi bi-clock me-1"></i>
            Dernière mise à jour : {{ user?.updatedAt || new Date().toLocaleDateString('fr-FR') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotifications } from '@/composables/useNotifications' // ou le bon chemin

const auth = useAuthStore()
const { notify } = useNotifications()
const user = ref({ ...auth.user })
const isEditing = ref(false)

const startEditing = () => {
  isEditing.value = true
}

const saveProfile = async () => {
  try {
    if (!user.value.nom || !user.value.prenom) {
      notify.warning('Nom et prénom sont requis.', 'Champs manquants')
      return
    }

    const success = await auth.saveUserProfile({
      nom: user.value.nom,
      prenom: user.value.prenom,
      pseudo: user.value.pseudo,
      telephone: user.value.telephone,
      dateNaissance: user.value.dateNaissance,
    })

    if (success) {
      isEditing.value = false
      notify.success('Profil mis à jour avec succès.')
    } else {
      notify.error('Une erreur est survenue lors de la mise à jour.')
    }
  } catch (error) {
    console.error('[saveProfile] Exception :', error)
    notify.error('Erreur inattendue lors de la sauvegarde du profil.')
  }
}

const statusClass = computed(() => {
  return user.value.statut === 'actif' ? 'text-success' : 'text-danger'
})

const statusText = computed(() => {
  return user.value.statut === 'actif' ? 'Actif' : 'Inactif'
})

const fiabilityPercentage = computed(() => {
  return Math.max(0, Math.min(100, user.value.scoreFiabilite))
})

const fiabilityLevel = computed(() => {
  const score = user.value.scoreFiabilite
  if (score >= 80) return 'Excellent'
  if (score >= 60) return 'Bon'
  if (score >= 40) return 'Moyen'
  if (score >= 20) return 'Faible'
  return 'Très faible'
})

const fiabilityBadgeClass = computed(() => {
  const score = user.value.scoreFiabilite
  if (score >= 80) return 'bg-success'
  if (score >= 60) return 'bg-info'
  if (score >= 40) return 'bg-warning'
  return 'bg-danger'
})

const formatRole = (role) => {
  return role.replace('ROLE_', '').toLowerCase().replace('_', ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

const formatDateNaissance = (dateNaissance) => {
  if (!dateNaissance) return 'Non renseignée'
  try {
    const date = new Date(dateNaissance)
    if (isNaN(date.getTime())) return 'Date invalide'
    return date.toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch (error) {
    console.error('[formatDateNaissance] Erreur', error)
    return 'Date invalide'
  }
}

const getAge = (dateNaissance) => {
  if (!dateNaissance) return null
  try {
    const birth = new Date(dateNaissance)
    if (isNaN(birth.getTime())) return null
    const today = new Date()
    let age = today.getFullYear() - birth.getFullYear()
    const monthDiff = today.getMonth() - birth.getMonth()
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--
    }
    return age >= 0 ? age : null
  } catch (error) {
    console.error('[getAge] Erreur', error)
    return null
  }
}
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  position: relative;
}

.bg-gradient-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.95) 0%, rgba(124, 58, 237, 0.95) 100%);
  backdrop-filter: blur(20px);
}

.bg-gradient-primary > * {
  position: relative;
  z-index: 1;
}

.avatar-circle {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 50%;
  backdrop-filter: blur(10px);
  border: 3px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.card {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: #ffffff;
}

.card-header {
  border: none;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.info-item {
  padding: 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(79, 70, 229, 0.1);
}

.progress {
  border-radius: 10px;
  background-color: #e2e8f0;
  height: 12px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-bar {
  border-radius: 10px;
  background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 100%);
  box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}

.btn {
  border-radius: 12px;
  font-weight: 600;
  padding: 0.75rem 2rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.875rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.btn-primary {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  border: none;
  color: white;
}

.btn-outline-primary {
  border: 2px solid #4f46e5;
  color: #4f46e5;
  background: transparent;
}

.btn-outline-primary:hover {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  border-color: transparent;
  color: white;
}

.badge {
  font-size: 0.75em;
  font-weight: 600;
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.shadow-lg {
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
}

.container {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  min-height: 100vh;
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.card-footer {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border-top: 1px solid rgba(79, 70, 229, 0.1);
  backdrop-filter: blur(10px);
}

@media (max-width: 768px) {
  .container {
    padding: 1rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .avatar-circle {
    width: 60px;
    height: 60px;
  }

  .avatar-circle i {
    font-size: 2rem !important;
  }
}
</style>
