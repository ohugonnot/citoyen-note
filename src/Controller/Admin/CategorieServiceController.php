<?php

namespace App\Controller\Admin;

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

#[Route('/api/admin/categories', name: 'api_categories_')]
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

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $categorie = new CategorieService();
            $categorie->setNom($data['nom'] ?? '')
                     ->setDescription($data['description'] ?? null)
                     ->setIcone($data['icone'] ?? null)
                     ->setCouleur($data['couleur'] ?? '#000000')
                     ->setOrdreAffichage($data['ordre_affichage'] ?? 0)
                     ->setActif($data['actif'] ?? true);

            $this->categorieManager->create($categorie);

            return $this->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => CategorieServiceJsonHelper::build($categorie)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Identifiant invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->categorieManager->findById($id);

        if (!$categorie) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['nom'])) $categorie->setNom($data['nom']);
            if (isset($data['description'])) $categorie->setDescription($data['description']);
            if (isset($data['icone'])) $categorie->setIcone($data['icone']);
            if (isset($data['couleur'])) $categorie->setCouleur($data['couleur']);
            if (isset($data['ordre_affichage'])) $categorie->setOrdreAffichage($data['ordre_affichage']);
            if (isset($data['actif'])) $categorie->setActif($data['actif']);

            $this->categorieManager->update($categorie);

            return $this->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => CategorieServiceJsonHelper::build($categorie)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return $this->json([
                'success' => false,
                'message' => 'Identifiant invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->categorieManager->findById($id);

        if (!$categorie) {
            return $this->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->categorieManager->delete($categorie); // Soft delete

            return $this->json([
                'success' => true,
                'message' => 'Catégorie désactivée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
