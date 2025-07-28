<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Statut;
use App\Enum\StatutUser;
use App\Helper\UserJsonHelper;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'email et password requis'], 400);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], 400);
        }

        try {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPseudo($data['pseudo'] ?? null);
            $user->setPassword(
                $passwordHasher->hashPassword($user, $data['password'])
            );
            $user->setIsVerified(false);
            $user->setRoles(['ROLE_USER']);
            $user->setStatut(StatutUser::ACTIF);
            $user->setScoreFiabilite(0);
            $user->setAccepteNewsletters($data['accepte_newsletters'] ?? false);

            $em->persist($user);
            $em->flush();

            $logger->info('User registered successfully', ['email' => $data['email']]);

            return $this->json(['message' => 'Utilisateur enregistré']);
        } catch (\Exception $e) {
            $logger->error('Registration failed', ['email' => $data['email'], 'error' => $e->getMessage()]);
            return $this->json(['error' => 'Erreur lors de l\'enregistrement'], 500);
        }
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'email et password requis'], 400);
        }

        // Trouver l'utilisateur
        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        if (!$user) {
            $logger->warning('Login attempt with non-existent email', ['email' => $data['email']]);
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        // Vérifier le mot de passe
        if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
            $logger->warning('Login attempt with invalid password', ['email' => $data['email']]);
            return $this->json(['error' => 'Identifiants invalides'], 401);
        }

        try {
            // Générer le token JWT
            $token = $jwtManager->create($user);
            
            // Créer un refresh token
            $refreshToken = new \App\Entity\RefreshToken();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid((new \DateTime())->add(new \DateInterval('P7D')));
            
            $em->persist($refreshToken);
            $em->flush();
            
            $logger->info('User logged in successfully', ['email' => $user->getEmail()]);

            return $this->json([
                'token' => $token,
                'refresh_token' => $refreshToken->getRefreshToken(),
                'user' => UserJsonHelper::build($user)
            ]);
        } catch (\Exception $e) {
            $logger->error('JWT token generation failed', ['email' => $user->getEmail(), 'error' => $e->getMessage()]);
            return $this->json(['error' => 'Erreur lors de la génération du token'], 500);
        }
    }

    #[Route('/api/users/{id}', name: 'api_update_user', methods: ['PUT'])]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $currentUser
    ): JsonResponse {
        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // On ne permet que la modification de son propre profil
        if ($currentUser->getId() !== $id) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // On autorise uniquement certaines propriétés
        $allowedFields = ['nom', 'prenom', 'telephone', 'dateNaissance','pseudo', 'codePostal', 'ville'];
        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            // Convertir la date de naissance si nécessaire
            if ($field === 'dateNaissance' && is_string($value)) {
                try {
                    $value = new \DateTime($value);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide pour dateNaissance'], 400);
                }
            }

            $setter = 'set' . ucfirst($field);
            if (method_exists($currentUser, $setter)) {
                $currentUser->$setter($value);
            }
        }

        $em->persist($currentUser);
        $em->flush();

        return $this->json(UserJsonHelper::build($currentUser));
    }


    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST','GET'])]
    public function logout(): JsonResponse
    {
        return $this->json(['message' => 'Déconnexion réussie']);
    }
}