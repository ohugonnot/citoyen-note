<?php
// src/Service/PublicAuthService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PublicAuthService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository
    ) {}

    /**
     * Récupère l'utilisateur connecté depuis le JWT si présent
     * Retourne null si pas de token ou token invalide
     */
    public function getCurrentUser(Request $request): ?User
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        try {
            $token = substr($authHeader, 7);
            $payload = $this->jwtManager->parse($token);
            $username = $payload['username'] ?? $payload['email'] ?? null;
            
            if (!$username) {
                return null;
            }

            return $this->userRepository->findOneBy(['email' => $username]);
            
        } catch (\Exception $e) {
            // Token invalide ou expiré
            return null;
        }
    }

    /**
     * Vérifie si un utilisateur est connecté
     */
    public function isAuthenticated(Request $request): bool
    {
        return $this->getCurrentUser($request) !== null;
    }

    /**
     * Récupère les informations utilisateur pour l'API
     */
    public function getUserInfo(Request $request): ?array
    {
        $user = $this->getCurrentUser($request);
        
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'nomComplet' => $user->getPrenom() . ' ' . $user->getNom()
        ];
    }
}
