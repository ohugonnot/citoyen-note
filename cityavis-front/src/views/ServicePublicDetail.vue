<template>
  <div class="service-detail-page">
    <!-- Breadcrumb -->
    <Breadcrumb :model="breadcrumbItems" class="mb-4" />

    <!-- Composant principal -->
    <ServiceEvaluations
      :service-slug="$route.params.slug"
      @add-review="handleAddReview"
    />

    <!-- Modal pour ajouter un avis (si connecté) -->
    <Dialog
      v-model:visible="showAddReviewModal"
      modal
      header="Laisser un avis"
      :style="{width: '50rem'}"
      :breakpoints="{'1199px': '75vw', '575px': '90vw'}"
    >
      <div class="add-review-form">
        <p class="mb-4">Partagez votre expérience avec ce service public</p>

        <!-- Formulaire d'évaluation -->
        <div class="field">
          <label for="note">Note globale *</label>
          <Rating v-model="reviewForm.note" :cancel="false" />
        </div>

        <div class="field">
          <label for="commentaire">Votre commentaire</label>
          <Textarea
            id="commentaire"
            v-model="reviewForm.commentaire"
            rows="4"
            placeholder="Décrivez votre expérience avec ce service..."
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="qualite">Qualité du service</label>
          <Rating v-model="reviewForm.qualiteService" :cancel="true" />
        </div>

        <div class="field">
          <label for="facilite">Facilité d'utilisation</label>
          <Rating v-model="reviewForm.faciliteUtilisation" :cancel="true" />
        </div>

        <div class="field-checkbox">
          <Checkbox
            id="recommande"
            v-model="reviewForm.recommande"
            :binary="true"
          />
          <label for="recommande">Je recommande ce service</label>
        </div>
      </div>

      <template #footer>
        <Button
          label="Annuler"
          icon="pi pi-times"
          @click="closeAddReviewModal"
          class="p-button-text"
        />
        <Button
          label="Publier mon avis"
          icon="pi pi-check"
          @click="submitReview"
          :loading="submittingReview"
          :disabled="!reviewForm.note"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useServicePublicStore } from '@/stores/servicePublicStore'
import { useAuthStore } from '@/stores/authStore'
import { useToast } from 'primevue/usetoast'
import { storeToRefs } from 'pinia'
import ServiceEvaluations from '@/components/ServiceEvaluations.vue'

// Composables
const route = useRoute()
const router = useRouter()
const toast = useToast()

// Stores
const serviceStore = useServicePublicStore()
const authStore = useAuthStore()
const { currentService } = storeToRefs(serviceStore)
const { isAuthenticated } = storeToRefs(authStore)

// State
const showAddReviewModal = ref(false)
const submittingReview = ref(false)
const reviewForm = ref({
  note: null,
  commentaire: '',
  qualiteService: null,
  faciliteUtilisation: null,
  recommande: false
})

// Computed
const breadcrumbItems = computed(() => {
  const items = [
    { label: 'Accueil', route: '/' },
    { label: 'Services publics', route: '/services' }
  ]

  if (currentService.value) {
    items.push({
      label: currentService.value.nom,
      route: `/services/${route.params.slug}`
    })
  }

  return items
})

// Methods
const handleAddReview = () => {
  if (!isAuthenticated.value) {
    toast.add({
      severity: 'info',
      summary: 'Connexion requise',
      detail: 'Vous devez être connecté pour laisser un avis',
      life: 5000
    })

    // Redirection vers login avec retour sur cette page
    router.push({
      name: 'Login',
      query: { redirect: route.fullPath }
    })
    return
  }

  showAddReviewModal.value = true
}

const closeAddReviewModal = () => {
  showAddReviewModal.value = false
  resetForm()
}

const resetForm = () => {
  reviewForm.value = {
    note: null,
    commentaire: '',
    qualiteService: null,
    faciliteUtilisation: null,
    recommande: false
  }
}

const submitReview = async () => {
  try {
    submittingReview.value = true

    // Appel API pour créer l'évaluation
    // await evaluationStore.createEvaluation({
    //   servicePublicId: currentService.value.id,
    //   ...reviewForm.value
    // })

    console.log('Évaluation à soumettre:', {
      servicePublicId: currentService.value.id,
      ...reviewForm.value
    })

    toast.add({
      severity: 'success',
      summary: 'Avis publié',
      detail: 'Merci pour votre évaluation !',
      life: 3000
    })

    closeAddReviewModal()

    // Recharger les évaluations
    await serviceStore.fetchServiceBySlug(route.params.slug)

  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de publier votre avis. Veuillez réessayer.',
      life: 5000
    })
  } finally {
    submittingReview.value = false
  }
}

// Surveiller les changements de slug dans l'URL
watch(
  () => route.params.slug,
  (newSlug) => {
    if (newSlug) {
      serviceStore.fetchServiceBySlug(newSlug)
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.service-detail-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.add-review-form .field {
  margin-bottom: 1.5rem;
}

.add-review-form .field-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.add-review-form label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.add-review-form .field-checkbox label {
  display: inline;
  margin-bottom: 0;
}

@media (max-width: 768px) {
  .service-detail-page {
    padding: 0.5rem;
  }
}
</style>
