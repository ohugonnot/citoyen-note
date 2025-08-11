<?php

namespace App\Controller\Public;

use App\Dto\ServicePublicFilterDto;
use App\Entity\Evaluation;
use App\Entity\ServicePublic;
use App\Enum\StatutEvaluation;
use App\Enum\StatutService;
use App\Helper\EvaluationJsonHelper;
use App\Helper\GeoHelper;
use App\Helper\ServicePublicJsonHelper;
use App\Repository\EvaluationRepository;
use App\Repository\ServicePublicRepository;
use App\Service\EvaluationManager;
use App\Service\GeolocationService;
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
        private EvaluationRepository $evaluationRepository,
        private EvaluationManager $evaluationManager,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private PublicAuthService $publicAuthService,
        private GeolocationService $geolocationService,
    ) {}

    #[Route('', name: 'api_services_publics_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $timeStart = microtime(true);
        $timings = [];

        $driver = (string) ($this->em->getConnection()->getParams()['driver'] ?? '');
        $isSqlite = str_starts_with($driver, 'pdo_sqlite');

        $villeData = $request->query->all('ville', []);
        $rayon = (float) min(50, max(1, (float) $request->query->get('rayon', 25)));
        $sortField = (string) $request->query->get('tri', 'nom'); 

        $filter = new ServicePublicFilterDto(
            search: (string) $request->query->get('search', ''),
            page: max(1, (int) $request->query->get('page', 1)),
            limit: min(100, max(1, (int) $request->query->get('limit', 100))),
            sortField: $sortField,
            sortOrder: (string) $request->query->get('order', 'ASC'),
            statut: StatutService::ACTIF->value,
            ville: $villeData['nom'] ?? null,
            categorie: $request->query->get('categorie') ?: null,
            source: $request->query->get('source') ?: null
        );

        // --- geocodage si coords manquantes
        $lat = isset($villeData['latitude']) ? (float) $villeData['latitude'] : null;
        $lng = isset($villeData['longitude']) ? (float) $villeData['longitude'] : null;
        $coordonnees = null;

        if (!$this->geolocationService->validateCoordinates($lat, $lng)) {
            $lat = $lng = null;
            $ville = $villeData['nom'] ?? '';
            $cp = $villeData['codePostal'] ?? '';
            if ($ville || $cp) {
                $geo = $this->geolocationService->geocodeAddress($ville);
                if ($geo) {
                    $lat = $geo['lat'] ?? null;
                    $lng = $geo['lng'] ?? null;
                }
            }
        }
        if ($this->geolocationService->validateCoordinates($lat, $lng)) {
            $coordonnees = ['lat' => $lat, 'lng' => $lng];
        }

        $stepStart = microtime(true);
        $result = $this->serviceRepository->findServicesWithFilters(
            $filter,
            lat: $lat,
            lng: $lng,
            rayon: $rayon,
            useGeoSql: !$isSqlite
        );
        $services = $result['services'];
        $total = (int) $result['total'];
        $timings['query'] = round((microtime(true) - $stepStart) * 1000, 2);

        $ids = array_map(fn(ServicePublic $s) => $s->getId()->toBinary(), $services);
        $stats = $this->evaluationRepository->getStatsForServices($ids);
        $statsById = [];
        foreach ($stats as $row) {
            $statsById[$row['sid']] = [
                'moyenne' => round((float) $row['moyenne'], 1),
                'total'   => (int) $row['total'],
            ];
        }

        $servicesData = [];
        foreach ($services as $row) {
            $service = is_array($row) ? $row[0] : $row;
            $data = ServicePublicJsonHelper::build($service, 'public');

            $st = $statsById[$service->getId()->toBinary()] ?? ['moyenne' => 0.0, 'total' => 0];
            $data['note_moyenne']       = $st['moyenne'];
            $data['nombre_evaluations'] = $st['total'];

            if ($coordonnees) {
                $distance = null;
                if (!$isSqlite && is_array($row) && isset($row['distance'])) {
                    $distance = round($row['distance'], 1);
                } elseif ($service->getLatitude() && $service->getLongitude()) {
                    $distance = round(GeoHelper::haversine($coordonnees['lat'], $coordonnees['lng'], $service->getLatitude(), $service->getLongitude()), 1);
                }
                if ($distance !== null) {
                    $data['distance'] = $distance;
                    $data['distance_text'] = GeoHelper::formatDistance($distance);
                }
            }

            $servicesData[] = $data;
        }

        // tri distance côté PHP si SQLite
        if ($isSqlite && $sortField === 'distance' && $coordonnees) {
            usort($servicesData, fn($a, $b) => ($a['distance'] ?? INF) <=> ($b['distance'] ?? INF));
        }

        $response = [
            'success' => true,
            'data' => $servicesData,
            'pagination' => [
                'page' => $filter->page,
                'limit' => $filter->limit,
                'total' => $total,
                'pages' => (int) ceil($total / $filter->limit),
                'hasNext' => $filter->page < ceil($total / $filter->limit),
                'hasPrev' => $filter->page > 1,
            ],
            'filters' => [
                'search' => $filter->search,
                'ville' => $villeData,
                'categorie' => $filter->categorie,
                'rayon' => $rayon,
                'tri' => $sortField,
                'order' => $filter->sortOrder,
            ],
            'geolocation' => [
                'center' => $coordonnees,
                'radius' => $rayon,
                'has_coordinates' => (bool) $coordonnees,
            ]
        ];

        if ($this->getParameter('kernel.environment') === 'dev') {
            $timings['total'] = round((microtime(true) - $timeStart) * 1000, 2);
            $response['debug'] = [
                'timings' => $timings,
                'counts' => ['services' => count($servicesData)],
                'sql_filters' => [
                    'has_geo_filter' => (bool) $coordonnees,
                    'radius_km' => $rayon,
                    'sort_by' => $sortField,
                ]
            ];
        }

        return $this->json($response, 200, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ]);
    }

    #[Route('/{id}/evaluations', name: 'public_service_evaluations_create', methods: ['POST'])]
    public function createEvaluation(Uuid $id, Request $request): JsonResponse
    {
        $ip = $request->getClientIp();
        $service = $this->serviceRepository->find($id);

        if (!$service) {
            return $this->json(['error' => 'Service non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $note = (int) ($data['note'] ?? 0);
        if ($note < 1 || $note > 5) {
            return $this->json(['error' => 'Note invalide (1-5)'], 400);
        }

        $user = $this->publicAuthService->getCurrentUser($request);
        $recentEvaluation = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->where('e.servicePublic = :service')
            ->andWhere('e.createdAt > :since')
            ->setParameter('service', $service->getId()->toBinary())
            ->setParameter('since', new \DateTimeImmutable('-5 minutes'));

        if ($user) {
            $recentEvaluation->andWhere('e.user = :user')->setParameter('user', $user);
        } else {
            $recentEvaluation->andWhere('e.ip = :ip')->setParameter('ip', $ip);
        }

        if ($recentEvaluation->getQuery()->getOneOrNullResult()) {
            return $this->json([
                'error' => 'Vous avez déjà laissé un avis récemment. Veuillez patienter quelques minutes avant de réessayer.'
            ], 429);
        }

        $evaluation = new Evaluation();
        if ($user) {
            $evaluation->setUser($user);
        } else {
            $nomAnonyme = trim($data['nom_anonyme'] ?? '');
            $evaluation->setPseudo($nomAnonyme);
            $evaluation->setEstAnonyme(true);
            $evaluation->setUser(null);
        }

        $evaluation->setEstVerifie(false);
        $evaluation->setStatut(StatutEvaluation::ACTIVE);
        $evaluation->setServicePublic($service);
        $evaluation->setNote($note);
        $evaluation->setIp($ip);
        $evaluation->setCommentaire((string) ($data['commentaire'] ?? ''));

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
                'liste' => array_map(fn($eval) => EvaluationJsonHelper::build($eval), $evaluationStats['evaluations'])
            ]
        ]);

        $response->headers->set('Cache-Control', 'public, max-age=300');

        return $response;
    }
}
