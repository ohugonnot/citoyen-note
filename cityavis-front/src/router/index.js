import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/ServicesPublics.vue'),
    meta: {
      title: 'Accueil - CitoyenNote',
      description: 'Évaluez et améliorez vos services publics locaux',
    },
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: {
      requiresGuest: true,
      title: 'Connexion - CitoyenNote',
      description: 'Connectez-vous à votre espace citoyen',
    },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/RegisterView.vue'),
    meta: {
      requiresGuest: true,
      title: 'Inscription - CitoyenNote',
      description: 'Créez votre compte citoyen',
    },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/MeView.vue'),
    meta: {
      requiresAuth: true,
      title: 'Mon Profil - CitoyenNote',
      description: 'Gérez votre profil citoyen',
    },
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('@/views/UsersList.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Gestion Utilisateurs - CitoyenNote',
    },
  },
  {
    path: '/admin/users/:id/edit',
    name: 'AdminUserEdit',
    component: () => import('@/views/EditUser.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Modifier Utilisateur - CitoyenNote',
      description: 'Modifier les informations d’un utilisateur',
    },
  },
  {
    path: '/admin/services-publiques',
    name: 'AdminServicesPubliques',
    component: () => import('@/views/ServicesPublicsList.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Gestion des Services Publiques - CitoyenNote',
    },
  },
  {
    path: '/admin/services-publiques/create',
    name: 'NewService',
    component: () => import('@/views/CreateServicePublic.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Ajouter un Service Public - CitoyenNote',
    },
  },
  // Dans ton fichier de routes
  {
    path: '/admin/services-publiques/:id/edit',
    name: 'EditService',
    component: () => import('@/views/EditServicePublic.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Modifier un Service Public - CitoyenNote',
    },
  },
  {
    path: '/admin/evaluations',
    name: 'AdminEvaluations',
    component: () => import('@/views/EvaluationsList.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: false,
      title: 'Gestion des évaluations - CitoyenNote',
    },
  },
  {
    path: '/services',
    name: 'ServicesPublics',
    component: () => import('@/views/ServicesPublics.vue'),
    meta: {
      title: 'Services Publics - CitoyenNote',
      description: 'Découvrez et évaluez tous les services publics de votre région',
    },
  },
  // {
  //   path: '/services/:slug',
  //   name: 'ServicePublicDetail',
  //   component: () => import('@/views/ServicePublicDetail.vue'),
  //   meta: {
  //     title: 'Service Public - CitoyenNote',
  //     description: 'Consultez les évaluations et informations détaillées de ce service public',
  //   },
  // },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/login',
  },
  /*
  {
    path: '/mes-evaluations',
    name: 'MyEvaluations',
    component: () => import('@/views/MyEvaluations.vue'),
    meta: {
      requiresAuth: true,
      title: 'Mes Évaluations - CitoyenNote',
      description: 'Consultez vos évaluations de services publics'
    }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/Settings.vue'),
    meta: {
      requiresAuth: true,
      title: 'Paramètres - CitoyenNote',
      description: 'Configurez votre compte'
    }
  },
  {
    path: '/services',
    name: 'Services',
    component: () => import('@/views/Services.vue'),
    meta: {
      title: 'Services Publics - CitoyenNote',
      description: 'Découvrez tous les services publics de votre région'
    }
  },
  {
    path: '/services/:id',
    name: 'ServiceDetail',
    component: () => import('@/views/ServiceDetail.vue'),
    meta: {
      title: 'Service Public - CitoyenNote'
    }
  },
  {
    path: '/admin',
    redirect: '/admin/dashboard'
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('@/views/admin/Dashboard.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      title: 'Administration - CitoyenNote',
      description: 'Tableau de bord administrateur'
    }
  },

  {
    path: '/admin/services',
    name: 'AdminServices',
    component: () => import('@/views/admin/Services.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      title: 'Gestion Services - CitoyenNote'
    }
  },
  {
    path: '/admin/analytics',
    name: 'AdminAnalytics',
    component: () => import('@/views/admin/Analytics.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
      title: 'Statistiques - CitoyenNote'
    }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/ForgotPassword.vue'),
    meta: {
      requiresGuest: true,
      title: 'Mot de passe oublié - CitoyenNote'
    }
  },
  {
    path: '/terms',
    name: 'Terms',
    component: () => import('@/views/Terms.vue'),
    meta: {
      title: 'Conditions d\'utilisation - CitoyenNote'
    }
  },
  {
    path: '/privacy',
    name: 'Privacy',
    component: () => import('@/views/Privacy.vue'),
    meta: {
      title: 'Politique de confidentialité - CitoyenNote'
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue'),
    meta: {
      title: 'Page non trouvée - CitoyenNote'
    }
  }
  */
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    return { top: 0, behavior: 'smooth' }
  },
})

router.afterEach((to) => {
  document.title = to.meta.title || 'CitoyenNote - République Française'

  const description = to.meta.description || 'Évaluez et améliorez vos services publics locaux'
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.name = 'description'
    document.head.appendChild(metaDescription)
  }
  metaDescription.content = description
})

export default router
