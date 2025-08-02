<?php

namespace App\Controller\Public;

use App\Entity\CategorieService;
use App\Entity\Evaluation;
use App\Entity\ServicePublic;
use App\Enum\StatutService;
use App\Enum\StatutEvaluation;
use App\Helper\CategorieServiceJsonHelper;
use App\Helper\EvaluationJsonHelper;
use App\Helper\ServicePublicJsonHelper;
use App\Repository\CategorieServiceRepository;
use App\Repository\EvaluationRepository;
use App\Repository\ServicePublicRepository;
use App\Service\EvaluationManager;
use App\Service\PublicAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/public/services', name: 'public_service_')]
class ServicePublicController extends AbstractController
{
    public function __construct(
        private ServicePublicRepository $serviceRepository,
        private EvaluationManager $evaluationManager,
        private CategorieServiceRepository $categorieRepository,
        private EvaluationRepository $evaluationRepository,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private PublicAuthService $publicAuthService
    ) {}

    #[Route('', name: 'api_services_publics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $timeStart = microtime(true);
        $timings = [];
        
        // Récupération des paramètres
        $search = $request->query->get('search', '');
        $villeData = $request->query->all('ville', []);
        $categorie = $request->query->get('categorie');
        $uuid = $categorie ? Uuid::fromString($categorie) : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 12)));

        $ville = $villeData['nom'] ?? '';
        $codePostal = $villeData['codePostal'] ?? '';
        
        $timings['params'] = round((microtime(true) - $timeStart) * 1000, 2);

        try {
            // ÉTAPE 1: Compter le total
            $stepStart = microtime(true);
            
            $countQb = $this->serviceRepository->createQueryBuilder('s')
                ->select('COUNT(DISTINCT s.id)')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF);

            $this->applyFilters($countQb, $search, $ville, $uuid, $codePostal);
            $total = (int) $countQb->getQuery()->getSingleScalarResult();
            
            $timings['count_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            // ÉTAPE 2: Récupérer les services
            $stepStart = microtime(true);
            
            $qb = $this->serviceRepository->createQueryBuilder('s')
                ->select('s, c')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF)
                ->orderBy('s.nom', 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);

            $this->applyFilters($qb, $search, $ville, $uuid, $codePostal);
            $services = $qb->getQuery()->getResult();
            
            $timings['services_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            // ÉTAPE 3: Récupérer les catégories
            $stepStart = microtime(true);
            
            $categories = $this->categorieRepository->findAll();
            
            $timings['categories_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            // ÉTAPE 4: Traitement des données services
            $stepStart = microtime(true);
            
            $servicesData = array_map(function (ServicePublic $service) {
                $data = ServicePublicJsonHelper::build($service, 'public');

                // Nettoyage des chaînes
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $data[$key] = $this->cleanString($value);
                    }
                }

                // Calcul des évaluations
                $evaluations = $service->getEvaluations()->filter(
                    fn($evaluation) => $evaluation->getStatut() === StatutEvaluation::ACTIVE
                );

                $totalNotes = array_reduce(
                    $evaluations->toArray(),
                    fn($carry, $e) => $carry + $e->getNote(),
                    0
                );

                $nombreEvaluations = $evaluations->count();
                $noteMoyenne = $nombreEvaluations > 0 ? round($totalNotes / $nombreEvaluations, 1) : 0;

                $data['note_moyenne'] = $noteMoyenne;
                $data['nombre_evaluations'] = $nombreEvaluations;

                return $data;
            }, $services);
            
            $timings['services_processing'] = round((microtime(true) - $stepStart) * 1000, 2);

            // ÉTAPE 5: Traitement des catégories
            $stepStart = microtime(true);
            
            $categoriesData = array_map(
                fn(CategorieService $c) => CategorieServiceJsonHelper::build($c),
                $categories
            );
            
            $timings['categories_processing'] = round((microtime(true) - $stepStart) * 1000, 2);

            // ÉTAPE 6: Construction de la réponse
            $stepStart = microtime(true);
            
            $response = [
                'success' => true,
                'data' => $servicesData,
                'categories' => $categoriesData,
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
            ];
            
            $timings['response_build'] = round((microtime(true) - $stepStart) * 1000, 2);
            $timings['total'] = round((microtime(true) - $timeStart) * 1000, 2);

            // Ajouter les timings à la réponse (seulement en dev)
            if ($this->getParameter('kernel.environment') === 'dev') {
                $response['debug'] = [
                    'timings' => $timings,
                    'counts' => [
                        'services' => count($servicesData),
                        'categories' => count($categoriesData),
                    ]
                ];
            }

            return $this->json($response, 200, [], [
                'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ]);
            
        } catch (\Throwable $e) {
            $timings['total'] = round((microtime(true) - $timeStart) * 1000, 2);
            
            $errorResponse = [
                'success' => false,
                'message' => 'Erreur lors de la récupération des services',
                'error' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : 'Erreur interne'
            ];
            
            if ($this->getParameter('kernel.environment') === 'dev') {
                $errorResponse['debug'] = [
                    'timings' => $timings,
                    'trace' => $e->getTraceAsString()
                ];
            }
            
            return $this->json($errorResponse, 500, [], [
                'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ]);
        }
    }

    private function applyFilters($qb, string $search, string $ville, ?Uuid $categorie, string $codePostal = ''): void
    {
        if ($search) {
            $qb->andWhere('LOWER(s.nom) LIKE :search OR LOWER(s.description) LIKE :search')
            ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($ville) {
            $conditions = ['LOWER(s.ville) LIKE :ville'];
            $qb->setParameter('ville', '%' . mb_strtolower($ville) . '%');

            if ($codePostal) {
                $conditions[] = 's.codePostal = :codePostal';
                $conditions[] = 's.codePostal LIKE :codePostalPattern';
                $qb->setParameter('codePostal', $codePostal);
                $qb->setParameter('codePostalPattern', $codePostal . '%');
            }

            $qb->andWhere('(' . implode(' OR ', $conditions) . ')');
        }

        if ($categorie) {
            $qb->andWhere('c.id = :categorie')
            ->setParameter('categorie',$categorie->toBinary());
        }
    }

    private function cleanString(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        $clean = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean);

        return trim($clean) ?: null;
    }

    #[Route('/{id}/evaluations', name: 'public_service_evaluations_create', methods: ['POST'])]
    public function createEvaluation(Uuid $id, Request $request): JsonResponse
    {
        $service = $this->serviceRepository->find($id);

        if (!$service) {
            return $this->json(['error' => 'Service non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['note']) || $data['note'] < 1 || $data['note'] > 5) {
            return $this->json(['error' => 'Note invalide (1-5)'], 400);
        }

        $user = $this->publicAuthService->getCurrentUser($request);
        $evaluation = new Evaluation();

        if ($user) {
            $evaluation->setUser($user);
        } else {
            $nomAnonyme = trim($data['nom_anonyme'] ?? null);
             dump($nomAnonyme);
            $evaluation->setPseudo($nomAnonyme);
            $evaluation->setEstAnonyme(true);
            $evaluation->setUser(null);
        }

        $evaluation->setServicePublic($service);
        $evaluation->setNote($data['note']);
        $evaluation->setCommentaire($data['commentaire'] ?? '');

        $errors = $this->validator->validate($evaluation);
        if (count($errors) > 0) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $this->em->persist($evaluation);
        $this->em->flush();

        return $this->json([
            'message' => 'Évaluation créée avec succès',
            'evaluation' => [
                'id' => $evaluation->getId(),
                'note' => $evaluation->getNote(),
                'commentaire' => $evaluation->getCommentaire(),
                'date' => $evaluation->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $service = $this->serviceRepository->findOneBy([
            'slug' => $slug,
            'statut' => StatutService::ACTIF,
        ]);

        if (!$service) {
            throw new NotFoundHttpException('Service non trouvé');
        }

        $evaluationStats = $this->evaluationManager->getPublicServiceStats($service);

        $response = $this->json([
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
        ]);

        $response->headers->set('Cache-Control', 'public, max-age=300');

        return $response;
    }
}
