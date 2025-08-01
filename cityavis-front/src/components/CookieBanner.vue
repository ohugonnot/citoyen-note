<!-- components/CookieBanner.vue -->
<template>
  <Transition name="cookie-banner" appear>
    <div
      v-if="cookieStore.isInfoDisplayed && !cookieStore.hasBeenAcknowledged"
      class="cookie-banner"
    >
      <Card class="cookie-banner-card shadow-3">
        <template #content>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <!-- Icône et message -->
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <i class="bi bi-shield-check text-primary fs-4"></i>
              <div>
                <h6 class="mb-1">🍪 Utilisation des cookies</h6>
                <p class="mb-0 text-muted small">
                  CitoyenNote utilise des cookies essentiels pour la connexion et la sécurité
                  anti-abus. Aucun cookie de tracking n'est utilisé.
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <Button label="En savoir plus" text size="small" @click="showCookieDetails" />
              <Button label="J'ai compris" size="small" @click="acknowledgeCookies" />
              <Button icon="bi bi-x" text size="small" severity="secondary" @click="hideBanner" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </Transition>

  <!-- Modal détails cookies -->
  <Dialog
    v-model:visible="showDetails"
    header="🍪 Informations sur les cookies"
    modal
    :style="{ width: '90vw', maxWidth: '600px' }"
  >
    <div class="cookie-details">
      <div class="mb-4">
        <h6>Cookies essentiels utilisés :</h6>
        <ul class="list-unstyled mt-2">
          <li class="mb-2">
            <i class="bi bi-shield-fill text-success me-2"></i>
            <strong>Authentification :</strong> pour maintenir votre connexion
          </li>
          <li class="mb-2">
            <i class="bi bi-shield-fill text-warning me-2"></i>
            <strong>Anti-abus :</strong> pour protéger contre les attaques
          </li>
          <li class="mb-2">
            <i class="bi bi-gear-fill text-info me-2"></i>
            <strong>Préférences :</strong> pour retenir vos choix d'interface
          </li>
        </ul>
      </div>

      <div class="mb-4">
        <h6>Ce que nous ne faisons PAS :</h6>
        <ul class="list-unstyled mt-2 text-muted">
          <li class="mb-1">❌ Cookies de tracking publicitaire</li>
          <li class="mb-1">❌ Analytics tiers</li>
          <li class="mb-1">❌ Partage de données personnelles</li>
        </ul>
      </div>
    </div>

    <template #footer>
      <Button label="Fermer" autofocus @click="showDetails = false" />
      <Button label="J'ai compris" severity="success" @click="acknowledgeCookiesAndClose" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import { useCookieStore } from '@/stores/cookieStore'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

const cookieStore = useCookieStore()
const showDetails = ref(false)

const acknowledgeCookies = () => {
  cookieStore.acknowledgeCookies()
}

const hideBanner = () => {
  cookieStore.hideCookieInfo()
  // On garde en mémoire qu'il faut re-montrer plus tard
  setTimeout(() => {
    if (!cookieStore.hasBeenAcknowledged) {
      cookieStore.showCookieInfo()
    }
  }, 30000) // Re-montrer dans 30 secondes si pas validé
}

const showCookieDetails = () => {
  showDetails.value = true
}

const acknowledgeCookiesAndClose = () => {
  acknowledgeCookies()
  showDetails.value = false
}
</script>

<style scoped>
.cookie-banner {
  position: fixed;
  bottom: 20px;
  left: 20px;
  right: 20px;
  z-index: 1050;
  max-width: 800px;
  margin: 0 auto;
}

.cookie-banner-card {
  border-left: 4px solid var(--primary-color);
}

@media (max-width: 768px) {
  .cookie-banner {
    bottom: 10px;
    left: 10px;
    right: 10px;
  }

  .cookie-banner .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
  }

  .cookie-banner .flex-shrink-0 {
    width: 100%;
    justify-content: flex-end;
    margin-top: 10px;
  }
}

/* Animations */
.cookie-banner-enter-active,
.cookie-banner-leave-active {
  transition: all 0.3s ease;
}

.cookie-banner-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.cookie-banner-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.cookie-details ul li {
  padding: 0.25rem 0;
}
</style>
