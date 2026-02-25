<?php

namespace App\Service;

use App\Entity\User;
use App\Dto\{CreateUserDto, UpdateUserDto};
use App\Enum\StatutUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Psr\Log\LoggerInterface;

class UserManager
{
    private const VALID_ROLES = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_SUPER_ADMIN'];
    private const DEFAULT_ROLE = 'ROLE_USER';
    private const TEMP_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository, // ✅ Injection directe
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
        private readonly ?EmailService $emailService = null
    ) {}

    public function createUser(CreateUserDto $dto): User
    {
        $this->validateUniqueConstraints($dto->email, $dto->pseudo);

        $user = new User();
        $plainPassword = $dto->password ?? $this->generateTemporaryPassword();
        
        $this->configureUserBasics($user, $dto->email, $plainPassword);
        $this->applyCreateUserData($user, $dto);

        $this->em->persist($user);
        $this->em->flush();

        $this->handleWelcomeEmail($user, $dto, $plainPassword);

        $this->logger->info('Utilisateur créé avec succès', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        return $user;
    }

    public function updateUser(User $user, UpdateUserDto $dto): User
    {
        $this->validateUpdateConstraints($user, $dto);
        $this->applyUpdateUserData($user, $dto);

        $this->em->flush();

        $this->logger->info('Utilisateur mis à jour', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $userId = $user->getId();
        $userEmail = $user->getEmail();

        $this->em->remove($user);
        $this->em->flush();

        $this->logger->info('Utilisateur supprimé', [
            'user_id' => $userId,
            'email' => $userEmail
        ]);
    }

    public function bulkDeleteUsers(array $userIds, User $currentUser): int
    {
        if (empty($userIds)) {
            throw new \InvalidArgumentException('Aucun ID fourni');
        }

        $users = $this->userRepository->findByIds($userIds);
        $currentUserId = $currentUser->getId();
        $deletedCount = 0;

        foreach ($users as $user) {
            if ($user->getId() === $currentUserId) {
                continue; // Skip self-deletion
            }
            
            $this->em->remove($user);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            $this->em->flush();
            
            $this->logger->info('Suppression en masse effectuée', [
                'deleted_count' => $deletedCount,
                'requested_ids' => $userIds,
                'executor_id' => $currentUserId
            ]);
        }

        return $deletedCount;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $this->em->flush();

        $this->logger->info('Mot de passe modifié', ['user_id' => $user->getId()]);
    }

    // ✅ Méthodes privées optimisées
    private function validateUniqueConstraints(string $email, ?string $pseudo = null): void
    {
        $violations = new ConstraintViolationList();

        // Validation email
        if ($this->userRepository->findOneBy(['email' => $email])) {
            $violations->add(new ConstraintViolation(
                'Cet email est déjà utilisé',
                null,
                [],
                $email,
                'email',
                $email
            ));
        }

        // Validation pseudo
        if ($pseudo && $this->userRepository->findOneBy(['pseudo' => $pseudo])) {
            $violations->add(new ConstraintViolation(
                'Ce pseudo est déjà utilisé',
                null,
                [],
                $pseudo,
                'pseudo',
                $pseudo
            ));
        }

        if (count($violations) > 0) {
            throw new ValidationFailedException(null, $violations);
        }
    }

    private function validateUpdateConstraints(User $user, UpdateUserDto $dto): void
    {
        $violations = new ConstraintViolationList();

        // Validation email si changé
        if ($dto->email && $dto->email !== $user->getEmail()) {
            if ($this->userRepository->findOneBy(['email' => $dto->email])) {
                $violations->add(new ConstraintViolation(
                    'Cet email est déjà utilisé',
                    null,
                    [],
                    $dto,
                    'email',
                    $dto->email
                ));
            }
        }
        
        // Validation pseudo si changé
        if ($dto->pseudo && $dto->pseudo !== $user->getPseudo()) {
            if ($this->userRepository->findOneBy(['pseudo' => $dto->pseudo])) {
                $violations->add(new ConstraintViolation(
                    'Ce pseudo est déjà utilisé',
                    null,
                    [],
                    $dto,
                    'pseudo',
                    $dto->pseudo
                ));
            }
        }

        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
    }

    private function configureUserBasics(User $user, string $email, string $plainPassword): void
    {
        $user->setEmail($email);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        $user->setStatut(StatutUser::ACTIF);
    }

    private function applyCreateUserData(User $user, CreateUserDto $dto): void
    {
        if ($dto->nom !== null) $user->setNom($dto->nom);
        if ($dto->prenom !== null) $user->setPrenom($dto->prenom);
        if ($dto->pseudo !== null) $user->setPseudo($dto->pseudo);
        if ($dto->telephone !== null) $user->setTelephone($dto->telephone);
        if ($dto->codePostal !== null) $user->setCodePostal($dto->codePostal);
        if ($dto->ville !== null) $user->setVille($dto->ville);

        if ($dto->dateNaissance) {
            $user->setDateNaissance($this->parseDate($dto->dateNaissance));
        }

        $user->setRoles($this->validateAndNormalizeRoles($dto->roles));

        if ($dto->scoreFiabilite !== null) {
            $user->setScoreFiabilite($dto->scoreFiabilite);
        }

        $user->setIsVerified($dto->isVerified);
        $user->setAccepteNewsletters($dto->accepteNewsletters);

        if ($dto->isVerified) {
            $user->setVerifiedAt(new \DateTime());
        }
    }

    private function applyUpdateUserData(User $user, UpdateUserDto $dto): void
    {
        if ($dto->email !== null) $user->setEmail($dto->email);
        if ($dto->nom !== null) $user->setNom($dto->nom);
        if ($dto->prenom !== null) $user->setPrenom($dto->prenom);
        if ($dto->pseudo !== null) $user->setPseudo($dto->pseudo);
        if ($dto->telephone !== null) $user->setTelephone($dto->telephone);
        if ($dto->codePostal !== null) $user->setCodePostal($dto->codePostal);
        if ($dto->ville !== null) $user->setVille($dto->ville);

        if ($dto->dateNaissance !== null) {
            $user->setDateNaissance(
                $dto->dateNaissance ? $this->parseDate($dto->dateNaissance) : null
            );
        }

        if ($dto->roles !== null) {
            $user->setRoles($this->validateAndNormalizeRoles($dto->roles));
        }

        if ($dto->scoreFiabilite !== null) {
            $user->setScoreFiabilite($dto->scoreFiabilite);
        }

        if ($dto->isVerified !== null) {
            $user->setIsVerified($dto->isVerified);
            if ($dto->isVerified && !$user->getVerifiedAt()) {
                $user->setVerifiedAt(new \DateTime());
            }
        }

        if ($dto->accepteNewsletters !== null) {
            $user->setAccepteNewsletters($dto->accepteNewsletters);
        }

        if ($dto->statut) {
            $statut = StatutUser::tryFrom($dto->statut);
            if ($statut) {
                $user->setStatut($statut);
            }
        }
    }

    private function handleWelcomeEmail(User $user, CreateUserDto $dto, string $plainPassword): void
    {
        if ($dto->sendWelcomeEmail && $this->emailService && !$dto->password) {
            try {
                $this->emailService->sendWelcomeEmail($user, $plainPassword);
                
                $this->logger->info('Email de bienvenue envoyé', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email bienvenue', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
                // On ne fait pas échouer la création pour un email
            }
        }
    }

    private function parseDate(string $date): \DateTime
    {
        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Format de date invalide pour dateNaissance: ' . $date);
        }
    }

    private function validateAndNormalizeRoles(array $roles): array
    {
        $validRoles = array_intersect($roles, self::VALID_ROLES);
        
        // Toujours inclure ROLE_USER
        if (!in_array(self::DEFAULT_ROLE, $validRoles)) {
            $validRoles[] = self::DEFAULT_ROLE;
        }

        return array_values(array_unique($validRoles));
    }

    private function generateTemporaryPassword(): string
    {
        return bin2hex(random_bytes(self::TEMP_PASSWORD_LENGTH));
    }
}
