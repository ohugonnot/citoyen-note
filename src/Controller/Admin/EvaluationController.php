<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\AdminControllerTrait;
use App\Dto\CreateEvaluationDto;
use App\Dto\EvaluationFilterDto;
use App\Dto\UpdateEvaluationDto;
use App\Helper\EvaluationJsonHelper;
use App\Repository\EvaluationRepository;
use App\Service\EvaluationManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security; 
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/evaluations', name: 'admin_evaluations_')]
class EvaluationController extends AbstractController
{
    use AdminControllerTrait;
    private const REQUIRED_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly EvaluationRepository $repository,
        private readonly EvaluationManager $manager,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $filterDto = $this::createEvaluationFilterDtoFromRequest($request);
            $result = $this->repository->findPaginated($filterDto);
            return $this->json([
                'data' => array_map([EvaluationJsonHelper::class, 'build'], $result['items']),
                'pagination' => $this->buildPaginationData($result, $filterDto->page, $filterDto->limit),
                'filters' => $filterDto->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des évaluations');
        }
    }

    public static function createEvaluationFilterDtoFromRequest(Request $request): EvaluationFilterDto
    {
        return new EvaluationFilterDto(
            search: $request->query->get('search', ''),
            page: max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 25))),
            sortField: $request->query->get('sortField', 'createdAt'),
            sortOrder: $request->query->get('sortOrder', 'desc'),
            statut: $request->query->get('statut'),
            note_min: $request->query->has('note_min') ? $request->query->getInt('note_min') : null,
            note_max: $request->query->has('note_max') ? $request->query->getInt('note_max') : null,
            service_id: $request->query->get('service_id'),
            user_id: $request->query->get('user_id'),
            est_verifie: $request->query->has('est_verifie') ? filter_var($request->query->get('est_verifie'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            est_anonyme: $request->query->has('est_anonyme') ? filter_var($request->query->get('est_anonyme'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
        );
    }

    #[Route('/{uuid}', name: 'show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $evaluation = $this->repository->findOneBy(['uuid' => Uuid::fromString($uuid)]);
        if (!$evaluation) {
            return $this->json(['error' => 'Évaluation introuvable'], 404);
        }

        return $this->json(EvaluationJsonHelper::build($evaluation));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $dto = new CreateEvaluationDto($this->getJsonData($request));
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $evaluation = $this->manager->create($dto);
        return $this->json(EvaluationJsonHelper::build($evaluation), 201);
    }

    #[Route('/{uuid}', name: 'update', methods: ['PUT'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $evaluation = $this->repository->findOneBy(['uuid' => Uuid::fromString($uuid)]);
        if (!$evaluation) {
            return $this->json(['error' => 'Évaluation introuvable'], 404);
        }

        $dto = new UpdateEvaluationDto($this->getJsonData($request));
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $evaluation = $this->manager->update($evaluation, $dto);
        return $this->json(EvaluationJsonHelper::build($evaluation));
    }

    #[Route('/bulk', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $data = $this->getJsonData($request);
        $uuids = $data['ids'] ?? [];

        if (!is_array($uuids) || empty($uuids)) {
            return $this->json(['error' => 'Liste d\'UUIDs vide.'], 400);
        }

        $this->manager->bulkDelete($uuids);
        return $this->json(null, 204);
    }

    #[Route('/bulk-validate', name: 'bulk_validate', methods: ['POST'])]
    public function bulkValidate(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $data = $this->getJsonData($request);
        $uuids = $data['ids'] ?? [];
        $estVerifie = $data['est_verifie'] ?? true; // Par défaut, on valide

        if (!is_array($uuids) || empty($uuids)) {
            return $this->json(['error' => 'Liste d\'UUIDs vide.'], 400);
        }

        try {
            $updatedCount = $this->manager->bulkValidate($uuids, $estVerifie);
            
            return $this->json([
                'success' => true,
                'message' => sprintf(
                    '%d évaluation(s) %s avec succès',
                    $updatedCount,
                    $estVerifie ? 'validée(s)' : 'invalidée(s)'
                ),
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la validation en masse');
        }
    }

    #[Route('/{uuid}/toggle-validation', name: 'toggle_validation', methods: ['PATCH'])]
    public function toggleValidation(string $uuid, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $evaluation = $this->repository->findOneBy(['uuid' => Uuid::fromString($uuid)]);
        if (!$evaluation) {
            return $this->json(['error' => 'Évaluation introuvable'], 404);
        }

        $data = $this->getJsonData($request);
        $newStatus = $data['est_verifie'] ?? !$evaluation->isEstVerifie();

        try {
            $evaluation = $this->manager->toggleValidation($evaluation, $newStatus);
            
            return $this->json([
                'success' => true,
                'evaluation' => EvaluationJsonHelper::build($evaluation),
                'message' => sprintf(
                    'Évaluation %s avec succès',
                    $newStatus ? 'validée' : 'invalidée'
                )
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors du changement de statut');
        }
    }

    #[Route('/{uuid}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        $evaluation = $this->repository->findOneBy(['uuid' => Uuid::fromString($uuid)]);
        if (!$evaluation) {
            return $this->json(['error' => 'Évaluation introuvable'], 404);
        }

        $this->manager->delete($evaluation);
        return $this->json(null, 204);
    }

}