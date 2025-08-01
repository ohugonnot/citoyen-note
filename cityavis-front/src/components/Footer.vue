<!-- components/Footer.vue -->
<template>
  <footer class="footer-minimal">
    <div class="container-fluid px-4">
      <div class="row align-items-center">
        <!-- Logo et nom -->
        <div class="col-md-4">
          <div class="d-flex align-items-center">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>
            <small class="text-muted">Plateforme d'évaluation des services publics</small>
          </div>
        </div>

        <!-- Liens centrés -->
        <div class="col-md-4 text-center">
          <div class="footer-links-inline">
            <button class="btn-link-minimal me-3" @click="privacyStore.openPolicy('general')">
              Confidentialité
            </button>
            <button class="btn-link-minimal me-3" @click="privacyStore.openPolicy('cookies')">
              Cookies
            </button>
            <button class="btn-link-minimal" @click="openLegalNotices">Mentions</button>
          </div>
        </div>

        <!-- Badge RGPD compact -->
        <div class="col-md-4 text-end">
          <span class="badge-rgpd">
            <i class="bi bi-shield-check text-success me-1"></i>
            <small>Conforme RGPD</small>
          </span>
          <br />
          <small class="text-muted">Vos données sont protégées</small>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { usePrivacyStore } from '@/stores/privacyStore'

const privacyStore = usePrivacyStore()

const openLegalNotices = () => {
  // Logique mentions légales
}
</script>

<style scoped>
.footer-minimal {
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
  padding: 1rem 0;
  margin-top: auto;
  font-size: 0.875rem;
}

.btn-link-minimal {
  background: none;
  border: none;
  color: #6c757d;
  text-decoration: none;
  font-size: 0.875rem;
  padding: 0;
  transition: color 0.2s;
}

.btn-link-minimal:hover {
  color: #000091;
  text-decoration: underline;
}

.badge-rgpd {
  display: inline-flex;
  align-items: center;
}

/* Version mobile encore plus compacte */
@media (max-width: 768px) {
  .footer-minimal {
    padding: 0.75rem 0;
    text-align: center;
  }

  .footer-minimal .row > div {
    margin-bottom: 0.5rem;
  }

  .footer-minimal .row > div:last-child {
    margin-bottom: 0;
  }

  .footer-links-inline {
    margin: 0.5rem 0;
  }

  .btn-link-minimal {
    font-size: 0.8rem;
    margin: 0 0.5rem !important;
  }
}
</style>
