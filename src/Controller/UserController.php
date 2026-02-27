<?php

namespace App\Controller;

use App\Dto\UpdateUserDto;
use App\Entity\User;
use App\Helper\UserJsonHelper;
use App\Service\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json(UserJsonHelper::build($user));
    }

    #[Route('/api/users/{id}', name: 'api_update_user', methods: ['PUT'])]
    public function updateUser(
        int $id,
        Request $request,
        #[CurrentUser] ?User $currentUser
    ): JsonResponse {
        if (!$currentUser) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        if ($currentUser->getId() !== $id) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'JSON invalide'], 400);
            }

            $updateDto = new UpdateUserDto($data ?? []);
            $violations = $this->validator->validate($updateDto);
            if (count($violations) > 0) {
                throw new ValidationFailedException($updateDto, $violations);
            }

            $updatedUser = $this->userManager->updateUser($currentUser, $updateDto);

            return $this->json(UserJsonHelper::build($updatedUser));

        } catch (ValidationFailedException $e) {
            $errors = [];
            foreach ($e->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            $lines = [];
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $lines[] = "$field: $message";
                }
            }

            return $this->json([
                'violations' => $errors,
                'error' => implode("\n", $lines),
            ], 422);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du profil', [
                'user_id' => $currentUser->getId(),
                'error' => $e->getMessage(),
            ]);
            return $this->json(['error' => 'Erreur lors de la mise à jour du profil'], 500);
        }
    }
}
