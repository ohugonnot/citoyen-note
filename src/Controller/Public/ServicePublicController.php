<?php
// src/Controller/Public/ServicePublicController.php

namespace App\Controller\Public;

use App\Entity\CategorieService;
use App\Entity\ServicePublic;
use App\Enum\StatutService;
use App\Enum\StatutEvaluation;
use App\Helper\CategorieServiceJsonHelper;
use App\Helper\ServicePublicJsonHelper;
use App\Helper\EvaluationJsonHelper;
use App\Repository\CategorieServiceRepository;
use App\Repository\EvaluationRepository;
use App\Service\EvaluationManager;
use App\Repository\ServicePublicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/public/services', name: 'public_service_')]
class ServicePublicController extends AbstractController
{
    public function __construct(
        private ServicePublicRepository $serviceRepository,
        private EvaluationManager $evaluationManager,
        private CategorieServiceRepository $categorieRepository,
        private EvaluationRepository $evaluationRepository,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'api_services_publics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Récupération des paramètres
        $search = $request->query->get('search', '');
        $villeParam = $request->query->get('ville');

        if (is_array($villeParam)) {
            $villeData = $villeParam;
        } else {
            $villeData = !empty($villeParam) ? [$villeParam] : [];
        }
        $categorie = $request->query->get('categorie');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 12)));
        
        $ville = '';
        $codePostal = '';
        if (!empty($villeData)) {
            $ville = $villeData['nom'] ?? '';
            $codePostal = $villeData['codePostal'] ?? '';
        }
        
        try {
            // Requête pour compter le total
            $countQb = $this->serviceRepository->createQueryBuilder('s')
                ->select('COUNT(DISTINCT s.id)')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF);

            // Application des filtres avec code postal
            $this->applyFilters($countQb, $search, $ville, $categorie, $codePostal);
            $total = (int) $countQb->getQuery()->getSingleScalarResult();

            // Requête pour récupérer les données
            $qb = $this->serviceRepository->createQueryBuilder('s')
                ->select('s, c')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF)
                ->orderBy('s.nom', 'ASC');

            // Application des mêmes filtres
            $this->applyFilters($qb, $search, $ville, $categorie, $codePostal);
            
            $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

            $services = $qb->getQuery()->getResult();

            // Transformation des données avec les stats d'évaluation
            $servicesData = array_map(function(ServicePublic $service) {
                $data = ServicePublicJsonHelper::build($service, 'public');
                
                // Clean les données si nécessaire
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $data[$key] = $this->cleanString($value);
                    }
                }
                
                // Calcul des stats manuellement
                $evaluations = $service->getEvaluations()->filter(function($evaluation) {
                    return $evaluation->getStatut() === StatutEvaluation::ACTIVE;
                });
                
                $noteMoyenne = 0;
                $nombreEvaluations = $evaluations->count();
                
                if ($nombreEvaluations > 0) {
                    $totalNotes = 0;
                    foreach ($evaluations as $evaluation) {
                        $totalNotes += $evaluation->getNote();
                    }
                    $noteMoyenne = round($totalNotes / $nombreEvaluations, 1);
                }

                $data['note_moyenne'] = $noteMoyenne;
                $data['nombre_evaluations'] = $nombreEvaluations;
                
                return $data;
            }, $services);

            return $this->json([
                'success' => true,
                'data' => $servicesData,
                'categories' => array_map(fn(CategorieService $categorie) => CategorieServiceJsonHelper::build($categorie), $this->categorieRepository->findAll()),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                    'hasNext' => $page < ceil($total / $limit),
                    'hasPrev' => $page > 1,
                ],
                'filters' => [
                    'search' => $search,
                    'ville' => $villeData,
                    'categorie' => $categorie,
                ],
            ], 200, [], [
                'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des services',
                'error' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : 'Erreur interne'
            ], 500, [], [
                'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ]);
        }
    }

    // 🎯 Méthode applyFilters avec code postal
    private function applyFilters($qb, string $search, string $ville, ?string $categorie, string $codePostal = ''): void
    {
        // Filtre de recherche
        if (!empty($search)) {
            $qb->andWhere('s.nom LIKE :search OR s.description LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }

        // 🎯 Filtre ville avec code postal et code INSEE
        if (!empty($ville)) {
            $conditions = ['s.ville LIKE :ville'];
            $qb->setParameter('ville', '%' . $ville . '%');
            
            if (!empty($codePostal)) {
                $conditions[] = 's.codePostal = :codePostal';
                $conditions[] = 's.codePostal LIKE :codePostalPattern';
                $qb->setParameter('codePostal', $codePostal)
                ->setParameter('codePostalPattern', $codePostal . '%');
            }
            
            $qb->andWhere('(' . implode(' OR ', $conditions) . ')');
        }

        // Filtre catégorie
        if (!empty($categorie)) {
            $qb->andWhere('c.nom = :categorie')
            ->setParameter('categorie', $categorie);
        }
    }

    private function cleanString(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }
        
        // Convertit en UTF-8 propre
        $clean = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        
        // Supprime les caractères de contrôle mais garde les accents
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean);
        
        return trim($clean) ?: null;
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $service = $this->serviceRepository->findOneBy([
            'slug' => $slug,
            'statut' => StatutService::ACTIF
        ]);

        if (!$service) {
            throw new NotFoundHttpException('Service non trouvé');
        }

        // Stats publiques seulement
        $evaluationStats = $this->evaluationManager->getPublicServiceStats($service);

        $response = [
            'service' => ServicePublicJsonHelper::build($service, 'public'),
            'evaluations' => [
                'moyenne' => $evaluationStats['moyenne'],
                'total' => $evaluationStats['total'],
                'repartition' => $evaluationStats['repartition'],
                'liste' => array_map(
                    fn($eval) => EvaluationJsonHelper::build($eval),
                    $evaluationStats['evaluations']
                )
            ]
        ];

        // Headers pour le SEO et cache
        $response = $this->json($response);
        $response->headers->set('Cache-Control', 'public, max-age=300'); // 5 min de cache
        
        return $response;
    }
}
