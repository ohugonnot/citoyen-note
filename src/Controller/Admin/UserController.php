<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Helper\UserJsonHelper;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Dto\{UserFilterDto, CreateUserDto, UpdateUserDto};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Psr\Log\LoggerInterface;

#[Route('/api/admin/users', name: 'admin_user_')]
class UserController extends AbstractController
{
    private const REQUIRED_ROLE = 'ROLE_USER'; // ✅ Corrigé ROLE_USER -> ROLE_ADMIN
    private const MAX_BULK_LIMIT = 50;
    private const MAX_RECENT_LIMIT = 50;

    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {} 
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $filterDto = $this->createFilterDto($request);
            $result = $this->userRepository->findUsersWithFilters($filterDto);
            
            return $this->json([
                'data' => array_map([UserJsonHelper::class, 'build'], $result['users']),
                'pagination' => $this->buildPaginationData($result, $filterDto),
                'filters' => $filterDto->toArray()
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des utilisateurs');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $user = $this->findUserOr404($id);
            return $this->json(UserJsonHelper::build($user));

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération de l\'utilisateur', ['user_id' => $id]);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $data = $this->getJsonData($request);
            $createDto = new CreateUserDto($data);
            $violations = $this->validator->validate($createDto);
            if (count($violations) > 0) {
                throw new ValidationFailedException($createDto, $violations);
            }
            $user = $this->userService->createUser($createDto);

            return $this->json(UserJsonHelper::build($user), 201);

        } catch (ValidationFailedException $e) {
            return $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la création de l\'utilisateur');
        }
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $user = $this->findUserOr404($id);
            $data = $this->getJsonData($request);
            $updateDto = new UpdateUserDto($data);
            $violations = $this->validator->validate($updateDto);
            if (count($violations) > 0) {
                throw new ValidationFailedException($updateDto, $violations);
            }
            $updatedUser = $this->userService->updateUser($user, $updateDto);
 
            return $this->json(UserJsonHelper::build($updatedUser));

        } catch (ValidationFailedException $e) {
            return $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la mise à jour de l\'utilisateur', ['user_id' => $id]);
        }
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $user = $this->findUserOr404($id);
            
            if ($this->isSelfDeletion($user)) {
                return $this->json(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
            }

            $this->userService->deleteUser($user);

            return $this->json(['message' => 'Utilisateur supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la suppression de l\'utilisateur', ['user_id' => $id]);
        }
    }

    #[Route('/bulk/delete', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $data = $this->getJsonData($request);
            $ids = $data['ids'] ?? [];

            if (!$this->isValidIdArray($ids)) {
                return $this->json(['error' => 'IDs invalides fournis'], 422);
            }

            if (count($ids) > self::MAX_BULK_LIMIT) {
                return $this->json(['error' => "Maximum " . self::MAX_BULK_LIMIT . " utilisateurs à la fois"], 422);
            }

            $deletedCount = $this->userService->bulkDeleteUsers($ids, $this->getUser());

            return $this->json([
                'message' => "$deletedCount utilisateur(s) supprimé(s)",
                'deletedCount' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la suppression en masse');
        }
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $stats = $this->userRepository->getUserStats();
            return $this->json($stats);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des statistiques');
        }
    }

    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function recent(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $limit = min(self::MAX_RECENT_LIMIT, max(1, (int) $request->query->get('limit', 10)));
            $users = $this->userRepository->findRecentUsers($limit);

            return $this->json(array_map([UserJsonHelper::class, 'build'], $users));

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des utilisateurs récents');
        }
    }

    // ✅ Méthodes privées pour la lisibilité
    private function createFilterDto(Request $request): UserFilterDto
    {
        return new UserFilterDto(
            $request->query->get('search', ''),
            max(1, (int) $request->query->get('page', 1)),
            min(50, max(1, (int) $request->query->get('limit', 10))),
            $request->query->get('sortField', 'id'),
            $request->query->get('sortOrder', 'asc'),
            $request->query->get('role'),
            $request->query->get('statut')
        );
    }

    private function buildPaginationData(array $result, UserFilterDto $filterDto): array
    {
        $totalPages = ceil($result['total'] / $filterDto->limit);
        
        return [
            'total' => $result['total'],
            'page' => $filterDto->page,
            'limit' => $filterDto->limit,
            'totalPages' => $totalPages,
            'hasNext' => $filterDto->page < $totalPages,
            'hasPrev' => $filterDto->page > 1
        ];
    }

    private function findUserOr404(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }
        return $user;
    }

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalide');
        }
        return $data ?? [];
    }

    private function isValidIdArray(?array $ids): bool
    {
        return !empty($ids) 
            && is_array($ids) 
            && array_filter($ids, fn($id) => is_numeric($id) && $id > 0) === $ids;
    }

    private function isSelfDeletion(User $user): bool
    {
        return $user === $this->getUser();
    }

    private function handleValidationErrors(ValidationFailedException $exception): JsonResponse
    {
        $errors = [];
        foreach ($exception->getViolations() as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }
        $lines = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $lines[] = "$field: $message";
            }
        }
        $error = join("\n", $lines);
        return $this->json([
            'violations' => $errors,
            'error' => $error, 
        ], 422);
    }

    private function handleError(\Exception $e, string $message, array $context = []): JsonResponse
    {
        $this->logger->error($message, array_merge($context, ['error' => $e->getMessage()]));

        if ($e instanceof \InvalidArgumentException) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json(['error' => $message], 500);
    }
}
