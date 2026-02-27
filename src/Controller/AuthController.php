<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatutUser;
use App\Validator\StrongPassword;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger,
        RateLimiterFactory $registerLimiter,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Rate limiting
        $limiter = $registerLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
        }

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis'], 400);
        }

        // Validation de l'email
        $emailViolations = $validator->validate($data['email'], [new Assert\Email()]);
        if (count($emailViolations) > 0) {
            return $this->json(['error' => 'Format d\'email invalide'], 400);
        }

        // Validation du mot de passe via le validator dédié
        $password = $data['password'];
        $passwordViolations = $validator->validate($password, [new StrongPassword()]);
        if (count($passwordViolations) > 0) {
            return $this->json(['error' => $passwordViolations[0]->getMessage()], 400);
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
                $passwordHasher->hashPassword($user, $password)
            );
            $user->setIsVerified(false);
            $user->setRoles(['ROLE_USER']);
            $user->setStatut(StatutUser::ACTIF);
            $user->setScoreFiabilite(0);
            $user->setAccepteNewsletters($data['accepte_newsletters'] ?? false);

            $em->persist($user);
            $em->flush();

            $logger->info('User registered successfully', ['email' => $data['email']]);

            return $this->json(['message' => 'Utilisateur enregistré avec succès'], 201);
        } catch (\Exception $e) {
            $logger->error('Registration failed', [
                'email' => $data['email'], 
                'error' => $e->getMessage()
            ]);
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
        LoggerInterface $logger,
        RateLimiterFactory $loginLimiter
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return $this->json(['error' => 'email et password requis'], 400);
        }

        // Rate limiting
        $limiter = $loginLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], 429);
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

        // Vérifier le statut du compte
        if ($user->getStatut() !== StatutUser::ACTIF) {
            $logger->warning('Login attempt with inactive account', ['email' => $data['email'], 'statut' => $user->getStatut()->value]);
            return $this->json(['error' => 'Compte désactivé ou suspendu'], 403);
        }

        if (!$user->isVerified()) {
            $logger->warning('Login attempt with unverified account', ['email' => $data['email']]);
            return $this->json(['error' => 'Compte non vérifié. Veuillez vérifier votre email.'], 403);
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

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if ($refreshToken) {
            $token = $em->getRepository(\App\Entity\RefreshToken::class)
                ->findOneBy(['refreshToken' => $refreshToken]);
            if ($token) {
                $em->remove($token);
                $em->flush();
            }
        }

        return $this->json(['message' => 'Déconnexion réussie']);
    }
}