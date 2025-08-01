<?php

namespace App\Controller\Public;

use App\Entity\CategorieService;
use App\Helper\CategorieServiceJsonHelper;
use App\Service\CategorieServiceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/api/public/categories', name: 'api_public_categories_')]
class CategorieServiceController extends AbstractController
{
    public function __construct(
        private CategorieServiceManager $categorieManager,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->categorieManager->findAllActive();
        
        $data = array_map(
            fn(CategorieService $categorie) => CategorieServiceJsonHelper::build($categorie),
            $categories
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'total' => count($data)
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Identifiant invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->categorieManager->findActiveById($id);

        if (!$categorie) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => CategorieServiceJsonHelper::build($categorie)
        ]);
    }
}
