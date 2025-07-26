<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Helper\UserJsonHelper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

const ROLE_ADMIN = "ROLE_USER";

#[Route('/api/admin/users', name: 'admin_user_')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        // Paramètres de recherche et pagination
        $search = $request->query->get('search', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $sortField = $request->query->get('sortField', 'id');
        $sortOrder = $request->query->get('sortOrder', 'asc');

        // Filtres
        $role = $request->query->get('role');
        $statut = $request->query->get('statut');

        // Récupération des données avec pagination
        $result = $userRepository->findUsersWithFilters([
            'search' => $search,
            'role' => $role,
            'statut' => $statut,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'page' => $page,
            'limit' => $limit
        ]);

        $users = array_map(fn(User $user) => UserJsonHelper::build($user), $result['users']);

        return $this->json([
            'data' => $users,
            'pagination' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($result['total'] / $limit),
                'hasNext' => $page < ceil($result['total'] / $limit),
                'hasPrev' => $page > 1
            ],
            'filters' => [
                'search' => $search,
                'role' => $role,
                'statut' => $statut,
                'sortField' => $sortField,
                'sortOrder' => $sortOrder
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, User $user, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides'], 400);
        }

        $this->updateUserFromData($user, $data, $em);

        $em->flush();

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        // Vérifier que l'utilisateur ne se supprime pas lui-même
        if ($user === $this->getUser()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous supprimer vous-même'], 400);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    #[Route('/bulk/delete', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            return $this->json(['error' => 'IDs invalides'], 400);
        }

        $users = $em->getRepository(User::class)->findBy(['id' => $ids]);
        $currentUser = $this->getUser();
        $deletedCount = 0;

        foreach ($users as $user) {
            if ($user !== $currentUser) {
                $em->remove($user);
                $deletedCount++;
            }
        }

        $em->flush();

        return $this->json([
            'message' => "$deletedCount utilisateur(s) supprimé(s)",
            'deletedCount' => $deletedCount
        ]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $stats = $userRepository->getUserStats();

        return $this->json($stats);
    }

    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function recent(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $users = $userRepository->findRecentUsers($limit);

        $data = array_map(fn(User $user) => UserJsonHelper::build($user), $users);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer = null
    ): JsonResponse {
        $this->denyAccessUnlessGranted(ROLE_ADMIN);

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides'], 400);
        }

        // Validation des champs requis
        $requiredFields = ['email'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Le champ '$field' est requis"], 400);
            }
        }

        // Vérifier l'unicité de l'email
        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
        }

        // Vérifier l'unicité du pseudo si fourni
        if (!empty($data['pseudo'])) {
            if ($em->getRepository(User::class)->findOneBy(['pseudo' => $data['pseudo']])) {
                return $this->json(['error' => 'Ce pseudo est déjà utilisé'], 400);
            }
        }

        try {
            $user = new User();
            $user->setEmail($data['email']);

            // Gestion du mot de passe
            if (!empty($data['password'])) {
                // Utiliser le mot de passe fourni par le frontend
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
                $tempPassword = null; // Pas de mot de passe temporaire
            } else {
                // Générer un mot de passe temporaire si aucun n'est fourni
                $tempPassword = bin2hex(random_bytes(8));
                $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
                $user->setPassword($hashedPassword);
            }

            // Mettre à jour les autres données
            $this->updateUserFromData($user, $data, $em);

            $em->persist($user);
            $em->flush();

            // Envoyer un email de bienvenue si demandé
            if (!empty($data['sendWelcomeEmail']) && $data['sendWelcomeEmail'] && $mailer) {
                $this->sendWelcomeEmail($user, $tempPassword, $mailer);
            }

            $response = [
                'user' => UserJsonHelper::build($user),
                'message' => 'Utilisateur créé avec succès'
            ];

            // Inclure le mot de passe temporaire seulement s'il a été généré
            if ($tempPassword) {
                $response['tempPassword'] = $tempPassword;
            }

            return $this->json($response, 201);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function updateUserFromData(User $user, array $data, EntityManagerInterface $em): void
    {
        $allowedFields = [
            'nom', 'prenom', 'telephone', 'dateNaissance',
            'pseudo', 'isVerified', 'scoreFiabilite'
        ];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            // Traitement spécial pour la date de naissance
            if ($field === 'dateNaissance' && is_string($value)) {
                if (empty($value)) {
                    $value = null;
                } else {
                    try {
                        $value = new \DateTime($value);
                    } catch (\Exception $e) {
                        throw new \InvalidArgumentException('Format de date invalide pour dateNaissance');
                    }
                }
            }

            // Validation du score de fiabilité
            if ($field === 'scoreFiabilite' && $value !== null && ($value < 0 || $value > 100)) {
                throw new \InvalidArgumentException('Le score de fiabilité doit être entre 0 et 100');
            }

            // Traitement spécial pour isVerified
            if ($field === 'isVerified') {
                $value = (bool) $value;
            }

            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            }
        }

        // Gestion des rôles
        if (isset($data['roles']) && is_array($data['roles'])) {
            $validRoles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_SUPER_ADMIN'];
            $roles = array_intersect($data['roles'], $validRoles);
            if (!empty($roles)) {
                // S'assurer qu'au moins ROLE_USER est présent
                if (!in_array('ROLE_USER', $roles)) {
                    $roles[] = 'ROLE_USER';
                }
                $user->setRoles($roles);
            }
        } else {
            // Rôle par défaut
            $user->setRoles(['ROLE_USER']);
        }

        // Gestion du statut
        if (isset($data['statut']) && method_exists($user, 'setStatut')) {
            $enum = \App\Enum\Statut::tryFrom($data['statut']);
            if ($enum) {
                $user->setStatut($enum);
            }
        }

        // Mise à jour de l'email si fourni (avec vérification d'unicité)
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser !== $user) {
                throw new \InvalidArgumentException('Cet email est déjà utilisé');
            }
            $user->setEmail($data['email']);
        }

        // Gestion de la vérification d'email
        if (isset($data['isVerified']) && $data['isVerified']) {
            $user->setIsVerified(true);
            if (method_exists($user, 'setVerifiedAt')) {
                $user->setVerifiedAt(new \DateTime());
            }
        }
    }

    private function sendWelcomeEmail(User $user, ?string $tempPassword, MailerInterface $mailer): void
    {
        // Implémentation de l'envoi d'email de bienvenue
        // Exemple basique - à adapter selon votre système d'email

        /*
        use Symfony\Component\Mime\Email;

        $email = (new Email())
            ->from('noreply@votre-site.com')
            ->to($user->getEmail())
            ->subject('Bienvenue sur notre plateforme')
            ->html($this->renderView('emails/welcome.html.twig', [
                'user' => $user,
                'tempPassword' => $tempPassword
            ]));

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création
            error_log('Erreur envoi email: ' . $e->getMessage());
        }
        */
    }
}