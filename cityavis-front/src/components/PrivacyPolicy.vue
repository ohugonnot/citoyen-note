<!-- components/PrivacyPolicy.vue -->
<template>
  <Dialog
    v-model:visible="privacyStore.showPolicyModal"
    modal
    :style="{ width: '90vw', maxWidth: '800px' }"
    :maximizable="true"
    class="privacy-policy-dialog"
    header="🔒 Politique de confidentialité"
  >
    <div class="privacy-content">
      <!-- Navigation par sections -->
      <div class="privacy-nav mb-4">
        <div class="d-flex flex-wrap gap-2">
          <Button
            v-for="section in privacyStore.sections"
            :key="section.id"
            :label="section.label"
            :icon="section.icon"
            :severity="privacyStore.currentSection === section.id ? 'primary' : 'secondary'"
            :outlined="privacyStore.currentSection !== section.id"
            size="small"
            @click="privacyStore.goToSection(section.id)"
          />
        </div>
      </div>

      <!-- Contenu des sections -->
      <div class="privacy-sections">
        <!-- Section Générale -->
        <div v-show="privacyStore.currentSection === 'general'" class="privacy-section">
          <h3><i class="bi bi-info-circle me-2"></i>Informations générales</h3>
          <Card>
            <template #content>
              <div class="mb-3">
                <strong>Responsable de traitement :</strong>
                <p class="mt-2">
                  [NOM DE LA COLLECTIVITÉ/ORGANISATION]<br />
                  [ADRESSE COMPLÈTE]<br />
                  Email : [EMAIL_CONTACT]<br />
                  Téléphone : [TÉLÉPHONE]
                </p>
              </div>

              <div class="mb-3">
                <strong>Délégué à la Protection des Données (DPO) :</strong>
                <p class="mt-2">
                  Email : <a href="mailto:dpo@[DOMAINE].fr">dpo@[DOMAINE].fr</a><br />
                  Ou via le formulaire de contact
                </p>
              </div>

              <Message severity="info">
                CitoyenNote respecte le Règlement Général sur la Protection des Données (RGPD) et
                s'engage à protéger vos données personnelles.
              </Message>
            </template>
          </Card>
        </div>

        <!-- Section Données collectées -->
        <div v-show="privacyStore.currentSection === 'data'" class="privacy-section">
          <h3><i class="bi bi-database me-2"></i>Données collectées</h3>

          <div class="row">
            <div class="col-md-6 mb-3">
              <Card>
                <template #header>
                  <div class="p-3 bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Données de compte</h5>
                  </div>
                </template>
                <template #content>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-2"></i>Nom d'utilisateur</li>
                    <li><i class="bi bi-check text-success me-2"></i>Adresse email</li>
                    <li><i class="bi bi-check text-success me-2"></i>Mot de passe (chiffré)</li>
                    <li><i class="bi bi-check text-success me-2"></i>Date de création</li>
                  </ul>
                </template>
              </Card>
            </div>

            <div class="col-md-6 mb-3">
              <Card>
                <template #header>
                  <div class="p-3 bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-chat-quote me-2"></i>Données d'avis</h5>
                  </div>
                </template>
                <template #content>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check text-success me-2"></i>Contenu des avis</li>
                    <li><i class="bi bi-check text-success me-2"></i>Notes attribuées</li>
                    <li><i class="bi bi-check text-success me-2"></i>Date de publication</li>
                    <li><i class="bi bi-check text-success me-2"></i>Service évalué</li>
                  </ul>
                </template>
              </Card>
            </div>

            <div class="col-12">
              <Card>
                <template #header>
                  <div class="p-3 bg-warning">
                    <h5 class="mb-0">
                      <i class="bi bi-shield-exclamation me-2"></i>Données techniques
                    </h5>
                  </div>
                </template>
                <template #content>
                  <ul class="list-unstyled mb-0">
                    <li>
                      <i class="bi bi-check text-success me-2"></i>Adresse IP (hachée pour
                      anonymisation)
                    </li>
                    <li>
                      <i class="bi bi-check text-success me-2"></i>Logs de connexion (sécurité)
                    </li>
                    <li>
                      <i class="bi bi-check text-success me-2"></i>Données de navigation (cookies
                      techniques)
                    </li>
                  </ul>
                </template>
              </Card>
            </div>
          </div>
        </div>

        <!-- Section Finalités -->
        <div v-show="privacyStore.currentSection === 'purposes'" class="privacy-section">
          <h3><i class="bi bi-target me-2"></i>Finalités du traitement</h3>

          <div class="mb-3">
            <Card>
              <template #content>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="text-center p-3 border rounded">
                      <i class="bi bi-person-check fs-1 text-primary d-block mb-2"></i>
                      <h5>Authentification</h5>
                      <p class="small text-muted">
                        Gestion des comptes utilisateurs et connexion sécurisée
                      </p>
                      <Badge value="Base légale : Contrat" severity="info" />
                    </div>
                  </div>

                  <div class="col-md-4 mb-3">
                    <div class="text-center p-3 border rounded">
                      <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                      <h5>Anti-abus</h5>
                      <p class="small text-muted">
                        Prévention du spam et des contenus inappropriés
                      </p>
                      <Badge value="Base légale : Intérêt légitime" severity="success" />
                    </div>
                  </div>

                  <div class="col-md-4 mb-3">
                    <div class="text-center p-3 border rounded">
                      <i class="bi bi-graph-up fs-1 text-warning d-block mb-2"></i>
                      <h5>Statistiques</h5>
                      <p class="small text-muted">Amélioration du service (données anonymisées)</p>
                      <Badge value="Base légale : Intérêt légitime" severity="warning" />
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Section Conservation -->
        <div v-show="privacyStore.currentSection === 'retention'" class="privacy-section">
          <h3><i class="bi bi-clock-history me-2"></i>Durée de conservation</h3>

          <Timeline :value="retentionData" align="alternate" class="w-100">
            <template #marker="slotProps">
              <span
                class="flex w-2rem h-2rem align-items-center justify-content-center text-white border-circle z-1 shadow-2"
                :style="{ backgroundColor: slotProps.item.color }"
              >
                <i :class="slotProps.item.icon"></i>
              </span>
            </template>
            <template #content="slotProps">
              <Card class="mt-3">
                <template #title>{{ slotProps.item.title }}</template>
                <template #content>
                  <p>{{ slotProps.item.description }}</p>
                  <Badge :value="slotProps.item.duration" :severity="slotProps.item.severity" />
                </template>
              </Card>
            </template>
          </Timeline>
        </div>

        <!-- Section Droits -->
        <div v-show="privacyStore.currentSection === 'rights'" class="privacy-section">
          <h3><i class="bi bi-shield-check me-2"></i>Vos droits RGPD</h3>

          <div class="row">
            <div v-for="right in userRights" :key="right.id" class="col-md-6 mb-3">
              <Card class="h-100">
                <template #header>
                  <div class="p-3" :class="right.bgClass">
                    <h6 class="mb-0 text-white">
                      <i :class="right.icon + ' me-2'"></i>{{ right.title }}
                    </h6>
                  </div>
                </template>
                <template #content>
                  <p class="small">{{ right.description }}</p>
                  <div class="mt-3">
                    <Button
                      :label="right.action"
                      :severity="right.severity"
                      size="small"
                      outlined
                      @click="contactForRight(right.id)"
                    />
                  </div>
                </template>
              </Card>
            </div>
          </div>

          <Message severity="info" class="mt-3">
            <strong>Comment exercer vos droits :</strong><br />
            Contactez notre DPO à <a href="mailto:dpo@[DOMAINE].fr">dpo@[DOMAINE].fr</a>
            en précisant votre demande et en joignant une copie de votre pièce d'identité.
          </Message>
        </div>

        <!-- Section Cookies -->
        <div v-show="privacyStore.currentSection === 'cookies'" class="privacy-section">
          <h3><i class="bi bi-gear me-2"></i>Utilisation des cookies</h3>

          <Card>
            <template #content>
              <h5>🍪 Cookies essentiels uniquement</h5>
              <p>
                CitoyenNote n'utilise que des cookies strictement nécessaires au fonctionnement :
              </p>

              <DataTable :value="cookieData" responsive-layout="scroll">
                <Column field="name" header="Cookie"></Column>
                <Column field="purpose" header="Finalité"></Column>
                <Column field="duration" header="Durée"></Column>
                <Column field="type" header="Type">
                  <template #body="slotProps">
                    <Badge
                      :value="slotProps.data.type"
                      :severity="slotProps.data.type === 'Essentiel' ? 'success' : 'info'"
                    />
                  </template>
                </Column>
              </DataTable>

              <Message severity="success" class="mt-3">
                <strong>Aucun cookie de tracking :</strong><br />
                CitoyenNote ne dépose aucun cookie analytique, publicitaire ou de réseaux sociaux
                dans cette version MVP.
              </Message>
            </template>
          </Card>
        </div>

        <!-- Section Contact -->
        <div v-show="privacyStore.currentSection === 'contact'" class="privacy-section">
          <h3><i class="bi bi-envelope me-2"></i>Contact & réclamations</h3>

          <div class="row">
            <div class="col-md-6">
              <Card>
                <template #header>
                  <div class="p-3 bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>DPO</h5>
                  </div>
                </template>
                <template #content>
                  <p><strong>Délégué à la Protection des Données</strong></p>
                  <p class="mb-2">
                    <i class="bi bi-envelope me-2"></i>
                    <a href="mailto:dpo@[DOMAINE].fr">dpo@[DOMAINE].fr</a>
                  </p>
                  <p class="small text-muted">Réponse sous 30 jours maximum</p>
                </template>
              </Card>
            </div>

            <div class="col-md-6">
              <Card>
                <template #header>
                  <div class="p-3 bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>CNIL</h5>
                  </div>
                </template>
                <template #content>
                  <p><strong>Droit de réclamation</strong></p>
                  <p class="mb-2">
                    <i class="bi bi-globe me-2"></i>
                    <a href="https://www.cnil.fr/fr/plaintes" target="_blank"
                      >www.cnil.fr/plaintes</a
                    >
                  </p>
                  <p class="small text-muted">En cas de réponse insatisfaisante de notre part</p>
                </template>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted mx-2">Dernière mise à jour : {{ lastUpdate }}</small>
        <Button label="Fermer" @click="privacyStore.closePolicy()" />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePrivacyStore } from '@/stores/privacyStore'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Badge from 'primevue/badge'
import Timeline from 'primevue/timeline'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const privacyStore = usePrivacyStore()

const lastUpdate = ref('15 janvier 2024')

const retentionData = ref([
  {
    title: 'Logs de connexion',
    description: 'Données techniques et adresses IP hachées pour la sécurité',
    duration: '12 mois',
    severity: 'warning',
    color: '#FF9800',
    icon: 'bi bi-clock',
  },
  {
    title: 'Comptes utilisateurs',
    description: 'Données de profil et préférences',
    duration: '3 ans ou suppression sur demande',
    severity: 'info',
    color: '#2196F3',
    icon: 'bi bi-person',
  },
  {
    title: 'Avis et évaluations',
    description: 'Contenus publiés sur les services publics',
    duration: '3 ans ou suppression sur demande',
    severity: 'success',
    color: '#4CAF50',
    icon: 'bi bi-chat-quote',
  },
  {
    title: 'Comptes inactifs',
    description: 'Suppression automatique des comptes non utilisés',
    duration: "2 ans d'inactivité",
    severity: 'secondary',
    color: '#9E9E9E',
    icon: 'bi bi-trash',
  },
])

const userRights = ref([
  {
    id: 'access',
    title: "Droit d'accès",
    description: 'Obtenir une copie de vos données personnelles',
    action: 'Demander mes données',
    icon: 'bi bi-download',
    severity: 'info',
    bgClass: 'bg-info',
  },
  {
    id: 'rectification',
    title: 'Droit de rectification',
    description: 'Corriger des données inexactes ou incomplètes',
    action: 'Modifier mes données',
    icon: 'bi bi-pencil-square',
    severity: 'primary',
    bgClass: 'bg-primary',
  },
  {
    id: 'deletion',
    title: "Droit à l'effacement",
    description: 'Supprimer vos données dans certaines conditions',
    action: 'Supprimer mon compte',
    icon: 'bi bi-trash',
    severity: 'danger',
    bgClass: 'bg-danger',
  },
  {
    id: 'portability',
    title: 'Droit à la portabilité',
    description: 'Récupérer vos données dans un format exploitable',
    action: 'Exporter mes données',
    icon: 'bi bi-arrow-right-circle',
    severity: 'success',
    bgClass: 'bg-success',
  },
  {
    id: 'opposition',
    title: "Droit d'opposition",
    description: "S'opposer au traitement de vos données",
    action: "M'opposer au traitement",
    icon: 'bi bi-x-circle',
    severity: 'warning',
    bgClass: 'bg-warning',
  },
  {
    id: 'limitation',
    title: 'Droit à la limitation',
    description: "Limiter temporairement l'usage de vos données",
    action: 'Limiter le traitement',
    icon: 'bi bi-pause-circle',
    severity: 'secondary',
    bgClass: 'bg-secondary',
  },
])

const cookieData = ref([
  {
    name: 'auth_token',
    purpose: 'Authentification utilisateur',
    duration: 'Session',
    type: 'Essentiel',
  },
  {
    name: 'csrf_token',
    purpose: 'Protection contre les attaques CSRF',
    duration: 'Session',
    type: 'Essentiel',
  },
  {
    name: 'session_id',
    purpose: 'Maintien de la session',
    duration: '24h',
    type: 'Essentiel',
  },
  {
    name: 'rate_limit',
    purpose: 'Limitation anti-abus',
    duration: '1h',
    type: 'Essentiel',
  },
])

const contactForRight = (rightId) => {
  const subject = encodeURIComponent(`Demande RGPD - ${rightId}`)
  const body = encodeURIComponent(
    `Bonjour,\n\nJe souhaite exercer mon droit ${rightId} concernant mes données personnelles sur CitoyenNote.\n\nMerci de me recontacter.\n\nCordialement`,
  )

  window.open(`mailto:dpo@[DOMAINE].fr?subject=${subject}&body=${body}`)
}
</script>

<style scoped>
.privacy-policy-dialog {
  font-size: 0.9rem;
}

.privacy-nav {
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 1rem;
}

.privacy-section {
  min-height: 400px;
}

.privacy-content {
  max-height: 70vh;
  overflow-y: auto;
}
</style>
