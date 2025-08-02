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
use App\Service\GeolocationService;
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
        private PublicAuthService $publicAuthService,
        private GeolocationService $geolocationService
    ) {}

    #[Route('', name: 'api_services_publics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $timeStart = microtime(true);
        $timings = [];
        $driver = $this->em->getConnection()->getParams()['driver'] ?? '';
        $isSqlite = str_starts_with($driver, 'pdo_sqlite');

        $search = $request->query->get('search', '');
        $villeData = $request->query->all('ville', []);
        $categorie = $request->query->get('categorie');
        $uuid = $categorie ? Uuid::fromString($categorie) : null;
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 100)));

        $rayon = min(50, max(1, (float) $request->query->get('rayon', 25)));
        $lat = $villeData['latitude'] ?? null;
        $lng = $villeData['longitude'] ?? null;
        $tri = $request->query->get('tri', 'nom');

        $ville = $villeData['nom'] ?? '';
        $codePostal = $villeData['codePostal'] ?? '';
        $timings['params'] = round((microtime(true) - $timeStart) * 1000, 2);

        try {
            $stepStart = microtime(true);
            $coordonnees = null;

            if (!$lat || !$lng) {
                if ($ville || $codePostal) {
                    $coordonnees = $this->geolocationService->geocodeAddress($ville);
                    if ($coordonnees && !empty($coordonnees["temp"])) {
                        $lat = $coordonnees["temp"]['lat'];
                        $lng = $coordonnees["temp"]['lng'];
                    }
                }
            } else {
                $coordonnees = ['lat' => $lat, 'lng' => $lng];
            }

            $timings['geolocation'] = round((microtime(true) - $stepStart) * 1000, 2);

            $stepStart = microtime(true);
            $countQb = $this->serviceRepository->createQueryBuilder('s')
                ->select('COUNT(DISTINCT s.id)')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF);

            $this->applyFilters($countQb, $search, $ville, $uuid, $codePostal, $lat, $lng, $rayon, !$isSqlite);
            $total = (int) $countQb->getQuery()->getSingleScalarResult();
            $timings['count_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            $stepStart = microtime(true);
            $qb = $this->serviceRepository->createQueryBuilder('s')
                ->select('s, c')
                ->leftJoin('s.categorie', 'c')
                ->where('s.statut = :statut')
                ->setParameter('statut', StatutService::ACTIF);

            if ($lat && $lng && !$isSqlite) {
                $qb->addSelect(sprintf(
                    '(6371 * acos(cos(radians(%f)) * cos(radians(s.latitude)) * cos(radians(s.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(s.latitude)))) AS HIDDEN distance',
                    $lat, $lng, $lat
                ));
            }

            $this->applyFilters($qb, $search, $ville, $uuid, $codePostal, $lat, $lng, $rayon, !$isSqlite);

            switch ($tri) {
                case 'distance':
                    if ($lat && $lng && !$isSqlite) {
                        $qb->orderBy('distance', 'ASC');
                    } else {
                        $qb->orderBy('s.nom', 'ASC');
                    }
                    break;
                case 'note':
                    $qb->leftJoin('s.evaluations', 'e', 'WITH', 'e.statut = :statutEval')
                        ->addSelect('AVG(e.note) as HIDDEN moyenne_note')
                        ->setParameter('statutEval', StatutEvaluation::ACTIVE)
                        ->groupBy('s.id')
                        ->orderBy('moyenne_note', 'DESC');
                    break;
                default:
                    $qb->orderBy('s.nom', 'ASC');
            }

            $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
            $results = $qb->getQuery()->getResult();
            $timings['services_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            $stepStart = microtime(true);
            $categories = $this->categorieRepository->findAll();
            $timings['categories_query'] = round((microtime(true) - $stepStart) * 1000, 2);

            $stepStart = microtime(true);
            $servicesData = [];
            foreach ($results as $result) {
                $service = is_array($result) ? $result[0] : $result;
                $data = ServicePublicJsonHelper::build($service, 'public');
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $data[$key] = $this->cleanString($value);
                    }
                }

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

                $distance = null;
                if ($lat && $lng) {
                    if (!$isSqlite && is_array($result) && isset($result['distance'])) {
                        $distance = round($result['distance'], 1);
                    } elseif ($service->getLatitude() && $service->getLongitude()) {
                        $distance = round($this->haversine($lat, $lng, $service->getLatitude(), $service->getLongitude()), 1);
                    }
                    if ($distance !== null) {
                        $data['distance'] = $distance;
                        $data['distance_text'] = $this->formatDistance($distance);
                    }
                }

                $servicesData[] = $data;
            }

            if ($isSqlite && $tri === 'distance') {
                usort($servicesData, fn($a, $b) => ($a['distance'] ?? INF) <=> ($b['distance'] ?? INF));
            }

            $timings['services_processing'] = round((microtime(true) - $stepStart) * 1000, 2);

            $stepStart = microtime(true);
            $categoriesData = array_map(fn(CategorieService $c) => CategorieServiceJsonHelper::build($c), $categories);
            $timings['categories_processing'] = round((microtime(true) - $stepStart) * 1000, 2);

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
                    'rayon' => $rayon,
                    'tri' => $tri,
                ],
                'geolocation' => [
                    'center' => $coordonnees,
                    'radius' => $rayon,
                    'has_coordinates' => $lat && $lng,
                ]
            ];

            $timings['response_build'] = round((microtime(true) - $stepStart) * 1000, 2);
            $timings['total'] = round((microtime(true) - $timeStart) * 1000, 2);

            if ($this->getParameter('kernel.environment') === 'dev') {
                $response['debug'] = [
                    'timings' => $timings,
                    'counts' => [
                        'services' => count($servicesData),
                        'categories' => count($categoriesData),
                    ],
                    'sql_filters' => [
                        'has_geo_filter' => $lat && $lng,
                        'radius_km' => $rayon,
                        'sort_by' => $tri
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

    private function applyFilters($qb, string $search, string $ville, ?Uuid $categorie, string $codePostal, ?float $lat, ?float $lng, float $rayon, bool $useGeoSQL = true): void
    {
        if ($search) {
            $qb->andWhere('LOWER(s.nom) LIKE :search OR LOWER(s.description) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($lat && $lng && $useGeoSQL) {
            $qb->andWhere(sprintf(
                '(6371 * acos(cos(radians(%f)) * cos(radians(s.latitude)) * cos(radians(s.longitude) - radians(%f)) + sin(radians(%f)) * sin(radians(s.latitude)))) <= :rayon',
                $lat, $lng, $lat
            ))
                ->andWhere('s.latitude IS NOT NULL AND s.longitude IS NOT NULL')
                ->setParameter('rayon', $rayon);
        } elseif ($ville) {
            $conditions = ['LOWER(s.ville) LIKE :ville'];
            $qb->setParameter('ville', mb_strtolower($ville));

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
                ->setParameter('categorie', $categorie->toBinary());
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Formate la distance pour l'affichage
     */
    private function formatDistance(float $distance): string
    {
        if ($distance < 1) {
            return round($distance * 1000) . ' m';
        }
        return round($distance, 1) . ' km';
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