<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\AdminControllerTrait;
use App\Entity\ServicePublic;
use App\Helper\ServicePublicJsonHelper;
use App\Repository\ServicePublicRepository;
use App\Dto\{ServicePublicFilterDto, CreateServicePublicDto, UpdateServicePublicDto};
use App\Service\ServicePublicManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/admin/services-publics', name: 'admin_service_public_')]
class ServicePublicController extends AbstractController
{
    use AdminControllerTrait;
    private const REQUIRED_ROLE = 'ROLE_ADMIN';
    private const MAX_BULK_LIMIT = 50;
    private const MAX_RECENT_LIMIT = 50;

    public function __construct(
        private readonly ServicePublicManager $servicePublicService,
        private readonly ServicePublicRepository $servicePublicRepository,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $filterDto = $this::createFilterDtoFromRequest($request);
            $result = $this->servicePublicRepository->findServicesWithFilters($filterDto);
            
            return $this->json([
                'data' => array_map([ServicePublicJsonHelper::class, 'build'], $result['services']),
                'pagination' => $this->buildPaginationData($result, $filterDto->page, $filterDto->limit),
                'filters' => $filterDto->toArray()
            ]);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des services publics');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $service = $this->findServiceOr404($id);
            return $this->json(ServicePublicJsonHelper::build($service));

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération du service public', ['service_id' => $id]);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $data = $this->getJsonData($request);
            $createDto = new CreateServicePublicDto($data);
            $violations = $this->validator->validate($createDto);
            
            if (count($violations) > 0) {
                throw new ValidationFailedException($createDto, $violations);
            }

            $service = $this->servicePublicService->creerDepuisDto($createDto);

            return $this->json(ServicePublicJsonHelper::build($service), 201);

        } catch (ValidationFailedException $e) {
            return $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la création du service public');
        }
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $service = $this->findServiceOr404($id);
            $data = $this->getJsonData($request);
            $updateDto = new UpdateServicePublicDto($data);
            $violations = $this->validator->validate($updateDto);
            
            if (count($violations) > 0) {
                throw new ValidationFailedException($updateDto, $violations);
            }

            $updatedService = $this->servicePublicService->modifier($service, $updateDto);
 
            return $this->json(ServicePublicJsonHelper::build($updatedService));

        } catch (ValidationFailedException $e) {
            return $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la mise à jour du service public', ['service_id' => $id]);
        }
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $service = $this->findServiceOr404($id);
            $this->servicePublicService->supprimer($service);

            return $this->json(null, 204);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la suppression du service public', ['service_id' => $id]);
        }
    }

    #[Route('/bulk', name: 'bulk_delete', methods: ['DELETE'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $data = $this->getJsonData($request);
            $ids = $data['ids'] ?? [];
        
            if (!$this->isValidUuidArray($ids)) {
                return $this->json(['error' => 'IDs invalides fournis'], 422);
            }

            if (count($ids) > self::MAX_BULK_LIMIT) {
                return $this->json(['error' => "Maximum " . self::MAX_BULK_LIMIT . " services à la fois"], 422);
            }

            $deletedCount = $this->servicePublicService->supprimerPlusieurs($ids);

            return $this->json([
                'message' => "$deletedCount service(s) public(s) supprimé(s)",
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
            $stats = $this->servicePublicRepository->getServiceStats();
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
            $services = $this->servicePublicRepository->findRecentServices($limit);

            return $this->json(array_map([ServicePublicJsonHelper::class, 'build'], $services));

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des services récents');
        }
    }

    #[Route('/import-csv', name: 'import_csv', methods: ['POST'])]
    public function importCsv(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $uploadedFile = $request->files->get('csv_file');
            
            if (!$uploadedFile) {
                return $this->json(['error' => 'Aucun fichier fourni'], 422);
            }

            if ($uploadedFile->getClientOriginalExtension() !== 'csv') {
                return $this->json(['error' => 'Seuls les fichiers CSV sont acceptés'], 422);
            }

            $viderAvant = $request->request->getBoolean('vider_avant', true);
            $resultats = $this->servicePublicService->importerDepuisCsv(
                $uploadedFile->getPathname(),
                $viderAvant
            );

            return $this->json($resultats);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de l\'import CSV');
        }
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $this->denyAccessUnlessGranted(self::REQUIRED_ROLE);

        try {
            $categories = $this->servicePublicRepository->findAllCategories();
            return $this->json($categories);

        } catch (\Exception $e) {
            return $this->handleError($e, 'Erreur lors de la récupération des catégories');
        }
    }

    private static function createFilterDtoFromRequest(Request $request): ServicePublicFilterDto
    {
        return new ServicePublicFilterDto(
            $request->query->get('search', ''),
            max(1, (int) $request->query->get('page', 1)),
            min(100, max(1, (int) $request->query->get('limit', 10))),
            $request->query->get('sortField', 'nom'),
            $request->query->get('sortOrder', 'asc'),
            $request->query->get('statut'),
            $request->query->get('ville'),
            $request->query->get('categorie'),
            $request->query->get('source')
        );
    }

    private function findServiceOr404(string $id): ServicePublic
    {
        $service = $this->servicePublicRepository->find($id);
        if (!$service) {
            throw $this->createNotFoundException('Service public introuvable');
        }
        return $service;
    }

    private function isValidUuidArray(?array $ids): bool
    {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }

        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
        
        foreach ($ids as $id) {
            if (!is_string($id) || !preg_match($uuidPattern, $id)) {
                return false;
            }
        }
        
        return true;
    }

}
